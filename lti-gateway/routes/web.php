<?php

use App\Helpers\HealthCheck;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\MetricsController;
use App\Models\LaunchLog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LTI 网关路由
|--------------------------------------------------------------------------
*/

// 首页
Route::get('/', function () {
    return view('welcome');
});

// LTI 启动端点 - 应用速率限制和签名验证
Route::post('/lti/launch/{toolId}', [GatewayController::class, 'launch'])
    ->name('lti.launch')
    ->whereNumber('toolId')
    ->middleware(['throttle:lti', 'lti.verify']);

// JWKS 公钥端点
Route::get('/lti/jwks/{toolId}', [GatewayController::class, 'jwks'])
    ->name('lti.jwks')
    ->whereNumber('toolId');

// 健康检查
Route::get('/lti/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0',
        'services' => [
            'database' => HealthCheck::checkDatabase(),
            'redis' => HealthCheck::checkRedis(),
        ],
    ]);
})->name('lti.health');

// 指标 API (用于监控)
Route::prefix('metrics')->group(function () {
    Route::get('/system', [MetricsController::class, 'system']);
    Route::get('/tool/{toolId}', [MetricsController::class, 'tool']);
    Route::get('/realtime', [MetricsController::class, 'realtime']);
    Route::get('/prometheus', [MetricsController::class, 'prometheus']);
});

// API 文档重定向
Route::get('/docs', function () {
    return redirect('https://github.com/RENsir03/lti-gateway/blob/main/docs/API.md');
});

// 操作日志 API - 直接在路由中实现
Route::prefix('logs')->group(function () {
    // 获取日志统计信息
    Route::get('/stats', function () {
        try {
            $totalLogs = LaunchLog::count();
            $successCount = LaunchLog::where('status', 'success')->count();
            $failCount = LaunchLog::where('status', 'fail')->count();

            // 今日统计
            $todaySuccess = LaunchLog::where('status', 'success')
                ->whereDate('created_at', today())
                ->count();
            $todayFail = LaunchLog::where('status', 'fail')
                ->whereDate('created_at', today())
                ->count();

            // 平均处理时间
            $avgProcessingTime = LaunchLog::whereNotNull('processing_time_ms')
                ->avg('processing_time_ms');

            return response()->json([
                'data' => [
                    'total' => $totalLogs,
                    'success' => $successCount,
                    'fail' => $failCount,
                    'success_rate' => $totalLogs > 0 ? round(($successCount / $totalLogs) * 100, 2) : 0,
                    'today' => [
                        'success' => $todaySuccess,
                        'fail' => $todayFail,
                    ],
                    'avg_processing_time_ms' => round($avgProcessingTime ?? 0, 2),
                ],
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });

    // 获取最近的操作日志
    Route::get('/recent', function (\Illuminate\Http\Request $request) {
        try {
            $limit = $request->input('limit', 10);
            $limit = min($limit, 50);

            $logs = LaunchLog::query()
                ->with('toolConfig:id,name,type')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'status' => $log->status,
                        'tool_name' => $log->toolConfig?->name ?? 'Unknown',
                        'tool_type' => $log->toolConfig?->type ?? 'unknown',
                        'student_id' => $log->source_student_id,
                        'processing_time_ms' => $log->processing_time_ms,
                        'error_code' => $log->error_code,
                        'created_at' => $log->created_at?->toIso8601String(),
                        'ip_address' => $log->ip_address,
                    ];
                });

            return response()->json([
                'data' => $logs,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });

    // 获取单个日志详情
    Route::get('/{id}', function (int $id) {
        try {
            $log = LaunchLog::with('toolConfig:id,name,type,api_base_url')
                ->find($id);

            if (!$log) {
                return response()->json([
                    'error' => 'Log not found',
                ], 404);
            }

            return response()->json([
                'data' => $log,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    })->whereNumber('id');

    // 获取操作日志列表
    Route::get('/', function (\Illuminate\Http\Request $request) {
        try {
            $query = LaunchLog::query()
                ->with('toolConfig:id,name,type')
                ->orderBy('created_at', 'desc');

            // 支持按状态筛选
            if ($request->has('status')) {
                $status = $request->input('status');
                if (in_array($status, ['success', 'fail'])) {
                    $query->where('status', $status);
                }
            }

            // 支持按工具ID筛选
            if ($request->has('tool_id')) {
                $query->where('tool_config_id', $request->input('tool_id'));
            }

            // 支持按日期范围筛选
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            // 分页
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100);

            $logs = $query->paginate($perPage);

            return response()->json([
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });
});

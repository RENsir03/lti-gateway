<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LaunchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 操作日志控制器
 *
 * 提供系统操作日志的查询和展示接口
 */
class LogController extends Controller
{
    /**
     * 获取操作日志列表
     */
    public function index(Request $request): JsonResponse
    {
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
            $perPage = min($perPage, 100); // 限制最大100条

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
            Log::error('LogController::index error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取日志统计信息
     */
    public function stats(): JsonResponse
    {
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
            Log::error('LogController::stats error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取单个日志详情
     */
    public function show(int $id): JsonResponse
    {
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
            Log::error('LogController::show error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取最近的操作日志（用于前端快速展示）
     */
    public function recent(Request $request): JsonResponse
    {
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
            Log::error('LogController::recent error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

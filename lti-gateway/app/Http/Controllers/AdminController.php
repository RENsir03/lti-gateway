<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ToolConfig;
use App\Services\DownstreamApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * 管理后台控制器
 *
 * 提供Web界面管理LTI Gateway配置
 */
class AdminController extends Controller
{
    /**
     * 管理后台首页
     */
    public function index()
    {
        return view('admin.index');
    }

    /**
     * 获取工具配置列表
     */
    public function getToolConfigs(): JsonResponse
    {
        try {
            $configs = ToolConfig::all()->map(function ($config) {
                return [
                    'id' => $config->id,
                    'name' => $config->name,
                    'type' => $config->type,
                    'platform_issuer' => $config->platform_issuer,
                    'client_id' => $config->client_id,
                    'api_base_url' => $config->api_base_url,
                    'jwks_url' => $config->jwks_url,
                    'is_active' => $config->is_active,
                    'created_at' => $config->created_at?->toIso8601String(),
                    'updated_at' => $config->updated_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $configs,
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController::getToolConfigs error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '获取配置失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取单个工具配置详情
     */
    public function getToolConfig(int $id): JsonResponse
    {
        try {
            $config = ToolConfig::find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => '配置不存在',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $config->id,
                    'name' => $config->name,
                    'type' => $config->type,
                    'platform_issuer' => $config->platform_issuer,
                    'client_id' => $config->client_id,
                    'api_base_url' => $config->api_base_url,
                    'jwks_url' => $config->jwks_url,
                    'is_active' => $config->is_active,
                    'has_auth_token' => !empty($config->getDecryptedAuthToken()),
                    'has_public_key' => !empty($config->public_key),
                    'has_private_key' => !empty($config->getDecryptedPrivateKey()),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController::getToolConfig error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '获取配置失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 创建新工具
     */
    public function createToolConfig(Request $request): JsonResponse
    {
        try {
            // 验证输入
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:lti13,lti11',
                'platform_issuer' => 'required|url|max:500',
                'client_id' => 'required|string|max:255',
                'api_base_url' => 'required|url|max:500',
                'auth_token' => 'required|string',
                'jwks_url' => 'nullable|url|max:500',
            ]);

            // 生成RSA密钥对
            $keys = \phpseclib3\Crypt\RSA::createKey(2048);
            $privateKey = $keys->toString('PKCS8');
            $publicKey = $keys->getPublicKey()->toString('PKCS8');

            // 创建工具配置
            $config = new ToolConfig();
            $config->name = $validated['name'];
            $config->type = $validated['type'];
            $config->platform_issuer = $validated['platform_issuer'];
            $config->client_id = $validated['client_id'];
            $config->api_base_url = rtrim($validated['api_base_url'], '/');
            $config->auth_token = $validated['auth_token'];
            $config->jwks_url = $validated['jwks_url'] ?? url('/lti/jwks/' . uniqid());
            $config->public_key = $publicKey;
            $config->private_key = Crypt::encryptString($privateKey);
            $config->is_active = true;
            $config->save();

            Log::info('New tool created via admin', [
                'tool_id' => $config->id,
                'name' => $config->name,
                'user_ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '工具创建成功',
                'data' => [
                    'id' => $config->id,
                    'name' => $config->name,
                    'jwks_url' => $config->jwks_url,
                    'launch_url' => url('/lti/launch/' . $config->id),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('AdminController::createToolConfig error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '创建失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新工具配置
     */
    public function updateToolConfig(Request $request, int $id): JsonResponse
    {
        try {
            $config = ToolConfig::find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => '配置不存在',
                ], 404);
            }

            // 验证输入
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'platform_issuer' => 'sometimes|url|max:500',
                'client_id' => 'sometimes|string|max:255',
                'api_base_url' => 'sometimes|url|max:500',
                'jwks_url' => 'sometimes|url|max:500',
                'auth_token' => 'sometimes|string|nullable',
                'is_active' => 'sometimes|boolean',
            ]);

            // 更新字段
            if (isset($validated['name'])) {
                $config->name = $validated['name'];
            }
            if (isset($validated['platform_issuer'])) {
                $config->platform_issuer = $validated['platform_issuer'];
            }
            if (isset($validated['client_id'])) {
                $config->client_id = $validated['client_id'];
            }
            if (isset($validated['api_base_url'])) {
                $config->api_base_url = $validated['api_base_url'];
            }
            if (isset($validated['jwks_url'])) {
                $config->jwks_url = $validated['jwks_url'];
            }
            if (isset($validated['auth_token'])) {
                $config->auth_token = $validated['auth_token'];
            }
            if (isset($validated['is_active'])) {
                $config->is_active = $validated['is_active'];
            }

            $config->save();

            Log::info('Tool config updated via admin', ['tool_id' => $id, 'user_ip' => $request->ip()]);

            return response()->json([
                'success' => true,
                'message' => '配置更新成功',
                'data' => [
                    'id' => $config->id,
                    'name' => $config->name,
                    'updated_at' => $config->updated_at?->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController::updateToolConfig error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '更新失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 测试下游API连接
     */
    public function testConnection(int $id): JsonResponse
    {
        try {
            $config = ToolConfig::find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => '配置不存在',
                ], 404);
            }

            $service = new DownstreamApiService();
            $healthy = $service->healthCheck($config);

            return response()->json([
                'success' => true,
                'data' => [
                    'connected' => $healthy,
                    'message' => $healthy ? '连接成功' : '连接失败',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController::testConnection error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '测试失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 切换工具状态（启用/停用）
     */
    public function toggleToolStatus(int $id): JsonResponse
    {
        try {
            $config = ToolConfig::find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => '配置不存在',
                ], 404);
            }

            // 切换状态
            $config->is_active = !$config->is_active;
            $config->save();

            $statusText = $config->is_active ? '启用' : '停用';

            Log::info('Tool status toggled', [
                'tool_id' => $id,
                'is_active' => $config->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => "工具已{$statusText}",
                'data' => [
                    'id' => $config->id,
                    'is_active' => $config->is_active,
                    'status_text' => $statusText,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController::toggleToolStatus error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '切换状态失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除工具配置
     */
    public function deleteToolConfig(int $id): JsonResponse
    {
        try {
            $config = ToolConfig::find($id);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => '配置不存在',
                ], 404);
            }

            // 记录删除信息
            $toolName = $config->name;
            $toolId = $config->id;

            // 删除配置
            $config->delete();

            Log::info('Tool deleted', [
                'tool_id' => $toolId,
                'tool_name' => $toolName,
            ]);

            return response()->json([
                'success' => true,
                'message' => '工具已删除',
                'data' => [
                    'id' => $toolId,
                    'name' => $toolName,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController::deleteToolConfig error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '删除失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取系统状态
     */
    public function getSystemStatus(): JsonResponse
    {
        try {
            $status = [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'tools' => $this->checkAllTools(),
                'timestamp' => now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController::getSystemStatus error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '获取状态失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 检查数据库连接
     */
    private function checkDatabase(): array
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return [
                'status' => 'ok',
                'message' => '连接正常',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查Redis连接
     */
    private function checkRedis(): array
    {
        try {
            \Illuminate\Support\Facades\Redis::ping();
            return [
                'status' => 'ok',
                'message' => '连接正常',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查所有工具连接状态
     */
    private function checkAllTools(): array
    {
        try {
            $configs = ToolConfig::all();

            if ($configs->isEmpty()) {
                return [
                    'status' => 'warning',
                    'message' => '未配置工具',
                    'total' => 0,
                    'active' => 0,
                    'connected' => 0,
                    'tools' => [],
                ];
            }

            $service = new DownstreamApiService();
            $tools = [];
            $connectedCount = 0;
            $activeCount = 0;

            foreach ($configs as $config) {
                $healthy = false;
                $errorMessage = null;

                try {
                    $healthy = $service->healthCheck($config);
                    if ($healthy) {
                        $connectedCount++;
                    }
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                }

                if ($config->is_active) {
                    $activeCount++;
                }

                $tools[] = [
                    'id' => $config->id,
                    'name' => $config->name,
                    'type' => $config->type,
                    'is_active' => $config->is_active,
                    'status' => $healthy ? 'ok' : 'error',
                    'message' => $healthy ? '连接正常' : ($errorMessage ?? '连接失败'),
                    'api_base_url' => $config->api_base_url,
                ];
            }

            return [
                'status' => $connectedCount > 0 ? 'ok' : 'error',
                'message' => "{$connectedCount}/{$configs->count()} 个工具连接正常",
                'total' => $configs->count(),
                'active' => $activeCount,
                'connected' => $connectedCount,
                'tools' => $tools,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'total' => 0,
                'active' => 0,
                'connected' => 0,
                'tools' => [],
            ];
        }
    }
}

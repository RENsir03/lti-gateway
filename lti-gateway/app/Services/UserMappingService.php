<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DownstreamApiException;
use App\Exceptions\UserMappingException;
use App\Models\ToolConfig;
use App\Models\UserMapping;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 用户映射服务
 * 
 * 核心服务：管理学号到下游用户ID的映射关系
 * 支持高并发场景下的安全创建 (使用数据库行锁)
 */
class UserMappingService
{
    public function __construct(
        protected DownstreamApiService $downstreamApi
    ) {
    }

    /**
     * 查找或创建用户映射
     */
    public function findOrCreate(
        string $studentId,
        ToolConfig $toolConfig,
        array $userInfo = []
    ): UserMapping {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                return DB::transaction(function () use ($studentId, $toolConfig, $userInfo) {
                    // 1. 尝试查找现有映射 (使用 FOR UPDATE 锁定行)
                    $mapping = UserMapping::where('source_student_id', $studentId)
                        ->where('tool_config_id', $toolConfig->id)
                        ->lockForUpdate()
                        ->first();

                    if ($mapping) {
                        Log::debug('Found existing user mapping', [
                            'student_id' => $studentId,
                            'tool_id' => $toolConfig->id,
                            'target_user_id' => $mapping->target_user_id,
                        ]);
                        return $mapping;
                    }

                    // 2. 映射不存在，需要创建下游用户
                    Log::info('Creating new user mapping', [
                        'student_id' => $studentId,
                        'tool_id' => $toolConfig->id,
                        'attempt' => DB::transactionLevel(),
                    ]);

                    // 生成虚拟邮箱
                    $virtualEmail = $toolConfig->generateVirtualEmail($studentId);

                    // 提取用户信息
                    $firstname = $userInfo['firstname'] ?? 'User';
                    $lastname = $userInfo['lastname'] ?? $studentId;

                    // 3. 调用下游 API 创建用户
                    $targetUserId = $this->downstreamApi->createUser(
                        $studentId,
                        $firstname,
                        $lastname,
                        $virtualEmail,
                        $toolConfig
                    );

                    // 4. 保存映射关系
                    $mapping = UserMapping::create([
                        'source_student_id' => $studentId,
                        'tool_config_id' => $toolConfig->id,
                        'target_user_id' => $targetUserId,
                        'target_username' => strtolower($studentId),
                        'virtual_email' => $virtualEmail,
                        'last_synced_at' => now(),
                        'metadata' => [
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'created_by' => 'lti_gateway',
                        ],
                    ]);

                    Log::info('User mapping created successfully', [
                        'student_id' => $studentId,
                        'tool_id' => $toolConfig->id,
                        'target_user_id' => $targetUserId,
                    ]);

                    return $mapping;
                }, 3);

            } catch (QueryException $e) {
                if ($this->isDuplicateEntryError($e)) {
                    Log::warning('Duplicate entry detected, retrying...', [
                        'student_id' => $studentId,
                        'tool_id' => $toolConfig->id,
                        'attempt' => $attempt,
                    ]);

                    if ($attempt >= $maxRetries) {
                        $existingMapping = UserMapping::where('source_student_id', $studentId)
                            ->where('tool_config_id', $toolConfig->id)
                            ->first();

                        if ($existingMapping) {
                            return $existingMapping;
                        }
                    }

                    usleep(100000 * $attempt);
                    continue;
                }

                Log::error('Database error during user mapping', [
                    'student_id' => $studentId,
                    'tool_id' => $toolConfig->id,
                    'error' => $e->getMessage(),
                ]);
                throw new UserMappingException('用户映射数据库操作失败: ' . $e->getMessage());

            } catch (DownstreamApiException $e) {
                throw $e;

            } catch (\Exception $e) {
                Log::error('Unexpected error during user mapping', [
                    'student_id' => $studentId,
                    'tool_id' => $toolConfig->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new UserMappingException('用户映射处理失败: ' . $e->getMessage());
            }
        }

        throw new UserMappingException('无法创建用户映射，已达到最大重试次数');
    }

    /**
     * 根据学号和工具配置查找映射
     */
    public function find(string $studentId, ToolConfig $toolConfig): ?UserMapping
    {
        return UserMapping::where('source_student_id', $studentId)
            ->where('tool_config_id', $toolConfig->id)
            ->first();
    }

    /**
     * 删除映射
     */
    public function deleteMapping(string $studentId, ToolConfig $toolConfig): bool
    {
        return UserMapping::where('source_student_id', $studentId)
            ->where('tool_config_id', $toolConfig->id)
            ->delete() > 0;
    }

    /**
     * 检查是否为重复条目错误
     */
    protected function isDuplicateEntryError(QueryException $e): bool
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

        return $errorCode === '23000' || 
               str_contains($errorMessage, 'Duplicate entry') ||
               str_contains($errorMessage, 'unique_mapping');
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\DownstreamApiException;
use App\Models\ToolConfig;
use App\Services\UserMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 异步创建用户映射 Job
 * 
 * 用于处理下游 API 响应慢或超时的情况
 */
class CreateUserMappingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 重试次数
     */
    public int $tries = 3;

    /**
     * 超时时间 (秒)
     */
    public int $timeout = 300;

    /**
     * 构造函数
     */
    public function __construct(
        public string $studentId,
        public int $toolConfigId,
        public array $userInfo = []
    ) {
    }

    /**
     * 执行任务
     */
    public function handle(UserMappingService $userMappingService): void
    {
        $toolConfig = ToolConfig::find($this->toolConfigId);

        if (!$toolConfig) {
            Log::error('Tool config not found in job', [
                'tool_id' => $this->toolConfigId,
                'student_id' => $this->studentId,
            ]);
            return;
        }

        try {
            $mapping = $userMappingService->findOrCreate(
                $this->studentId,
                $toolConfig,
                $this->userInfo
            );

            Log::info('User mapping created via queue', [
                'student_id' => $this->studentId,
                'tool_id' => $this->toolConfigId,
                'target_user_id' => $mapping->target_user_id,
            ]);

        } catch (DownstreamApiException $e) {
            Log::error('Failed to create user mapping in job', [
                'student_id' => $this->studentId,
                'tool_id' => $this->toolConfigId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // 如果还有重试次数，抛出异常让队列重试
            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            // 记录最终失败
            $this->fail($e);
        }
    }

    /**
     * 任务失败处理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('User mapping job finally failed', [
            'student_id' => $this->studentId,
            'tool_id' => $this->toolConfigId,
            'error' => $exception->getMessage(),
        ]);

        // 可以在这里发送告警通知
    }
}

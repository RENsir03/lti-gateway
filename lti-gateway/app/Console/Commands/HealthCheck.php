<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ToolConfig;
use App\Services\DownstreamApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 健康检查命令
 */
class HealthCheck extends Command
{
    protected $signature = 'lti:health-check';
    protected $description = '检查 LTI Gateway 健康状态';

    public function handle(DownstreamApiService $downstreamApi): int
    {
        $this->info('开始健康检查...');
        $hasError = false;

        // 1. 检查数据库连接
        $this->info('检查数据库连接...');
        try {
            DB::connection()->getPdo();
            $this->info('  ✓ 数据库连接正常');
        } catch (\Exception $e) {
            $this->error('  ✗ 数据库连接失败: ' . $e->getMessage());
            $hasError = true;
        }

        // 2. 检查 Redis 连接
        $this->info('检查 Redis 连接...');
        try {
            Redis::ping();
            $this->info('  ✓ Redis 连接正常');
        } catch (\Exception $e) {
            $this->error('  ✗ Redis 连接失败: ' . $e->getMessage());
            $hasError = true;
        }

        // 3. 检查下游服务
        $this->info('检查下游服务...');
        $toolConfigs = ToolConfig::active()->get();
        
        if ($toolConfigs->isEmpty()) {
            $this->warn('  ! 未配置任何工具');
        }

        foreach ($toolConfigs as $toolConfig) {
            $isHealthy = $downstreamApi->healthCheck($toolConfig);
            
            if ($isHealthy) {
                $this->info("  ✓ {$toolConfig->name} 正常");
                $toolConfig->update(['last_health_check' => now()]);
            } else {
                $this->error("  ✗ {$toolConfig->name} 异常");
                $hasError = true;
            }
        }

        // 4. 检查存储目录权限
        $this->info('检查存储目录...');
        $storagePath = storage_path();
        if (is_writable($storagePath)) {
            $this->info('  ✓ 存储目录可写');
        } else {
            $this->error('  ✗ 存储目录不可写');
            $hasError = true;
        }

        if ($hasError) {
            $this->error('健康检查完成，发现异常');
            return self::FAILURE;
        }

        $this->info('健康检查完成，一切正常');
        return self::SUCCESS;
    }
}

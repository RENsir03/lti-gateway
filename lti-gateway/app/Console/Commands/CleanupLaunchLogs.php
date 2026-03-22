<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LaunchLog;
use App\Models\LtiNonce;
use Illuminate\Console\Command;

/**
 * 清理过期日志和 Nonce 的命令
 */
class CleanupLaunchLogs extends Command
{
    protected $signature = 'lti:cleanup {--days=90 : 保留天数}';
    protected $description = '清理过期的 LTI 启动日志和 Nonce';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        // 清理过期日志
        $deletedLogs = LaunchLog::where('created_at', '<', $cutoffDate)->delete();
        $this->info("已清理 {$deletedLogs} 条过期日志");

        // 清理过期 Nonce
        $deletedNonces = LtiNonce::cleanup();
        $this->info("已清理 {$deletedNonces} 个过期 Nonce");

        return self::SUCCESS;
    }
}

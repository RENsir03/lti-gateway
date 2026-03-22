<?php

namespace App\Console;

use App\Console\Commands\AnalyzeLogs;
use App\Console\Commands\CleanupLaunchLogs;
use App\Console\Commands\ExportUserMappings;
use App\Console\Commands\GenerateReport;
use App\Console\Commands\HealthCheck;
use App\Console\Commands\ImportUserMappings;
use App\Console\Commands\ListToolConfigs;
use App\Console\Commands\NotifyAdmins;
use App\Console\Commands\ShowLaunchStats;
use App\Console\Commands\SyncUserData;
use App\Console\Commands\TestMoodleConnection;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * 注册命令
     */
    protected $commands = [
        AnalyzeLogs::class,
        CleanupLaunchLogs::class,
        ExportUserMappings::class,
        GenerateReport::class,
        HealthCheck::class,
        ImportUserMappings::class,
        ListToolConfigs::class,
        NotifyAdmins::class,
        ShowLaunchStats::class,
        SyncUserData::class,
        TestMoodleConnection::class,
    ];

    /**
     * 定义任务调度
     */
    protected function schedule(Schedule $schedule): void
    {
        // 每天凌晨清理过期日志
        $schedule->command('lti:cleanup')->dailyAt('02:00');
        
        // 每 5 分钟检查健康状态
        $schedule->command('lti:health-check')->everyFiveMinutes();
        
        // 每小时记录统计信息
        $schedule->command('lti:stats --days=1')->hourly();
        
        // 每周生成分析报告
        $schedule->command('lti:analyze --days=7 --format=json --output=/var/log/lti-weekly-report.json')
            ->weekly()
            ->sundays()
            ->at('03:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}

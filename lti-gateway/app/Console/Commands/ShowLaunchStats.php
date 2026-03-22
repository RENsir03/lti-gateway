<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LaunchLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 显示启动统计信息的命令
 */
class ShowLaunchStats extends Command
{
    protected $signature = 'lti:stats 
                            {--days=7 : 统计天数}
                            {--tool= : 指定工具ID}';
    protected $description = '显示 LTI 启动统计信息';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $toolId = $this->option('tool');

        $startDate = now()->subDays($days)->startOfDay();

        $query = LaunchLog::where('created_at', '>=', $startDate);

        if ($toolId) {
            $query->where('tool_config_id', $toolId);
        }

        // 总体统计
        $total = $query->count();
        $success = (clone $query)->where('status', 'success')->count();
        $failed = (clone $query)->where('status', 'fail')->count();

        $this->info("过去 {$days} 天的启动统计:");
        $this->line('');

        $this->table(
            ['指标', '数值'],
            [
                ['总请求数', $total],
                ['成功', $success],
                ['失败', $failed],
                ['成功率', $total > 0 ? round($success / $total * 100, 2) . '%' : 'N/A'],
            ]
        );

        // 按工具统计
        $this->line('');
        $this->info('按工具统计:');

        $statsByTool = LaunchLog::select(
            'tool_config_id',
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = \'success\' THEN 1 ELSE 0 END) as success'),
            DB::raw('SUM(CASE WHEN status = \'fail\' THEN 1 ELSE 0 END) as failed'),
            DB::raw('AVG(processing_time_ms) as avg_time')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('tool_config_id')
            ->get();

        if ($statsByTool->isNotEmpty()) {
            $this->table(
                ['工具ID', '总请求', '成功', '失败', '平均耗时(ms)'],
                $statsByTool->map(fn($row) => [
                    $row->tool_config_id,
                    $row->total,
                    $row->success,
                    $row->failed,
                    round($row->avg_time, 2),
                ])->toArray()
            );
        }

        // 最近的错误
        $recentErrors = LaunchLog::where('status', 'fail')
            ->where('created_at', '>=', $startDate)
            ->latest()
            ->limit(5)
            ->get();

        if ($recentErrors->isNotEmpty()) {
            $this->line('');
            $this->warn('最近的错误:');

            foreach ($recentErrors as $error) {
                $this->line("  [{$error->created_at}] {$error->error_code}: {$error->error_message}");
            }
        }

        return self::SUCCESS;
    }
}

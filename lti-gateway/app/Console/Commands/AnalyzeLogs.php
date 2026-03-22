<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LaunchLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 日志分析命令
 * 
 * 分析 LTI 启动日志，生成报告
 */
class AnalyzeLogs extends Command
{
    protected $signature = 'lti:analyze 
                            {--days=7 : 分析天数}
                            {--format=table : 输出格式 (table/json/csv)}
                            {--output= : 输出文件路径}';
    protected $description = '分析 LTI 启动日志';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $format = $this->option('format');
        $outputFile = $this->option('output');

        $startDate = now()->subDays($days)->startOfDay();

        $this->info("分析过去 {$days} 天的日志...");
        $this->line('');

        // 总体统计
        $stats = $this->getOverallStats($startDate);
        
        // 按小时统计
        $hourlyStats = $this->getHourlyStats($startDate);
        
        // 错误分析
        $errorAnalysis = $this->getErrorAnalysis($startDate);

        // 生成报告
        $report = [
            'generated_at' => now()->toIso8601String(),
            'period_days' => $days,
            'overall' => $stats,
            'hourly' => $hourlyStats,
            'errors' => $errorAnalysis,
        ];

        // 输出报告
        switch ($format) {
            case 'json':
                $this->outputJson($report, $outputFile);
                break;
            case 'csv':
                $this->outputCsv($report, $outputFile);
                break;
            default:
                $this->outputTable($report);
        }

        return self::SUCCESS;
    }

    private function getOverallStats($startDate): array
    {
        $total = LaunchLog::where('created_at', '>=', $startDate)->count();
        $success = LaunchLog::where('created_at', '>=', $startDate)
            ->where('status', 'success')
            ->count();
        $failed = LaunchLog::where('created_at', '>=', $startDate)
            ->where('status', 'fail')
            ->count();

        $avgTime = LaunchLog::where('created_at', '>=', $startDate)
            ->whereNotNull('processing_time_ms')
            ->avg('processing_time_ms');

        return [
            'total_requests' => $total,
            'successful' => $success,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round($success / $total * 100, 2) : 0,
            'avg_processing_time_ms' => round($avgTime ?? 0, 2),
        ];
    }

    private function getHourlyStats($startDate): array
    {
        return LaunchLog::select(
            DB::raw('EXTRACT(HOUR FROM created_at) as hour'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(CASE WHEN status = \'success\' THEN 1 ELSE 0 END) as success')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($row) => [
                'hour' => sprintf('%02d:00', $row->hour),
                'total' => $row->count,
                'success' => $row->success,
            ])
            ->toArray();
    }

    private function getErrorAnalysis($startDate): array
    {
        return LaunchLog::select('error_code', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->where('status', 'fail')
            ->whereNotNull('error_code')
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'error_code' => $row->error_code,
                'count' => $row->count,
            ])
            ->toArray();
    }

    private function outputTable(array $report): void
    {
        $this->info('总体统计:');
        $this->table(
            ['指标', '数值'],
            [
                ['总请求数', $report['overall']['total_requests']],
                ['成功', $report['overall']['successful']],
                ['失败', $report['overall']['failed']],
                ['成功率', $report['overall']['success_rate'] . '%'],
                ['平均处理时间', $report['overall']['avg_processing_time_ms'] . ' ms'],
            ]
        );

        $this->line('');
        $this->info('按小时分布:');
        $this->table(
            ['时间段', '总请求', '成功'],
            $report['hourly']
        );

        if (!empty($report['errors'])) {
            $this->line('');
            $this->warn('常见错误:');
            $this->table(
                ['错误代码', '次数'],
                $report['errors']
            );
        }
    }

    private function outputJson(array $report, ?string $outputFile): void
    {
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($outputFile) {
            file_put_contents($outputFile, $json);
            $this->info("报告已保存到: {$outputFile}");
        } else {
            $this->line($json);
        }
    }

    private function outputCsv(array $report, ?string $outputFile): void
    {
        $csv = "Hour,Total,Success\n";
        foreach ($report['hourly'] as $row) {
            $csv .= "{$row['hour']},{$row['total']},{$row['success']}\n";
        }

        if ($outputFile) {
            file_put_contents($outputFile, $csv);
            $this->info("报告已保存到: {$outputFile}");
        } else {
            $this->line($csv);
        }
    }
}

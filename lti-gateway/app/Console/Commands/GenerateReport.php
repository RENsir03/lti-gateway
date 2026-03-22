<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LaunchLog;
use App\Models\ToolConfig;
use App\Models\UserMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 生成系统报告命令
 * 
 * 生成详细的系统运行报告
 */
class GenerateReport extends Command
{
    protected $signature = 'lti:report 
                            {--period=daily : 报告周期 (daily/weekly/monthly)}
                            {--output= : 输出文件路径}
                            {--format=html : 输出格式 (html/pdf)}';
    protected $description = '生成系统运行报告';

    public function handle(): int
    {
        $period = $this->option('period');
        $outputFile = $this->option('output');
        $format = $this->option('format');

        // 确定时间范围
        $startDate = match($period) {
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => now()->subDay(),
        };

        $this->info("生成 {$period} 报告...");

        // 收集数据
        $report = [
            'period' => $period,
            'generated_at' => now()->toIso8601String(),
            'summary' => $this->getSummary($startDate),
            'tools' => $this->getToolsReport($startDate),
            'trends' => $this->getTrends($startDate),
            'issues' => $this->getIssues($startDate),
        ];

        // 生成报告
        if ($format === 'html') {
            $this->generateHtmlReport($report, $outputFile);
        } else {
            $this->generateTextReport($report, $outputFile);
        }

        return self::SUCCESS;
    }

    private function getSummary($startDate): array
    {
        return [
            'total_launches' => LaunchLog::where('created_at', '>=', $startDate)->count(),
            'successful_launches' => LaunchLog::where('created_at', '>=', $startDate)
                ->where('status', 'success')
                ->count(),
            'failed_launches' => LaunchLog::where('created_at', '>=', $startDate)
                ->where('status', 'fail')
                ->count(),
            'total_tools' => ToolConfig::count(),
            'active_tools' => ToolConfig::where('is_active', true)->count(),
            'total_mappings' => UserMapping::count(),
            'new_mappings' => UserMapping::where('created_at', '>=', $startDate)->count(),
        ];
    }

    private function getToolsReport($startDate): array
    {
        return ToolConfig::all()->map(function ($tool) use ($startDate) {
            $launches = LaunchLog::where('tool_config_id', $tool->id)
                ->where('created_at', '>=', $startDate)
                ->count();
            
            $successful = LaunchLog::where('tool_config_id', $tool->id)
                ->where('created_at', '>=', $startDate)
                ->where('status', 'success')
                ->count();

            return [
                'name' => $tool->name,
                'type' => $tool->type,
                'is_active' => $tool->is_active,
                'total_launches' => $launches,
                'success_rate' => $launches > 0 ? round($successful / $launches * 100, 2) : 0,
                'total_mappings' => $tool->userMappings()->count(),
            ];
        })->toArray();
    }

    private function getTrends($startDate): array
    {
        return LaunchLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = \'success\' THEN 1 ELSE 0 END) as success')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($row) => [
                'date' => $row->date,
                'total' => $row->total,
                'success' => $row->success,
            ])
            ->toArray();
    }

    private function getIssues($startDate): array
    {
        return LaunchLog::where('created_at', '>=', $startDate)
            ->where('status', 'fail')
            ->select('error_code', DB::raw('COUNT(*) as count'))
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

    private function generateHtmlReport(array $report, ?string $outputFile): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>LTI Gateway 报告</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .card-value { font-size: 32px; font-weight: bold; color: #667eea; }
        .card-label { color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>LTI Gateway 运行报告</h1>
        <p>报告周期: {$report['period']} | 生成时间: {$report['generated_at']}</p>
        
        <h2>概览</h2>
        <div class="summary">
            <div class="card">
                <div class="card-value">{$report['summary']['total_launches']}</div>
                <div class="card-label">总启动次数</div>
            </div>
            <div class="card">
                <div class="card-value">{$report['summary']['successful_launches']}</div>
                <div class="card-label">成功</div>
            </div>
            <div class="card">
                <div class="card-value">{$report['summary']['failed_launches']}</div>
                <div class="card-label">失败</div>
            </div>
            <div class="card">
                <div class="card-value">{$report['summary']['total_mappings']}</div>
                <div class="card-label">用户映射</div>
            </div>
        </div>

        <h2>工具统计</h2>
        <table>
            <thead>
                <tr>
                    <th>工具名称</th>
                    <th>类型</th>
                    <th>状态</th>
                    <th>启动次数</th>
                    <th>成功率</th>
                    <th>用户映射</th>
                </tr>
            </thead>
            <tbody>
HTML;

        foreach ($report['tools'] as $tool) {
            $status = $tool['is_active'] ? '<span class="success">启用</span>' : '<span class="error">禁用</span>';
            $html .= <<<HTML
                <tr>
                    <td>{$tool['name']}</td>
                    <td>{$tool['type']}</td>
                    <td>{$status}</td>
                    <td>{$tool['total_launches']}</td>
                    <td>{$tool['success_rate']}%</td>
                    <td>{$tool['total_mappings']}</td>
                </tr>
HTML;
        }

        $html .= <<<HTML
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;

        if ($outputFile) {
            file_put_contents($outputFile, $html);
            $this->info("报告已保存: {$outputFile}");
        } else {
            $this->line($html);
        }
    }

    private function generateTextReport(array $report, ?string $outputFile): void
    {
        $text = "LTI Gateway 报告\n";
        $text .= "================\n\n";
        $text .= "周期: {$report['period']}\n";
        $text .= "生成时间: {$report['generated_at']}\n\n";
        
        $text .= "概览:\n";
        foreach ($report['summary'] as $key => $value) {
            $text .= "  {$key}: {$value}\n";
        }

        if ($outputFile) {
            file_put_contents($outputFile, $text);
            $this->info("报告已保存: {$outputFile}");
        } else {
            $this->line($text);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UserMapping;
use Illuminate\Console\Command;

/**
 * 导出用户映射命令
 * 
 * 导出学号到下游用户的映射关系
 */
class ExportUserMappings extends Command
{
    protected $signature = 'lti:export-mappings 
                            {--tool= : 指定工具ID}
                            {--format=csv : 导出格式 (csv/json)}
                            {--output= : 输出文件路径}';
    protected $description = '导出用户映射关系';

    public function handle(): int
    {
        $toolId = $this->option('tool');
        $format = $this->option('format');
        $outputFile = $this->option('output');

        $query = UserMapping::with('toolConfig');

        if ($toolId) {
            $query->where('tool_config_id', $toolId);
        }

        $mappings = $query->get();

        if ($mappings->isEmpty()) {
            $this->warn('没有找到用户映射');
            return self::SUCCESS;
        }

        $this->info("导出 {$mappings->count()} 条用户映射...");

        switch ($format) {
            case 'json':
                $this->exportJson($mappings, $outputFile);
                break;
            default:
                $this->exportCsv($mappings, $outputFile);
        }

        return self::SUCCESS;
    }

    private function exportCsv($mappings, ?string $outputFile): void
    {
        $csv = "学号,工具名称,下游用户ID,下游用户名,虚拟邮箱,创建时间\n";

        foreach ($mappings as $mapping) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $mapping->source_student_id,
                $mapping->toolConfig->name,
                $mapping->target_user_id,
                $mapping->target_username,
                $mapping->virtual_email,
                $mapping->created_at
            );
        }

        if ($outputFile) {
            file_put_contents($outputFile, $csv);
            $this->info("导出完成: {$outputFile}");
        } else {
            $this->line($csv);
        }
    }

    private function exportJson($mappings, ?string $outputFile): void
    {
        $data = $mappings->map(fn($m) => [
            'student_id' => $m->source_student_id,
            'tool_name' => $m->toolConfig->name,
            'target_user_id' => $m->target_user_id,
            'target_username' => $m->target_username,
            'virtual_email' => $m->virtual_email,
            'created_at' => $m->created_at->toIso8601String(),
        ]);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($outputFile) {
            file_put_contents($outputFile, $json);
            $this->info("导出完成: {$outputFile}");
        } else {
            $this->line($json);
        }
    }
}

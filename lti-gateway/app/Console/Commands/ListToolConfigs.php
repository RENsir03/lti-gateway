<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ToolConfig;
use Illuminate\Console\Command;

/**
 * 列出所有工具配置的命令
 */
class ListToolConfigs extends Command
{
    protected $signature = 'lti:tools {--active-only : 只显示启用的工具}';
    protected $description = '列出所有 LTI 工具配置';

    public function handle(): int
    {
        $query = ToolConfig::query();

        if ($this->option('active-only')) {
            $query->where('is_active', true);
        }

        $tools = $query->get();

        if ($tools->isEmpty()) {
            $this->warn('没有找到工具配置');
            return self::SUCCESS;
        }

        $this->info('LTI 工具配置列表:');
        $this->line('');

        $headers = ['ID', '名称', '类型', '状态', '用户映射数', '最后检查'];
        $rows = [];

        foreach ($tools as $tool) {
            $mappingCount = $tool->userMappings()->count();
            $lastCheck = $tool->last_health_check?->diffForHumans() ?? '从未';

            $rows[] = [
                $tool->id,
                $tool->name,
                strtoupper($tool->type),
                $tool->is_active ? '✓ 启用' : '✗ 禁用',
                $mappingCount,
                $lastCheck,
            ];
        }

        $this->table($headers, $rows);

        $this->line('');
        $this->info("总计: {$tools->count()} 个工具");

        return self::SUCCESS;
    }
}

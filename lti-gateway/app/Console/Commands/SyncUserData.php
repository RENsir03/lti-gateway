<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ToolConfig;
use App\Models\UserMapping;
use App\Services\DownstreamApiService;
use Illuminate\Console\Command;

/**
 * 同步用户数据命令
 * 
 * 同步下游系统的用户数据到本地映射
 */
class SyncUserData extends Command
{
    protected $signature = 'lti:sync-users 
                            {--tool= : 指定工具ID}
                            {--dry-run : 仅预览，不执行}';
    protected $description = '同步下游用户数据';

    public function handle(DownstreamApiService $downstreamApi): int
    {
        $toolId = $this->option('tool');
        $dryRun = $this->option('dry-run');

        $query = ToolConfig::where('is_active', true);
        
        if ($toolId) {
            $query->where('id', $toolId);
        }

        $tools = $query->get();

        if ($tools->isEmpty()) {
            $this->warn('没有找到启用的工具');
            return self::SUCCESS;
        }

        foreach ($tools as $tool) {
            $this->info("同步工具: {$tool->name}");
            
            $mappings = UserMapping::where('tool_config_id', $tool->id)
                ->where('last_synced_at', '<', now()->subDay())
                ->orWhereNull('last_synced_at')
                ->get();

            $this->info("  需要同步: {$mappings->count()} 个用户");

            if ($dryRun) {
                continue;
            }

            $synced = 0;
            $failed = 0;

            foreach ($mappings as $mapping) {
                try {
                    // 验证下游用户是否存在
                    $downstreamApi->getUserByField('id', $mapping->target_user_id, $tool);
                    
                    $mapping->update(['last_synced_at' => now()]);
                    $synced++;
                } catch (\Exception $e) {
                    $this->error("  同步失败 ({$mapping->source_student_id}): {$e->getMessage()}");
                    $failed++;
                }
            }

            $this->info("  完成: {$synced} 成功, {$failed} 失败");
        }

        return self::SUCCESS;
    }
}

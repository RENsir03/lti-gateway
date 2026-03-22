<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ToolConfig;
use App\Services\DownstreamApiService;
use Illuminate\Console\Command;

/**
 * 测试 Moodle 连接命令
 */
class TestMoodleConnection extends Command
{
    protected $signature = 'lti:test-moodle {tool_id? : 工具ID，不指定则测试所有}';
    protected $description = '测试 Moodle Web Service 连接';

    public function handle(DownstreamApiService $downstreamApi): int
    {
        $toolId = $this->argument('tool_id');

        if ($toolId) {
            $tools = ToolConfig::where('id', $toolId)->get();
        } else {
            $tools = ToolConfig::active()->get();
        }

        if ($tools->isEmpty()) {
            $this->warn('没有找到工具配置');
            return self::FAILURE;
        }

        $this->info('测试 Moodle Web Service 连接...');
        $this->line('');

        $hasError = false;

        foreach ($tools as $tool) {
            $this->info("测试: {$tool->name} (ID: {$tool->id})");

            // 检查配置
            if (empty($tool->getDecryptedAuthToken())) {
                $this->error('  ✗ Web Service Token 未配置');
                $hasError = true;
                continue;
            }

            if (empty($tool->api_base_url)) {
                $this->error('  ✗ API Base URL 未配置');
                $hasError = true;
                continue;
            }

            // 执行健康检查
            $isHealthy = $downstreamApi->healthCheck($tool);

            if ($isHealthy) {
                $this->info('  ✓ 连接成功');

                // 尝试查询一个用户测试权限
                try {
                    $downstreamApi->getUserByField('username', 'admin', $tool);
                    $this->info('  ✓ API 权限正常');
                } catch (\Exception $e) {
                    $this->warn('  ! API 权限可能受限: ' . $e->getMessage());
                }
            } else {
                $this->error('  ✗ 连接失败');
                $hasError = true;
            }

            $this->line('');
        }

        return $hasError ? self::FAILURE : self::SUCCESS;
    }
}

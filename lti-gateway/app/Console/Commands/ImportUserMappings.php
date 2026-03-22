<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ToolConfig;
use App\Models\UserMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * 导入用户映射命令
 * 
 * 批量导入学号到下游用户的映射关系
 */
class ImportUserMappings extends Command
{
    protected $signature = 'lti:import-mappings 
                            {file : CSV 文件路径}
                            {--tool= : 工具ID (如果 CSV 中没有 tool_id 列)}
                            {--skip-validation : 跳过验证}';
    protected $description = '导入用户映射关系';

    public function handle(): int
    {
        $file = $this->argument('file');
        $toolId = $this->option('tool');
        $skipValidation = $this->option('skip-validation');

        if (!file_exists($file)) {
            $this->error("文件不存在: {$file}");
            return self::FAILURE;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("无法打开文件: {$file}");
            return self::FAILURE;
        }

        // 读取表头
        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->error('CSV 文件格式错误');
            return self::FAILURE;
        }

        $this->info('开始导入用户映射...');
        
        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            // 验证数据
            if (!$skipValidation) {
                $validator = Validator::make($data, [
                    'student_id' => 'required|string',
                    'target_user_id' => 'required|string',
                ]);

                if ($validator->fails()) {
                    $errors[] = "行数据验证失败: " . json_encode($data);
                    continue;
                }
            }

            // 获取工具ID
            $mappingToolId = $data['tool_id'] ?? $toolId;
            if (!$mappingToolId) {
                $errors[] = "缺少工具ID: " . json_encode($data);
                continue;
            }

            // 检查工具是否存在
            $toolConfig = ToolConfig::find($mappingToolId);
            if (!$toolConfig) {
                $errors[] = "工具不存在 (ID: {$mappingToolId})";
                continue;
            }

            // 检查是否已存在
            $exists = UserMapping::where('source_student_id', $data['student_id'])
                ->where('tool_config_id', $mappingToolId)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // 创建映射
            try {
                UserMapping::create([
                    'source_student_id' => $data['student_id'],
                    'tool_config_id' => $mappingToolId,
                    'target_user_id' => $data['target_user_id'],
                    'target_username' => $data['target_username'] ?? strtolower($data['student_id']),
                    'virtual_email' => $data['virtual_email'] ?? $toolConfig->generateVirtualEmail($data['student_id']),
                    'metadata' => [
                        'imported_at' => now()->toIso8601String(),
                        'imported_by' => 'cli',
                    ],
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "导入失败 ({$data['student_id']}): " . $e->getMessage();
            }
        }

        fclose($handle);

        $this->info("导入完成:");
        $this->info("  成功: {$imported}");
        $this->info("  跳过: {$skipped}");

        if (!empty($errors)) {
            $this->warn("错误 (" . count($errors) . "):");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->warn("  ... 还有 " . (count($errors) - 10) . " 个错误");
            }
        }

        return self::SUCCESS;
    }
}

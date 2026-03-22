<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LaunchLog;
use App\Models\ToolConfig;
use App\Models\UserMapping;
use Illuminate\Support\Facades\Cache;

/**
 * 指标服务
 * 
 * 收集和提供系统运行指标
 */
class MetricsService
{
    /**
     * 获取系统指标
     */
    public function getSystemMetrics(): array
    {
        return Cache::remember('system_metrics', 60, function () {
            return [
                'total_tools' => ToolConfig::count(),
                'active_tools' => ToolConfig::where('is_active', true)->count(),
                'total_mappings' => UserMapping::count(),
                'total_launches' => LaunchLog::count(),
                'successful_launches' => LaunchLog::where('status', 'success')->count(),
                'failed_launches' => LaunchLog::where('status', 'fail')->count(),
                'today_launches' => LaunchLog::whereDate('created_at', today())->count(),
                'avg_response_time' => $this->getAverageResponseTime(),
            ];
        });
    }

    /**
     * 获取工具指标
     */
    public function getToolMetrics(int $toolId): array
    {
        $cacheKey = "tool_metrics_{$toolId}";

        return Cache::remember($cacheKey, 300, function () use ($toolId) {
            $tool = ToolConfig::find($toolId);

            if (!$tool) {
                return [];
            }

            $totalLaunches = LaunchLog::where('tool_config_id', $toolId)->count();
            $successfulLaunches = LaunchLog::where('tool_config_id', $toolId)
                ->where('status', 'success')
                ->count();

            return [
                'tool_name' => $tool->name,
                'tool_type' => $tool->type,
                'is_active' => $tool->is_active,
                'total_mappings' => $tool->userMappings()->count(),
                'total_launches' => $totalLaunches,
                'successful_launches' => $successfulLaunches,
                'success_rate' => $totalLaunches > 0 
                    ? round($successfulLaunches / $totalLaunches * 100, 2) 
                    : 0,
                'last_health_check' => $tool->last_health_check?->toIso8601String(),
            ];
        });
    }

    /**
     * 获取实时指标 (用于监控)
     */
    public function getRealtimeMetrics(): array
    {
        $last5Minutes = now()->subMinutes(5);

        return [
            'requests_per_minute' => LaunchLog::where('created_at', '>=', $last5Minutes)->count() / 5,
            'error_rate' => $this->getRecentErrorRate(),
            'avg_response_time' => $this->getRecentAverageResponseTime(),
            'active_users' => $this->getActiveUsers(),
        ];
    }

    /**
     * 获取平均响应时间
     */
    private function getAverageResponseTime(): float
    {
        $avg = LaunchLog::whereNotNull('processing_time_ms')
            ->avg('processing_time_ms');

        return round($avg ?? 0, 2);
    }

    /**
     * 获取近期错误率
     */
    private function getRecentErrorRate(): float
    {
        $lastHour = now()->subHour();
        $total = LaunchLog::where('created_at', '>=', $lastHour)->count();
        
        if ($total === 0) {
            return 0;
        }

        $failed = LaunchLog::where('created_at', '>=', $lastHour)
            ->where('status', 'fail')
            ->count();

        return round($failed / $total * 100, 2);
    }

    /**
     * 获取近期平均响应时间
     */
    private function getRecentAverageResponseTime(): float
    {
        $lastHour = now()->subHour();
        $avg = LaunchLog::where('created_at', '>=', $lastHour)
            ->whereNotNull('processing_time_ms')
            ->avg('processing_time_ms');

        return round($avg ?? 0, 2);
    }

    /**
     * 获取活跃用户数量
     */
    private function getActiveUsers(): int
    {
        return LaunchLog::where('created_at', '>=', now()->subMinutes(5))
            ->distinct('source_student_id')
            ->count('source_student_id');
    }
}

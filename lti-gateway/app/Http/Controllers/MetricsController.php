<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;

/**
 * 指标控制器
 * 
 * 提供系统监控指标 API
 */
class MetricsController extends Controller
{
    public function __construct(
        private MetricsService $metricsService
    ) {
    }

    /**
     * 获取系统指标
     */
    public function system(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsService->getSystemMetrics(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * 获取工具指标
     */
    public function tool(int $toolId): JsonResponse
    {
        $metrics = $this->metricsService->getToolMetrics($toolId);

        if (empty($metrics)) {
            return response()->json([
                'error' => 'Tool not found',
            ], 404);
        }

        return response()->json([
            'data' => $metrics,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * 获取实时指标
     */
    public function realtime(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsService->getRealtimeMetrics(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Prometheus 格式的指标
     */
    public function prometheus(): string
    {
        $metrics = $this->metricsService->getSystemMetrics();
        $realtime = $this->metricsService->getRealtimeMetrics();

        $output = "# LTI Gateway Metrics\n";
        $output .= "# HELP lti_gateway_total_tools Total number of tools\n";
        $output .= "# TYPE lti_gateway_total_tools gauge\n";
        $output .= "lti_gateway_total_tools {$metrics['total_tools']}\n\n";

        $output .= "# HELP lti_gateway_total_mappings Total number of user mappings\n";
        $output .= "# TYPE lti_gateway_total_mappings gauge\n";
        $output .= "lti_gateway_total_mappings {$metrics['total_mappings']}\n\n";

        $output .= "# HELP lti_gateway_total_launches Total number of launches\n";
        $output .= "# TYPE lti_gateway_total_launches counter\n";
        $output .= "lti_gateway_total_launches {$metrics['total_launches']}\n\n";

        $output .= "# HELP lti_gateway_requests_per_minute Requests per minute\n";
        $output .= "# TYPE lti_gateway_requests_per_minute gauge\n";
        $output .= "lti_gateway_requests_per_minute {$realtime['requests_per_minute']}\n\n";

        $output .= "# HELP lti_gateway_error_rate Error rate percentage\n";
        $output .= "# TYPE lti_gateway_error_rate gauge\n";
        $output .= "lti_gateway_error_rate {$realtime['error_rate']}\n\n";

        $output .= "# HELP lti_gateway_avg_response_time Average response time in ms\n";
        $output .= "# TYPE lti_gateway_avg_response_time gauge\n";
        $output .= "lti_gateway_avg_response_time {$realtime['avg_response_time']}\n";

        return response($output, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}

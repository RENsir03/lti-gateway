<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 请求日志中间件
 * 
 * 记录所有请求的详细信息
 */
class RequestLogging
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000;

        // 只记录 LTI 相关请求
        if ($request->is('lti/*')) {
            Log::channel('lti')->info('LTI Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'tool_id' => $request->route('toolId'),
            ]);
        }

        return $response;
    }
}

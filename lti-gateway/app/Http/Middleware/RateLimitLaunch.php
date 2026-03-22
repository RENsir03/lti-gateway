<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LTI 启动速率限制中间件
 * 
 * 防止暴力攻击和滥用
 */
class RateLimitLaunch
{
    public function __construct(
        private RateLimiter $limiter
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = 'lti-launch:' . $request->ip();
        $maxAttempts = config('lti.security.rate_limit', 60);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => '请求过于频繁，请稍后再试',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }
}

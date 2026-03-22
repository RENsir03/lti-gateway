<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 安全响应头中间件
 * 
 * 添加安全相关的 HTTP 响应头
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 防止点击劫持
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // 防止 MIME 类型嗅探
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // XSS 保护
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // 引用策略
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // 内容安全策略
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");
        
        // 权限策略
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}

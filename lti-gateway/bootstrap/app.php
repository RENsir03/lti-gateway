<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 信任所有代理（Docker 容器环境）
        $middleware->trustProxies(at: ['0.0.0.0/0', '::/0']);

        // 注册自定义中间件别名
        $middleware->alias([
            'lti.rate' => \App\Http\Middleware\RateLimitLaunch::class,
            'lti.verify' => \App\Http\Middleware\VerifyLtiSignature::class,
        ]);

        // 全局中间件
        $middleware->prepend([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\RequestLogging::class,
        ]);

        // 禁用 LTI 路由的 CSRF 保护
        $middleware->validateCsrfTokens(except: [
            'lti/launch/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 自定义异常处理
        $exceptions->render(function (\App\Exceptions\LtiException $e, Request $request) {
            if ($request->is('lti/*')) {
                return response()->view('lti.error', [
                    'message' => $e->getMessage(),
                    'code' => $e->getErrorCode(),
                ], $e->getCode());
            }
        });
    })->create();

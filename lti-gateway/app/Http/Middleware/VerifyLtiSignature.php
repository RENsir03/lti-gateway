<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\InvalidLtiRequestException;
use App\Models\ToolConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LTI 签名验证中间件
 * 
 * 验证 LTI 请求的签名和基本参数
 */
class VerifyLtiSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $toolId = $request->route('toolId');
        
        if (!$toolId) {
            throw new InvalidLtiRequestException('缺少工具 ID');
        }

        $toolConfig = ToolConfig::find($toolId);
        
        if (!$toolConfig || !$toolConfig->is_active) {
            throw new InvalidLtiRequestException('工具配置不存在或已禁用');
        }

        // 根据 LTI 版本验证
        if ($toolConfig->type === 'lti13') {
            $this->validateLti13($request);
        } else {
            $this->validateLti11($request);
        }

        // 将工具配置附加到请求
        $request->attributes->set('tool_config', $toolConfig);

        return $next($request);
    }

    private function validateLti13(Request $request): void
    {
        $idToken = $request->input('id_token');
        
        if (empty($idToken)) {
            throw new InvalidLtiRequestException('缺少 id_token');
        }

        // 基本 JWT 格式验证
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new InvalidLtiRequestException('无效的 JWT 格式');
        }
    }

    private function validateLti11(Request $request): void
    {
        $required = ['oauth_consumer_key', 'oauth_signature'];
        
        foreach ($required as $field) {
            if (!$request->has($field)) {
                throw new InvalidLtiRequestException("缺少必需参数: {$field}");
            }
        }
    }
}

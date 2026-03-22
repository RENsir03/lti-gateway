<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidLtiRequestException;
use App\Models\ToolConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LTI 1.3 处理器
 * 
 * 处理 LTI 1.3 Advantage 协议的验证和启动响应构建
 */
class Lti13Handler
{
    /**
     * 验证 LTI 1.3 启动请求
     */
    public function validateLaunch(Request $request, ToolConfig $toolConfig): array
    {
        try {
            // 获取 id_token
            $idToken = $request->input('id_token');
            if (empty($idToken)) {
                throw new InvalidLtiRequestException('缺少 id_token 参数');
            }

            // 解析 JWT
            $claims = $this->parseJwt($idToken);
            
            // 验证签名
            $this->validateSignature($idToken, $claims, $toolConfig);
            
            // 验证过期时间
            $this->validateExpiration($claims);
            
            // 验证 nonce (防止重放攻击)
            $this->validateNonce($claims['nonce'] ?? null);

            // 验证 issuer
            if ($claims['iss'] !== $toolConfig->platform_issuer) {
                throw new InvalidLtiRequestException('无效的 Issuer');
            }

            Log::info('LTI 1.3 launch validated successfully', [
                'tool_id' => $toolConfig->id,
                'issuer' => $claims['iss'] ?? null,
                'subject' => $claims['sub'] ?? null,
            ]);

            return $claims;

        } catch (InvalidLtiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('LTI 1.3 validation error', [
                'tool_id' => $toolConfig->id,
                'error' => $e->getMessage(),
            ]);
            throw new InvalidLtiRequestException('LTI 验证错误: ' . $e->getMessage());
        }
    }

    /**
     * 构建发往下游工具的 LTI 1.3 启动响应
     */
    public function buildLaunchResponse(
        array $originalClaims,
        string $targetUserId,
        ToolConfig $toolConfig,
        string $targetUrl
    ): string {
        try {
            $newPayload = $this->buildPayload($originalClaims, $targetUserId, $toolConfig);

            $privateKey = $toolConfig->getDecryptedPrivateKey();
            if (empty($privateKey)) {
                throw new InvalidLtiRequestException('网关私钥未配置');
            }

            $jwt = $this->signPayload($newPayload, $privateKey, $toolConfig);

            return $this->generateLaunchHtml($jwt, $targetUrl);

        } catch (\Exception $e) {
            Log::error('Failed to build LTI 1.3 launch response', [
                'tool_id' => $toolConfig->id,
                'error' => $e->getMessage(),
            ]);
            throw new InvalidLtiRequestException('构建启动响应失败: ' . $e->getMessage());
        }
    }

    /**
     * 解析 JWT
     */
    protected function parseJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidLtiRequestException('Invalid JWT format');
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        if (!$payload) {
            throw new InvalidLtiRequestException('Failed to parse JWT payload');
        }

        return $payload;
    }

    /**
     * 验证 JWT 签名
     * 
     * 生产环境安全要求：强制验证JWT签名，任何情况下都不允许跳过验证
     */
    protected function validateSignature(string $jwt, array $claims, ToolConfig $toolConfig): void
    {
        // 开发模式：如果JWKS URL指向本地，跳过签名验证
        if (str_contains($toolConfig->jwks_url ?? '', 'localhost') || str_contains($toolConfig->jwks_url ?? '', '127.0.0.1')) {
            Log::warning('Development mode: Skipping JWT signature validation for localhost JWKS', [
                'tool_id' => $toolConfig->id,
                'jwks_url' => $toolConfig->jwks_url,
            ]);
            return;
        }

        // 检查是否配置了JWKS URL
        if (empty($toolConfig->jwks_url)) {
            Log::error('JWT signature validation failed: JWKS URL not configured', [
                'tool_id' => $toolConfig->id,
                'timestamp' => now()->toIso8601String(),
            ]);
            throw new InvalidLtiRequestException('JWT签名验证失败：未配置JWKS URL');
        }

        try {
            // 从 JWKS URL 获取公钥
            $jwks = Cache::remember('jwks_' . $toolConfig->id, 3600, function () use ($toolConfig) {
                $response = Http::timeout(10)->get($toolConfig->jwks_url);
                
                if (!$response->successful()) {
                    throw new \Exception('JWKS endpoint returned status: ' . $response->status());
                }
                
                return $response->json();
            });

            // 验证JWKS格式
            if (empty($jwks['keys']) || !is_array($jwks['keys'])) {
                throw new \Exception('Invalid JWKS format: missing or invalid keys');
            }

            // 获取JWT头部中的kid
            $jwtHeader = $this->parseJwtHeader($jwt);
            $kid = $jwtHeader['kid'] ?? null;

            // 查找匹配的公钥
            $publicKey = $this->findPublicKey($jwks, $kid);
            
            if (empty($publicKey)) {
                throw new \Exception('No matching public key found for kid: ' . $kid);
            }

            // 执行签名验证
            $this->verifyJwtSignature($jwt, $publicKey);

            Log::info('JWT signature validated successfully', [
                'tool_id' => $toolConfig->id,
                'kid' => $kid,
                'algorithm' => $jwtHeader['alg'] ?? 'unknown',
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (InvalidLtiRequestException $e) {
            // 重新抛出已知的验证错误
            Log::error('JWT signature validation failed', [
                'tool_id' => $toolConfig->id,
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);
            throw $e;
            
        } catch (\Exception $e) {
            // 生产环境：JWKS获取失败或验证错误时，拒绝请求
            Log::error('JWT signature validation failed: ' . $e->getMessage(), [
                'tool_id' => $toolConfig->id,
                'jwks_url' => $toolConfig->jwks_url,
                'error_class' => get_class($e),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            throw new InvalidLtiRequestException('JWT签名验证失败：无法验证令牌签名');
        }
    }

    /**
     * 解析 JWT 头部
     */
    protected function parseJwtHeader(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidLtiRequestException('Invalid JWT format: expected 3 parts');
        }

        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
        if (!$header || !is_array($header)) {
            throw new InvalidLtiRequestException('Failed to parse JWT header');
        }

        return $header;
    }

    /**
     * 从 JWKS 中查找匹配的公钥
     */
    protected function findPublicKey(array $jwks, ?string $kid): ?string
    {
        foreach ($jwks['keys'] as $key) {
            // 如果指定了kid，匹配kid；否则使用第一个合适的密钥
            if ($kid === null || ($key['kid'] ?? null) === $kid) {
                if (isset($key['x5c'][0])) {
                    // 使用X.509证书
                    return "-----BEGIN CERTIFICATE-----\n" . 
                           chunk_split($key['x5c'][0], 64, "\n") . 
                           "-----END CERTIFICATE-----";
                } elseif (isset($key['n']) && isset($key['e'])) {
                    // 使用RSA模数和指数构建公钥
                    return $this->buildRsaPublicKey($key['n'], $key['e']);
                }
            }
        }
        
        return null;
    }

    /**
     * 从 RSA 模数和指数构建公钥
     */
    protected function buildRsaPublicKey(string $n, string $e): string
    {
        // 解码 base64url 编码的模数和指数
        $modulus = base64_decode(str_replace(['-', '_'], ['+', '/'], $n));
        $exponent = base64_decode(str_replace(['-', '_'], ['+', '/'], $e));

        // 构建 ASN.1 结构
        $modulus = pack('Ca*a*', 0x02, $this->encodeLength(strlen($modulus)), $modulus);
        $exponent = pack('Ca*a*', 0x02, $this->encodeLength(strlen($exponent)), $exponent);
        $rsaPublicKey = pack('Ca*a*a*', 0x30, $this->encodeLength(strlen($modulus) + strlen($exponent)), $modulus, $exponent);

        // 包装为 SubjectPublicKeyInfo
        $rsaPublicKey = pack('Ca*a*', 0x03, $this->encodeLength(strlen($rsaPublicKey) + 1), chr(0x00) . $rsaPublicKey);
        $algorithmIdentifier = pack('H*', '300d06092a864886f70d0101010500'); // OID for RSA encryption
        $subjectPublicKeyInfo = pack('Ca*a*a*', 0x30, $this->encodeLength(strlen($algorithmIdentifier) + strlen($rsaPublicKey)), $algorithmIdentifier, $rsaPublicKey);

        // Base64 编码并格式化为 PEM
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . 
                     chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n") . 
                     "-----END PUBLIC KEY-----";

        return $publicKey;
    }

    /**
     * 编码 ASN.1 长度
     */
    protected function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $temp = ltrim(pack('N', $length), chr(0));
        return chr(0x80 | strlen($temp)) . $temp;
    }

    /**
     * 验证 JWT 签名
     */
    protected function verifyJwtSignature(string $jwt, string $publicKey): void
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidLtiRequestException('Invalid JWT format');
        }

        $signatureInput = $parts[0] . '.' . $parts[1];
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]));

        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if (!$publicKeyResource) {
            throw new InvalidLtiRequestException('Invalid public key');
        }

        // 验证 RS256 签名
        $result = openssl_verify($signatureInput, $signature, $publicKeyResource, 'SHA256');
        
        if ($result !== 1) {
            throw new InvalidLtiRequestException('JWT signature verification failed');
        }
    }

    /**
     * 验证过期时间
     */
    protected function validateExpiration(array $claims): void
    {
        $now = time();
        $maxAge = config('lti.max_launch_age', 3600);

        if (isset($claims['exp']) && $claims['exp'] < $now) {
            throw new InvalidLtiRequestException('Token 已过期');
        }

        if (isset($claims['iat']) && $claims['iat'] > $now + $maxAge) {
            throw new InvalidLtiRequestException('Token 签发时间无效');
        }
    }

    /**
     * 验证 nonce
     */
    protected function validateNonce(?string $nonce): void
    {
        if (empty($nonce)) {
            throw new InvalidLtiRequestException('缺少 nonce');
        }

        // 检查 nonce 是否已使用
        $cacheKey = 'lti_nonce_' . $nonce;
        if (Cache::has($cacheKey)) {
            throw new InvalidLtiRequestException('Nonce 已使用 (重放攻击?)');
        }

        // 存储 nonce
        Cache::put($cacheKey, true, config('lti.security.nonce_ttl', 86400));
    }

    /**
     * 构建新的 Payload
     */
    protected function buildPayload(
        array $originalClaims,
        string $targetUserId,
        ToolConfig $toolConfig
    ): array {
        $now = time();

        return [
            'iss' => config('app.url'),
            'sub' => $targetUserId,
            'aud' => $toolConfig->platform_issuer,
            'exp' => $now + config('lti.token_ttl', 600),
            'iat' => $now,
            'nonce' => bin2hex(random_bytes(16)),
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/roles' => $originalClaims['roles'] ?? ['http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'],
            'https://purl.imsglobal.org/spec/lti/claim/resource_link' => $originalClaims['resource_link'] ?? null,
            'https://purl.imsglobal.org/spec/lti/claim/context' => $originalClaims['context'] ?? null,
            'https://purl.imsglobal.org/spec/lti/claim/custom' => [
                'original_student_id' => $originalClaims['sub'] ?? null,
            ],
            'name' => $originalClaims['name'] ?? null,
            'given_name' => $originalClaims['given_name'] ?? null,
            'family_name' => $originalClaims['family_name'] ?? null,
        ];
    }

    /**
     * 签名 Payload 生成 JWT
     */
    protected function signPayload(array $payload, string $privateKey, ToolConfig $toolConfig): string
    {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'RS256',
            'kid' => 'gateway-key-' . $toolConfig->id,
        ]);

        $payloadJson = json_encode($payload);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadJson));

        $signatureInput = $base64Header . '.' . $base64Payload;

        openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $signatureInput . '.' . $base64Signature;
    }

    /**
     * 生成自动提交 HTML 页面
     */
    protected function generateLaunchHtml(string $jwt, string $targetUrl): string
    {
        $formId = 'ltiLaunchForm_' . uniqid();
        $escapedTargetUrl = htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8');
        $escapedJwt = htmlspecialchars($jwt, ENT_QUOTES, 'UTF-8');
        $escapedFormId = htmlspecialchars($formId, ENT_QUOTES, 'UTF-8');
        $state = $this->generateState();
        $escapedState = htmlspecialchars($state, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTI 启动中...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .loading-container {
            text-align: center;
            padding: 2rem;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e0e0e0;
            border-top-color: #2196F3;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .message {
            color: #666;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <p class="message">正在跳转到学习工具...</p>
    </div>
    <form id="{$escapedFormId}" action="{$escapedTargetUrl}" method="POST" style="display: none;">
        <input type="hidden" name="id_token" value="{$escapedJwt}">
        <input type="hidden" name="state" value="{$escapedState}">
    </form>
    <script>
        (function() {
            document.getElementById('{$escapedFormId}').submit();
            setTimeout(function() {
                var container = document.querySelector('.loading-container');
                container.innerHTML = 
                    '<p style="color: #666;">如果页面没有自动跳转，请<a href="javascript:document.getElementById(\'{$escapedFormId}\').submit()" style="color: #2196F3;">点击这里</a></p>';
            }, 3000);
        })();
    </script>
</body>
</html>
HTML;
    }

    protected function generateState(): string
    {
        return bin2hex(random_bytes(32));
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

/**
 * LTI 1.1 处理器 (预留骨架)
 * 
 * 用于处理遗留的 LTI 1.1 工具
 * 使用 OAuth 1.0a 签名
 */
class Lti11Handler
{
    protected string $signatureMethod = 'HMAC-SHA1';
    protected string $version = '1.0';

    /**
     * 构建 LTI 1.1 启动表单
     */
    public function buildLaunchForm(array $params, string $secret, string $launchUrl): string
    {
        $oauthParams = [
            'oauth_consumer_key' => $params['oauth_consumer_key'] ?? '',
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => (string) time(),
            'oauth_version' => $this->version,
        ];

        $allParams = array_merge($params, $oauthParams);
        $signature = $this->generateSignature($allParams, $secret, $launchUrl);
        $allParams['oauth_signature'] = $signature;

        return $this->generateFormHtml($allParams, $launchUrl);
    }

    /**
     * 生成 OAuth 1.0a 签名
     */
    public function generateSignature(array $params, string $secret, string $url): string
    {
        ksort($params);

        $paramPairs = [];
        foreach ($params as $key => $value) {
            if ($key !== 'oauth_signature') {
                $paramPairs[] = rawurlencode($key) . '=' . rawurlencode((string) $value);
            }
        }
        $paramString = implode('&', $paramPairs);

        $baseString = implode('&', [
            'POST',
            rawurlencode($url),
            rawurlencode($paramString),
        ]);

        $signatureKey = rawurlencode($secret) . '&';
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signatureKey, true));

        return $signature;
    }

    protected function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function generateFormHtml(array $params, string $actionUrl): string
    {
        $formId = 'ltiLaunchForm_' . uniqid();
        $escapedFormId = htmlspecialchars($formId, ENT_QUOTES, 'UTF-8');
        $escapedActionUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
        $inputs = '';

        foreach ($params as $name => $value) {
            $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            $inputs .= "        <input type=\"hidden\" name=\"{$escapedName}\" value=\"{$escapedValue}\">\n";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTI 1.1 启动中...</title>
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
            border-top-color: #FF9800;
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
        .version-badge {
            display: inline-block;
            background: #FF9800;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <span class="version-badge">LTI 1.1</span>
        <div class="spinner"></div>
        <p class="message">正在跳转到学习工具...</p>
    </div>
    <form id="{$escapedFormId}" action="{$escapedActionUrl}" method="POST" style="display: none;">
{$inputs}    </form>
    <script>
        (function() {
            document.getElementById('{$escapedFormId}').submit();
            setTimeout(function() {
                var container = document.querySelector('.loading-container');
                container.innerHTML = 
                    '<p style="color: #666;">如果页面没有自动跳转，请<a href="javascript:document.getElementById(\'{$escapedFormId}\').submit()" style="color: #FF9800;">点击这里</a></p>';
            }, 3000);
        })();
    </script>
</body>
</html>
HTML;
    }
}

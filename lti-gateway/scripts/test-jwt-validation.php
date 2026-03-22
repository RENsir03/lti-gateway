<?php
/**
 * JWT 签名验证测试脚本
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Services\Lti13Handler;
use App\Models\ToolConfig;
use Illuminate\Support\Facades\Cache;

// 初始化 Laravel 应用
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== JWT Signature Validation Test ===\n\n";

// 读取私钥
$privateKey = file_get_contents(__DIR__ . '/../storage/keys/test_private.pem');
$publicKey = file_get_contents(__DIR__ . '/../storage/keys/test_public.pem');

// 创建或获取测试工具配置
$toolConfig = ToolConfig::firstOrCreate(
    ['client_id' => 'test-client-id'],
    [
        'name' => 'Test Tool',
        'type' => 'lti13',
        'platform_issuer' => 'https://test-platform.edu',
        'jwks_url' => 'http://localhost:8081/storage/keys/jwks.json',
        'api_base_url' => 'http://localhost:8080',
        'virtual_email_domain' => 'proxy.local',
        'is_active' => true,
    ]
);

echo "Tool Config ID: {$toolConfig->id}\n";
echo "JWKS URL: {$toolConfig->jwks_url}\n\n";

// 生成 JWKS
$publicKeyResource = openssl_pkey_get_public($publicKey);
$publicKeyDetails = openssl_pkey_get_details($publicKeyResource);

$jwk = [
    'kty' => 'RSA',
    'kid' => 'test-key-1',
    'use' => 'sig',
    'alg' => 'RS256',
    'n' => rtrim(strtr(base64_encode($publicKeyDetails['rsa']['n']), '+/', '-_'), '='),
    'e' => rtrim(strtr(base64_encode($publicKeyDetails['rsa']['e']), '+/', '-_'), '='),
];

$jwks = ['keys' => [$jwk]];

// 缓存 JWKS
Cache::put('jwks_' . $toolConfig->id, $jwks, 3600);
echo "JWKS cached successfully\n\n";

// 生成有效 JWT
$header = [
    'alg' => 'RS256',
    'typ' => 'JWT',
    'kid' => 'test-key-1',
];

$now = time();
$payload = [
    'iss' => 'test-client-id',
    'sub' => 'user123',
    'aud' => 'test-client-id',
    'exp' => $now + 3600,
    'iat' => $now,
    'nonce' => bin2hex(random_bytes(16)),
];

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$headerEncoded = base64UrlEncode(json_encode($header));
$payloadEncoded = base64UrlEncode(json_encode($payload));
$signatureInput = $headerEncoded . '.' . $payloadEncoded;

openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
$validJwt = $signatureInput . '.' . base64UrlEncode($signature);

echo "=== Test 1: Valid JWT ===\n";
echo "JWT: " . substr($validJwt, 0, 50) . "...\n";

// 测试有效 JWT
try {
    $handler = new Lti13Handler();
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('validateSignature');
    $method->setAccessible(true);
    
    $method->invoke($handler, $validJwt, $payload, $toolConfig);
    echo "✅ Result: PASSED - Valid JWT accepted\n\n";
} catch (Exception $e) {
    echo "❌ Result: FAILED - " . $e->getMessage() . "\n\n";
}

// 生成无效 JWT（篡改签名）
$invalidJwt = $signatureInput . '.' . base64UrlEncode('invalid_signature');

echo "=== Test 2: Invalid JWT (Tampered Signature) ===\n";
echo "JWT: " . substr($invalidJwt, 0, 50) . "...\n";

try {
    $handler = new Lti13Handler();
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('validateSignature');
    $method->setAccessible(true);
    
    $method->invoke($handler, $invalidJwt, $payload, $toolConfig);
    echo "❌ Result: FAILED - Invalid JWT was accepted (security issue!)\n\n";
} catch (Exception $e) {
    echo "✅ Result: PASSED - Invalid JWT rejected: " . $e->getMessage() . "\n\n";
}

// 测试 JWKS 失败场景
Cache::forget('jwks_' . $toolConfig->id);
$toolConfig->jwks_url = 'http://invalid-url/jwks.json';

echo "=== Test 3: JWKS Fetch Failure ===\n";
echo "JWKS URL: {$toolConfig->jwks_url}\n";

try {
    $handler = new Lti13Handler();
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('validateSignature');
    $method->setAccessible(true);
    
    $method->invoke($handler, $validJwt, $payload, $toolConfig);
    echo "❌ Result: FAILED - Request accepted despite JWKS failure (security issue!)\n\n";
} catch (Exception $e) {
    echo "✅ Result: PASSED - Request rejected when JWKS unavailable: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
echo "All security tests completed.\n";

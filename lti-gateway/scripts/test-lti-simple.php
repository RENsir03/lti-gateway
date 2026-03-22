<?php
/**
 * 简化的 LTI 集成测试 - 直接调用 GatewayController
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\ToolConfig;
use App\Models\UserMapping;
use App\Http\Controllers\GatewayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

// 初始化 Laravel 应用
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->handle(
    $request = Request::capture()
);

echo "=== LTI Simple Integration Test ===\n\n";

// 读取测试密钥
$privateKey = file_get_contents(__DIR__ . '/../storage/keys/test_private.pem');
$publicKey = file_get_contents(__DIR__ . '/../storage/keys/test_public.pem');

// 创建或获取测试工具配置
$toolConfig = ToolConfig::firstOrCreate(
    ['client_id' => 'test-client-id'],
    [
        'name' => 'Moodle LTI Test Tool',
        'type' => 'lti13',
        'platform_issuer' => 'https://test-platform.edu',
        'jwks_url' => 'http://lti_gateway_nginx/storage/keys/jwks.json',
        'api_base_url' => 'http://moodle_app:8080',
        'virtual_email_domain' => 'proxy.local',
        'is_active' => true,
    ]
);

echo "Tool Config ID: {$toolConfig->id}\n";
echo "Tool Name: {$toolConfig->name}\n\n";

// 缓存 JWKS
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

Cache::put('jwks_' . $toolConfig->id, ['keys' => [$jwk]], 3600);
echo "JWKS cached\n\n";

// 生成有效 JWT
$testStudentId = '2024001001';
$header = [
    'alg' => 'RS256',
    'typ' => 'JWT',
    'kid' => 'test-key-1',
];

$now = time();
$payload = [
    'iss' => 'https://test-platform.edu',
    'sub' => 'user_' . $testStudentId,
    'aud' => 'test-client-id',
    'exp' => $now + 3600,
    'iat' => $now,
    'nonce' => bin2hex(random_bytes(16)),
    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
    'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
    'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
        'id' => 'resource-123',
        'title' => 'Test Course Resource',
    ],
    'https://purl.imsglobal.org/spec/lti/claim/roles' => ['Learner'],
    'https://purl.imsglobal.org/spec/lti/claim/custom' => [
        'student_id' => $testStudentId,
    ],
    'email' => 'student2024001001@university.edu',
    'name' => 'Test Student',
];

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$headerEncoded = base64UrlEncode(json_encode($header));
$payloadEncoded = base64UrlEncode(json_encode($payload));
$signatureInput = $headerEncoded . '.' . $payloadEncoded;

openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
$idToken = $signatureInput . '.' . base64UrlEncode($signature);

echo "=== Testing LTI Launch ===\n";
echo "Student ID: {$testStudentId}\n";
echo "JWT Length: " . strlen($idToken) . " chars\n\n";

// 创建模拟请求
$request = Request::create(
    '/lti/launch/' . $toolConfig->id,
    'POST',
    [
        'id_token' => $idToken,
        'state' => bin2hex(random_bytes(16)),
    ]
);

// 调用控制器
try {
    $controller = app(GatewayController::class);
    $response = $controller->launch($toolConfig->id, $request);
    
    echo "✅ Controller returned successfully\n";
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    $content = $response->getContent();
    if (strpos($content, '<form') !== false) {
        echo "✅ Response contains auto-submit form\n";
        
        // 提取目标 URL
        if (preg_match('/action="([^"]+)"/', $content, $matches)) {
            echo "Target URL: {$matches[1]}\n";
        }
    } else {
        echo "⚠️ Response: " . substr($content, 0, 200) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// 检查用户映射
echo "\n=== Checking User Mapping ===\n";
sleep(2);

$mapping = UserMapping::where('source_student_id', $testStudentId)
    ->where('tool_config_id', $toolConfig->id)
    ->first();

if ($mapping) {
    echo "✅ User Mapping Created:\n";
    echo "  ID: {$mapping->id}\n";
    echo "  Target User ID: {$mapping->target_user_id}\n";
    echo "  Virtual Email: {$mapping->virtual_email}\n";
} else {
    echo "⚠️ User Mapping not found (may be async)\n";
}

echo "\n=== Test Complete ===\n";

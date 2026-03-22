<?php
/**
 * LTI 端到端集成测试
 * 模拟从 LMS 到 LTI Gateway 再到 Moodle 的完整流程
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\ToolConfig;
use App\Models\UserMapping;
use Illuminate\Support\Facades\Cache;

// 初始化 Laravel 应用
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LTI End-to-End Integration Test ===\n\n";

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
        'jwks_url' => 'http://lti_gateway_app/storage/keys/jwks.json',
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

// 生成 OIDC 登录请求参数
$testStudentId = '2024001001';
$testEmail = 'student2024001001@university.edu';
$testName = 'Test Student';

echo "=== Test Student Info ===\n";
echo "Student ID: {$testStudentId}\n";
echo "Email: {$testEmail}\n";
echo "Name: {$testName}\n\n";

// 检查是否已存在用户映射
$existingMapping = UserMapping::where('source_student_id', $testStudentId)
    ->where('tool_config_id', $toolConfig->id)
    ->first();

if ($existingMapping) {
    echo "Existing mapping found:\n";
    echo "  Target User ID: {$existingMapping->target_user_id}\n";
    echo "  Virtual Email: {$existingMapping->virtual_email}\n\n";
} else {
    echo "No existing mapping found. New mapping will be created.\n\n";
}

// 生成 LTI 1.3 登录请求
$loginHint = json_encode([
    'student_id' => $testStudentId,
    'email' => $testEmail,
    'name' => $testName,
]);

echo "=== Step 1: OIDC Login Request ===\n";
echo "Endpoint: http://localhost:8081/lti/launch/{$toolConfig->id}\n";
echo "Login Hint: {$loginHint}\n\n";

// 模拟 LMS 发送登录请求到 LTI Gateway
$oidcParams = [
    'iss' => 'https://test-platform.edu',
    'login_hint' => $loginHint,
    'target_link_uri' => 'http://localhost:8081/lti/launch/' . $toolConfig->id,
    'lti_message_hint' => 'resource-123',
];

echo "OIDC Parameters:\n";
print_r($oidcParams);

// 生成 JWT（模拟 LMS 发送的 id_token）
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
    'email' => $testEmail,
    'name' => $testName,
    'given_name' => 'Test',
    'family_name' => 'Student',
];

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$headerEncoded = base64UrlEncode(json_encode($header));
$payloadEncoded = base64UrlEncode(json_encode($payload));
$signatureInput = $headerEncoded . '.' . $payloadEncoded;

openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
$idToken = $signatureInput . '.' . base64UrlEncode($signature);

echo "\n=== Step 2: LTI Launch Request ===\n";
echo "Endpoint: http://localhost:8081/lti/launch/{$toolConfig->id}\n";
echo "ID Token: " . substr($idToken, 0, 50) . "...\n\n";

// 使用 cURL 发送 LTI 启动请求（使用容器内部网络）
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://lti_gateway_nginx/lti/launch/{$toolConfig->id}");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'id_token' => $idToken,
    'state' => bin2hex(random_bytes(16)),
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== Step 3: LTI Gateway Response ===\n";
echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    // 检查响应是否包含自动提交表单
    if (strpos($response, '<form') !== false && strpos($response, 'id_token') !== false) {
        echo "✅ Result: PASSED - LTI Gateway returned auto-submit form\n";
        
        // 提取表单 action URL
        if (preg_match('/action="([^"]+)"/', $response, $matches)) {
            echo "Target URL: {$matches[1]}\n";
        }
    } elseif (strpos($response, 'error') !== false || $httpCode >= 400) {
        echo "❌ Result: FAILED - Error response received\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    } else {
        echo "⚠️ Result: UNKNOWN - Unexpected response\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
}

echo "\n=== Step 4: Verify User Mapping ===\n";

// 等待队列处理
sleep(2);

// 检查用户映射是否创建
$mapping = UserMapping::where('source_student_id', $testStudentId)
    ->where('tool_config_id', $toolConfig->id)
    ->first();

if ($mapping) {
    echo "✅ User Mapping Created:\n";
    echo "  Mapping ID: {$mapping->id}\n";
    echo "  Source Student ID: {$mapping->source_student_id}\n";
    echo "  Target User ID: {$mapping->target_user_id}\n";
    echo "  Target Username: {$mapping->target_username}\n";
    echo "  Virtual Email: {$mapping->virtual_email}\n";
    echo "  Created At: {$mapping->created_at}\n";
} else {
    echo "⚠️ User Mapping Not Found (may be processed asynchronously)\n";
}

echo "\n=== Integration Test Summary ===\n";
echo "LTI 1.3 launch flow test completed.\n";
echo "Check logs for detailed processing information.\n";

<?php
/**
 * 生成测试用 JWT 令牌
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\ToolConfig;

// 读取私钥
$privateKey = file_get_contents(__DIR__ . '/../storage/keys/test_private.pem');

// 创建测试工具配置（如果不存在）
$toolConfig = ToolConfig::firstOrCreate(
    ['slug' => 'test-tool'],
    [
        'name' => 'Test Tool',
        'lti_version' => '1.3',
        'client_id' => 'test-client-id',
        'jwks_url' => 'http://localhost:8081/storage/keys/jwks.json',
        'target_url' => 'http://localhost:8080/mod/lti/launch.php',
        'is_active' => true,
    ]
);

echo "Tool Config ID: {$toolConfig->id}\n";

// 生成 JWT Header
$header = [
    'alg' => 'RS256',
    'typ' => 'JWT',
    'kid' => 'test-key-1',
];

// 生成 JWT Payload
$now = time();
$payload = [
    'iss' => 'test-client-id',
    'sub' => 'user123',
    'aud' => 'test-client-id',
    'exp' => $now + 3600,
    'iat' => $now,
    'nonce' => bin2hex(random_bytes(16)),
    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
    'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
    'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
        'id' => 'resource-123',
        'title' => 'Test Resource',
    ],
    'https://purl.imsglobal.org/spec/lti/claim/roles' => ['Learner'],
    'custom_student_id' => '2024001001',
    'email' => 'student@university.edu',
    'name' => 'Test Student',
];

// Base64Url 编码函数
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// 编码 Header 和 Payload
$headerEncoded = base64UrlEncode(json_encode($header));
$payloadEncoded = base64UrlEncode(json_encode($payload));

// 创建签名
$signatureInput = $headerEncoded . '.' . $payloadEncoded;
openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
$signatureEncoded = base64UrlEncode($signature);

// 组装 JWT
$jwt = $signatureInput . '.' . $signatureEncoded;

echo "\n=== Valid JWT ===\n";
echo $jwt . "\n";

// 保存到文件
file_put_contents(__DIR__ . '/../storage/keys/test_jwt_valid.txt', $jwt);
echo "\nValid JWT saved to: storage/keys/test_jwt_valid.txt\n";

// 生成无效 JWT（篡改签名）
$invalidJwt = $signatureInput . '.' . base64UrlEncode('invalid_signature');
file_put_contents(__DIR__ . '/../storage/keys/test_jwt_invalid.txt', $invalidJwt);
echo "Invalid JWT saved to: storage/keys/test_jwt_invalid.txt\n";

// 生成过期 JWT
$expiredPayload = $payload;
$expiredPayload['exp'] = $now - 3600; // 1小时前过期
$expiredPayloadEncoded = base64UrlEncode(json_encode($expiredPayload));
$expiredSignatureInput = $headerEncoded . '.' . $expiredPayloadEncoded;
openssl_sign($expiredSignatureInput, $expiredSignature, $privateKey, 'SHA256');
$expiredJwt = $expiredSignatureInput . '.' . base64UrlEncode($expiredSignature);
file_put_contents(__DIR__ . '/../storage/keys/test_jwt_expired.txt', $expiredJwt);
echo "Expired JWT saved to: storage/keys/test_jwt_expired.txt\n";

echo "\nTest JWTs generated successfully!\n";

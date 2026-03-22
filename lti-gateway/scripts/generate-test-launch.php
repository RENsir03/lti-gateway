<?php
/**
 * 生成 LTI 1.3 测试启动请求
 * 
 * 使用方法: php generate-test-launch.php [学号]
 */

$studentId = $argv[1] ?? '2024001001';
$studentName = $argv[2] ?? '测试学生';

// 工具配置
$toolId = 1;
$clientId = 'test-client-id';
$issuer = 'https://test-platform.edu';
$launchUrl = 'http://localhost:8081/lti/launch/' . $toolId;

// 生成密钥对
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];
$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privateKey);
$publicKey = openssl_pkey_get_details($res)['key'];

// JWT Header
$header = json_encode([
    'alg' => 'RS256',
    'typ' => 'JWT',
    'kid' => 'test-key-id',
]);

// 当前时间
$now = time();
$state = bin2hex(random_bytes(16));

// JWT Payload (LTI 1.3 Message)
$payload = json_encode([
    'iss' => $issuer,
    'aud' => $clientId,
    'sub' => 'user_' . $studentId,
    'exp' => $now + 600,
    'iat' => $now,
    'nonce' => bin2hex(random_bytes(16)),
    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
    'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
    'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => 'test-deployment',
    'https://purl.imsglobal.org/spec/lti/claim/target_link_uri' => $launchUrl,
    'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
        'id' => 'resource-' . rand(1000, 9999),
        'title' => '测试课程',
        'description' => '这是一个测试课程',
    ],
    'https://purl.imsglobal.org/spec/lti/claim/roles' => [
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
    ],
    'https://purl.imsglobal.org/spec/lti/claim/custom' => [
        'student_id' => $studentId,
        'course_id' => 'COURSE-' . rand(100, 999),
    ],
    'given_name' => $studentName,
    'family_name' => '',
    'name' => $studentName,
    'email' => $studentId . '@test.edu',
    'lis_person_sourcedid' => $studentId,
]);

// Base64Url 编码
function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

// 创建 JWT
$encodedHeader = base64UrlEncode($header);
$encodedPayload = base64UrlEncode($payload);
$signatureInput = $encodedHeader . '.' . $encodedPayload;

openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
$idToken = $signatureInput . '.' . base64UrlEncode($signature);

echo "========================================\n";
echo "    LTI 1.3 测试启动请求\n";
echo "========================================\n\n";

echo "学号: $studentId\n";
echo "姓名: $studentName\n";
echo "启动端点: $launchUrl\n\n";

echo "=== ID Token (JWT) ===\n";
echo $idToken . "\n\n";

echo "=== 测试命令 (cURL) ===\n";
echo "curl -X POST $launchUrl \\\n";
echo "  -d 'id_token=" . urlencode($idToken) . "' \\\n";
echo "  -d 'state=$state' \\\n";
echo "  -L\n\n";

echo "=== 或者使用浏览器 ===\n";
echo "创建一个 HTML 表单:\n\n";
echo "<form method='POST' action='$launchUrl'>\n";
echo "  <input type='hidden' name='id_token' value='$idToken' />\n";
echo "  <input type='hidden' name='state' value='$state' />\n";
echo "  <button type='submit'>启动 LTI</button>\n";
echo "</form>\n\n";

echo "=== 公钥 (用于验证) ===\n";
echo $publicKey . "\n";

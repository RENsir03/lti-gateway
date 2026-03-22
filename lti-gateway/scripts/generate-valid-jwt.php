<?php
/**
 * 生成有效的 LTI 1.3 JWT（使用正确的私钥签名）
 * 
 * 使用方法: php generate-valid-jwt.php [学号] [姓名]
 */

$studentId = $argv[1] ?? '2024001001';
$studentName = $argv[2] ?? '测试学生';

// 读取私钥
$privateKey = file_get_contents(__DIR__ . '/test-private-key.pem');
if (!$privateKey) {
    die("Error: Could not read private key\n");
}

$toolId = 1;
$clientId = 'test-client-id';
$issuer = 'https://test-platform.edu';
$launchUrl = 'http://localhost:8081/lti/launch/' . $toolId;

// Base64Url 编码函数
function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

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

// 创建 JWT
$encodedHeader = base64UrlEncode($header);
$encodedPayload = base64UrlEncode($payload);
$signatureInput = $encodedHeader . '.' . $encodedPayload;

// 使用私钥签名
openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
$idToken = $signatureInput . '.' . base64UrlEncode($signature);

echo "========================================\n";
echo "    LTI 1.3 有效 JWT（已签名）\n";
echo "========================================\n\n";

echo "学号: $studentId\n";
echo "姓名: $studentName\n";
echo "启动端点: $launchUrl\n\n";

echo "=== ID Token (JWT) ===\n";
echo $idToken . "\n\n";

echo "=== cURL 测试命令 ===\n";
echo "curl -X POST http://localhost:8081/lti/launch/$toolId \\\n";
echo "  -H 'Content-Type: application/x-www-form-urlencoded' \\\n";
echo "  -d 'id_token=$idToken' \\\n";
echo "  -d 'state=$state' \\\n";
echo "  -L\n\n";

echo "=== HTML 表单 ===\n";
echo "<form method='POST' action='$launchUrl'>\n";
echo "  <input type='hidden' name='id_token' value='$idToken' />\n";
echo "  <input type='hidden' name='state' value='$state' />\n";
echo "  <button type='submit'>启动 LTI</button>\n";
echo "</form>\n";

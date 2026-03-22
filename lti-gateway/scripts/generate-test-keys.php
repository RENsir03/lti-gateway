<?php
/**
 * 生成测试用 RSA 密钥对
 */

// 创建密钥目录
$keysDir = __DIR__ . '/../storage/keys';
if (!is_dir($keysDir)) {
    mkdir($keysDir, 0755, true);
}

// 生成 RSA 密钥对
$privateKey = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);

// 导出私钥
openssl_pkey_export($privateKey, $privateKeyPem);

// 导出公钥
$publicKey = openssl_pkey_get_details($privateKey);
$publicKeyPem = $publicKey['key'];

// 保存密钥
file_put_contents($keysDir . '/test_private.pem', $privateKeyPem);
file_put_contents($keysDir . '/test_public.pem', $publicKeyPem);

echo "Keys generated successfully\n";
echo "Private key: {$keysDir}/test_private.pem\n";
echo "Public key: {$keysDir}/test_public.pem\n";

// 生成 JWKS 格式
$jwk = [
    'kty' => 'RSA',
    'kid' => 'test-key-1',
    'use' => 'sig',
    'alg' => 'RS256',
    'n' => rtrim(strtr(base64_encode($publicKey['rsa']['n']), '+/', '-_'), '='),
    'e' => rtrim(strtr(base64_encode($publicKey['rsa']['e']), '+/', '-_'), '='),
];

$jwks = ['keys' => [$jwk]];
file_put_contents($keysDir . '/jwks.json', json_encode($jwks, JSON_PRETTY_PRINT));

echo "JWKS saved to: {$keysDir}/jwks.json\n";

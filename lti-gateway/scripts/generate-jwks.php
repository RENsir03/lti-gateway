<?php
/**
 * 生成 JWKS (JSON Web Key Set) 用于测试
 */

// 生成 RSA 密钥对
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];
$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privateKey);
$keyDetails = openssl_pkey_get_details($res);

// 提取公钥组件
$publicKey = $keyDetails['key'];
$rsaKey = $keyDetails['rsa'];

// Base64Url 编码函数
function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

// 构建 JWKS
$jwks = [
    'keys' => [
        [
            'kty' => 'RSA',
            'kid' => 'test-key-id',
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => base64UrlEncode($rsaKey['n']),
            'e' => base64UrlEncode($rsaKey['e']),
        ],
    ],
];

// 保存私钥供测试脚本使用
file_put_contents(__DIR__ . '/test-private-key.pem', $privateKey);
file_put_contents(__DIR__ . '/test-public-key.pem', $publicKey);
file_put_contents(__DIR__ . '/jwks.json', json_encode($jwks, JSON_PRETTY_PRINT));

echo "JWKS 生成成功！\n\n";
echo "文件位置:\n";
echo "  - 私钥: scripts/test-private-key.pem\n";
echo "  - 公钥: scripts/test-public-key.pem\n";
echo "  - JWKS: scripts/jwks.json\n\n";

echo "JWKS 内容:\n";
echo json_encode($jwks, JSON_PRETTY_PRINT) . "\n";

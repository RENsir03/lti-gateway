<?php

/**
 * RSA 密钥对生成脚本
 * 
 * 用法: php scripts/generate-keys.php
 */

$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$res = openssl_pkey_new($config);

if (!$res) {
    echo "错误: 无法生成密钥对\n";
    exit(1);
}

// 导出私钥
openssl_pkey_export($res, $privateKey);

// 获取公钥
$keyDetails = openssl_pkey_get_details($res);
$publicKey = $keyDetails['key'];

echo "=== RSA 密钥对生成成功 ===\n\n";

echo "私钥 (Private Key):\n";
echo "-------------------\n";
echo $privateKey;
echo "\n\n";

echo "公钥 (Public Key):\n";
echo "------------------\n";
echo $publicKey;
echo "\n\n";

echo "使用说明:\n";
echo "1. 将私钥保存到安全位置\n";
echo "2. 将公钥配置到上游 LMS\n";
echo "3. 在 LTI Gateway 中配置私钥\n";

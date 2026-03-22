<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ToolConfig;

// 生成 RSA 密钥对
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privateKey);
$publicKey = openssl_pkey_get_details($res)['key'];

$tool = ToolConfig::find(1);
if ($tool) {
    $tool->private_key = $privateKey;
    $tool->public_key = $publicKey;
    $tool->save();
    echo "Keys updated successfully\n";
    echo "Public Key:\n$publicKey\n";
} else {
    echo "Tool not found\n";
}

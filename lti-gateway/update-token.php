<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ToolConfig;
use Illuminate\Support\Facades\Crypt;

$token = '58d273e326a23f22f899f4c83837a917';

$config = ToolConfig::first();
if ($config) {
    $config->auth_token = $token;
    $config->save();
    echo "✅ Token 已加密并更新到数据库\n";
    echo "Token (明文): {$token}\n";
    echo "Token (加密后): " . Crypt::encryptString($token) . "\n";
} else {
    echo "❌ 未找到 ToolConfig 记录\n";
}

<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ToolConfig;

$token = $argv[1] ?? 'test-token-for-development';

$tool = ToolConfig::find(1);
if ($tool) {
    $tool->auth_token = $token;
    $tool->save();
    echo "Token updated successfully\n";
} else {
    echo "Tool config not found\n";
}

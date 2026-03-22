<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ToolConfig;

$publicKey = file_get_contents(__DIR__ . '/test-public-key.pem');

$tool = ToolConfig::find(1);
if ($tool) {
    $tool->public_key = $publicKey;
    $tool->jwks_url = 'http://lti_gateway_nginx/lti/jwks/1';
    $tool->save();
    echo "Tool config updated successfully\n";
    echo "JWKS URL: http://localhost:8081/lti/jwks/1\n";
} else {
    echo "Tool config not found\n";
}

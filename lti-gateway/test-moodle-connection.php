<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ToolConfig;
use App\Services\DownstreamApiService;
use Illuminate\Support\Facades\DB;

// 清除查询缓存
DB::connection()->enableQueryLog();

// 强制从数据库重新读取
$config = ToolConfig::query()->where('id', 1)->first();

echo "API Base URL from DB: {$config->api_base_url}\n";
echo "Token exists: " . ($config->getDecryptedAuthToken() ? 'YES' : 'NO') . "\n";

$service = new DownstreamApiService();

$healthy = $service->healthCheck($config);
echo "\nHealth Check: " . ($healthy ? "✅ OK" : "❌ FAILED") . "\n";

if ($healthy) {
    echo "✅ Moodle Web Service 连接正常！\n";
} else {
    echo "❌ Moodle Web Service 连接失败\n";
}

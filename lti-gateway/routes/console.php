<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 自定义命令
Artisan::command('lti:info', function () {
    $this->info('LTI Gateway v1.0.0');
    $this->line('');
    $this->line('可用命令:');
    $this->line('  lti:cleanup     - 清理过期日志');
    $this->line('  lti:health-check - 健康检查');
})->purpose('显示 LTI Gateway 信息');

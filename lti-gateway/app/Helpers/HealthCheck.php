<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheck
{
    public static function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return 'ok';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    public static function checkRedis(): string
    {
        try {
            Redis::ping();
            return 'ok';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}

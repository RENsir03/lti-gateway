<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * LTI Nonce 模型
 * 
 * 防止重放攻击的临时令牌存储
 * 需要定期清理过期记录
 */
class LtiNonce extends Model
{
    protected $table = 'lti_nonces';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'nonce',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function isValid(string $nonce): bool
    {
        $record = self::find($nonce);
        if (!$record) {
            return false;
        }
        return $record->expires_at->isFuture();
    }

    public static function store(string $nonce, int $ttlSeconds = 86400): bool
    {
        try {
            self::create([
                'nonce' => $nonce,
                'expires_at' => now()->addSeconds($ttlSeconds),
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function cleanup(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}

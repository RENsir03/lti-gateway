<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/**
 * LTI 工具配置模型
 * 
 * 管理下游 LTI 工具的配置信息，支持 LTI 1.1 和 1.3
 * 敏感字段自动加密/解密
 */
class ToolConfig extends Model
{
    use HasFactory;

    protected $table = 'tool_configs';

    /**
     * 可批量赋值字段
     */
    protected $fillable = [
        'name',
        'type',
        'platform_issuer',
        'client_id',
        'deployment_id',
        'jwks_url',
        'public_key',
        'private_key',
        'auth_token',
        'api_base_url',
        'virtual_email_domain',
        'is_active',
    ];

    /**
     * 类型转换
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_health_check' => 'datetime',
    ];

    /**
     * 获取虚拟邮箱地址
     */
    public function generateVirtualEmail(string $studentId): string
    {
        return sprintf('%s@%s', strtolower($studentId), $this->virtual_email_domain);
    }

    /**
     * 获取解密后的私钥
     */
    public function getDecryptedPrivateKey(): ?string
    {
        if (empty($this->private_key)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->private_key);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt private key', [
                'tool_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 获取解密后的认证令牌
     */
    public function getDecryptedAuthToken(): ?string
    {
        if (empty($this->auth_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->auth_token);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt auth token', [
                'tool_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 设置加密私钥
     */
    public function setPrivateKeyAttribute(?string $value): void
    {
        $this->attributes['private_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * 设置加密认证令牌
     */
    public function setAuthTokenAttribute(?string $value): void
    {
        $this->attributes['auth_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * 关联的用户映射
     */
    public function userMappings(): HasMany
    {
        return $this->hasMany(UserMapping::class, 'tool_config_id');
    }

    /**
     * 关联的启动日志
     */
    public function launchLogs(): HasMany
    {
        return $this->hasMany(LaunchLog::class, 'tool_config_id');
    }

    /**
     * 作用域：仅启用状态
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：按类型筛选
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

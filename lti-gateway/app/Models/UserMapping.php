<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 用户映射模型
 * 
 * 维护上游学号与下游系统用户ID的映射关系
 * 支持高并发场景下的安全创建
 */
class UserMapping extends Model
{
    use HasFactory;

    protected $table = 'user_mappings';

    protected $fillable = [
        'source_student_id',
        'tool_config_id',
        'target_user_id',
        'target_username',
        'virtual_email',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function toolConfig(): BelongsTo
    {
        return $this->belongsTo(ToolConfig::class, 'tool_config_id');
    }

    public function getStudentId(): string
    {
        return $this->source_student_id;
    }

    public function getTargetUserId(): string
    {
        return $this->target_user_id;
    }

    public function markSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    public function scopeByStudentId($query, string $studentId)
    {
        return $query->where('source_student_id', $studentId);
    }

    public function scopeByToolConfig($query, int $toolConfigId)
    {
        return $query->where('tool_config_id', $toolConfigId);
    }
}

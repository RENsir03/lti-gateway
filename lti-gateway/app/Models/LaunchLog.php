<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LTI 启动日志模型
 * 
 * 记录每次 LTI 启动请求的详细信息和结果
 * 用于审计、故障排查和统计分析
 */
class LaunchLog extends Model
{
    use HasFactory;

    protected $table = 'launch_logs';
    public $timestamps = false;

    protected $fillable = [
        'tool_config_id',
        'source_student_id',
        'status',
        'request_payload',
        'ip_address',
        'user_agent',
        'error_message',
        'error_code',
        'processing_time_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function toolConfig(): BelongsTo
    {
        return $this->belongsTo(ToolConfig::class, 'tool_config_id');
    }

    public static function logSuccess(
        ?int $toolConfigId,
        ?string $studentId,
        array $payload,
        ?int $processingTimeMs = null
    ): self {
        return self::create([
            'tool_config_id' => $toolConfigId,
            'source_student_id' => $studentId,
            'status' => 'success',
            'request_payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'processing_time_ms' => $processingTimeMs,
        ]);
    }

    public static function logFailure(
        ?int $toolConfigId,
        ?string $studentId,
        array $payload,
        string $errorMessage,
        ?string $errorCode = null,
        ?int $processingTimeMs = null
    ): self {
        return self::create([
            'tool_config_id' => $toolConfigId,
            'source_student_id' => $studentId,
            'status' => 'fail',
            'request_payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'processing_time_ms' => $processingTimeMs,
        ]);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'fail');
    }

    public function scopeBetweenDates($query, string $start, string $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}

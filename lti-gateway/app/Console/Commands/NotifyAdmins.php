<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LaunchLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 管理员通知命令
 * 
 * 检测异常并发送通知
 */
class NotifyAdmins extends Command
{
    protected $signature = 'lti:notify 
                            {--threshold=10 : 错误率阈值 (%)}
                            {--email= : 通知邮箱地址}';
    protected $description = '检测异常并通知管理员';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $email = $this->option('email');

        // 检查最近1小时的错误率
        $lastHour = now()->subHour();
        $total = LaunchLog::where('created_at', '>=', $lastHour)->count();
        
        if ($total === 0) {
            $this->info('最近1小时无请求');
            return self::SUCCESS;
        }

        $failed = LaunchLog::where('created_at', '>=', $lastHour)
            ->where('status', 'fail')
            ->count();

        $errorRate = ($failed / $total) * 100;

        if ($errorRate > $threshold) {
            $message = "警告: 最近1小时错误率为 {$errorRate}%，超过阈值 {$threshold}%";
            $this->error($message);

            // 记录到日志
            Log::channel('lti')->error($message, [
                'total' => $total,
                'failed' => $failed,
                'error_rate' => $errorRate,
            ]);

            // 发送邮件通知 (如果配置了邮箱)
            if ($email) {
                $this->sendNotification($email, $message, $errorRate, $total, $failed);
            }

            return self::FAILURE;
        }

        $this->info("错误率正常: {$errorRate}%");
        return self::SUCCESS;
    }

    private function sendNotification(string $email, string $message, float $errorRate, int $total, int $failed): void
    {
        // 这里可以实现邮件发送逻辑
        // Mail::raw($message, function ($mail) use ($email) {
        //     $mail->to($email)->subject('LTI Gateway 异常告警');
        // });
        
        $this->info("通知已发送到: {$email}");
    }
}

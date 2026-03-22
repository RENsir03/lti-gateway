<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 工具配置表
        Schema::create('tool_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('工具名称');
            $table->enum('type', ['lti11', 'lti13'])->default('lti13')->comment('LTI 版本');
            
            // LTI 1.3 专用字段
            $table->string('platform_issuer', 255)->nullable()->comment('平台 Issuer URL');
            $table->string('client_id', 100)->nullable()->comment('客户端ID');
            $table->string('deployment_id', 100)->nullable()->comment('部署ID');
            $table->string('jwks_url', 500)->nullable()->comment('JWKS 公钥集合 URL');
            
            // 密钥配置 (加密存储)
            $table->text('public_key')->nullable()->comment('RSA 公钥 (PEM格式)');
            $table->text('private_key')->nullable()->comment('RSA 私钥 (加密存储)');
            
            // 下游 API 配置
            $table->text('auth_token')->nullable()->comment('API 认证令牌 (加密存储)');
            $table->string('api_base_url', 500)->nullable()->comment('下游 API 基础URL');
            $table->string('virtual_email_domain', 100)->default('proxy.local')->comment('虚拟邮箱域名');
            
            // 状态控制
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamp('last_health_check')->nullable()->comment('最后健康检查时间');
            
            $table->timestamps();
            
            $table->index(['type', 'is_active'], 'idx_type_active');
            $table->index('platform_issuer', 'idx_issuer');
        });

        // 用户映射表
        Schema::create('user_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_student_id', 50)->comment('上游学号');
            $table->foreignId('tool_config_id')
                ->constrained('tool_configs')
                ->onDelete('cascade')
                ->comment('关联的工具配置');
            $table->string('target_user_id', 50)->comment('下游系统用户ID');
            $table->string('target_username', 100)->comment('下游系统用户名');
            
            $table->string('virtual_email', 100)->nullable()->comment('虚拟邮箱地址');
            $table->timestamp('last_synced_at')->nullable()->comment('最后同步时间');
            $table->json('metadata')->nullable()->comment('额外元数据');
            
            $table->timestamps();
            
            $table->unique(['source_student_id', 'tool_config_id'], 'unique_mapping');
            $table->index('source_student_id', 'idx_student_id');
            $table->index('target_user_id', 'idx_target_user');
            $table->index('tool_config_id', 'idx_tool_config');
        });

        // 启动日志表
        Schema::create('launch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_config_id')
                ->nullable()
                ->constrained('tool_configs')
                ->onDelete('set null')
                ->comment('关联的工具配置');
            $table->string('source_student_id', 50)->nullable()->comment('学号');
            $table->enum('status', ['success', 'fail', 'pending'])->default('pending')->comment('状态');
            
            $table->json('request_payload')->nullable()->comment('原始请求数据');
            $table->string('ip_address', 45)->nullable()->comment('客户端IP');
            $table->string('user_agent', 500)->nullable()->comment('User-Agent');
            
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->string('error_code', 50)->nullable()->comment('错误代码');
            
            $table->integer('processing_time_ms')->nullable()->comment('处理耗时(毫秒)');
            
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');
            
            $table->index(['created_at', 'status'], 'idx_time_status');
            $table->index('source_student_id', 'idx_log_student');
            $table->index('tool_config_id', 'idx_log_tool');
        });

        // Nonce 表
        Schema::create('lti_nonces', function (Blueprint $table) {
            $table->string('nonce', 128)->primary()->comment('唯一 Nonce');
            $table->timestamp('expires_at')->comment('过期时间');
            $table->index('expires_at', 'idx_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lti_nonces');
        Schema::dropIfExists('launch_logs');
        Schema::dropIfExists('user_mappings');
        Schema::dropIfExists('tool_configs');
    }
};

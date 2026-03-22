<?php

namespace Database\Seeders;

use App\Models\ToolConfig;
use Illuminate\Database\Seeder;

class ToolConfigSeeder extends Seeder
{
    public function run(): void
    {
        // 生成 RSA 密钥对
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        // 创建 Moodle LTI 1.3 配置示例
        ToolConfig::create([
            'name' => 'Moodle 课程平台',
            'type' => 'lti13',
            'platform_issuer' => 'https://your-lms.university.edu',
            'client_id' => 'your-client-id',
            'deployment_id' => 'your-deployment-id',
            'jwks_url' => 'https://your-lms.university.edu/.well-known/jwks.json',
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'auth_token' => 'your-moodle-webservice-token',
            'api_base_url' => 'http://moodle:8080',
            'virtual_email_domain' => 'proxy.university.edu',
            'is_active' => true,
        ]);

        $this->command->info('工具配置已创建');
        $this->command->info('请修改配置中的以下值:');
        $this->command->info('- platform_issuer: 上游 LMS 的 Issuer');
        $this->command->info('- client_id: LTI 客户端 ID');
        $this->command->info('- deployment_id: LTI 部署 ID');
        $this->command->info('- auth_token: Moodle Web Service Token');
    }
}

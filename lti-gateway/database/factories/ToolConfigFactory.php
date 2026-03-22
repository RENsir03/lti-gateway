<?php

namespace Database\Factories;

use App\Models\ToolConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class ToolConfigFactory extends Factory
{
    protected $model = ToolConfig::class;

    public function definition(): array
    {
        // 生成 RSA 密钥对
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        return [
            'name' => $this->faker->company . ' LTI Tool',
            'type' => 'lti13',
            'platform_issuer' => $this->faker->url,
            'client_id' => $this->faker->uuid,
            'deployment_id' => $this->faker->uuid,
            'jwks_url' => $this->faker->url . '/.well-known/jwks.json',
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'auth_token' => $this->faker->sha256,
            'api_base_url' => 'http://moodle:8080',
            'virtual_email_domain' => 'proxy.' . $this->faker->domainName,
            'is_active' => true,
        ];
    }

    public function lti11(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'lti11',
            'platform_issuer' => null,
            'client_id' => null,
            'deployment_id' => null,
            'jwks_url' => null,
            'public_key' => null,
            'private_key' => null,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

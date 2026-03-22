<?php

namespace Tests\Feature;

use App\Models\ToolConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint(): void
    {
        $response = $this->get('/lti/health');
        
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'timestamp', 'version']);
    }

    public function test_launch_with_invalid_tool_id(): void
    {
        $response = $this->post('/lti/launch/999', [
            'id_token' => 'invalid-token',
        ]);
        
        $response->assertStatus(404);
    }

    public function test_jwks_endpoint(): void
    {
        // 创建测试配置
        $config = ToolConfig::factory()->create([
            'public_key' => $this->generateTestPublicKey(),
        ]);
        
        $response = $this->get("/lti/jwks/{$config->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['keys']);
    }

    private function generateTestPublicKey(): string
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        $res = openssl_pkey_new($config);
        $details = openssl_pkey_get_details($res);
        
        return $details['key'];
    }
}

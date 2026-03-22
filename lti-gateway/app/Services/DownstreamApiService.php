<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DownstreamApiException;
use App\Models\ToolConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 下游 API 服务
 * 
 * 负责与下游工具 (如 Moodle) 的 API 交互
 * 包括用户创建、查询等操作
 */
class DownstreamApiService
{
    protected int $connectTimeout = 10;
    protected int $requestTimeout = 30;

    /**
     * 创建下游用户 (以 Moodle 为例)
     */
    public function createUser(
        string $studentId,
        string $firstname,
        string $lastname,
        string $email,
        ToolConfig $toolConfig
    ): string {
        $apiBaseUrl = rtrim($toolConfig->api_base_url, '/');
        $token = $toolConfig->getDecryptedAuthToken();

        if (empty($token)) {
            throw new DownstreamApiException('下游 API 认证令牌未配置');
        }

        $password = $this->generateSecurePassword();

        $params = [
            'wstoken' => $token,
            'moodlewsrestformat' => 'json',
            'wsfunction' => 'core_user_create_users',
            'users' => [
                [
                    'username' => strtolower($studentId),
                    'idnumber' => $studentId,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'password' => $password,
                    'auth' => 'manual',
                    'lang' => 'zh_cn',
                ],
            ],
        ];

        Log::info('Creating downstream user', [
            'tool_id' => $toolConfig->id,
            'student_id' => $studentId,
            'username' => strtolower($studentId),
            'api_base_url' => $apiBaseUrl,
            'full_url' => "{$apiBaseUrl}/webservice/rest/server.php",
        ]);

        try {
            $response = Http::withOptions([
                'connect_timeout' => $this->connectTimeout,
                'timeout' => $this->requestTimeout,
                'allow_redirects' => false, // 禁用重定向，避免Moodle重定向到localhost
            ])->post("{$apiBaseUrl}/webservice/rest/server.php", $params);

            if ($response->failed()) {
                $status = $response->status();
                Log::error('Downstream API request failed', [
                    'tool_id' => $toolConfig->id,
                    'status' => $status,
                    'body' => $response->body(),
                ]);
                throw new DownstreamApiException('下游服务响应异常', $status);
            }

            $data = $response->json();

            // 检查是否是Moodle重定向页面（当$CFG->wwwroot配置不正确时）
            if ($data === null && str_contains($response->body(), '重定向')) {
                Log::error('Moodle returned redirect page. Please check $CFG->wwwroot configuration in Moodle config.php', [
                    'tool_id' => $toolConfig->id,
                    'api_base_url' => $apiBaseUrl,
                    'response_preview' => substr($response->body(), 0, 200),
                ]);
                throw new DownstreamApiException('Moodle配置错误：请检查Moodle的$CFG->wwwroot配置，当前配置为localhost:8080，应该改为moodle:8080');
            }

            if (isset($data['exception'])) {
                $errorMessage = $data['message'] ?? '未知错误';
                
                if (str_contains($errorMessage, 'already exists')) {
                    Log::info('User already exists in downstream, fetching ID', [
                        'student_id' => $studentId,
                    ]);
                    return $this->getUserByField('idnumber', $studentId, $toolConfig);
                }

                Log::error('Moodle API returned error', [
                    'tool_id' => $toolConfig->id,
                    'exception' => $data['exception'],
                    'message' => $errorMessage,
                ]);
                throw new DownstreamApiException("下游服务错误: {$errorMessage}");
            }

            if (!empty($data[0]['id'])) {
                $userId = (string) $data[0]['id'];
                Log::info('User created successfully', [
                    'tool_id' => $toolConfig->id,
                    'student_id' => $studentId,
                    'target_user_id' => $userId,
                ]);
                return $userId;
            }

            throw new DownstreamApiException('无法解析下游服务响应');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Downstream API connection failed', [
                'tool_id' => $toolConfig->id,
                'api_base_url' => $apiBaseUrl,
                'full_url' => "{$apiBaseUrl}/webservice/rest/server.php",
                'error' => $e->getMessage(),
            ]);
            throw new DownstreamApiException('无法连接到下游服务', 0, 0, $e);
        }
    }

    /**
     * 通过字段查询用户
     */
    public function getUserByField(
        string $field,
        string $value,
        ToolConfig $toolConfig
    ): string {
        $apiBaseUrl = rtrim($toolConfig->api_base_url, '/');
        $token = $toolConfig->getDecryptedAuthToken();

        $params = [
            'wstoken' => $token,
            'moodlewsrestformat' => 'json',
            'wsfunction' => 'core_user_get_users_by_field',
            'field' => $field,
            'values' => [$value],
        ];

        try {
            $response = Http::withOptions([
                'connect_timeout' => $this->connectTimeout,
                'timeout' => $this->requestTimeout,
                'allow_redirects' => false, // 禁用重定向，避免Moodle重定向到localhost
            ])->post("{$apiBaseUrl}/webservice/rest/server.php", $params);

            if ($response->failed()) {
                throw new DownstreamApiException('查询用户失败', $response->status());
            }

            $data = $response->json();

            if (!empty($data[0]['id'])) {
                return (string) $data[0]['id'];
            }

            throw new DownstreamApiException("未找到用户: {$field}={$value}");

        } catch (\Exception $e) {
            if ($e instanceof DownstreamApiException) {
                throw $e;
            }
            throw new DownstreamApiException('查询用户时发生错误', 0, 0, $e);
        }
    }

    /**
     * 检查下游服务健康状态
     */
    public function healthCheck(ToolConfig $toolConfig): bool
    {
        try {
            $apiBaseUrl = rtrim($toolConfig->api_base_url, '/');
            $token = $toolConfig->getDecryptedAuthToken();

            $response = Http::withOptions([
                'connect_timeout' => 5,
                'timeout' => 10,
            ])->get("{$apiBaseUrl}/webservice/rest/server.php", [
                'wstoken' => $token,
                'wsfunction' => 'core_webservice_get_site_info',
                'moodlewsrestformat' => 'json',
            ]);

            return $response->successful() && !isset($response->json()['exception']);

        } catch (\Exception $e) {
            Log::warning('Health check failed', [
                'tool_id' => $toolConfig->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 生成安全随机密码
     */
    protected function generateSecurePassword(int $length = 16): string
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';

        $all = $upper . $lower . $numbers . $special;
        
        $password = '';
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}

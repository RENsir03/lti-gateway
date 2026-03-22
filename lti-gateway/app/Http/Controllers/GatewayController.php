<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\DownstreamApiException;
use App\Exceptions\InvalidLtiRequestException;
use App\Exceptions\MissingStudentIdException;
use App\Exceptions\UserMappingException;
use App\Models\LaunchLog;
use App\Models\ToolConfig;
use App\Services\Lti11Handler;
use App\Services\Lti13Handler;
use App\Services\StudentIdResolver;
use App\Services\UserMappingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LTI 网关控制器
 */
class GatewayController extends Controller
{
    public function __construct(
        protected StudentIdResolver $studentIdResolver,
        protected UserMappingService $userMappingService,
        protected Lti13Handler $lti13Handler,
        protected Lti11Handler $lti11Handler
    ) {
    }

    /**
     * 处理 LTI 启动请求
     */
    public function launch(int $toolId, Request $request)
    {
        $startTime = microtime(true);
        $toolConfig = null;
        $studentId = null;
        $claims = [];

        // DEBUG: 记录请求信息
        Log::debug('LTI Launch Request', [
            'tool_id' => $toolId,
            'method' => $request->method(),
            'has_id_token' => $request->has('id_token'),
            'has_state' => $request->has('state'),
            'all_params' => $request->all(),
        ]);

        try {
            $toolConfig = ToolConfig::find($toolId);
            
            // DEBUG: 记录工具配置查询结果
            Log::debug('Tool Config Query', [
                'tool_id' => $toolId,
                'found' => $toolConfig ? true : false,
                'is_active' => $toolConfig?->is_active,
                'type' => $toolConfig?->type,
            ]);
            
            if (!$toolConfig || !$toolConfig->is_active) {
                Log::warning('Tool config not found or inactive', ['tool_id' => $toolId]);
                abort(404, '工具配置不存在或已禁用');
            }

            if ($toolConfig->type === 'lti13') {
                return $this->handleLti13Launch($toolConfig, $request, $startTime);
            } else {
                return $this->handleLti11Launch($toolConfig, $request, $startTime);
            }

        } catch (MissingStudentIdException $e) {
            return $this->handleError($e, $toolConfig, $studentId, $claims, $startTime, '身份验证失败：缺少学号信息');
        } catch (InvalidLtiRequestException $e) {
            return $this->handleError($e, $toolConfig, $studentId, $claims, $startTime, '无效的 LTI 请求');
        } catch (DownstreamApiException $e) {
            return $this->handleError($e, $toolConfig, $studentId, $claims, $startTime, '服务暂时不可用，请联系管理员');
        } catch (UserMappingException $e) {
            return $this->handleError($e, $toolConfig, $studentId, $claims, $startTime, '用户数据处理失败，请稍后重试');
        } catch (\Exception $e) {
            Log::error('Unexpected error during LTI launch', [
                'tool_id' => $toolId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleError($e, $toolConfig, $studentId, $claims, $startTime, '系统错误，请联系管理员');
        }
    }

    protected function handleLti13Launch(ToolConfig $toolConfig, Request $request, float $startTime)
    {
        Log::debug('Starting LTI 1.3 launch', ['tool_id' => $toolConfig->id]);
        
        try {
            $claims = $this->lti13Handler->validateLaunch($request, $toolConfig);
            Log::debug('LTI 1.3 validation successful', ['claims_keys' => array_keys($claims)]);
        } catch (\Exception $e) {
            Log::error('LTI 1.3 validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
        
        $request->attributes->set('lti_claims', $claims);

        try {
            $studentId = $this->studentIdResolver->extract($request);
            Log::debug('Student ID extracted', ['student_id' => $studentId]);
        } catch (\Exception $e) {
            Log::error('Failed to extract student ID', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $userInfo = [
            'firstname' => $claims['given_name'] ?? $claims['name'] ?? 'User',
            'lastname' => $claims['family_name'] ?? $studentId,
        ];

        $mapping = $this->userMappingService->findOrCreate($studentId, $toolConfig, $userInfo);

        $targetUrl = $toolConfig->api_base_url;
        $html = $this->lti13Handler->buildLaunchResponse(
            $claims,
            $mapping->target_user_id,
            $toolConfig,
            $targetUrl
        );

        $processingTime = (int) ((microtime(true) - $startTime) * 1000);
        LaunchLog::logSuccess($toolConfig->id, $studentId, $claims, $processingTime);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function handleLti11Launch(ToolConfig $toolConfig, Request $request, float $startTime)
    {
        $studentId = $this->studentIdResolver->extract($request);

        $userInfo = [
            'firstname' => $request->input('lis_person_name_given', 'User'),
            'lastname' => $request->input('lis_person_name_family', $studentId),
        ];

        $mapping = $this->userMappingService->findOrCreate($studentId, $toolConfig, $userInfo);

        $params = [
            'user_id' => $mapping->target_user_id,
            'roles' => $request->input('roles', 'Learner'),
            'resource_link_id' => $request->input('resource_link_id'),
            'context_id' => $request->input('context_id'),
            'lis_person_name_full' => $userInfo['firstname'] . ' ' . $userInfo['lastname'],
            'lis_person_name_given' => $userInfo['firstname'],
            'lis_person_name_family' => $userInfo['lastname'],
            'lis_person_contact_email_primary' => $mapping->virtual_email,
            'oauth_consumer_key' => $toolConfig->client_id,
        ];

        $secret = $toolConfig->getDecryptedAuthToken() ?? '';
        $targetUrl = $toolConfig->api_base_url;
        $html = $this->lti11Handler->buildLaunchForm($params, $secret, $targetUrl);

        $processingTime = (int) ((microtime(true) - $startTime) * 1000);
        LaunchLog::logSuccess($toolConfig->id, $studentId, $params, $processingTime);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function handleError(
        \Exception $e,
        ?ToolConfig $toolConfig,
        ?string $studentId,
        array $claims,
        float $startTime,
        string $userMessage
    ) {
        $processingTime = (int) ((microtime(true) - $startTime) * 1000);

        LaunchLog::logFailure(
            $toolConfig?->id,
            $studentId,
            $claims,
            $e->getMessage(),
            $e instanceof \App\Exceptions\LtiException ? $e->getErrorCode() : 'UNKNOWN_ERROR',
            $processingTime
        );

        return response()->view('lti.error', [
            'message' => $userMessage,
            'code' => $e instanceof \App\Exceptions\LtiException ? $e->getErrorCode() : 'ERROR',
        ], 400);
    }

    /**
     * 返回 JWKS 公钥
     */
    public function jwks(int $toolId)
    {
        $toolConfig = ToolConfig::find($toolId);

        if (!$toolConfig || empty($toolConfig->public_key)) {
            abort(404, '未找到公钥配置');
        }

        try {
            $publicKey = $toolConfig->public_key;
            $keyId = 'gateway-key-' . $toolConfig->id;

            $rsaKey = openssl_pkey_get_public($publicKey);
            if (!$rsaKey) {
                throw new \Exception('Invalid public key');
            }

            $keyDetails = openssl_pkey_get_details($rsaKey);
            if (!$keyDetails) {
                throw new \Exception('Failed to get key details');
            }

            $jwks = [
                'keys' => [
                    [
                        'kty' => 'RSA',
                        'kid' => $keyId,
                        'use' => 'sig',
                        'alg' => 'RS256',
                        'n' => rtrim(strtr(base64_encode($keyDetails['rsa']['n']), '+/', '-_'), '='),
                        'e' => rtrim(strtr(base64_encode($keyDetails['rsa']['e']), '+/', '-_'), '='),
                    ],
                ],
            ];

            return response()->json($jwks);

        } catch (\Exception $e) {
            Log::error('Failed to generate JWKS', [
                'tool_id' => $toolId,
                'error' => $e->getMessage(),
            ]);
            abort(500, '生成 JWKS 失败');
        }
    }
}

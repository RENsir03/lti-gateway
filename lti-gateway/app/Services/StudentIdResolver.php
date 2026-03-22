<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MissingStudentIdException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 学号解析服务
 * 
 * 从 LTI 请求中提取学号，支持多种来源字段
 * 优先级: custom_student_id > lis_person_sourcedid > sub
 */
class StudentIdResolver
{
    /**
     * 学号字段优先级列表
     */
    protected array $priorityFields = [
        'custom_student_id',      // 最高优先级：自定义学号字段
        'lis_person_sourcedid',   // 次优先级：LTI 标准 sourcedid
        'sub',                    // 最低优先级：JWT subject (不推荐)
    ];

    /**
     * 从请求中提取学号
     *
     * @param Request $request HTTP 请求对象
     * @return string 提取到的学号
     * @throws MissingStudentIdException 当所有字段都为空时抛出
     */
    public function extract(Request $request): string
    {
        // 1. 优先从 JWT claims 中提取 (LTI 1.3)
        $claims = $request->attributes->get('lti_claims', []);
        
        if (!empty($claims)) {
            $studentId = $this->extractFromClaims($claims);
            if ($studentId) {
                Log::debug('Student ID extracted from LTI claims', [
                    'field' => $this->getMatchedField($claims),
                    'student_id' => $studentId,
                ]);
                return $studentId;
            }
        }

        // 2. 从请求参数中提取 (LTI 1.1 或表单提交)
        $studentId = $this->extractFromRequest($request);
        if ($studentId) {
            Log::debug('Student ID extracted from request parameters', [
                'field' => $this->getMatchedField($request->all()),
                'student_id' => $studentId,
            ]);
            return $studentId;
        }

        // 3. 未找到学号，记录警告并抛出异常
        Log::warning('Failed to extract student ID from request', [
            'available_claims' => array_keys($claims),
            'available_params' => $request->keys(),
            'ip' => $request->ip(),
        ]);

        throw new MissingStudentIdException();
    }

    /**
     * 从 JWT Claims 中提取学号
     */
    protected function extractFromClaims(array $claims): ?string
    {
        foreach ($this->priorityFields as $field) {
            if (!empty($claims[$field])) {
                return $this->sanitizeStudentId((string) $claims[$field]);
            }
        }

        // 检查 custom 命名空间下的字段
        if (!empty($claims['https://purl.imsglobal.org/spec/lti/claim/custom'])) {
            $custom = $claims['https://purl.imsglobal.org/spec/lti/claim/custom'];
            if (!empty($custom['student_id'])) {
                return $this->sanitizeStudentId((string) $custom['student_id']);
            }
        }

        return null;
    }

    /**
     * 从请求参数中提取学号
     */
    protected function extractFromRequest(Request $request): ?string
    {
        foreach ($this->priorityFields as $field) {
            if ($request->has($field) && !empty($request->input($field))) {
                return $this->sanitizeStudentId((string) $request->input($field));
            }
        }

        return null;
    }

    /**
     * 清理学号格式
     */
    protected function sanitizeStudentId(string $studentId): string
    {
        // 移除首尾空白
        $studentId = trim($studentId);
        
        // 转换为大写 (统一格式)
        $studentId = strtoupper($studentId);
        
        // 移除非字母数字字符 (保留部分安全字符)
        $studentId = preg_replace('/[^A-Z0-9\-_]/', '', $studentId);

        return $studentId;
    }

    /**
     * 获取匹配到的字段名
     */
    protected function getMatchedField(array $data): ?string
    {
        foreach ($this->priorityFields as $field) {
            if (!empty($data[$field])) {
                return $field;
            }
        }
        return null;
    }

    /**
     * 验证学号格式
     */
    public function validate(string $studentId): bool
    {
        // 学号长度检查：4-20位
        if (strlen($studentId) < 4 || strlen($studentId) > 20) {
            return false;
        }

        // 必须以字母或数字开头
        if (!preg_match('/^[A-Z0-9]/', $studentId)) {
            return false;
        }

        return true;
    }
}

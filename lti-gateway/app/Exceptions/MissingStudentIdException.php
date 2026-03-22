<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * 缺少学号异常
 */
class MissingStudentIdException extends LtiException
{
    protected string $errorCode = 'MISSING_STUDENT_ID';

    public function __construct(
        string $message = '身份验证失败：缺少学号信息',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

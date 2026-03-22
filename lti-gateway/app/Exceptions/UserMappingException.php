<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * 用户映射异常
 */
class UserMappingException extends LtiException
{
    protected string $errorCode = 'USER_MAPPING_ERROR';

    public function __construct(
        string $message = '用户映射处理失败',
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

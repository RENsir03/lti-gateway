<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * 无效 LTI 请求异常
 */
class InvalidLtiRequestException extends LtiException
{
    protected string $errorCode = 'INVALID_LTI_REQUEST';

    public function __construct(
        string $message = '无效的 LTI 请求',
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

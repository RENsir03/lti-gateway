<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * LTI 基础异常类
 */
class LtiException extends Exception
{
    protected string $errorCode = 'LTI_ERROR';

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

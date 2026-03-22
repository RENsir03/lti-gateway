<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * 下游 API 调用异常
 */
class DownstreamApiException extends LtiException
{
    protected string $errorCode = 'DOWNSTREAM_API_ERROR';
    protected ?int $httpStatusCode;

    public function __construct(
        string $message = '服务暂时不可用，请联系管理员',
        ?int $httpStatusCode = null,
        int $code = 503,
        ?\Throwable $previous = null
    ) {
        $this->httpStatusCode = $httpStatusCode;
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}

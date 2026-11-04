<?php

namespace App\Exceptions;

use RuntimeException;

class OpenRouterException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?int $retryAfterSeconds = null,
        public readonly ?string $errorType = null,
    ) {
        parent::__construct($message);
    }

    public function isRateLimited(): bool
    {
        return $this->httpStatus === 429 || $this->errorType === 'rate_limited';
    }

    public function isTimeout(): bool
    {
        return $this->errorType === 'timeout';
    }

    public function isConfigurationError(): bool
    {
        return $this->errorType === 'configuration';
    }

    public function runStatus(): string
    {
        return match (true) {
            $this->isRateLimited() => 'rate_limited',
            $this->isTimeout() => 'timeout',
            $this->errorType === 'invalid_output' => 'malformed',
            default => 'provider_error',
        };
    }

    public function runErrorCode(): string
    {
        return $this->httpStatus !== null ? 'http_'.$this->httpStatus : ($this->errorType ?? 'provider_unavailable');
    }
}

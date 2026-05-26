<?php

namespace App\Services\Whatsapp;

use RuntimeException;
use Throwable;

class WhatsappException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $errorBody
     */
    public function __construct(
        string $message,
        public readonly ?array $errorBody = null,
        public readonly ?int $httpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function isRetryable(): bool
    {
        if ($this->httpStatus === null) {
            return true;
        }

        if ($this->httpStatus >= 500) {
            return true;
        }

        $code = $this->errorBody['error']['code'] ?? null;

        return in_array($code, [
            130_429,
            131_026,
            131_047,
            368,
        ], true);
    }
}

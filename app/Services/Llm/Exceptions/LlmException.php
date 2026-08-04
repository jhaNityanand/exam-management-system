<?php

namespace App\Services\Llm\Exceptions;

use RuntimeException;

class LlmException extends RuntimeException
{
    public static function providerUnavailable(string $provider, ?string $reason = null): self
    {
        $message = "LLM provider [{$provider}] is unavailable.";
        if ($reason) {
            $message .= ' '.$reason;
        }

        return new self($message);
    }

    public static function notConfigured(string $provider): self
    {
        return new self("LLM provider [{$provider}] is not configured. Set the API key in .env.");
    }

    public static function invalidResponse(string $provider, string $detail = ''): self
    {
        $message = "LLM provider [{$provider}] returned an invalid response.";
        if ($detail !== '') {
            $message .= ' '.$detail;
        }

        return new self($message);
    }
}

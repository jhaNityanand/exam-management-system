<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Log;
use Throwable;

class LlmLogger
{
    public function success(
        string $provider,
        string $model,
        string $operation,
        float $executionMs,
        ?array $tokens = null,
        int $retries = 0,
        array $context = [],
    ): void {
        Log::channel('llm')->info('LLM success', array_filter([
            'provider' => $provider,
            'model' => $model,
            'operation' => $operation,
            'execution_ms' => round($executionMs, 2),
            'tokens' => $tokens,
            'retries' => $retries,
            'success' => true,
            ...$context,
        ], static fn ($value) => $value !== null));
    }

    public function failure(
        string $provider,
        string $model,
        string $operation,
        float $executionMs,
        Throwable $error,
        int $retries = 0,
        array $context = [],
    ): void {
        Log::channel('llm')->error('LLM failure', [
            'provider' => $provider,
            'model' => $model,
            'operation' => $operation,
            'execution_ms' => round($executionMs, 2),
            'retries' => $retries,
            'success' => false,
            'error' => $error->getMessage(),
            'exception' => $error::class,
            ...$context,
        ]);
    }

    public function retry(string $provider, string $model, string $operation, int $attempt, Throwable $error): void
    {
        Log::channel('llm')->warning('LLM retry', [
            'provider' => $provider,
            'model' => $model,
            'operation' => $operation,
            'attempt' => $attempt,
            'error' => $error->getMessage(),
        ]);
    }
}

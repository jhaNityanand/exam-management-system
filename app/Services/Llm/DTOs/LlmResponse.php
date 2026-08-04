<?php

namespace App\Services\Llm\DTOs;

final class LlmResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $content,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
        public readonly ?int $totalTokens = null,
        public readonly ?string $model = null,
        public readonly array $raw = [],
    ) {}

    public function tokenSummary(): ?array
    {
        if ($this->promptTokens === null && $this->completionTokens === null && $this->totalTokens === null) {
            return null;
        }

        return [
            'prompt' => $this->promptTokens,
            'completion' => $this->completionTokens,
            'total' => $this->totalTokens,
        ];
    }
}

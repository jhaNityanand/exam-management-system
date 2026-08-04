<?php

namespace App\Contracts;

use App\Services\Llm\DTOs\LlmResponse;
use App\Services\Llm\DTOs\SeoContent;

interface LlmProviderInterface
{
    public function name(): string;

    public function model(): string;

    public function isConfigured(): bool;

    /**
     * Low-level chat completion.
     *
     * @param  array<string, mixed>  $options
     */
    public function chat(string $system, string $user, array $options = []): LlmResponse;

    /**
     * Generate a full SEO package for one content item.
     *
     * @param  array<string, mixed>  $content
     */
    public function generateSEO(array $content): SeoContent;

    /**
     * Improve an existing SEO package for one content item.
     *
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $existingSeo
     */
    public function improveSEO(array $content, array $existingSeo): SeoContent;

    /**
     * Generate SEO for multiple items in a single LLM request.
     *
     * @param  list<array{id: int|string, mode: string, content: array<string, mixed>, existing_seo?: array<string, mixed>}>  $items
     * @return array<string, SeoContent> keyed by string id
     */
    public function generateSEOBatch(array $items): array;

    /**
     * @param  array<string, mixed>  $content
     */
    public function generateMetaTitle(array $content): string;

    /**
     * @param  array<string, mixed>  $content
     */
    public function generateMetaDescription(array $content): string;

    /**
     * @param  array<string, mixed>  $content
     */
    public function generateKeywords(array $content): string;
}

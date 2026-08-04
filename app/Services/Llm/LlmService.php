<?php

namespace App\Services\Llm;

use App\Contracts\LlmProviderInterface;
use App\Services\Llm\DTOs\LlmResponse;
use App\Services\Llm\DTOs\SeoContent;
use App\Services\Llm\Exceptions\LlmException;
use App\Services\Llm\Providers\GeminiProvider;
use App\Services\Llm\Providers\GroqProvider;
use App\Services\Llm\Providers\OpenRouterProvider;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Application-facing LLM entry point. Controllers/jobs must use this service
 * and never instantiate providers directly.
 */
class LlmService
{
    /** @var array<string, class-string<LlmProviderInterface>> */
    protected array $driverMap = [
        'groq' => GroqProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'gemini' => GeminiProvider::class,
    ];

    protected ?LlmProviderInterface $resolved = null;

    public function __construct(
        protected LlmLogger $logger,
        protected Prompts\SeoGeneratePrompt $generatePrompt,
        protected Prompts\SeoImprovePrompt $improvePrompt,
    ) {}

    public function provider(?string $name = null): LlmProviderInterface
    {
        if ($name === null && $this->resolved) {
            return $this->resolved;
        }

        $key = strtolower($name ?: (string) config('llm.provider', 'groq'));
        $config = (array) config("llm.providers.{$key}", []);

        if ($config === [] || ! isset($this->driverMap[$key])) {
            throw LlmException::providerUnavailable($key, 'Unknown provider. Use groq, openrouter, or gemini.');
        }

        $class = $this->driverMap[$key];
        /** @var LlmProviderInterface $provider */
        $provider = new $class($config, $this->logger, $this->generatePrompt, $this->improvePrompt);

        if ($name === null) {
            $this->resolved = $provider;
        }

        return $provider;
    }

    public function isAvailable(): bool
    {
        try {
            return $this->provider()->isConfigured();
        } catch (Throwable) {
            return false;
        }
    }

    public function chat(string $system, string $user, array $options = []): LlmResponse
    {
        return $this->provider()->chat($system, $user, $options);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function generateSEO(array $content): SeoContent
    {
        return $this->provider()->generateSEO($content);
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $existingSeo
     */
    public function improveSEO(array $content, array $existingSeo): SeoContent
    {
        return $this->provider()->improveSEO($content, $existingSeo);
    }

    /**
     * @param  list<array{id: int|string, mode: string, content: array<string, mixed>, existing_seo?: array<string, mixed>}>  $items
     * @return array<string, SeoContent>
     */
    public function generateSEOBatch(array $items): array
    {
        return $this->provider()->generateSEOBatch($items);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function generateMetaTitle(array $content): string
    {
        return $this->provider()->generateMetaTitle($content);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function generateMetaDescription(array $content): string
    {
        return $this->provider()->generateMetaDescription($content);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function generateKeywords(array $content): string
    {
        return $this->provider()->generateKeywords($content);
    }

    /**
     * Soft-fail wrapper so missing/broken providers never break the UI.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  T|null  $fallback
     * @return T|null
     */
    public function safely(callable $callback, mixed $fallback = null): mixed
    {
        try {
            if (! $this->isAvailable()) {
                Log::channel('llm')->warning('LLM skipped: provider not configured', [
                    'provider' => config('llm.provider'),
                ]);

                return $fallback;
            }

            return $callback();
        } catch (Throwable $e) {
            Log::channel('llm')->error('LLM safely() swallowed exception', [
                'provider' => config('llm.provider'),
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }
}

<?php

namespace App\Services\Llm\Providers;

use App\Contracts\LlmProviderInterface;
use App\Services\Llm\DTOs\LlmResponse;
use App\Services\Llm\DTOs\SeoContent;
use App\Services\Llm\Exceptions\LlmException;
use App\Services\Llm\LlmLogger;
use App\Services\Llm\Prompts\SeoGeneratePrompt;
use App\Services\Llm\Prompts\SeoImprovePrompt;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class AbstractProvider implements LlmProviderInterface
{
    public function __construct(
        protected array $config,
        protected LlmLogger $logger,
        protected SeoGeneratePrompt $generatePrompt,
        protected SeoImprovePrompt $improvePrompt,
    ) {}

    abstract public function name(): string;

    public function model(): string
    {
        return (string) ($this->config['model'] ?? config('llm.model') ?? '');
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey()) && filled($this->model());
    }

    public function generateSEO(array $content): SeoContent
    {
        $response = $this->chat(
            $this->generatePrompt->system(),
            $this->generatePrompt->user($content),
            ['operation' => 'generateSEO']
        );

        return SeoContent::fromArray($this->decodeJsonObject($response->content));
    }

    public function improveSEO(array $content, array $existingSeo): SeoContent
    {
        $response = $this->chat(
            $this->improvePrompt->system(),
            $this->improvePrompt->user($content, $existingSeo),
            ['operation' => 'improveSEO']
        );

        return SeoContent::fromArray($this->decodeJsonObject($response->content));
    }

    public function generateSEOBatch(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $hasImprove = collect($items)->contains(fn (array $item) => ($item['mode'] ?? 'generate') === 'improve');

        if ($hasImprove && collect($items)->every(fn (array $item) => ($item['mode'] ?? '') === 'improve')) {
            $promptItems = array_map(static fn (array $item) => [
                'id' => (string) $item['id'],
                'content' => $item['content'] ?? [],
                'existing_seo' => $item['existing_seo'] ?? [],
            ], $items);

            $response = $this->chat(
                $this->improvePrompt->system(),
                $this->improvePrompt->batchUser($promptItems),
                ['operation' => 'improveSEOBatch', 'batch_size' => count($items)]
            );
        } else {
            $promptItems = array_map(static fn (array $item) => [
                'id' => (string) $item['id'],
                'mode' => $item['mode'] ?? 'generate',
                'content' => $item['content'] ?? [],
                'existing_seo' => $item['existing_seo'] ?? [],
            ], $items);

            $system = $this->generatePrompt->system()."\nWhen mode is \"improve\", refine existing_seo instead of inventing from scratch.";
            $response = $this->chat(
                $system,
                $this->generatePrompt->batchUser($promptItems),
                ['operation' => 'generateSEOBatch', 'batch_size' => count($items)]
            );
        }

        $decoded = $this->decodeJsonObject($response->content);
        $result = [];

        foreach ($decoded as $id => $seo) {
            if (! is_array($seo)) {
                continue;
            }
            $result[(string) $id] = SeoContent::fromArray($seo);
        }

        return $result;
    }

    public function generateMetaTitle(array $content): string
    {
        return (string) ($this->generateSEO($content)->metaTitle ?? '');
    }

    public function generateMetaDescription(array $content): string
    {
        return (string) ($this->generateSEO($content)->metaDescription ?? '');
    }

    public function generateKeywords(array $content): string
    {
        return (string) ($this->generateSEO($content)->keywords ?? '');
    }

    public function chat(string $system, string $user, array $options = []): LlmResponse
    {
        if (! $this->isConfigured()) {
            throw LlmException::notConfigured($this->name());
        }

        $operation = (string) ($options['operation'] ?? 'chat');
        $retries = max(0, (int) config('llm.retry', 2));
        $attempt = 0;
        $started = microtime(true);
        $lastError = null;

        while ($attempt <= $retries) {
            try {
                $response = $this->sendChat($system, $user, $options);
                $this->logger->success(
                    $this->name(),
                    $response->model ?? $this->model(),
                    $operation,
                    (microtime(true) - $started) * 1000,
                    $response->tokenSummary(),
                    $attempt,
                    array_filter([
                        'batch_size' => $options['batch_size'] ?? null,
                    ])
                );

                return $response;
            } catch (Throwable $e) {
                $lastError = $e;
                if ($attempt < $retries) {
                    $this->logger->retry($this->name(), $this->model(), $operation, $attempt + 1, $e);
                    usleep(250000 * ($attempt + 1));
                }
                $attempt++;
            }
        }

        $this->logger->failure(
            $this->name(),
            $this->model(),
            $operation,
            (microtime(true) - $started) * 1000,
            $lastError ?? new LlmException('Unknown LLM failure.'),
            $retries,
        );

        throw LlmException::providerUnavailable(
            $this->name(),
            $lastError?->getMessage()
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    abstract protected function sendChat(string $system, string $user, array $options = []): LlmResponse;

    protected function apiKey(): string
    {
        return (string) ($this->config['api_key'] ?? '');
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    protected function organizationId(): string
    {
        return (string) ($this->config['organization_id'] ?? '');
    }

    protected function http(): PendingRequest
    {
        $request = Http::timeout((int) config('llm.timeout', 60))
            ->acceptJson()
            ->asJson();

        if (config('app.env') === 'local' || (bool) env('LLM_HTTP_VERIFY', true) === false) {
            $request->withoutVerifying();
        }

        return $request;
    }

    protected function temperature(): float
    {
        return (float) config('llm.temperature', 0.4);
    }

    protected function maxTokens(): int
    {
        return (int) config('llm.max_tokens', 4096);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonObject(string $content): array
    {
        $trimmed = trim($content);
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
            $trimmed = trim($trimmed);
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            throw LlmException::invalidResponse($this->name(), 'Expected a JSON object.');
        }

        return $decoded;
    }
}

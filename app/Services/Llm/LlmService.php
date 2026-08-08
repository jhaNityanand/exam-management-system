<?php

namespace App\Services\Llm;

use App\Contracts\LlmProviderInterface;
use App\Models\LlmAccount;
use App\Models\LlmErrorLog;
use App\Services\Llm\DTOs\LlmResponse;
use App\Services\Llm\DTOs\SeoContent;
use App\Services\Llm\Exceptions\LlmException;
use App\Services\Llm\Providers\GeminiProvider;
use App\Services\Llm\Providers\GroqProvider;
use App\Services\Llm\Providers\MistralProvider;
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
        'mistral' => MistralProvider::class,
        'groq' => GroqProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'gemini' => GeminiProvider::class,
    ];

    /** @var LlmAccount|null */
    protected ?LlmAccount $lastUsedAccount = null;

    public function __construct(
        protected LlmLogger $logger,
        protected LlmAccountResolver $resolver,
        protected Prompts\SeoGeneratePrompt $generatePrompt,
        protected Prompts\SeoImprovePrompt $improvePrompt,
    ) {}

    public function getLastUsedAccount(): ?LlmAccount
    {
        return $this->lastUsedAccount;
    }

    public function isAvailable(): bool
    {
        return $this->resolver->hasAvailableAccount();
    }

    /**
     * Instantiate concrete provider for a specific LlmAccount or config array.
     */
    public function makeProvider(LlmAccount|array $accountOrConfig): LlmProviderInterface
    {
        if ($accountOrConfig instanceof LlmAccount) {
            $providerKey = strtolower($accountOrConfig->provider);
            $config = [
                'driver' => $providerKey,
                'api_key' => $accountOrConfig->api_key,
                'model' => $accountOrConfig->model,
                'base_url' => $accountOrConfig->base_url,
                'organization_id' => $accountOrConfig->organization_id,
                'account_id' => $accountOrConfig->id,
                'account_name' => $accountOrConfig->account_name,
            ];
        } else {
            $providerKey = strtolower((string) ($accountOrConfig['driver'] ?? $accountOrConfig['provider'] ?? 'groq'));
            $config = $accountOrConfig;
        }

        if (! isset($this->driverMap[$providerKey])) {
            throw LlmException::providerUnavailable($providerKey, "Unknown provider driver [{$providerKey}].");
        }

        $class = $this->driverMap[$providerKey];

        return new $class($config, $this->logger, $this->generatePrompt, $this->improvePrompt);
    }

    /**
     * Core execution pipeline with multi-account automatic failover.
     *
     * @template T
     * @param callable(LlmProviderInterface): T $callback
     * @return T
     */
    protected function executeWithFailover(callable $callback, string $operation = 'chat'): mixed
    {
        $accounts = $this->resolver->getAvailableAccounts();

        // Fallback to config if no DB accounts exist
        if ($accounts->isEmpty() && LlmAccount::query()->count() === 0) {
            $defaultKey = strtolower((string) config('llm.provider', 'groq'));
            $defaultConfig = (array) config("llm.providers.{$defaultKey}", []);
            if ($defaultConfig !== [] && filled($defaultConfig['api_key'] ?? config('llm.api_key'))) {
                $provider = $this->makeProvider($defaultConfig);
                return $callback($provider);
            }
        }

        if ($accounts->isEmpty()) {
            throw LlmException::providerUnavailable('llm', 'All configured LLM accounts are currently disabled, in cooldown, or limit reached.');
        }

        $lastException = null;

        foreach ($accounts as $account) {
            try {
                $provider = $this->makeProvider($account);
                $result = $callback($provider);

                // On success, update account usage statistics
                $tokensUsed = 0;
                if ($result instanceof LlmResponse) {
                    $tokensUsed = (int) ($result->totalTokens ?? 0);
                }

                $isNewDay = $account->last_used_at === null || $account->last_used_at->isBefore(now()->startOfDay());
                $currentRequests = $isNewDay ? 0 : $account->requests_today;
                $currentTokens = $isNewDay ? 0 : $account->tokens_today;

                $account->update([
                    'last_used_at' => now(),
                    'requests_today' => $currentRequests + 1,
                    'tokens_today' => $currentTokens + $tokensUsed,
                    'error_count' => 0,
                ]);

                $this->lastUsedAccount = $account;

                return $result;
            } catch (Throwable $e) {
                $lastException = $e;

                $errMsg = $e->getMessage();
                $isRateOrQuota = preg_match('/429|rate limit|quota|exceeded|too many requests/i', $errMsg) === 1;
                $newErrorCount = $account->error_count + 1;

                $cooldownUntil = null;
                if ($isRateOrQuota || $newErrorCount >= 3) {
                    $cooldownUntil = now()->addHours(24);
                }

                $account->update([
                    'last_error_message' => substr($errMsg, 0, 1000),
                    'error_count' => $newErrorCount,
                    'cooldown_until' => $cooldownUntil ?? $account->cooldown_until,
                ]);

                // Record detailed LLM error log
                LlmErrorLog::create([
                    'provider' => $account->provider,
                    'account_id' => $account->id,
                    'account_name' => $account->account_name,
                    'model' => $account->model,
                    'request_type' => $operation,
                    'error_message' => substr($errMsg, 0, 2000),
                    'failed_at' => now(),
                ]);

                Log::channel('llm')->warning("LLM account failed, attempting failover to next provider/account", [
                    'failed_account' => $account->account_name,
                    'provider' => $account->provider,
                    'error' => $errMsg,
                    'cooldown_applied' => $cooldownUntil ? $cooldownUntil->toIso8601String() : false,
                ]);
            }
        }

        throw LlmException::providerUnavailable(
            'llm',
            'All configured LLM accounts failed. Last error: '.($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    public function chat(string $system, string $user, array $options = []): LlmResponse
    {
        return $this->executeWithFailover(
            fn (LlmProviderInterface $provider) => $provider->chat($system, $user, $options),
            $options['operation'] ?? 'chat'
        );
    }

    /**
     * @param array<string, mixed> $content
     */
    public function generateSEO(array $content): SeoContent
    {
        return $this->executeWithFailover(
            fn (LlmProviderInterface $provider) => $provider->generateSEO($content),
            'generateSEO'
        );
    }

    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $existingSeo
     */
    public function improveSEO(array $content, array $existingSeo): SeoContent
    {
        return $this->executeWithFailover(
            fn (LlmProviderInterface $provider) => $provider->improveSEO($content, $existingSeo),
            'improveSEO'
        );
    }

    /**
     * @param list<array{id: int|string, mode: string, content: array<string, mixed>, existing_seo?: array<string, mixed>}> $items
     * @return array<string, SeoContent>
     */
    public function generateSEOBatch(array $items): array
    {
        return $this->executeWithFailover(
            fn (LlmProviderInterface $provider) => $provider->generateSEOBatch($items),
            'generateSEOBatch'
        );
    }

    /**
     * @param array<string, mixed> $content
     */
    public function generateMetaTitle(array $content): string
    {
        return $this->executeWithFailover(
            fn (LlmProviderInterface $provider) => $provider->generateMetaTitle($content),
            'generateMetaTitle'
        );
    }

    /**
     * @param array<string, mixed> $content
     */
    public function generateMetaDescription(array $content): string
    {
        return $this->executeWithFailover(
            fn (LlmProviderInterface $provider) => $provider->generateMetaDescription($content),
            'generateMetaDescription'
        );
    }

    /**
     * @param array<string, mixed> $content
     */
    public function generateKeywords(array $content): string
    {
        return $this->executeWithFailover(
            fn (LlmProviderInterface $provider) => $provider->generateKeywords($content),
            'generateKeywords'
        );
    }

    /**
     * Soft-fail wrapper so missing/broken providers never break the UI.
     *
     * @template T
     * @param callable(): T $callback
     * @param T|null $fallback
     * @return T|null
     */
    public function safely(callable $callback, mixed $fallback = null): mixed
    {
        try {
            if (! $this->isAvailable()) {
                Log::channel('llm')->warning('LLM safely() skipped: no available provider account.');
                return $fallback;
            }

            return $callback();
        } catch (Throwable $e) {
            Log::channel('llm')->error('LLM safely() swallowed exception', [
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }
}

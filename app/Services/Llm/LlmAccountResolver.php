<?php

namespace App\Services\Llm;

use App\Models\LlmAccount;
use Illuminate\Support\Collection;

class LlmAccountResolver
{
    /**
     * Priority order of providers.
     */
    public const PROVIDER_ORDER = ['mistral', 'groq', 'gemini', 'openrouter'];

    /**
     * Get all eligible active accounts from database in priority order.
     *
     * @return Collection<int, LlmAccount>
     */
    public function getAvailableAccounts(): Collection
    {
        return LlmAccount::query()
            ->available()
            ->orderedByProviderPriority()
            ->get();
    }

    /**
     * Get all accounts regardless of status for admin UI/analytics.
     *
     * @return Collection<int, LlmAccount>
     */
    public function getAllAccounts(): Collection
    {
        return LlmAccount::query()
            ->orderedByProviderPriority()
            ->get();
    }

    /**
     * Check if at least one provider account (DB or fallback config) is ready for use.
     */
    public function hasAvailableAccount(): bool
    {
        if ($this->getAvailableAccounts()->isNotEmpty()) {
            return true;
        }

        // Fallback check to .env if DB is completely empty
        if (LlmAccount::query()->count() === 0) {
            $defaultKey = (string) config('llm.api_key');
            $defaultProvider = (string) config('llm.provider');

            return filled($defaultKey) || filled(config("llm.providers.{$defaultProvider}.api_key"));
        }

        return false;
    }
}

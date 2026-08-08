<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LlmAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'account_name',
        'api_key',
        'model',
        'base_url',
        'organization_id',
        'is_active',
        'priority',
        'daily_request_limit',
        'daily_token_limit',
        'requests_today',
        'tokens_today',
        'last_used_at',
        'last_error_message',
        'error_count',
        'cooldown_until',
        'notes',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $appends = [
        'masked_api_key',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'daily_request_limit' => 'integer',
        'daily_token_limit' => 'integer',
        'requests_today' => 'integer',
        'tokens_today' => 'integer',
        'error_count' => 'integer',
        'last_used_at' => 'datetime',
        'cooldown_until' => 'datetime',
    ];

    public function errorLogs(): HasMany
    {
        return $this->hasMany(LlmErrorLog::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
            ->where(function (Builder $q) {
                $q->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('daily_request_limit')
                    ->orWhereNull('last_used_at')
                    ->orWhere('last_used_at', '<', now()->startOfDay())
                    ->orWhereColumn('requests_today', '<', 'daily_request_limit');
            })
            ->where(function (Builder $q) {
                $q->whereNull('daily_token_limit')
                    ->orWhereNull('last_used_at')
                    ->orWhere('last_used_at', '<', now()->startOfDay())
                    ->orWhereColumn('tokens_today', '<', 'daily_token_limit');
            });
    }

    public function scopeOrderedByProviderPriority(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE provider
                WHEN 'mistral' THEN 1
                WHEN 'groq' THEN 2
                WHEN 'gemini' THEN 3
                WHEN 'openrouter' THEN 4
                ELSE 5
            END ASC
        ")->orderBy('priority', 'asc')->orderBy('id', 'asc');
    }

    public function isInCooldown(): bool
    {
        return $this->cooldown_until !== null && $this->cooldown_until->isFuture();
    }

    public function isLimitReached(): bool
    {
        if ($this->last_used_at !== null && $this->last_used_at->isBefore(now()->startOfDay())) {
            return false;
        }

        if ($this->daily_request_limit !== null && $this->requests_today >= $this->daily_request_limit) {
            return true;
        }

        if ($this->daily_token_limit !== null && $this->tokens_today >= $this->daily_token_limit) {
            return true;
        }

        return false;
    }

    public function statusBadge(): string
    {
        if (! $this->is_active) {
            return 'disabled';
        }
        if ($this->isInCooldown()) {
            return 'cooldown';
        }
        if ($this->isLimitReached()) {
            return 'paused';
        }
        if ($this->error_count >= 3 && ($this->last_used_at === null || $this->updated_at->diffInMinutes(now()) < 60)) {
            return 'error';
        }

        return 'active';
    }

    public function getMaskedApiKeyAttribute(): string
    {
        $key = (string) $this->api_key;
        if (strlen($key) <= 8) {
            return '••••••••';
        }

        return substr($key, 0, 4).'••••••••'.substr($key, -4);
    }
}

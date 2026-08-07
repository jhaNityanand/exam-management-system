<?php

use App\Models\LlmAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gallery:prune-orphans')->daily();

Schedule::command('seo:generate')->dailyAt('02:15');

if (filter_var(env('LLM_SEO_SCHEDULE_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
    $every = max(1, (int) env('LLM_SEO_EVERY_MINUTES', 5));
    Schedule::command('llm:process-seo')->cron("*/{$every} * * * *")->withoutOverlapping();
}

/**
 * LLM Management Scheduler:
 * 1. Automatically reactivate accounts when 24h cooldown expires.
 * 2. Reset daily request and token counters at midnight.
 */
Schedule::call(function () {
    LlmAccount::query()
        ->whereNotNull('cooldown_until')
        ->where('cooldown_until', '<=', now())
        ->update([
            'cooldown_until' => null,
            'error_count' => 0,
        ]);
})->everyFiveMinutes();

Schedule::call(function () {
    LlmAccount::query()->update([
        'requests_today' => 0,
        'tokens_today' => 0,
    ]);
})->dailyAt('00:00');

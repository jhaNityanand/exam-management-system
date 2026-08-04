<?php

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

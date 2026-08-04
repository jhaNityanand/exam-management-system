<?php

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Cms\SitePage;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;

return [

    /*
    |--------------------------------------------------------------------------
    | Active LLM Provider
    |--------------------------------------------------------------------------
    |
    | Switch providers by changing LLM_PROVIDER only (groq|openrouter|gemini).
    | No application code changes are required.
    |
    */

    'provider' => env('LLM_PROVIDER', 'groq'),

    /*
    |--------------------------------------------------------------------------
    | Fallback / shared defaults
    |--------------------------------------------------------------------------
    |
    | LLM_API_KEY and LLM_MODEL apply when the active provider-specific
    | variables are empty. Prefer provider-specific keys in production.
    |
    */

    'api_key' => env('LLM_API_KEY'),
    'model' => env('LLM_MODEL'),

    'timeout' => (int) env('LLM_TIMEOUT', 60),
    'retry' => (int) env('LLM_RETRY', 2),
    'queue_size' => (int) env('LLM_QUEUE_SIZE', 50),
    'batch_size' => (int) env('LLM_BATCH_SIZE', 6),
    'temperature' => (float) env('LLM_TEMPERATURE', 0.4),
    'max_tokens' => (int) env('LLM_MAX_TOKENS', 4096),

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    */

    'schedule' => [
        'enabled' => filter_var(env('LLM_SEO_SCHEDULE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'every_minutes' => (int) env('LLM_SEO_EVERY_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        'groq' => [
            'driver' => 'groq',
            'api_key' => env('GROQ_API_KEY', env('LLM_API_KEY')),
            'model' => env('GROQ_MODEL', env('LLM_MODEL', 'llama-3.3-70b-versatile')),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'api_key' => env('OPENROUTER_API_KEY', env('LLM_API_KEY')),
            'model' => env('OPENROUTER_MODEL', env('LLM_MODEL', 'openai/gpt-4o-mini')),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'site_url' => env('OPENROUTER_SITE_URL', env('APP_URL')),
            'site_name' => env('OPENROUTER_SITE_NAME', env('APP_NAME', 'Exam Management System')),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'api_key' => env('GEMINI_API_KEY', env('LLM_API_KEY')),
            'model' => env('GEMINI_MODEL', env('LLM_MODEL', 'gemini-2.0-flash')),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | SEO content types processed by the batch scheduler
    |--------------------------------------------------------------------------
    */

    'seo_types' => [
        'question' => Question::class,
        'exam' => Exam::class,
        'blog' => Blog::class,
        'news' => News::class,
        'question_category' => QuestionCategory::class,
        'exam_category' => ExamCategory::class,
        'blog_category' => BlogCategory::class,
        'news_category' => NewsCategory::class,
        'organization' => Organization::class,
        'site_page' => SitePage::class,
    ],

];

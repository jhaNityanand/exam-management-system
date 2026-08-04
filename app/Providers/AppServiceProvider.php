<?php

namespace App\Providers;

use App\Models\Organization;
use App\Services\Llm\LlmService;
use App\Services\Llm\SeoBatchProcessor;
use App\Services\Settings\EmailConfigurationService;
use App\Services\Settings\IntegrationsSettingsService;
use App\View\Composers\FrontendLayoutComposer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LlmService::class);
        $this->app->singleton(SeoBatchProcessor::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->applyOrganizationEmailConfig();

        View::composer([
            'backend.layouts.base',
            'backend.layouts.app',
            'layouts.guest',
        ], function ($view) {
            $user = auth()->user();
            if (! $user) {
                $view->with('userThemeSetting', 'system');

                return;
            }

            $user->loadMissing(['appSettings']);

            /*
             * SINGLE-ORG MODE:
             *   Always resolve the one organization directly — no session lookup,
             *   no per-user org list needed.
             *
             * MULTI-ORG MODE (future):
             *   Uncomment the lines below and remove the single-org block.
             *
             *   $user->loadMissing(['organizations', 'appSettings']);
             *   $navOrganizations = $user->organizations()->orderBy('organizations.name')->get();
             *   $currentOrgModel  = Organization::find(session(config('organization.session_key')));
             */

            // ── SINGLE-ORG MODE ───────────────────────────────────────────────
            $singleOrg = Organization::first();
            $navOrganizations = $singleOrg ? collect([$singleOrg]) : collect();
            $currentOrgModel = $singleOrg;
            // ─────────────────────────────────────────────────────────────────

            $view->with([
                'navOrganizations' => $navOrganizations,
                'currentOrgModel' => $currentOrgModel,
                'userThemeSetting' => $user->appSettings?->theme ?? 'system',
                'sidebarCollapsedSetting' => (bool) ($user->appSettings?->sidebar_collapsed ?? false),
            ]);
        });

        View::composer(
            ['frontend.*', 'errors.*'],
            FrontendLayoutComposer::class
        );
    }

    /**
     * Apply org-scoped SMTP / From settings over env defaults when available.
     */
    protected function applyOrganizationEmailConfig(): void
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                return;
            }

            app(EmailConfigurationService::class)->applyToConfig();
            $this->applyIntegrationsRuntime();
        } catch (\Throwable) {
            // Ignore during early install / migrate before tables exist.
        }
    }

    protected function applyIntegrationsRuntime(): void
    {
        $integrations = app(IntegrationsSettingsService::class)->get();
        $timezone = trim((string) ($integrations['default_timezone'] ?? ''));
        $locale = trim((string) ($integrations['default_locale'] ?? ''));

        if ($timezone !== '') {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        if ($locale !== '') {
            app()->setLocale($locale);
        }
    }
}

<?php

namespace App\Providers;

use App\Models\Organization;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        View::composer([
            'backend.layouts.base',
            'backend.layouts.app',
            'layouts.app',
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
            $singleOrg       = Organization::first();
            $navOrganizations = $singleOrg ? collect([$singleOrg]) : collect();
            $currentOrgModel  = $singleOrg;
            // ─────────────────────────────────────────────────────────────────

            $view->with([
                'navOrganizations'        => $navOrganizations,
                'currentOrgModel'         => $currentOrgModel,
                'userThemeSetting'        => $user->appSettings?->theme ?? 'system',
                'sidebarCollapsedSetting' => (bool) ($user->appSettings?->sidebar_collapsed ?? false),
            ]);
        });

        View::composer(
            ['frontend.*'],
            \App\View\Composers\FrontendLayoutComposer::class
        );
    }
}

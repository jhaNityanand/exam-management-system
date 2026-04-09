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

        View::composer(['layouts.app', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            if (! $user) {
                return;
            }
            $user->loadMissing(['organizations', 'appSettings']);
            $view->with([
                'navOrganizations'      => $user->organizations()->orderBy('organizations.name')->get(),
                'currentOrgModel'       => Organization::find(session(config('organization.session_key'))),
                'userThemeSetting'      => $user->appSettings?->theme ?? 'system',
                'sidebarCollapsedSetting' => (bool) ($user->appSettings?->sidebar_collapsed ?? false),
            ]);
        });
    }
}

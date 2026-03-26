<?php

namespace App\Providers;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(OrganizationContext::class, fn () => new OrganizationContext(null));
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Blade::if('orgCan', function (string $permission): bool {
            $user = auth()->user();
            if (! $user) {
                return false;
            }

            return $user->canInCurrentOrg($permission);
        });

        View::composer(['layouts.app', 'layouts.org-admin', 'layouts.editor', 'layouts.viewer', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            if (! $user) {
                return;
            }
            $user->loadMissing(['organizations', 'appSettings']);
            $view->with([
                'navOrganizations' => $user->organizations()->orderBy('organizations.name')->get(),
                'currentOrgModel' => Organization::find(current_organization_id()),
                'userThemeSetting' => $user->appSettings?->theme ?? 'system',
                'sidebarCollapsedSetting' => (bool) ($user->appSettings?->sidebar_collapsed ?? false),
            ]);
        });
    }
}

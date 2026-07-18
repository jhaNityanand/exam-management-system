<?php

use App\Models\Organization;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Support\Facades\Auth;

if (! function_exists('current_organization_id')) {
    /**
     * Return the active organization ID for the current context.
     *
     * Resolution order:
     *   1. If a user is authenticated, look up their first active record in
     *      user_organizations and return that organization_id.
     *   2. Fallback (guests / CLI only): first organization in the table.
     *      Authenticated users without membership return null (no silent tenant leak).
     *
     * @return int|null  null when no organization exists / membership missing.
     */
    function current_organization_id(): ?int
    {
        if (Auth::check()) {
            $orgId = UserOrganization::where('user_id', Auth::id())
                ->where('status', 'active')
                ->value('organization_id');

            return $orgId ? (int) $orgId : null;
        }

        static $cachedId = null;

        if ($cachedId === null) {
            $cachedId = Organization::value('id');
        }

        return $cachedId ? (int) $cachedId : null;
    }
}

if (! function_exists('current_organization_role')) {
    function current_organization_role(): ?string
    {
        if (! Auth::check()) {
            return null;
        }

        return UserOrganization::where('user_id', Auth::id())
            ->where('status', 'active')
            ->value('role');
    }
}

if (! function_exists('user_can_access_admin')) {
    function user_can_access_admin(): bool
    {
        return OrganizationRoles::canAccessAdminPanel(current_organization_role());
    }
}

if (! function_exists('site_setting')) {
    /**
     * Read a CMS site setting (group.key), e.g. site_setting('brand.site_name').
     */
    function site_setting(string $key, mixed $default = null): mixed
    {
        return app(\App\Services\Frontend\SiteCmsService::class)->setting($key, $default);
    }
}

if (! function_exists('versioned_asset')) {
    /**
     * Generate an asset URL with a filemtime-based cache-buster.
     */
    function versioned_asset(string $path): string
    {
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        $url = asset($relative);
        $fullPath = public_path($relative);

        if (is_file($fullPath)) {
            return $url.'?v='.filemtime($fullPath);
        }

        return $url;
    }
}

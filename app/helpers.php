<?php

use App\Models\Organization;
use App\Models\UserOrganization;
use Illuminate\Support\Facades\Auth;

if (! function_exists('current_organization_id')) {
    /**
     * Return the active organization ID for the current context.
     *
     * Resolution order:
     *   1. If a user is authenticated, look up their first active record in
     *      user_organizations and return that organization_id.
     *   2. Fallback: return the first organization in the organizations table.
     *      Used for CLI (seeders / artisan commands) and unauthenticated contexts.
     *
     * MULTI-ORG MODE (future):
     *   Replace step 1 with a session-based lookup:
     *     $id = session(config('organization.session_key'));
     *     Validate the user belongs to that org before returning it.
     *
     * @return int|null  null when no organization exists at all.
     */
    function current_organization_id(): ?int
    {
        // ── Step 1: Authenticated user → user_organizations ───────────────────
        if (Auth::check()) {
            $orgId = UserOrganization::where('user_id', Auth::id())
                ->where('status', 'active')
                ->value('organization_id');

            if ($orgId) {
                return (int) $orgId;
            }
        }

        // ── Step 2: Fallback — first org (seeders / CLI / guests) ─────────────
        static $cachedId = null;

        if ($cachedId === null) {
            $cachedId = Organization::value('id'); // first org in table, or null
        }

        return $cachedId ? (int) $cachedId : null;
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

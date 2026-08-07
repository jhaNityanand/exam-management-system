<?php

use App\Models\Organization;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

if (! function_exists('current_organization_id')) {
    /**
     * Return the active organization ID for the current context.
     *
     * Resolution order:
     *   1. Session `current_organization_id` when the user has an active membership there.
     *   2. Highest-privilege active membership (admin → org_admin → other).
     *   3. Fallback (guests / CLI only): first organization in the table.
     *      Authenticated users without membership return null (no silent tenant leak).
     *
     * @return int|null  null when no organization exists / membership missing.
     */
    function current_organization_id(): ?int
    {
        if (Auth::check()) {
            $userId = (int) Auth::id();
            $sessionOrgId = (int) (session('current_organization_id') ?: 0);

            if ($sessionOrgId > 0) {
                $inSession = UserOrganization::query()
                    ->where('user_id', $userId)
                    ->where('organization_id', $sessionOrgId)
                    ->where('status', 'active')
                    ->exists();

                if ($inSession) {
                    return $sessionOrgId;
                }
            }

            $membership = UserOrganization::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'org_admin' THEN 1 ELSE 2 END")
                ->orderBy('id')
                ->first();

            return $membership?->organization_id ? (int) $membership->organization_id : null;
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

        $orgId = current_organization_id();
        if (! $orgId) {
            return null;
        }

        return UserOrganization::query()
            ->where('user_id', Auth::id())
            ->where('organization_id', $orgId)
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

if (! function_exists('admin_can')) {
    /**
     * Whether the authenticated user has an admin-panel capability
     * (content | organization | platform).
     */
    function admin_can(string $capability): bool
    {
        return \App\Support\AdminCapabilities::userCan(Auth::user(), $capability);
    }
}

if (! function_exists('safe_intended_url')) {
    /**
     * Accept only same-origin absolute URLs or relative app paths for post-auth redirects.
     */
    function safe_intended_url(mixed $redirect): ?string
    {
        if (! is_string($redirect)) {
            return null;
        }

        $redirect = trim($redirect);
        if ($redirect === '' || str_starts_with($redirect, '//')) {
            return null;
        }

        if (str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return $redirect;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '') {
            return null;
        }

        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $redirectHost = parse_url($redirect, PHP_URL_HOST);
        $redirectScheme = parse_url($redirect, PHP_URL_SCHEME);

        if (! in_array($redirectScheme, ['http', 'https'], true) || ! $appHost || ! $redirectHost) {
            return null;
        }

        if (strcasecmp((string) $appHost, (string) $redirectHost) !== 0) {
            return null;
        }

        return $redirect;
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

if (! function_exists('user_avatar')) {
    /**
     * @return array{url:?string, initials:string, color:string, name:string}
     */
    function user_avatar(?\App\Models\User $user, ?string $nameFallback = null): array
    {
        return \App\Support\UserAvatar::resolve($user, $nameFallback);
    }
}

if (! function_exists('user_initials')) {
    /**
     * Initials from a display name (e.g. "Jane Doe" → "JD").
     */
    function user_initials(?string $name, string $fallback = 'U'): string
    {
        return \App\Support\UserAvatar::initials($name, $fallback);
    }
}

if (! function_exists('display_value')) {
    /**
     * Admin detail display helper — always show a value, or a clear empty label.
     */
    function display_value(mixed $value, string $empty = 'Not Set'): string
    {
        if ($value instanceof \Illuminate\Support\Carbon) {
            return $value->format('M j, Y g:i A');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $value = collect($value)
                ->filter(fn ($item) => $item !== null && $item !== '')
                ->map(fn ($item) => is_scalar($item) ? (string) $item : '')
                ->filter()
                ->implode(', ');
        }

        $string = trim((string) ($value ?? ''));

        return $string !== '' ? $string : $empty;
    }
}

if (! function_exists('build_article_toc')) {
    /**
     * Inject heading IDs and build a table-of-contents list from article HTML.
     *
     * @return array{items: list<array{level:int, text:string, id:string, number:string}>, html: string}
     */
    function build_article_toc(?string $html): array
    {
        $html = (string) $html;
        $items = [];

        if ($html === '') {
            return ['items' => $items, 'html' => $html];
        }

        $index = 0;
        $major = 0;
        $minor = 0;

        $html = preg_replace_callback(
            '/<h([23])([^>]*)>(.*?)<\/h\1>/is',
            function (array $match) use (&$items, &$index, &$major, &$minor): string {
                $index++;
                $level = (int) $match[1];
                $attrs = (string) ($match[2] ?? '');
                $inner = (string) ($match[3] ?? '');
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if (preg_match('/\bid\s*=\s*([\'"])(.*?)\1/i', $attrs, $idMatch)) {
                    $id = trim((string) $idMatch[2]);
                } else {
                    $slug = \Illuminate\Support\Str::slug(\Illuminate\Support\Str::limit($text !== '' ? $text : 'section', 48, ''));
                    $id = 'section-'.$index.($slug !== '' ? '-'.$slug : '');
                    $attrs = rtrim($attrs).' id="'.e($id).'"';
                }

                if ($text !== '' && $id !== '') {
                    if ($level === 2) {
                        $major++;
                        $minor = 0;
                        $number = (string) $major;
                    } else {
                        if ($major === 0) {
                            $major = 1;
                        }
                        $minor++;
                        $number = $major.'.'.$minor;
                    }

                    $items[] = [
                        'level' => $level,
                        'text' => $text,
                        'id' => $id,
                        'number' => $number,
                    ];
                }

                return '<h'.$level.$attrs.'>'.$inner.'</h'.$level.'>';
            },
            $html
        ) ?? $html;

        return ['items' => $items, 'html' => $html];
    }
}

if (! function_exists('author_role')) {
    /**
     * Resolve the public author role for a user in the current org context.
     *
     * @return array{key:string, label:string, short:string}
     */
    function author_role(?\App\Models\User $user, ?string $fallback = null): array
    {
        $key = $fallback;
        if ($user && blank($key)) {
            $key = $user->getAttribute('public_role');
        }
        if ($user && blank($key)) {
            $orgId = current_organization_id();
            $membership = $user->organizations()
                ->wherePivot('status', 'active')
                ->when($orgId, fn ($q) => $q->where('organizations.id', $orgId))
                ->wherePivotIn('role', \App\Support\OrganizationRoles::adminPanelRoles())
                ->get()
                ->sortBy(fn ($org) => array_search($org->pivot->role, \App\Support\OrganizationRoles::adminPanelRoles(), true))
                ->first();
            $key = $membership?->pivot?->role;
        }

        $key = (string) ($key ?: 'editor');

        return [
            'key' => $key,
            'label' => \App\Support\OrganizationRoles::label($key),
            'short' => \App\Support\OrganizationRoles::shortLabel($key),
        ];
    }
}

if (! function_exists('ad_slot')) {
    /**
     * Render active advertisements for a page/position slot.
     *
     * Preferred: ad_slot('home', 'after_hero')
     * Legacy:    ad_slot('blog_detail_above_h1')
     */
    function ad_slot(string $pageOrLegacy, ?string $positionKey = null): string
    {
        return app(\App\Services\Advertisement\AdvertisementService::class)
            ->renderSlot($pageOrLegacy, $positionKey);
    }
}

if (! function_exists('ad_custom_code')) {
    /**
     * @return array{header_code: string, footer_code: string}
     */
    function ad_custom_code(): array
    {
        return app(\App\Services\Advertisement\AdvertisementService::class)->frontendCustomCode();
    }
}

if (! function_exists('ads_preview_mode')) {
    /**
     * Local/staging visual markers for ad slots (not on examtube.in production host).
     */
    function ads_preview_mode(): bool
    {
        $host = strtolower((string) request()->getHost());
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return $host !== 'examtube.in';
    }
}

if (! function_exists('frontend_ad_page_key')) {
    /**
     * Resolve the advertisement catalog page key for the current frontend request.
     */
    function frontend_ad_page_key(?string $override = null): ?string
    {
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $fromView = View::shared('frontendAdPageKey');
        if (is_string($fromView) && $fromView !== '') {
            return $fromView;
        }

        return \App\Support\AdvertisementCatalog::pageKeyFromRoute(
            optional(request()->route())->getName()
        );
    }
}

if (! function_exists('seo_default_image')) {
    /**
     * Absolute URL for a typed default social/meta image.
     */
    function seo_default_image(string $type = 'home'): string
    {
        return \App\Support\SeoImage::defaultUrl($type);
    }
}

if (! function_exists('seo_image')) {
    /**
     * Prefer an uploaded image URL; fall back to a branded default by type.
     */
    function seo_image(?string $uploadedUrl, string $type = 'home'): string
    {
        return \App\Support\SeoImage::resolve($uploadedUrl, $type);
    }
}

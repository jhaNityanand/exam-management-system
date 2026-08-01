<?php

namespace App\Services\Settings;

use App\Models\Cms\HeroBanner;
use App\Models\Cms\SiteSetting;
use App\Models\Cms\SocialLink;
use App\Models\Gallery;
use App\Models\Organization;
use App\Services\Frontend\SiteCmsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrganizationSettingsService
{
    /**
     * Canonical social platforms for organization settings.
     *
     * @var array<string, string>
     */
    public const SOCIAL_PLATFORMS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'x' => 'X (Twitter)',
        'youtube' => 'YouTube',
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
        'discord' => 'Discord',
        'github' => 'GitHub',
        'pinterest' => 'Pinterest',
        'reddit' => 'Reddit',
        'threads' => 'Threads',
        'tiktok' => 'TikTok',
        'medium' => 'Medium',
        'quora' => 'Quora',
        'bluesky' => 'Bluesky',
    ];

    /**
     * Placeholders and helper copy for the Social media settings UI.
     *
     * @var array<string, array{placeholder: string, hint: string}>
     */
    public const SOCIAL_PLATFORM_META = [
        'facebook' => [
            'placeholder' => 'https://facebook.com/yourpage',
            'hint' => 'Public Facebook page or profile URL',
        ],
        'instagram' => [
            'placeholder' => 'https://instagram.com/yourhandle',
            'hint' => 'Instagram profile URL',
        ],
        'linkedin' => [
            'placeholder' => 'https://linkedin.com/company/yourorg',
            'hint' => 'Company page or personal profile',
        ],
        'x' => [
            'placeholder' => 'https://x.com/yourhandle',
            'hint' => 'X (formerly Twitter) profile URL',
        ],
        'youtube' => [
            'placeholder' => 'https://youtube.com/@yourchannel',
            'hint' => 'Channel, handle, or custom URL',
        ],
        'telegram' => [
            'placeholder' => 'https://t.me/yourchannel',
            'hint' => 'Public channel or group invite link',
        ],
        'whatsapp' => [
            'placeholder' => 'https://wa.me/910000000000',
            'hint' => 'Use wa.me with country code, no spaces',
        ],
        'discord' => [
            'placeholder' => 'https://discord.gg/yourinvite',
            'hint' => 'Server invite or vanity URL',
        ],
        'github' => [
            'placeholder' => 'https://github.com/yourorg',
            'hint' => 'Organization or user profile',
        ],
        'pinterest' => [
            'placeholder' => 'https://pinterest.com/yourprofile',
            'hint' => 'Pinterest profile or board URL',
        ],
        'reddit' => [
            'placeholder' => 'https://reddit.com/r/yourcommunity',
            'hint' => 'Subreddit or user profile URL',
        ],
        'threads' => [
            'placeholder' => 'https://threads.net/@yourhandle',
            'hint' => 'Threads profile URL',
        ],
        'tiktok' => [
            'placeholder' => 'https://tiktok.com/@yourhandle',
            'hint' => 'TikTok profile URL',
        ],
        'medium' => [
            'placeholder' => 'https://medium.com/@yourhandle',
            'hint' => 'Publication or author profile',
        ],
        'quora' => [
            'placeholder' => 'https://quora.com/profile/Your-Name',
            'hint' => 'Quora profile or space URL',
        ],
        'bluesky' => [
            'placeholder' => 'https://bsky.app/profile/yourhandle.bsky.social',
            'hint' => 'Bluesky profile URL',
        ],
    ];

    /**
     * @var array<string, array{group: string, type: string, label: string, default: mixed}>
     */
    public const FIELDS = [
        // Branding
        'site_name' => ['group' => 'brand', 'type' => 'string', 'label' => 'Organization name', 'default' => 'Examtube.in'],
        'application_url' => ['group' => 'brand', 'type' => 'string', 'label' => 'Application URL', 'default' => ''],
        'tagline' => ['group' => 'brand', 'type' => 'string', 'label' => 'Tagline', 'default' => ''],
        'description' => ['group' => 'brand', 'type' => 'text', 'label' => 'Description', 'default' => ''],
        'logo_text' => ['group' => 'brand', 'type' => 'string', 'label' => 'Logo text', 'default' => 'Examtube'],
        'logo_gallery_id' => ['group' => 'brand', 'type' => 'integer', 'label' => 'Logo gallery ID', 'default' => null],
        'favicon_gallery_id' => ['group' => 'brand', 'type' => 'integer', 'label' => 'Favicon gallery ID', 'default' => null],
        'og_image_gallery_id' => ['group' => 'brand', 'type' => 'integer', 'label' => 'OG image gallery ID', 'default' => null],

        // Contact
        'email' => ['group' => 'contact', 'type' => 'string', 'label' => 'Email', 'default' => ''],
        'phone' => ['group' => 'contact', 'type' => 'string', 'label' => 'Phone', 'default' => ''],
        'whatsapp' => ['group' => 'contact', 'type' => 'string', 'label' => 'WhatsApp', 'default' => ''],
        'address' => ['group' => 'contact', 'type' => 'text', 'label' => 'Address', 'default' => ''],
        'hours' => ['group' => 'contact', 'type' => 'string', 'label' => 'Support hours', 'default' => ''],
        'support_hours' => ['group' => 'contact', 'type' => 'json', 'label' => 'Support hours schedule', 'default' => []],
        'maps_url' => ['group' => 'contact', 'type' => 'string', 'label' => 'Google Maps URL', 'default' => ''],

        // Homepage / CTA / Footer
        'footer_about' => ['group' => 'footer', 'type' => 'text', 'label' => 'Footer about', 'default' => '', 'key' => 'about'],
        'footer_copyright' => ['group' => 'footer', 'type' => 'string', 'label' => 'Copyright', 'default' => '', 'key' => 'copyright'],
        'cta_title' => ['group' => 'cta', 'type' => 'string', 'label' => 'CTA title', 'default' => '', 'key' => 'title'],
        'cta_subtitle' => ['group' => 'cta', 'type' => 'text', 'label' => 'CTA subtitle', 'default' => '', 'key' => 'subtitle'],
        'cta_primary_label' => ['group' => 'cta', 'type' => 'string', 'label' => 'CTA primary label', 'default' => '', 'key' => 'primary_label'],
        'cta_primary_url' => ['group' => 'cta', 'type' => 'string', 'label' => 'CTA primary URL', 'default' => '', 'key' => 'primary_url'],
        'cta_secondary_label' => ['group' => 'cta', 'type' => 'string', 'label' => 'CTA secondary label', 'default' => '', 'key' => 'secondary_label'],
        'cta_secondary_url' => ['group' => 'cta', 'type' => 'string', 'label' => 'CTA secondary URL', 'default' => '', 'key' => 'secondary_url'],
        'newsletter_title' => ['group' => 'newsletter', 'type' => 'string', 'label' => 'Newsletter title', 'default' => '', 'key' => 'title'],
        'newsletter_subtitle' => ['group' => 'newsletter', 'type' => 'text', 'label' => 'Newsletter subtitle', 'default' => '', 'key' => 'subtitle'],
        'newsletter_cta' => ['group' => 'newsletter', 'type' => 'string', 'label' => 'Newsletter CTA', 'default' => '', 'key' => 'cta'],

        // Default SEO (organization-level)
        'seo_default_title' => ['group' => 'seo', 'type' => 'string', 'label' => 'Default SEO title', 'default' => '', 'key' => 'default_title'],
        'seo_default_description' => ['group' => 'seo', 'type' => 'text', 'label' => 'Default SEO description', 'default' => '', 'key' => 'default_description'],
        'seo_default_keywords' => ['group' => 'seo', 'type' => 'string', 'label' => 'Default SEO keywords', 'default' => '', 'key' => 'default_keywords'],
    ];

    public function __construct(
        protected SiteCmsService $siteCms,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        $all = $this->siteCms->settings($orgId);
        $organization = $orgId ? Organization::query()->find($orgId) : null;

        $settings = [];
        foreach (self::FIELDS as $field => $meta) {
            $dbKey = $meta['key'] ?? $field;
            $fullKey = $meta['group'].'.'.$dbKey;
            $value = $all[$fullKey] ?? $meta['default'];

            if ($meta['type'] === 'integer') {
                $value = ($value === null || $value === '') ? null : (int) $value;
            } elseif ($meta['type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($meta['type'] === 'json') {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : ($meta['default'] ?? []);
                } elseif (! is_array($value)) {
                    $value = $meta['default'] ?? [];
                }
            } else {
                $value = $value ?? '';
            }

            $settings[$field] = $value;
        }

        if ($organization && blank($settings['site_name'])) {
            $settings['site_name'] = $organization->name;
        }
        if ($organization && blank($settings['description']) && filled($organization->description)) {
            $settings['description'] = $organization->description;
        }

        $settings['support_hours'] = self::normalizeSupportHours(
            is_array($settings['support_hours'] ?? null) ? $settings['support_hours'] : []
        );
        if ($settings['support_hours'] === []) {
            $settings['support_hours'] = self::defaultSupportHours();
        }
        $settings['hours'] = self::formatSupportHoursSummary($settings['support_hours']);

        $settings['logo_url'] = $this->galleryUrl($settings['logo_gallery_id'] ?? null);
        $settings['favicon_url'] = $this->galleryUrl($settings['favicon_gallery_id'] ?? null);
        $settings['og_image_url'] = $this->galleryUrl($settings['og_image_gallery_id'] ?? null)
            ?? ($all['seo.og_image'] ?? null);

        return [
            'settings' => $settings,
            'social' => $this->socialMap($orgId),
            'heroes' => $this->heroes($orgId),
            'organization' => [
                'id' => $organization?->id,
                'name' => $organization?->name,
                'slug' => $organization?->slug,
                'status' => $organization?->status,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data, ?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        return DB::transaction(function () use ($data, $orgId) {
            foreach (self::FIELDS as $field => $meta) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }

                $raw = $data[$field];
                if ($field === 'application_url') {
                    $raw = self::normalizeApplicationUrl(is_string($raw) ? $raw : '');
                }
                if ($field === 'support_hours') {
                    // Handled after the loop so we can also refresh the summary string.
                    continue;
                }
                if ($field === 'hours' && array_key_exists('support_hours', $data)) {
                    continue;
                }
                $value = match ($meta['type']) {
                    'integer' => ($raw === null || $raw === '') ? null : (string) (int) $raw,
                    'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                    'json' => json_encode(is_array($raw) ? $raw : [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    default => $raw === null ? null : (string) $raw,
                };

                $dbKey = $meta['key'] ?? $field;

                SiteSetting::query()->updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'group' => $meta['group'],
                        'key' => $dbKey,
                    ],
                    [
                        'value' => $value,
                        'type' => $meta['type'],
                        'label' => $meta['label'],
                    ]
                );
            }

            if (array_key_exists('support_hours', $data)) {
                $rows = self::normalizeSupportHours(is_array($data['support_hours']) ? $data['support_hours'] : []);
                if ($rows === []) {
                    $rows = self::defaultSupportHours();
                }

                SiteSetting::query()->updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'group' => 'contact',
                        'key' => 'support_hours',
                    ],
                    [
                        'value' => json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'type' => 'json',
                        'label' => 'Support hours schedule',
                    ]
                );

                SiteSetting::query()->updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'group' => 'contact',
                        'key' => 'hours',
                    ],
                    [
                        'value' => self::formatSupportHoursSummary($rows),
                        'type' => 'string',
                        'label' => 'Support hours',
                    ]
                );
            }

            if (array_key_exists('site_name', $data) || array_key_exists('description', $data)) {
                $org = Organization::query()->find($orgId);
                if ($org) {
                    $org->fill(array_filter([
                        'name' => $data['site_name'] ?? null,
                        'description' => $data['description'] ?? null,
                    ], fn ($v) => $v !== null))->save();
                }
            }

            // Keep seo.og_image as resolved URL for frontend partials that read the string directly.
            if (array_key_exists('og_image_gallery_id', $data)) {
                $url = $this->galleryUrl($data['og_image_gallery_id'] ?? null);
                SiteSetting::query()->updateOrCreate(
                    ['organization_id' => $orgId, 'group' => 'seo', 'key' => 'og_image'],
                    [
                        'value' => $url,
                        'type' => 'string',
                        'label' => 'Default OG image URL',
                    ]
                );
            }

            if (isset($data['social']) && is_array($data['social'])) {
                $this->syncSocial($orgId, $data['social']);
            }

            $this->siteCms->clearCache($orgId);

            return $this->get($orgId);
        });
    }

    /**
     * @return Collection<int, HeroBanner>
     */
    public function heroes(?int $orgId = null): Collection
    {
        $orgId ??= current_organization_id();

        return HeroBanner::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['image', 'mobileImage'])
            ->ordered()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveHero(array $data, ?int $orgId = null, ?int $heroId = null): HeroBanner
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        $payload = [
            'organization_id' => $orgId,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'badge_text' => $data['badge_text'] ?? null,
            'primary_cta_label' => $data['primary_cta_label'] ?? null,
            'primary_cta_url' => $data['primary_cta_url'] ?? null,
            'secondary_cta_label' => $data['secondary_cta_label'] ?? null,
            'secondary_cta_url' => $data['secondary_cta_url'] ?? null,
            'image_id' => $data['image_id'] ?? null,
            'mobile_image_id' => $data['mobile_image_id'] ?? null,
            'theme' => $data['theme'] ?? 'emerald',
            'show_search' => (bool) ($data['show_search'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];

        if ($heroId) {
            $hero = HeroBanner::query()->forOrg($orgId)->findOrFail($heroId);
            $hero->update($payload);
        } else {
            if (! isset($data['sort_order'])) {
                $payload['sort_order'] = (int) HeroBanner::query()->forOrg($orgId)->max('sort_order') + 1;
            }
            $hero = HeroBanner::query()->create($payload);
        }

        $this->siteCms->clearCache($orgId);

        return $hero->load(['image', 'mobileImage']);
    }

    public function deleteHero(int $heroId, ?int $orgId = null): void
    {
        $orgId ??= current_organization_id();
        $hero = HeroBanner::query()->forOrg($orgId)->findOrFail($heroId);
        $hero->delete();
        $this->siteCms->clearCache($orgId);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderHeroes(array $orderedIds, ?int $orgId = null): void
    {
        $orgId ??= current_organization_id();
        foreach ($orderedIds as $index => $id) {
            HeroBanner::query()
                ->forOrg($orgId)
                ->where('id', $id)
                ->update(['sort_order' => $index + 1]);
        }
        $this->siteCms->clearCache($orgId);
    }

    /**
     * @return array<string, array{platform: string, label: string, url: string, is_visible: bool}>
     */
    protected function socialMap(?int $orgId): array
    {
        $rows = SocialLink::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->get()
            ->keyBy('platform');

        $map = [];
        foreach (self::SOCIAL_PLATFORMS as $platform => $label) {
            $row = $rows->get($platform);
            // Legacy seed used "twitter" — map into x if present.
            if (! $row && $platform === 'x') {
                $row = $rows->get('twitter');
            }
            $map[$platform] = [
                'platform' => $platform,
                'label' => $label,
                'url' => $row?->url ?? '',
                'is_visible' => $row ? (bool) $row->is_visible : false,
            ];
        }

        return $map;
    }

    /**
     * @param  array<string, array{url?: string, is_visible?: bool|int|string}>  $social
     */
    protected function syncSocial(int $orgId, array $social): void
    {
        $order = 1;
        foreach (self::SOCIAL_PLATFORMS as $platform => $label) {
            $payload = $social[$platform] ?? null;
            $url = trim((string) ($payload['url'] ?? ''));
            $visible = filter_var($payload['is_visible'] ?? ($url !== ''), FILTER_VALIDATE_BOOLEAN);

            if ($url === '') {
                SocialLink::query()
                    ->forOrg($orgId)
                    ->whereIn('platform', $platform === 'x' ? ['x', 'twitter'] : [$platform])
                    ->delete();
                continue;
            }

            SocialLink::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'platform' => $platform,
                ],
                [
                    'label' => $label,
                    'url' => $url,
                    'is_visible' => $visible,
                    'sort_order' => $order++,
                ]
            );

            if ($platform === 'x') {
                SocialLink::query()->forOrg($orgId)->where('platform', 'twitter')->delete();
            }
        }
    }

    protected function galleryUrl(mixed $galleryId): ?string
    {
        $id = (int) $galleryId;
        if ($id <= 0) {
            return null;
        }

        return Gallery::query()->find($id)?->file_url;
    }

    /**
     * Normalize a public application URL / domain into an https URL (or empty string).
     */
    public static function normalizeApplicationUrl(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Allow bare domains like examtube.in
        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.ltrim($value, '/');
        }

        $parts = parse_url($value);
        if (! is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (! in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'https';
        }

        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';
        if ($path === '/') {
            $path = '';
        }

        return $scheme.'://'.$host.$port.$path;
    }

    /**
     * Host-only display form for application URL (examtube.in).
     */
    public static function applicationHost(?string $value): string
    {
        $normalized = self::normalizeApplicationUrl($value);
        if ($normalized === '') {
            return '';
        }

        return (string) (parse_url($normalized, PHP_URL_HOST) ?: '');
    }

    /**
     * @return array<string, string>
     */
    public static function supportHourDays(): array
    {
        return [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];
    }

    /**
     * Common timezones for support-hour schedules.
     *
     * @return array<string, string>
     */
    public static function supportHourTimezones(): array
    {
        return [
            'Asia/Kolkata' => 'Asia/Kolkata (IST)',
            'Asia/Dubai' => 'Asia/Dubai (GST)',
            'Asia/Singapore' => 'Asia/Singapore (SGT)',
            'Asia/Tokyo' => 'Asia/Tokyo (JST)',
            'UTC' => 'UTC',
            'Europe/London' => 'Europe/London (GMT/BST)',
            'Europe/Berlin' => 'Europe/Berlin (CET)',
            'America/New_York' => 'America/New_York (ET)',
            'America/Chicago' => 'America/Chicago (CT)',
            'America/Denver' => 'America/Denver (MT)',
            'America/Los_Angeles' => 'America/Los_Angeles (PT)',
            'Australia/Sydney' => 'Australia/Sydney (AEST)',
        ];
    }

    /**
     * Default Mon–Sat, 10:00 AM – 4:00 PM IST.
     *
     * @return list<array{day:string,from:string,to:string,timezone:string}>
     */
    public static function defaultSupportHours(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        return array_map(static fn (string $day): array => [
            'day' => $day,
            'from' => '10:00',
            'to' => '16:00',
            'timezone' => 'Asia/Kolkata',
        ], $days);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{day:string,from:string,to:string,timezone:string}>
     */
    public static function normalizeSupportHours(array $rows): array
    {
        $days = self::supportHourDays();
        $zones = self::supportHourTimezones();
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $day = strtolower(trim((string) ($row['day'] ?? '')));
            $from = self::normalizeClockTime((string) ($row['from'] ?? ''));
            $to = self::normalizeClockTime((string) ($row['to'] ?? ''));
            $timezone = trim((string) ($row['timezone'] ?? 'Asia/Kolkata'));

            if (! isset($days[$day]) || $from === null || $to === null) {
                continue;
            }
            if (! isset($zones[$timezone])) {
                $timezone = 'Asia/Kolkata';
            }
            if ($from >= $to) {
                continue;
            }

            $normalized[] = [
                'day' => $day,
                'from' => $from,
                'to' => $to,
                'timezone' => $timezone,
            ];

            if (count($normalized) >= 7) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * @param  list<array{day:string,from:string,to:string,timezone:string}>  $rows
     */
    public static function formatSupportHoursSummary(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $days = self::supportHourDays();
        $labels = [];

        foreach ($rows as $row) {
            $day = $days[$row['day']] ?? ucfirst($row['day']);
            $from = self::formatClockAmPm($row['from']);
            $to = self::formatClockAmPm($row['to']);
            $tz = $row['timezone'] === 'Asia/Kolkata' ? 'IST' : $row['timezone'];
            $labels[] = "{$day} {$from} – {$to} ({$tz})";
        }

        return implode('; ', $labels);
    }

    public static function normalizeClockTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?:\s*([AaPp][Mm]))?$/', $value, $m)) {
            $hour = (int) $m[1];
            $minute = (int) $m[2];
            $meridiem = isset($m[3]) ? strtoupper($m[3]) : null;

            if ($meridiem !== null) {
                if ($hour < 1 || $hour > 12 || $minute > 59) {
                    return null;
                }
                if ($meridiem === 'AM') {
                    $hour = $hour === 12 ? 0 : $hour;
                } else {
                    $hour = $hour === 12 ? 12 : $hour + 12;
                }
            } elseif ($hour > 23 || $minute > 59) {
                return null;
            }

            return sprintf('%02d:%02d', $hour, $minute);
        }

        return null;
    }

    public static function formatClockAmPm(string $value): string
    {
        $normalized = self::normalizeClockTime($value);
        if ($normalized === null) {
            return $value;
        }

        [$hour, $minute] = array_map('intval', explode(':', $normalized));
        $meridiem = $hour >= 12 ? 'PM' : 'AM';
        $hour12 = $hour % 12;
        if ($hour12 === 0) {
            $hour12 = 12;
        }

        return sprintf('%d:%02d %s', $hour12, $minute, $meridiem);
    }
}

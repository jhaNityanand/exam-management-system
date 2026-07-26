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
    ];

    /**
     * @var array<string, array{group: string, type: string, label: string, default: mixed}>
     */
    public const FIELDS = [
        // Branding
        'site_name' => ['group' => 'brand', 'type' => 'string', 'label' => 'Organization name', 'default' => 'Examtube.in'],
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
                $value = match ($meta['type']) {
                    'integer' => ($raw === null || $raw === '') ? null : (string) (int) $raw,
                    'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
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
}

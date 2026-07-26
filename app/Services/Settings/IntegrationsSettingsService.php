<?php

namespace App\Services\Settings;

use App\Models\Cms\SiteSetting;
use App\Services\Frontend\SiteCmsService;

class IntegrationsSettingsService
{
    public const GROUP = 'integrations';

    /**
     * @var array<string, array{type: string, label: string, default: mixed}>
     */
    public const DEFINITIONS = [
        // Analytics & tags
        'analytics_enabled' => ['type' => 'boolean', 'label' => 'Analytics enabled', 'default' => false],
        'google_analytics_id' => ['type' => 'string', 'label' => 'Google Analytics 4 ID', 'default' => ''],
        'gtm_container_id' => ['type' => 'string', 'label' => 'Google Tag Manager ID', 'default' => ''],
        'facebook_pixel_id' => ['type' => 'string', 'label' => 'Facebook Pixel ID', 'default' => ''],
        'custom_head_scripts' => ['type' => 'text', 'label' => 'Custom head scripts', 'default' => ''],
        'custom_body_scripts' => ['type' => 'text', 'label' => 'Custom body scripts', 'default' => ''],

        // Cookie consent
        'cookies_enabled' => ['type' => 'boolean', 'label' => 'Cookie consent enabled', 'default' => true],
        'cookies_mode' => ['type' => 'string', 'label' => 'Cookie consent mode', 'default' => 'opt_in'],
        'cookies_title' => ['type' => 'string', 'label' => 'Cookie banner title', 'default' => 'We use cookies'],
        'cookies_message' => [
            'type' => 'text',
            'label' => 'Cookie banner message',
            'default' => 'We use cookies to improve your experience and understand how Examtube is used. You can accept analytics cookies or continue with essential cookies only.',
        ],
        'cookies_accept_label' => ['type' => 'string', 'label' => 'Accept button label', 'default' => 'Accept all'],
        'cookies_reject_label' => ['type' => 'string', 'label' => 'Reject button label', 'default' => 'Essential only'],
        'cookies_policy_url' => ['type' => 'string', 'label' => 'Privacy policy URL', 'default' => '/privacy-policy'],

        // Localization
        'default_timezone' => ['type' => 'string', 'label' => 'Default timezone', 'default' => 'Asia/Kolkata'],
        'default_locale' => ['type' => 'string', 'label' => 'Default locale', 'default' => 'en'],

        // Feature flags
        'registration_enabled' => ['type' => 'boolean', 'label' => 'Public registration enabled', 'default' => true],
        'newsletter_enabled' => ['type' => 'boolean', 'label' => 'Newsletter signup enabled', 'default' => true],
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
        $config = [];

        foreach (self::DEFINITIONS as $key => $meta) {
            $value = $all[self::GROUP.'.'.$key] ?? $meta['default'];
            $config[$key] = $this->castValue($value, $meta['type'], $meta['default']);
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data, ?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        foreach (self::DEFINITIONS as $key => $meta) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            SiteSetting::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'group' => self::GROUP,
                    'key' => $key,
                ],
                [
                    'value' => $this->storeValue($data[$key], $meta['type']),
                    'type' => $meta['type'] === 'text' ? 'text' : ($meta['type'] === 'boolean' ? 'boolean' : 'string'),
                    'label' => $meta['label'],
                ]
            );
        }

        $this->siteCms->clearCache($orgId);

        return $this->get($orgId);
    }

    public function seedDefaults(?int $orgId, array $overrides = []): void
    {
        foreach (self::DEFINITIONS as $key => $meta) {
            $value = $overrides[$key] ?? $meta['default'];
            SiteSetting::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'group' => self::GROUP,
                    'key' => $key,
                ],
                [
                    'value' => $this->storeValue($value, $meta['type']),
                    'type' => $meta['type'] === 'text' ? 'text' : ($meta['type'] === 'boolean' ? 'boolean' : 'string'),
                    'label' => $meta['label'],
                ]
            );
        }
    }

    public function isRegistrationEnabled(?int $orgId = null): bool
    {
        return (bool) $this->get($orgId)['registration_enabled'];
    }

    public function isNewsletterEnabled(?int $orgId = null): bool
    {
        return (bool) $this->get($orgId)['newsletter_enabled'];
    }

    /**
     * Tracking tags that should load for the current consent/mode.
     *
     * @return array{analytics_enabled: bool, ga_id: string, gtm_id: string, pixel_id: string, custom_head: string, custom_body: string, cookies: array<string, mixed>}
     */
    public function frontendPayload(?int $orgId = null): array
    {
        $c = $this->get($orgId);

        return [
            'analytics_enabled' => (bool) $c['analytics_enabled'],
            'ga_id' => trim((string) $c['google_analytics_id']),
            'gtm_id' => trim((string) $c['gtm_container_id']),
            'pixel_id' => trim((string) $c['facebook_pixel_id']),
            'custom_head' => (string) $c['custom_head_scripts'],
            'custom_body' => (string) $c['custom_body_scripts'],
            'cookies' => [
                'enabled' => (bool) $c['cookies_enabled'],
                'mode' => (string) $c['cookies_mode'],
                'title' => (string) $c['cookies_title'],
                'message' => (string) $c['cookies_message'],
                'accept_label' => (string) $c['cookies_accept_label'],
                'reject_label' => (string) $c['cookies_reject_label'],
                'policy_url' => (string) $c['cookies_policy_url'],
            ],
            'registration_enabled' => (bool) $c['registration_enabled'],
            'newsletter_enabled' => (bool) $c['newsletter_enabled'],
            'default_locale' => (string) $c['default_locale'],
            'default_timezone' => (string) $c['default_timezone'],
        ];
    }

    protected function castValue(mixed $value, string $type, mixed $default): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => $value === null || $value === '' ? (int) $default : (int) $value,
            default => $value ?? $default ?? '',
        };
    }

    protected function storeValue(mixed $raw, string $type): ?string
    {
        return match ($type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'integer' => ($raw === null || $raw === '') ? null : (string) (int) $raw,
            default => $raw === null ? null : (string) $raw,
        };
    }
}

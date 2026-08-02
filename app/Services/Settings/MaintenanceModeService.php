<?php

namespace App\Services\Settings;

use App\Models\Cms\SiteSetting;
use App\Services\Frontend\SiteCmsService;
use Illuminate\Support\Carbon;

class MaintenanceModeService
{
    public const GROUP = 'maintenance';

    /**
     * @var array<string, array{type: string, label: string, default: mixed}>
     */
    public const DEFINITIONS = [
        'enabled' => ['type' => 'boolean', 'label' => 'Maintenance enabled', 'default' => false],
        'title' => ['type' => 'string', 'label' => 'Maintenance title', 'default' => 'We will be right back'],
        'message' => [
            'type' => 'text',
            'label' => 'Maintenance message',
            'default' => '<p>We are currently performing scheduled maintenance to improve your experience.</p><p>Please check back shortly.</p>',
        ],
        'estimated_at' => ['type' => 'string', 'label' => 'Restore date and time', 'default' => ''],
        'social_facebook' => ['type' => 'string', 'label' => 'Facebook URL', 'default' => ''],
        'social_instagram' => ['type' => 'string', 'label' => 'Instagram URL', 'default' => ''],
        'social_linkedin' => ['type' => 'string', 'label' => 'LinkedIn URL', 'default' => ''],
        'social_twitter' => ['type' => 'string', 'label' => 'Twitter/X URL', 'default' => ''],
        'social_youtube' => ['type' => 'string', 'label' => 'YouTube URL', 'default' => ''],
        'social_telegram' => ['type' => 'string', 'label' => 'Telegram URL', 'default' => ''],
    ];

    public function __construct(
        protected SiteCmsService $siteCms,
    ) {}

    public function isEnabled(?int $orgId = null): bool
    {
        return (bool) $this->get($orgId)['enabled'];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        $all = $this->siteCms->settings($orgId);
        $config = [];

        foreach (self::DEFINITIONS as $key => $meta) {
            $fullKey = self::GROUP.'.'.$key;
            $value = $all[$fullKey] ?? $meta['default'];

            if ($meta['type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($meta['type'] === 'integer') {
                $value = $value === null || $value === '' ? null : (int) $value;
            } else {
                $value = $value ?? '';
            }

            $config[$key] = $value;
        }

        $estimated = $this->parseEstimatedAt((string) ($config['estimated_at'] ?? ''));
        if ($estimated) {
            $config['estimated_at'] = $estimated->timezone(config('app.timezone'))->format('Y-m-d H:i');
            $config['estimated_at_iso'] = $estimated->toIso8601String();
            $config['estimated_at_unix'] = $estimated->getTimestamp();
            $config['estimated_at_formatted'] = $estimated->timezone(config('app.timezone'))
                ->format('D, M j, Y · g:i A T');
        } else {
            $config['estimated_at_iso'] = null;
            $config['estimated_at_unix'] = null;
            $config['estimated_at_formatted'] = null;
        }

        $config['social_links'] = $this->socialLinksFromConfig($config);
        $config['site_name'] = (string) ($all['brand.site_name'] ?? config('app.name', 'Examtube'));
        $plain = strip_tags((string) ($config['message'] ?? ''));
        $config['message_plain'] = trim(preg_replace('/\s+/u', ' ', $plain) ?: '');

        return $config;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, ?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        foreach (self::DEFINITIONS as $key => $meta) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $raw = $data[$key];
            $value = match ($meta['type']) {
                'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'integer' => ($raw === null || $raw === '') ? null : (string) (int) $raw,
                default => $raw === null ? null : (string) $raw,
            };

            SiteSetting::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'group' => self::GROUP,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                ]
            );
        }

        $this->siteCms->clearCache($orgId);

        return $this->get($orgId);
    }

    /**
     * Seed default maintenance settings for an organization.
     */
    public function seedDefaults(?int $orgId, array $overrides = []): void
    {
        foreach (self::DEFINITIONS as $key => $meta) {
            $value = $overrides[$key] ?? $meta['default'];
            $stored = match ($meta['type']) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'integer' => ($value === null || $value === '') ? null : (string) (int) $value,
                default => $value === null ? null : (string) $value,
            };

            SiteSetting::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'group' => self::GROUP,
                    'key' => $key,
                ],
                [
                    'value' => $stored,
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{platform: string, label: string, url: string}>
     */
    protected function socialLinksFromConfig(array $config): array
    {
        $map = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'twitter' => 'X (Twitter)',
            'youtube' => 'YouTube',
            'telegram' => 'Telegram',
        ];

        $links = [];
        foreach ($map as $platform => $label) {
            $url = trim((string) ($config['social_'.$platform] ?? ''));
            if ($url !== '') {
                $links[] = [
                    'platform' => $platform,
                    'label' => $label,
                    'url' => $url,
                ];
            }
        }

        return $links;
    }

    protected function parseEstimatedAt(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}

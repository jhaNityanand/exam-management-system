<?php

namespace App\Services\Settings;

use App\Models\Cms\SiteSetting;
use App\Services\Frontend\SiteCmsService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

class SecuritySettingsService
{
    public const GROUP = 'security';

    /**
     * @var array<string, array{type: string, label: string, default: mixed}>
     */
    public const DEFINITIONS = [
        'recaptcha_enabled' => ['type' => 'boolean', 'label' => 'reCAPTCHA enabled', 'default' => false],
        'recaptcha_version' => ['type' => 'string', 'label' => 'reCAPTCHA version', 'default' => 'v3'],
        'recaptcha_site_key' => ['type' => 'string', 'label' => 'reCAPTCHA site key', 'default' => ''],
        'recaptcha_secret_key' => ['type' => 'secret', 'label' => 'reCAPTCHA secret key', 'default' => ''],
        'recaptcha_score_threshold' => ['type' => 'string', 'label' => 'reCAPTCHA v3 score threshold', 'default' => '0.5'],
        'recaptcha_on_login' => ['type' => 'boolean', 'label' => 'reCAPTCHA on login', 'default' => true],
        'recaptcha_on_register' => ['type' => 'boolean', 'label' => 'reCAPTCHA on register', 'default' => true],
        'recaptcha_on_contact' => ['type' => 'boolean', 'label' => 'reCAPTCHA on contact', 'default' => true],
        'recaptcha_on_newsletter' => ['type' => 'boolean', 'label' => 'reCAPTCHA on newsletter', 'default' => true],
        'recaptcha_on_password_reset' => ['type' => 'boolean', 'label' => 'reCAPTCHA on password reset', 'default' => true],
        'login_lockout_enabled' => ['type' => 'boolean', 'label' => 'Login lockout enabled', 'default' => true],
        'login_max_attempts' => ['type' => 'integer', 'label' => 'Login max attempts', 'default' => 5],
        'login_decay_minutes' => ['type' => 'integer', 'label' => 'Login lockout minutes', 'default' => 1],
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

            if ($meta['type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($meta['type'] === 'integer') {
                $value = $value === null || $value === '' ? (int) $meta['default'] : (int) $value;
            } elseif ($meta['type'] === 'secret') {
                $value = $this->decryptSecret($value);
            } else {
                $value = $value ?? '';
            }

            $config[$key] = $value;
        }

        $config['has_recaptcha_secret'] = trim((string) $config['recaptcha_secret_key']) !== '';
        $config['recaptcha_secret_key'] = '';

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function getForRuntime(?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        $all = $this->siteCms->settings($orgId);
        $config = [];

        foreach (self::DEFINITIONS as $key => $meta) {
            $value = $all[self::GROUP.'.'.$key] ?? $meta['default'];

            if ($meta['type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($meta['type'] === 'integer') {
                $value = $value === null || $value === '' ? (int) $meta['default'] : (int) $value;
            } elseif ($meta['type'] === 'secret') {
                $value = $this->decryptSecret($value);
            } else {
                $value = $value ?? '';
            }

            $config[$key] = $value;
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

            $raw = $data[$key];

            if ($meta['type'] === 'secret') {
                $incoming = is_string($raw) ? trim($raw) : '';
                if ($incoming === '') {
                    continue;
                }
                $value = Crypt::encryptString($incoming);
            } else {
                $value = match ($meta['type']) {
                    'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                    'integer' => ($raw === null || $raw === '') ? null : (string) (int) $raw,
                    default => $raw === null ? null : (string) $raw,
                };
            }

            SiteSetting::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'group' => self::GROUP,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'type' => $meta['type'] === 'secret' ? 'string' : ($meta['type'] === 'boolean' ? 'boolean' : ($meta['type'] === 'integer' ? 'integer' : 'string')),
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
            $stored = match ($meta['type']) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'integer' => ($value === null || $value === '') ? null : (string) (int) $value,
                'secret' => ($value === null || $value === '') ? null : Crypt::encryptString((string) $value),
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
                    'type' => $meta['type'] === 'secret' ? 'string' : ($meta['type'] === 'boolean' ? 'boolean' : ($meta['type'] === 'integer' ? 'integer' : 'string')),
                    'label' => $meta['label'],
                ]
            );
        }
    }

    public function requiresRecaptcha(string $context, ?int $orgId = null): bool
    {
        $c = $this->getForRuntime($orgId);
        if (! $c['recaptcha_enabled'] || trim((string) $c['recaptcha_site_key']) === '' || trim((string) $c['recaptcha_secret_key']) === '') {
            return false;
        }

        return match ($context) {
            'login' => (bool) $c['recaptcha_on_login'],
            'register' => (bool) $c['recaptcha_on_register'],
            'contact' => (bool) $c['recaptcha_on_contact'],
            'newsletter' => (bool) $c['recaptcha_on_newsletter'],
            'password_reset' => (bool) $c['recaptcha_on_password_reset'],
            default => false,
        };
    }

    /**
     * @return array{ok: bool, message?: string, score?: float|null}
     */
    public function verify(?string $token, string $action = 'submit', ?int $orgId = null): array
    {
        $c = $this->getForRuntime($orgId);
        $secret = trim((string) $c['recaptcha_secret_key']);
        $token = trim((string) $token);

        if ($token === '' || $secret === '') {
            return ['ok' => false, 'message' => 'reCAPTCHA verification failed. Please try again.'];
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);

            $payload = $response->json() ?? [];
            if (! ($payload['success'] ?? false)) {
                return ['ok' => false, 'message' => 'reCAPTCHA verification failed. Please try again.'];
            }

            if (($c['recaptcha_version'] ?? 'v3') === 'v3') {
                $score = isset($payload['score']) ? (float) $payload['score'] : null;
                $threshold = (float) ($c['recaptcha_score_threshold'] ?? 0.5);
                if ($score !== null && $score < $threshold) {
                    return [
                        'ok' => false,
                        'message' => 'reCAPTCHA score too low. Please try again.',
                        'score' => $score,
                    ];
                }

                return ['ok' => true, 'score' => $score];
            }

            return ['ok' => true, 'score' => null];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'Unable to verify reCAPTCHA right now. Please try again.'];
        }
    }

    /**
     * @return array{enabled: bool, max_attempts: int, decay_minutes: int}
     */
    public function loginProtection(?int $orgId = null): array
    {
        $c = $this->getForRuntime($orgId);

        return [
            'enabled' => (bool) $c['login_lockout_enabled'],
            'max_attempts' => max(1, (int) $c['login_max_attempts']),
            'decay_minutes' => max(1, (int) $c['login_decay_minutes']),
        ];
    }

    /**
     * Safe public payload for Blade/JS (no secrets).
     *
     * @return array<string, mixed>
     */
    public function frontendRecaptcha(?int $orgId = null): array
    {
        $c = $this->getForRuntime($orgId);
        $enabled = (bool) $c['recaptcha_enabled'] && trim((string) $c['recaptcha_site_key']) !== '';

        return [
            'enabled' => $enabled,
            'version' => (string) $c['recaptcha_version'],
            'site_key' => $enabled ? (string) $c['recaptcha_site_key'] : '',
            'contexts' => [
                'login' => $enabled && (bool) $c['recaptcha_on_login'],
                'register' => $enabled && (bool) $c['recaptcha_on_register'],
                'contact' => $enabled && (bool) $c['recaptcha_on_contact'],
                'newsletter' => $enabled && (bool) $c['recaptcha_on_newsletter'],
                'password_reset' => $enabled && (bool) $c['recaptcha_on_password_reset'],
            ],
        ];
    }

    protected function decryptSecret(mixed $value): string
    {
        $value = (string) ($value ?? '');
        if ($value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }
}

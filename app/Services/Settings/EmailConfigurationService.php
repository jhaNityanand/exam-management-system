<?php

namespace App\Services\Settings;

use App\Mail\SettingsTestMail;
use App\Models\Cms\SiteSetting;
use App\Services\Frontend\SiteCmsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailConfigurationService
{
    public const GROUP = 'email';

    /**
     * @var array<string, array{type: string, label: string, default: mixed}>
     */
    public const DEFINITIONS = [
        'mailer' => ['type' => 'string', 'label' => 'Mail transport', 'default' => 'log'],
        'host' => ['type' => 'string', 'label' => 'SMTP host', 'default' => ''],
        'port' => ['type' => 'integer', 'label' => 'SMTP port', 'default' => 587],
        'username' => ['type' => 'string', 'label' => 'SMTP username', 'default' => ''],
        'password' => ['type' => 'secret', 'label' => 'SMTP password', 'default' => ''],
        'encryption' => ['type' => 'string', 'label' => 'SMTP encryption', 'default' => 'tls'],
        'from_address' => ['type' => 'string', 'label' => 'From address', 'default' => ''],
        'from_name' => ['type' => 'string', 'label' => 'From name', 'default' => ''],
        'google_oauth_enabled' => ['type' => 'boolean', 'label' => 'Google OAuth enabled', 'default' => false],
        'google_client_id' => ['type' => 'string', 'label' => 'Google client ID', 'default' => ''],
        'google_client_secret' => ['type' => 'secret', 'label' => 'Google client secret', 'default' => ''],
        'google_redirect_uri' => ['type' => 'string', 'label' => 'Google redirect URI', 'default' => ''],
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
            $fullKey = self::GROUP.'.'.$key;
            $value = $all[$fullKey] ?? $meta['default'];

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

        if (trim((string) $config['from_address']) === '') {
            $config['from_address'] = (string) ($all['contact.email'] ?? config('mail.from.address', 'support@examtube.in'));
        }
        if (trim((string) $config['from_name']) === '') {
            $config['from_name'] = (string) ($all['brand.site_name'] ?? config('mail.from.name', config('app.name')));
        }
        if (trim((string) $config['google_redirect_uri']) === '') {
            $config['google_redirect_uri'] = $this->defaultGoogleRedirectUri();
        }

        $config['has_smtp_password'] = trim((string) $config['password']) !== '';
        $config['has_google_client_secret'] = trim((string) $config['google_client_secret']) !== '';
        // Never expose secrets to the Blade form
        $config['password'] = '';
        $config['google_client_secret'] = '';
        $config['mailer_label'] = $this->mailerLabel((string) $config['mailer']);

        return $config;
    }

    /**
     * Secrets included — for applying runtime mail config / sending.
     *
     * @return array<string, mixed>
     */
    public function getForRuntime(?int $orgId = null): array
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
                $value = $value === null || $value === '' ? (int) $meta['default'] : (int) $value;
            } elseif ($meta['type'] === 'secret') {
                $value = $this->decryptSecret($value);
            } else {
                $value = $value ?? '';
            }

            $config[$key] = $value;
        }

        if (trim((string) $config['from_address']) === '') {
            $config['from_address'] = (string) ($all['contact.email'] ?? config('mail.from.address'));
        }
        if (trim((string) $config['from_name']) === '') {
            $config['from_name'] = (string) ($all['brand.site_name'] ?? config('mail.from.name'));
        }
        if (trim((string) $config['google_redirect_uri']) === '') {
            $config['google_redirect_uri'] = $this->defaultGoogleRedirectUri();
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

        $existing = $this->getForRuntime($orgId);

        foreach (self::DEFINITIONS as $key => $meta) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $raw = $data[$key];

            if ($meta['type'] === 'secret') {
                $incoming = is_string($raw) ? trim($raw) : '';
                if ($incoming === '') {
                    // Keep existing secret when the form field is left blank
                    continue;
                }
                $value = $this->encryptSecret($incoming);
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
                    'type' => $meta['type'] === 'secret' ? 'string' : $meta['type'],
                    'label' => $meta['label'],
                ]
            );
        }

        // Preserve secrets if keys were omitted entirely from payload
        foreach (['password', 'google_client_secret'] as $secretKey) {
            if (array_key_exists($secretKey, $data)) {
                continue;
            }
            if (trim((string) ($existing[$secretKey] ?? '')) === '') {
                continue;
            }
            SiteSetting::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'group' => self::GROUP,
                    'key' => $secretKey,
                ],
                [
                    'value' => $this->encryptSecret((string) $existing[$secretKey]),
                    'type' => 'string',
                    'label' => self::DEFINITIONS[$secretKey]['label'],
                ]
            );
        }

        $this->siteCms->clearCache($orgId);
        $this->applyToConfig($orgId);

        return $this->get($orgId);
    }

    public function applyToConfig(?int $orgId = null): void
    {
        $config = $this->getForRuntime($orgId);
        $mailer = in_array($config['mailer'], ['smtp', 'log', 'sendmail', 'array'], true)
            ? $config['mailer']
            : 'log';

        Config::set('mail.default', $mailer);

        if ($mailer === 'smtp') {
            $encryption = strtolower((string) $config['encryption']);
            if (! in_array($encryption, ['tls', 'ssl', 'none'], true)) {
                $encryption = 'tls';
            }

            Config::set('mail.mailers.smtp.host', (string) $config['host']);
            Config::set('mail.mailers.smtp.port', (int) $config['port']);
            Config::set('mail.mailers.smtp.username', (string) $config['username'] ?: null);
            Config::set('mail.mailers.smtp.password', (string) $config['password'] ?: null);
            Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
            Config::set('mail.mailers.smtp.scheme', $encryption === 'ssl' ? 'smtps' : null);
        }

        if (trim((string) $config['from_address']) !== '') {
            Config::set('mail.from.address', (string) $config['from_address']);
        }
        if (trim((string) $config['from_name']) !== '') {
            Config::set('mail.from.name', (string) $config['from_name']);
        }

        // Stash Google credentials for future Socialite wiring (UI-only for now)
        Config::set('services.google.client_id', (string) $config['google_client_id'] ?: env('GOOGLE_CLIENT_ID'));
        Config::set('services.google.client_secret', (string) $config['google_client_secret'] ?: env('GOOGLE_CLIENT_SECRET'));
        Config::set('services.google.redirect', (string) $config['google_redirect_uri'] ?: $this->defaultGoogleRedirectUri());
        Config::set('services.google.enabled', (bool) $config['google_oauth_enabled']);
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function sendTestEmail(string $to, ?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        $this->applyToConfig($orgId);
        $config = $this->getForRuntime($orgId);
        $fromAddress = (string) ($config['from_address'] ?: config('mail.from.address'));
        $fromName = (string) ($config['from_name'] ?: config('mail.from.name'));
        $siteName = (string) ($this->siteCms->setting('brand.site_name', config('app.name'), $orgId) ?: config('app.name'));

        $mailer = in_array($config['mailer'], ['smtp', 'log', 'sendmail', 'array'], true)
            ? (string) $config['mailer']
            : 'log';

        try {
            Mail::mailer($mailer)
                ->to($to)
                ->send((new SettingsTestMail($siteName))->from($fromAddress, $fromName));

            return [
                'success' => true,
                'message' => "Test email sent to {$to} using the {$this->mailerLabel((string) $config['mailer'])} transport.",
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
            ];
        }
    }

    public function seedDefaults(?int $orgId, array $overrides = []): void
    {
        $defaults = [
            'mailer' => 'log',
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from_address' => 'support@examtube.in',
            'from_name' => 'Examtube.in',
            'google_oauth_enabled' => false,
            'google_client_id' => '',
            'google_client_secret' => '',
            'google_redirect_uri' => $this->defaultGoogleRedirectUri(),
        ];

        foreach (array_merge($defaults, $overrides) as $key => $value) {
            if (! isset(self::DEFINITIONS[$key])) {
                continue;
            }
            $meta = self::DEFINITIONS[$key];
            $stored = match ($meta['type']) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'integer' => ($value === null || $value === '') ? null : (string) (int) $value,
                'secret' => ($value === null || $value === '') ? null : $this->encryptSecret((string) $value),
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
                    'type' => $meta['type'] === 'secret' ? 'string' : $meta['type'],
                    'label' => $meta['label'],
                ]
            );
        }
    }

    public function defaultGoogleRedirectUri(): string
    {
        return rtrim((string) config('app.url'), '/').'/auth/google/callback';
    }

    protected function mailerLabel(string $mailer): string
    {
        return match ($mailer) {
            'smtp' => 'SMTP',
            'sendmail' => 'Sendmail',
            'array' => 'Array (testing)',
            default => 'Log (development)',
        };
    }

    protected function encryptSecret(string $value): string
    {
        return Crypt::encryptString($value);
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
            // Legacy / plaintext fallback during development
            return $value;
        }
    }
}

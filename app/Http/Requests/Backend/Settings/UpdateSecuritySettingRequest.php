<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSecuritySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recaptcha_enabled' => ['required', 'boolean'],
            'recaptcha_version' => ['required', Rule::in(['v2', 'v3'])],
            'recaptcha_site_key' => ['nullable', 'string', 'max:120'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:120'],
            'recaptcha_score_threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'recaptcha_on_login' => ['required', 'boolean'],
            'recaptcha_on_register' => ['required', 'boolean'],
            'recaptcha_on_contact' => ['required', 'boolean'],
            'recaptcha_on_newsletter' => ['required', 'boolean'],
            'recaptcha_on_password_reset' => ['required', 'boolean'],
            'login_lockout_enabled' => ['required', 'boolean'],
            'login_max_attempts' => ['required', 'integer', 'min:1', 'max:50'],
            'login_decay_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $boolKeys = [
            'recaptcha_enabled',
            'recaptcha_on_login',
            'recaptcha_on_register',
            'recaptcha_on_contact',
            'recaptcha_on_newsletter',
            'recaptcha_on_password_reset',
            'login_lockout_enabled',
        ];

        $merged = [];
        foreach ($boolKeys as $key) {
            $merged[$key] = filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN);
        }

        $merged['recaptcha_site_key'] = trim((string) $this->input('recaptcha_site_key', ''));
        $merged['login_max_attempts'] = (int) $this->input('login_max_attempts', 5);
        $merged['login_decay_minutes'] = (int) $this->input('login_decay_minutes', 1);
        $merged['recaptcha_score_threshold'] = (string) $this->input('recaptcha_score_threshold', '0.5');

        $this->merge($merged);
    }
}

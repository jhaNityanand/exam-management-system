<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::PLATFORM);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mailer = $this->input('mailer');

        return [
            'mailer' => ['required', Rule::in(['smtp', 'log', 'sendmail'])],
            'host' => [$mailer === 'smtp' ? 'required' : 'nullable', 'string', 'max:255'],
            'port' => [$mailer === 'smtp' ? 'required' : 'nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'from_address' => ['required', 'email', 'max:190'],
            'from_name' => ['required', 'string', 'max:120'],
            'google_oauth_enabled' => ['required', 'boolean'],
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:500'],
            'google_redirect_uri' => ['nullable', 'url', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'google_oauth_enabled' => filter_var($this->input('google_oauth_enabled'), FILTER_VALIDATE_BOOLEAN),
            'port' => $this->filled('port') ? (int) $this->input('port') : null,
            'host' => trim((string) $this->input('host', '')),
            'username' => trim((string) $this->input('username', '')),
            'from_address' => trim((string) $this->input('from_address', '')),
            'from_name' => trim((string) $this->input('from_name', '')),
            'google_client_id' => trim((string) $this->input('google_client_id', '')),
            'google_redirect_uri' => trim((string) $this->input('google_redirect_uri', '')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'host.required' => 'SMTP host is required when using the SMTP transport.',
            'port.required' => 'SMTP port is required when using the SMTP transport.',
            'from_address.required' => 'Please enter a From email address.',
            'from_name.required' => 'Please enter a From name.',
        ];
    }
}

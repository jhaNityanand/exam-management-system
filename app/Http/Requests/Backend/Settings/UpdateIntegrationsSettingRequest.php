<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationsSettingRequest extends FormRequest
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
        return [
            'analytics_enabled' => ['required', 'boolean'],
            'google_analytics_id' => ['nullable', 'string', 'max:40'],
            'gtm_container_id' => ['nullable', 'string', 'max:40'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:40'],
            'custom_head_scripts' => ['nullable', 'string', 'max:20000'],
            'custom_body_scripts' => ['nullable', 'string', 'max:20000'],
            'cookies_enabled' => ['required', 'boolean'],
            'cookies_mode' => ['required', Rule::in(['opt_in', 'opt_out', 'info_only'])],
            'cookies_title' => ['required', 'string', 'max:120'],
            'cookies_message' => ['required', 'string', 'max:1000'],
            'cookies_accept_label' => ['required', 'string', 'max:60'],
            'cookies_reject_label' => ['required', 'string', 'max:60'],
            'cookies_policy_url' => ['nullable', 'string', 'max:500'],
            'default_timezone' => ['required', 'timezone'],
            'default_locale' => ['required', 'string', 'max:12'],
            'registration_enabled' => ['required', 'boolean'],
            'newsletter_enabled' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'analytics_enabled' => filter_var($this->input('analytics_enabled'), FILTER_VALIDATE_BOOLEAN),
            'cookies_enabled' => filter_var($this->input('cookies_enabled'), FILTER_VALIDATE_BOOLEAN),
            'registration_enabled' => filter_var($this->input('registration_enabled'), FILTER_VALIDATE_BOOLEAN),
            'newsletter_enabled' => filter_var($this->input('newsletter_enabled'), FILTER_VALIDATE_BOOLEAN),
            'google_analytics_id' => trim((string) $this->input('google_analytics_id', '')),
            'gtm_container_id' => trim((string) $this->input('gtm_container_id', '')),
            'facebook_pixel_id' => trim((string) $this->input('facebook_pixel_id', '')),
            'cookies_policy_url' => trim((string) $this->input('cookies_policy_url', '')),
            'default_timezone' => trim((string) $this->input('default_timezone', 'Asia/Kolkata')),
            'default_locale' => trim((string) $this->input('default_locale', 'en')),
        ]);
    }
}

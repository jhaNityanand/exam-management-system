<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::ORGANIZATION);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chunk_size' => ['required', 'integer', 'min:100', 'max:50000'],
            'robots_extra' => ['nullable', 'string', 'max:5000'],
            'humans_text' => ['nullable', 'string', 'max:10000'],
            'security_contact_email' => ['nullable', 'email', 'max:190'],
            'security_policy_url' => ['nullable', 'url', 'max:255'],
            'manifest_name' => ['required', 'string', 'max:120'],
            'manifest_short_name' => ['required', 'string', 'max:40'],
            'manifest_theme_color' => ['required', 'string', 'max:20'],
            'manifest_background_color' => ['required', 'string', 'max:20'],
        ];
    }
}

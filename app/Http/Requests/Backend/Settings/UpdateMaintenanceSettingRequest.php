<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceSettingRequest extends FormRequest
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
        $orgId = current_organization_id();

        return [
            'enabled' => ['required', 'boolean'],
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'estimated_at' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_telegram' => ['nullable', 'url', 'max:255'],
            'logo_gallery_id' => [
                'nullable',
                'integer',
                Rule::exists('galleries', 'id')->where(function ($query) use ($orgId) {
                    if ($orgId) {
                        $query->where('organization_id', $orgId);
                    }
                    $query->whereNull('deleted_at');
                }),
            ],
            'background_gallery_id' => [
                'nullable',
                'integer',
                Rule::exists('galleries', 'id')->where(function ($query) use ($orgId) {
                    if ($orgId) {
                        $query->where('organization_id', $orgId);
                    }
                    $query->whereNull('deleted_at');
                }),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => filter_var($this->input('enabled'), FILTER_VALIDATE_BOOLEAN),
            'logo_gallery_id' => $this->filled('logo_gallery_id') ? (int) $this->input('logo_gallery_id') : null,
            'background_gallery_id' => $this->filled('background_gallery_id') ? (int) $this->input('background_gallery_id') : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a maintenance title.',
            'message.required' => 'Please enter a maintenance message.',
            'contact_email.email' => 'Enter a valid contact email address.',
        ];
    }
}

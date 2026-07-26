<?php

namespace App\Http\Requests\Backend\Settings;

use App\Services\Settings\OrganizationSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationSettingRequest extends FormRequest
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
        $galleryRule = [
            'nullable',
            'integer',
            Rule::exists('galleries', 'id')->where(function ($query) use ($orgId) {
                if ($orgId) {
                    $query->where('organization_id', $orgId);
                }
                $query->whereNull('deleted_at');
            }),
        ];

        $platforms = array_keys(OrganizationSettingsService::SOCIAL_PLATFORMS);

        return [
            'site_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo_text' => ['nullable', 'string', 'max:80'],
            'logo_gallery_id' => $galleryRule,
            'favicon_gallery_id' => $galleryRule,
            'og_image_gallery_id' => $galleryRule,

            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'hours' => ['nullable', 'string', 'max:120'],
            'maps_url' => ['nullable', 'url', 'max:500'],

            'footer_about' => ['nullable', 'string', 'max:2000'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'cta_title' => ['nullable', 'string', 'max:160'],
            'cta_subtitle' => ['nullable', 'string', 'max:1000'],
            'cta_primary_label' => ['nullable', 'string', 'max:80'],
            'cta_primary_url' => ['nullable', 'string', 'max:255'],
            'cta_secondary_label' => ['nullable', 'string', 'max:80'],
            'cta_secondary_url' => ['nullable', 'string', 'max:255'],
            'newsletter_title' => ['nullable', 'string', 'max:160'],
            'newsletter_subtitle' => ['nullable', 'string', 'max:1000'],
            'newsletter_cta' => ['nullable', 'string', 'max:80'],

            'seo_default_title' => ['nullable', 'string', 'max:160'],
            'seo_default_description' => ['nullable', 'string', 'max:500'],
            'seo_default_keywords' => ['nullable', 'string', 'max:500'],

            'social' => ['nullable', 'array'],
            ...collect($platforms)->mapWithKeys(fn ($p) => [
                "social.{$p}.url" => ['nullable', 'url', 'max:255'],
                "social.{$p}.is_visible" => ['nullable', 'boolean'],
            ])->all(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['logo_gallery_id', 'favicon_gallery_id', 'og_image_gallery_id'] as $field) {
            $merge[$field] = $this->filled($field) ? (int) $this->input($field) : null;
        }

        $social = $this->input('social', []);
        if (is_array($social)) {
            foreach ($social as $platform => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $social[$platform]['is_visible'] = filter_var($row['is_visible'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
            $merge['social'] = $social;
        }

        $this->merge($merge);
    }
}

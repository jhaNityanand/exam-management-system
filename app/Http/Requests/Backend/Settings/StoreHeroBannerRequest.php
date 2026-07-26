<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHeroBannerRequest extends FormRequest
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

        return [
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'badge_text' => ['nullable', 'string', 'max:120'],
            'primary_cta_label' => ['nullable', 'string', 'max:80'],
            'primary_cta_url' => ['nullable', 'string', 'max:255'],
            'secondary_cta_label' => ['nullable', 'string', 'max:80'],
            'secondary_cta_url' => ['nullable', 'string', 'max:255'],
            'image_id' => $galleryRule,
            'mobile_image_id' => $galleryRule,
            'theme' => ['nullable', 'string', 'max:40'],
            'show_search' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_search' => filter_var($this->input('show_search', true), FILTER_VALIDATE_BOOLEAN),
            'image_id' => $this->filled('image_id') ? (int) $this->input('image_id') : null,
            'mobile_image_id' => $this->filled('mobile_image_id') ? (int) $this->input('mobile_image_id') : null,
        ]);
    }
}

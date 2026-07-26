<?php

namespace App\Http\Requests\Backend\Advertisement;

use App\Support\AdvertisementCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvertisementRequest extends FormRequest
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
        $gallery = [
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
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(array_keys(AdvertisementCatalog::types()))],
            'placement' => ['required', Rule::in(AdvertisementCatalog::placementKeys())],
            'headline' => ['nullable', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:5000'],
            'code' => ['nullable', 'string', 'max:50000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'image_id' => $gallery,
            'mobile_image_id' => $gallery,
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'image_id' => $this->filled('image_id') ? (int) $this->input('image_id') : null,
            'mobile_image_id' => $this->filled('mobile_image_id') ? (int) $this->input('mobile_image_id') : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            if (in_array($type, ['google_ads', 'custom_html', 'iframe'], true) && blank($this->input('code'))) {
                $validator->errors()->add('code', 'Ad code / HTML is required for this type.');
            }
            if ($type === 'banner' && blank($this->input('image_id')) && blank($this->input('cta_url'))) {
                // Soft requirement: recommend image, don't hard-fail if headline-only promo
            }
        });
    }
}

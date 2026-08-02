<?php

namespace App\Http\Requests\Backend\Advertisement;

use App\Support\AdvertisementCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdPlacementRequest extends FormRequest
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
            'page_key' => ['required', Rule::in(AdvertisementCatalog::pageKeys())],
            'position_key' => ['required', 'string', 'max:80'],
            'source_type' => ['required', Rule::in([
                AdvertisementCatalog::SOURCE_GOOGLE,
                AdvertisementCatalog::SOURCE_CUSTOM,
            ])],
            'advertisement_id' => ['nullable', 'integer'],
            'google_advertisement_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'advertisement_id' => $this->filled('advertisement_id') ? (int) $this->input('advertisement_id') : null,
            'google_advertisement_id' => $this->filled('google_advertisement_id')
                ? (int) $this->input('google_advertisement_id')
                : null,
            'is_enabled' => $this->boolean('is_enabled', true),
        ]);
    }
}

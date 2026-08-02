<?php

namespace App\Http\Requests\Backend\Advertisement;

use App\Support\AdvertisementCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvertisementRequest extends FormRequest
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
        $orgId = current_organization_id();
        $type = $this->input('type');

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

        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:160'],
            'type' => ['required', Rule::in(AdvertisementCatalog::typeKeys())],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image_id' => $gallery,
            'target_url' => ['nullable', 'string', 'max:500'],
            'open_in_new_tab' => ['sometimes', 'boolean'],
            'banner_size' => ['nullable', Rule::in(AdvertisementCatalog::bannerSizeKeys())],
            'iframe_url' => ['nullable', 'string', 'max:1000'],
            'width' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'is_responsive' => ['sometimes', 'boolean'],
            'html_code' => ['nullable', 'string', 'max:100000'],
            'css_code' => ['nullable', 'string', 'max:100000'],
            'js_code' => ['nullable', 'string', 'max:100000'],
        ];

        if ($type === AdvertisementCatalog::TYPE_BANNER) {
            $rules['image_id'][0] = 'required';
            $rules['banner_size'] = ['required', Rule::in(AdvertisementCatalog::bannerSizeKeys())];
        }

        if ($type === AdvertisementCatalog::TYPE_IFRAME) {
            $rules['iframe_url'] = ['required', 'string', 'max:1000'];
            $rules['width'] = ['required', 'integer', 'min:1', 'max:5000'];
            $rules['height'] = ['required', 'integer', 'min:1', 'max:5000'];
        }

        if ($type === AdvertisementCatalog::TYPE_HTML) {
            $rules['html_code'] = ['required', 'string', 'max:100000'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'image_id' => $this->filled('image_id') ? (int) $this->input('image_id') : null,
            'open_in_new_tab' => filter_var($this->input('open_in_new_tab', true), FILTER_VALIDATE_BOOLEAN),
            'is_responsive' => filter_var($this->input('is_responsive', true), FILTER_VALIDATE_BOOLEAN),
            'title' => $this->filled('title') ? $this->input('title') : $this->input('name'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image_id.required' => 'Upload or select a banner image.',
            'banner_size.required' => 'Select a recommended banner size.',
            'iframe_url.required' => 'Enter the iframe URL.',
            'html_code.required' => 'HTML code is required for HTML advertisements.',
        ];
    }
}

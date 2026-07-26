<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFaqRequest extends FormRequest
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
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:10000'],
            'faq_category_id' => [
                'nullable',
                'integer',
                Rule::exists('faq_categories', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => filter_var($this->input('is_featured'), FILTER_VALIDATE_BOOLEAN),
            'faq_category_id' => $this->input('faq_category_id') === '' ? null : $this->input('faq_category_id'),
        ]);
    }
}

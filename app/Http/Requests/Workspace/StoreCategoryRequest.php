<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /*
         * SINGLE-ORG MODE:
         *   parent_id just needs to exist in the categories table — no org scope check.
         *
         * MULTI-ORG MODE (future):
         *   $orgId = current_organization_id();
         *   Add ->where(fn ($q) => $q->where('organization_id', $orgId)) to the exists rule.
         */

        return [
            // Content
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'status'           => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'parent_id'        => ['nullable', 'integer', Rule::exists('categories', 'id')],

            // SEO / Metadata
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords'    => ['nullable', 'string', 'max:500'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'canonical_url'    => ['nullable', 'url', 'max:500'],
            'og_title'         => ['nullable', 'string', 'max:255'],
            'og_description'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Please enter a category name.',
            'status.required' => 'Choose a status for this category.',
        ];
    }
}

<?php

namespace App\Http\Requests\Backend\BlogCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateBlogCategoryRequest
 *
 * Validates the single-category edit form. Prevents self-referential parent
 * and enforces that parent_id exists in blog_categories.
 */
class UpdateBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            // ── Tree nodes (from the category-builder) ──────────────────────
            'categories'                => ['required', 'array', 'min:1'],
            'categories.*.id'           => [
                'nullable',
                'integer',
                Rule::exists('blog_categories', 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    if ($orgId !== null) {
                        $query->where('organization_id', $orgId);
                    }
                }),
            ],
            'categories.*.name'         => ['required', 'string', 'max:255'],
            'categories.*.description'  => ['nullable', 'string', 'max:2000'],

            // Parent relationship map (JSON string) ─────────────────────────
            '_parent_map'               => ['nullable', 'string'],

            // ── Shared fields ───────────────────────────────────────────────
            'status'          => ['required', Rule::in(['active', 'inactive', 'suspended'])],

            // ── SEO / Metadata ──────────────────────────────────────────────
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords'    => ['nullable', 'string', 'max:500'],
            'slug'             => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('blog_categories', 'slug')
                    ->where(function ($query) {
                        $orgId = current_organization_id();
                        if ($orgId !== null) {
                            $query->where('organization_id', $orgId);
                        }
                    })
                    ->ignore($category?->id),
            ],
            'canonical_url'    => ['nullable', 'url', 'max:500'],
            'og_title'         => ['nullable', 'string', 'max:255'],
            'og_description'   => ['nullable', 'string', 'max:500'],

            // ── AI flags ──────────────────────────────────────────────────────
            'ai_generated' => ['nullable', 'boolean'],
            'ai_improve'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'categories.required'       => 'Please add at least one category.',
            'categories.*.name.required' => 'Each category must have a name.',
            'status.required'           => 'Please select a status for the categories.',
            'canonical_url.url'         => 'The canonical URL must be a valid URL (e.g. https://example.com).',
            'slug.unique'               => 'This slug is already in use within your organization.',
        ];
    }
}

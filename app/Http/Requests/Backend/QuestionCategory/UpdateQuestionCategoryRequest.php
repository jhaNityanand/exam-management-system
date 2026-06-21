<?php

namespace App\Http\Requests\Backend\QuestionCategory;

use App\Models\QuestionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateQuestionCategoryRequest
 *
 * Validates the single-category edit form. Prevents self-referential parent
 * and enforces that parent_id exists in question_categories.
 */
class UpdateQuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var QuestionCategory|null $category */
        $category = $this->route('category');

        // parent_id must exist in question_categories and must not be self
        $parentRules = [
            'nullable',
            'integer',
            Rule::exists('question_categories', 'id'),
        ];

        if ($category) {
            // Cannot set a category as its own parent
            $parentRules[] = Rule::notIn([$category->id]);
        }

        return [
            // ── Content ──────────────────────────────────────────────────────
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'parent_id'   => $parentRules,

            // ── SEO / Metadata ────────────────────────────────────────────────
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords'    => ['nullable', 'string', 'max:500'],
            'slug'             => ['nullable', 'string', 'max:255'],
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
            'name.required'       => 'Please enter a category name.',
            'status.required'     => 'Please select a status for this category.',
            'parent_id.not_in'   => 'A category cannot be its own parent.',
            'parent_id.exists'   => 'The selected parent category does not exist.',
            'canonical_url.url'  => 'The canonical URL must be a valid URL (e.g. https://example.com).',
        ];
    }
}

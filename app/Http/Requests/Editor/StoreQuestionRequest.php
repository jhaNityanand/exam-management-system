<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /*
         * SINGLE-ORG MODE:
         *   category_id just needs to exist in the categories table — no org scope check.
         *
         * MULTI-ORG MODE (future):
         *   $orgId = current_organization_id();
         *   Add ->where(fn ($q) => $q->where('organization_id', $orgId)) to the exists rule.
         */

        return [
            // Classification
            'category_id'      => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'type'             => ['required', Rule::in(['mcq', 'true_false', 'short_answer'])],
            'difficulty'       => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'marks'            => ['required', 'integer', 'min:1', 'max:100'],
            'status'           => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'previous_exam'    => ['nullable', 'string', 'max:255'],

            // Content
            'body'             => ['required', 'string'],
            'allows_multiple'  => ['sometimes', 'boolean'],
            'options'          => ['required_if:type,mcq', 'array', 'min:2'],
            'options.*.text'   => ['nullable', 'string', 'max:2000'],
            'correct_answer'   => ['required_without:correct_answers', 'nullable', 'string', 'max:500'],
            'correct_answers'  => ['nullable', 'array', 'min:1'],
            'correct_answers.*'=> ['string', 'max:500'],
            'explanation'      => ['nullable', 'string'],

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
            'body.required'       => 'Enter the question text.',
            'difficulty.required' => 'Select a difficulty level.',
            'marks.required'      => 'Set the marks for this question.',
        ];
    }
}

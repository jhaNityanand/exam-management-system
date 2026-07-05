<?php

namespace App\Http\Requests\Backend\Question;

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
        return [
            // Classification
            'category_id'      => ['nullable', 'integer', Rule::exists('question_categories', 'id')],
            'type'             => ['required', Rule::in(['mcq', 'true_false', 'short_answer'])],
            'difficulty'       => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'marks_type'       => ['required', Rule::in(['single', 'multiple'])],
            'marks'            => ['required_if:marks_type,single', 'nullable', 'integer', 'min:1', 'max:10'],
            'marks_list'       => ['required_if:marks_type,multiple', 'nullable', 'array', 'min:1'],
            'marks_list.*'     => ['integer', 'min:1', 'max:10'],
            'status'           => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'reference'        => ['nullable', 'string', 'max:255'],

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

            // AI Flags
            'ai_generated'     => ['nullable', 'boolean'],
            'ai_improve'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required'             => 'Enter the question text.',
            'difficulty.required'       => 'Select a difficulty level.',
            'marks.required_if'         => 'Select the marks for this question.',
            'marks_list.required_if'    => 'Select at least one mark value.',
            'category_id.exists'        => 'Select a valid category.',
        ];
    }
}

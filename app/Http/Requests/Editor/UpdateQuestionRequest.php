<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $orgId = current_organization_id();

        return [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'body' => ['required', 'string'],
            'type' => ['required', Rule::in(['mcq', 'true_false', 'short_answer'])],
            'allows_multiple' => ['sometimes', 'boolean'],
            'options' => ['required_if:type,mcq', 'array', 'min:2'],
            'options.*' => ['nullable', 'string', 'max:2000'],
            'correct_answer' => ['required_without:correct_answers', 'string', 'max:500'],
            'correct_answers' => ['nullable', 'array', 'min:1'],
            'correct_answers.*' => ['string', 'max:500'],
            'explanation' => ['nullable', 'string'],
            'marks' => ['required', 'integer', 'min:1', 'max:100'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}

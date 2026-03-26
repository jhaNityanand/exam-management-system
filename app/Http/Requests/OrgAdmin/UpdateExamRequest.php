<?php

namespace App\Http\Requests\OrgAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $orgId = current_organization_id();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'duration' => ['required', 'integer', 'min:1', 'max:480'],
            'pass_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'active', 'inactive', 'suspended'])],
            'scheduled_start' => ['nullable', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after_or_equal:scheduled_start'],
            'negative_mark_per_question' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'exam_mode' => ['nullable', 'string', 'max:32'],
            'category_question_rules' => ['nullable', 'array'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => [
                'integer',
                Rule::exists('questions', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
        ];
    }
}

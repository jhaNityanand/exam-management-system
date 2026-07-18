<?php

namespace App\Http\Requests\Backend\Question;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'import_question_id' => [
                'required',
                'integer',
                Rule::exists('import_questions', 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    if ($orgId !== null) {
                        $query->where('organization_id', $orgId);
                    }
                    $query->where('status', 'processing');
                }),
            ],
            'rows' => ['required', 'array', 'min:1', 'max:100'],
            'rows.*' => ['required', 'array'],
            'rows.*._row' => ['required', 'integer', 'min:2'],
            'rows.*.question' => ['required', 'string', 'max:20000'],
            'rows.*.type' => ['required', 'string', 'max:50'],
            'rows.*.category' => ['nullable', 'string', 'max:1000'],
            'rows.*.difficulty' => ['required', 'string', 'max:50'],
            'rows.*.marks_type' => ['nullable', 'string', 'max:50'],
            'rows.*.marks' => ['nullable'],
            'rows.*.option_a' => ['nullable', 'string', 'max:2000'],
            'rows.*.option_b' => ['nullable', 'string', 'max:2000'],
            'rows.*.option_c' => ['nullable', 'string', 'max:2000'],
            'rows.*.option_d' => ['nullable', 'string', 'max:2000'],
            'rows.*.option_e' => ['nullable', 'string', 'max:2000'],
            'rows.*.option_f' => ['nullable', 'string', 'max:2000'],
            'rows.*.correct_answer' => ['nullable', 'string', 'max:2000'],
            'rows.*.correct_answers' => ['nullable', 'string', 'max:2000'],
            'rows.*.explanation' => ['nullable', 'string', 'max:20000'],
            'rows.*.reference' => ['nullable', 'string', 'max:255'],
            'rows.*.status' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.max' => 'Each import request may contain at most 100 questions.',
            'rows.*.question.required' => 'Question text is required.',
            'rows.*._row.required' => 'The source row number is missing.',
        ];
    }
}

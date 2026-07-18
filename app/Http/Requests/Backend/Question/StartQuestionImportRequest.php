<?php

namespace App\Http\Requests\Backend\Question;

use Illuminate\Foundation\Http\FormRequest;

class StartQuestionImportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('initial_errors_json')) {
            return;
        }

        $decoded = json_decode((string) $this->input('initial_errors_json'), true);
        $this->merge([
            'initial_errors' => is_array($decoded) ? $decoded : [],
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:15360', 'extensions:xlsx,csv'],
            'total_rows' => ['required', 'integer', 'min:1', 'max:10000'],
            'failed_rows' => ['nullable', 'integer', 'min:0', 'lte:total_rows'],
            'initial_errors_json' => ['nullable', 'string'],
            'initial_errors' => ['nullable', 'array', 'max:10000'],
            'initial_errors.*.row' => ['required', 'integer', 'min:2'],
            'initial_errors.*.errors' => ['required', 'array', 'min:1'],
            'initial_errors.*.errors.*' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.extensions' => 'Only XLSX and CSV question files can be imported.',
        ];
    }
}

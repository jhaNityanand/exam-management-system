<?php

namespace App\Http\Requests\Backend\ExamCategory;

use App\Http\Requests\Concerns\CategoryTreeRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExamCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return CategoryTreeRules::update('exam_categories', $category?->id);
    }

    public function messages(): array
    {
        return CategoryTreeRules::messages();
    }
}

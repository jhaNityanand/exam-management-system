<?php

namespace App\Http\Requests\Backend\ExamCategory;

use App\Http\Requests\Concerns\CategoryTreeRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return CategoryTreeRules::store('exam_categories');
    }

    public function messages(): array
    {
        return CategoryTreeRules::messages();
    }
}

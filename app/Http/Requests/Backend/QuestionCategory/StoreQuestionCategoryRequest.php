<?php

namespace App\Http\Requests\Backend\QuestionCategory;

use App\Http\Requests\Concerns\CategoryTreeRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return CategoryTreeRules::store('question_categories');
    }

    public function messages(): array
    {
        return CategoryTreeRules::messages();
    }
}

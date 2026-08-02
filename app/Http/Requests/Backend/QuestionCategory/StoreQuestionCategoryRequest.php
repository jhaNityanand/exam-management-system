<?php

namespace App\Http\Requests\Backend\QuestionCategory;

use App\Http\Requests\Concerns\CategoryTreeRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::CONTENT);
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

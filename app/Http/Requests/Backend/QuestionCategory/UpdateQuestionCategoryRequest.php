<?php

namespace App\Http\Requests\Backend\QuestionCategory;

use App\Http\Requests\Concerns\CategoryTreeRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::CONTENT);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return CategoryTreeRules::update('question_categories', $category?->id);
    }

    public function messages(): array
    {
        return CategoryTreeRules::messages();
    }
}

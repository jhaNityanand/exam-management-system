<?php

namespace App\Http\Requests\Backend\NewsCategory;

use App\Http\Requests\Concerns\CategoryTreeRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return CategoryTreeRules::update('news_categories', $category?->id);
    }

    public function messages(): array
    {
        return CategoryTreeRules::messages();
    }
}

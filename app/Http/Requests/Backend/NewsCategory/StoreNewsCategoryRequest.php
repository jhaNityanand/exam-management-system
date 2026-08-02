<?php

namespace App\Http\Requests\Backend\NewsCategory;

use App\Http\Requests\Concerns\CategoryTreeRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreNewsCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::CONTENT);
    }

    public function rules(): array
    {
        return CategoryTreeRules::store('news_categories');
    }

    public function messages(): array
    {
        return CategoryTreeRules::messages();
    }
}

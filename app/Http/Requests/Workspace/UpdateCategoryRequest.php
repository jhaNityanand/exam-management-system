<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $orgId = current_organization_id();
        /** @var \App\Models\Category|null $category */
        $category = $this->route('category');
        $parentRules = [
            'nullable',
            'integer',
            Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
        ];
        if ($category) {
            $parentRules[] = Rule::notIn([$category->id]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'parent_id' => $parentRules,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a category name.',
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }
}

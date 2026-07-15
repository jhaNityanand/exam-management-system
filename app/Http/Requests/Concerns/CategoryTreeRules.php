<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Shared validation for hierarchical category tree create/update forms.
 */
class CategoryTreeRules
{
    public static function store(string $table): array
    {
        return array_merge(self::shared(), [
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.description' => ['nullable', 'string', 'max:2000'],
            'slug' => self::uniqueSlugRule($table),
        ]);
    }

    public static function update(string $table, mixed $ignoreId = null): array
    {
        return array_merge(self::shared(), [
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.id' => [
                'nullable',
                'integer',
                Rule::exists($table, 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    if ($orgId !== null) {
                        $query->where('organization_id', $orgId);
                    }
                }),
            ],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.description' => ['nullable', 'string', 'max:2000'],
            'slug' => self::uniqueSlugRule($table, $ignoreId),
        ]);
    }

    public static function messages(): array
    {
        return [
            'categories.required' => 'Please add at least one category.',
            'categories.*.name.required' => 'Each category must have a name.',
            'status.required' => 'Please select a status for the categories.',
            'canonical_url.url' => 'The canonical URL must be a valid URL (e.g. https://example.com).',
            'slug.unique' => 'This slug is already in use within your organization.',
        ];
    }

    protected static function shared(): array
    {
        return [
            '_parent_map' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'ai_generated' => ['nullable', 'boolean'],
            'ai_improve' => ['nullable', 'boolean'],
        ];
    }

    protected static function uniqueSlugRule(string $table, mixed $ignoreId = null): array
    {
        $rule = Rule::unique($table, 'slug')->where(function ($query) {
            $orgId = current_organization_id();
            if ($orgId !== null) {
                $query->where('organization_id', $orgId);
            }
        });

        if ($ignoreId !== null) {
            $rule = $rule->ignore($ignoreId);
        }

        return ['nullable', 'string', 'max:255', $rule];
    }
}

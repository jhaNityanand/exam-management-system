<?php

namespace App\Http\Requests\Backend\Blog;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('meta_title')) {
            $merge['seo_title'] = $this->input('meta_title');
        }
        if ($this->has('meta_description')) {
            $merge['seo_description'] = $this->input('meta_description');
        }
        if ($this->has('meta_keywords')) {
            $merge['seo_keywords'] = $this->input('meta_keywords');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $orgId = current_organization_id();

        $orgScoped = function (string $table) use ($orgId) {
            return Rule::exists($table, 'id')->where(function ($query) use ($orgId) {
                if ($orgId !== null) {
                    $query->where('organization_id', $orgId);
                }
            });
        };

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'blog_category_id' => ['nullable', 'integer', $orgScoped('blog_categories')],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'banner_image_id' => ['nullable', 'integer', $orgScoped('galleries')],
            'banner_ids' => ['nullable', 'array', 'max:12'],
            'banner_ids.*' => ['integer', $orgScoped('galleries')],
            'og_image_id' => ['nullable', 'integer', $orgScoped('galleries')],
            'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'author_name' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(array_keys(Blog::statuses()))],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'robots' => ['nullable', 'string', 'max:255'],
            'schema_markup' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['integer', $orgScoped('galleries')],
            'ai_generated' => ['nullable', 'boolean'],
            'ai_improve' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Enter a blog title.',
            'blog_category_id.exists' => 'Select a valid category.',
            'banner_image_id.exists' => 'Select a valid banner image.',
            'og_image_id.exists' => 'Select a valid OG image.',
            'attachment_ids.*.exists' => 'One or more attachments are invalid.',
        ];
    }
}

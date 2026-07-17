<?php

namespace App\Http\Requests\Backend\News;

use App\Models\News;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsRequest extends FormRequest
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

        foreach (['is_featured', 'is_breaking', 'is_trending', 'ai_generated', 'ai_improve'] as $flag) {
            if ($this->has($flag)) {
                $merge[$flag] = filter_var($this->input($flag), FILTER_VALIDATE_BOOLEAN);
            }
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
            'news_category_id' => ['nullable', 'integer', $orgScoped('news_categories')],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'banner_image_id' => ['nullable', 'integer', $orgScoped('galleries')],
            'banner_ids' => ['nullable', 'array', 'max:12'],
            'banner_ids.*' => ['integer', $orgScoped('galleries')],
            'featured_image_id' => ['nullable', 'integer', $orgScoped('galleries')],
            'og_image_id' => ['nullable', 'integer', $orgScoped('galleries')],
            'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'author_name' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(array_keys(News::statuses()))],
            'visibility' => ['sometimes', Rule::in(array_keys(News::visibilities()))],
            'is_featured' => ['nullable', 'boolean'],
            'is_breaking' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', Rule::when($this->filled('published_at'), ['after:published_at'])],
            'breaking_until' => ['nullable', 'date', Rule::when($this->filled('published_at'), ['after:published_at'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
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
            'title.required' => 'Enter a news title.',
            'news_category_id.exists' => 'Select a valid category.',
            'banner_image_id.exists' => 'Select a valid banner image.',
            'featured_image_id.exists' => 'Select a valid featured image.',
            'og_image_id.exists' => 'Select a valid SEO / OG image.',
            'attachment_ids.*.exists' => 'One or more attachments are invalid.',
            'expires_at.after' => 'Expiry date must be greater than the publish date.',
            'breaking_until.after' => 'Breaking News Until must be greater than the publish date.',
        ];
    }
}

<?php

namespace App\Services\Llm;

use App\Services\Llm\DTOs\SeoContent;
use Illuminate\Database\Eloquent\Model;

class SeoFieldMapper
{
    /**
     * Build a compact content payload for prompts.
     *
     * @return array<string, mixed>
     */
    public function contentPayload(Model $model, string $type): array
    {
        $title = (string) ($model->getAttribute('title')
            ?? $model->getAttribute('name')
            ?? $model->getAttribute('body')
            ?? '');

        $body = (string) ($model->getAttribute('content')
            ?? $model->getAttribute('description')
            ?? $model->getAttribute('excerpt')
            ?? $model->getAttribute('body')
            ?? '');

        return [
            'type' => $type,
            'id' => $model->getKey(),
            'title' => mb_substr(strip_tags($title), 0, 300),
            'summary' => mb_substr(strip_tags($body), 0, 1200),
            'slug' => $model->getAttribute('slug'),
            'status' => $model->getAttribute('status'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function existingSeoPayload(Model $model): array
    {
        $usesSeoPrefix = $this->usesSeoPrefix($model);

        return array_filter([
            'seo_title' => $usesSeoPrefix ? $model->getAttribute('seo_title') : $model->getAttribute('meta_title'),
            'meta_title' => $usesSeoPrefix ? $model->getAttribute('seo_title') : $model->getAttribute('meta_title'),
            'meta_description' => $usesSeoPrefix ? $model->getAttribute('seo_description') : $model->getAttribute('meta_description'),
            'keywords' => $usesSeoPrefix ? $model->getAttribute('seo_keywords') : $model->getAttribute('meta_keywords'),
            'og_title' => $model->getAttribute('og_title'),
            'og_description' => $model->getAttribute('og_description'),
            'canonical_url' => $model->getAttribute('canonical_url'),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Map generated SEO onto the model's column naming convention.
     * Does not set OG image — callers keep the existing/default image.
     *
     * @return array<string, mixed>
     */
    public function toModelAttributes(Model $model, SeoContent $seo): array
    {
        $attributes = [];

        if ($this->usesSeoPrefix($model)) {
            $attributes['seo_title'] = $seo->seoTitle ?? $seo->metaTitle;
            $attributes['seo_description'] = $seo->metaDescription;
            $attributes['seo_keywords'] = $seo->keywords;
        } else {
            $attributes['meta_title'] = $seo->metaTitle ?? $seo->seoTitle;
            $attributes['meta_description'] = $seo->metaDescription;
            $attributes['meta_keywords'] = $seo->keywords;
        }

        if ($this->hasColumn($model, 'og_title')) {
            $attributes['og_title'] = $seo->ogTitle ?? $seo->twitterTitle ?? $seo->metaTitle;
        }
        if ($this->hasColumn($model, 'og_description')) {
            $attributes['og_description'] = $seo->ogDescription ?? $seo->twitterDescription ?? $seo->metaDescription;
        }
        if ($this->hasColumn($model, 'canonical_url') && filled($seo->canonicalRecommendation)) {
            // Only set when empty so we do not overwrite an editor-chosen absolute URL.
            if (! filled($model->getAttribute('canonical_url'))) {
                $attributes['canonical_url'] = $seo->canonicalRecommendation;
            }
        }

        $attributes['is_ai_generated'] = true;

        return array_filter($attributes, static fn ($value) => $value !== null);
    }

    protected function usesSeoPrefix(Model $model): bool
    {
        return $this->hasColumn($model, 'seo_title');
    }

    protected function hasColumn(Model $model, string $column): bool
    {
        return in_array($column, $model->getFillable(), true)
            || array_key_exists($column, $model->getAttributes());
    }
}

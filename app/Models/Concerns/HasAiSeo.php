<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasAiSeo
{
    public static function bootHasAiSeo(): void
    {
        static::saving(function (Model $model) {
            // Re-queue when Improve With AI is newly enabled.
            if ($model->isDirty('ai_improve') && (bool) $model->getAttribute('ai_improve')) {
                $model->setAttribute('is_ai_generated', false);
            }

            // Re-queue when Create With AI is newly enabled.
            if ($model->isDirty('ai_generated')
                && (bool) $model->getAttribute('ai_generated')
                && ! (bool) $model->getOriginal('ai_generated')
            ) {
                $model->setAttribute('is_ai_generated', false);
            }

            // New/changed slugs need to be picked up by the next sitemap pass.
            if ($model->isFillable('is_sitemap_url_created') && $model->isDirty('slug')) {
                $model->setAttribute('is_sitemap_url_created', false);
            }
        });
    }

    public function scopePendingAiSeo(Builder $query): Builder
    {
        return $query
            ->where('is_ai_generated', false)
            ->where(function (Builder $inner) {
                $inner->where('ai_generated', true)
                    ->orWhere('ai_improve', true);
            });
    }

    public function scopePendingSitemap(Builder $query): Builder
    {
        return $query->where('is_sitemap_url_created', false);
    }

    public function prefersAiImprove(): bool
    {
        return (bool) $this->ai_improve && filled($this->existingSeoForImprove());
    }

    /**
     * @return array<string, mixed>
     */
    protected function existingSeoForImprove(): array
    {
        $fields = ['meta_title', 'seo_title', 'meta_description', 'seo_description', 'og_title'];

        foreach ($fields as $field) {
            if (filled($this->getAttribute($field))) {
                return [$field => $this->getAttribute($field)];
            }
        }

        return [];
    }
}

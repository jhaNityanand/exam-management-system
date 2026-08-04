<?php

namespace App\Services\Llm\DTOs;

final class SeoContent
{
    public function __construct(
        public readonly ?string $seoTitle = null,
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly ?string $keywords = null,
        public readonly ?string $ogTitle = null,
        public readonly ?string $ogDescription = null,
        public readonly ?string $twitterTitle = null,
        public readonly ?string $twitterDescription = null,
        public readonly ?string $canonicalRecommendation = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $metaTitle = self::stringOrNull($data['meta_title'] ?? $data['seo_title'] ?? null);
        $metaDescription = self::stringOrNull($data['meta_description'] ?? $data['seo_description'] ?? null);
        $keywords = self::stringOrNull($data['keywords'] ?? $data['meta_keywords'] ?? $data['seo_keywords'] ?? null);
        $ogTitle = self::stringOrNull($data['og_title'] ?? null) ?: $metaTitle;
        $ogDescription = self::stringOrNull($data['og_description'] ?? null) ?: $metaDescription;
        $twitterTitle = self::stringOrNull($data['twitter_title'] ?? null) ?: $ogTitle;
        $twitterDescription = self::stringOrNull($data['twitter_description'] ?? null) ?: $ogDescription;

        return new self(
            seoTitle: self::stringOrNull($data['seo_title'] ?? null) ?: $metaTitle,
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            keywords: $keywords,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            twitterTitle: $twitterTitle,
            twitterDescription: $twitterDescription,
            canonicalRecommendation: self::stringOrNull($data['canonical_recommendation'] ?? $data['canonical_url'] ?? null),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'seo_title' => $this->seoTitle,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'keywords' => $this->keywords,
            'og_title' => $this->ogTitle,
            'og_description' => $this->ogDescription,
            'twitter_title' => $this->twitterTitle,
            'twitter_description' => $this->twitterDescription,
            'canonical_recommendation' => $this->canonicalRecommendation,
        ];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}

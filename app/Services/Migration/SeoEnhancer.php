<?php

namespace App\Services\Migration;

use App\Services\Llm\LlmService;
use Illuminate\Support\Str;

class SeoEnhancer
{
    public function __construct(
        protected LlmService $llmService,
        protected ContentEnhancer $contentEnhancer
    ) {}

    /**
     * Enrich SEO metadata for a Blog post record.
     *
     * @param  array<string, mixed>  $blogData
     * @return array<string, mixed>
     */
    public function enhanceBlogSeo(array $blogData, string $baseUrl = ''): array
    {
        $title = (string) ($blogData['title'] ?? 'Untitled');
        $content = (string) ($blogData['content'] ?? '');
        $slug = (string) ($blogData['slug'] ?? Str::slug($title));
        $existingMetaTitle = (string) ($blogData['meta_title'] ?? '');
        $existingMetaDesc = (string) ($blogData['meta_description'] ?? '');
        $tags = (string) ($blogData['tags'] ?? '');

        // Try AI SEO generation via LlmService safely
        $aiSeo = $this->llmService->safely(function () use ($title, $content, $tags) {
            $excerpt = $this->contentEnhancer->generateExcerpt($content, 300);
            return $this->llmService->generateSEO([
                'title' => $title,
                'content' => $excerpt,
                'tags' => $tags,
            ]);
        });

        if ($aiSeo !== null) {
            $seoTitle = $aiSeo->metaTitle ?: $this->buildMetaTitle($title, $existingMetaTitle);
            $seoDesc = $aiSeo->metaDescription ?: $this->buildMetaDescription($content, $existingMetaDesc);
            if (is_array($aiSeo->keywords)) {
                $keywords = implode(', ', $aiSeo->keywords);
            } elseif (is_string($aiSeo->keywords) && trim($aiSeo->keywords) !== '') {
                $keywords = trim($aiSeo->keywords);
            } else {
                $keywords = $this->buildKeywords($title, $tags);
            }
            $ogTitle = $aiSeo->ogTitle ?: $title;
            $ogDesc = $aiSeo->ogDescription ?: $seoDesc;
            $isAi = true;
        } else {
            $seoTitle = $this->buildMetaTitle($title, $existingMetaTitle);
            $seoDesc = $this->buildMetaDescription($content, $existingMetaDesc);
            $keywords = $this->buildKeywords($title, $tags);
            $ogTitle = $title;
            $ogDesc = $seoDesc;
            $isAi = false;
        }

        $canonicalUrl = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/blog/' . mb_substr($slug, 0, 100) : null;

        return [
            'seo_title' => mb_substr(trim($seoTitle), 0, 185),
            'seo_description' => mb_substr(trim($seoDesc), 0, 500),
            'seo_keywords' => mb_substr(trim($keywords), 0, 185),
            'og_title' => mb_substr(trim($ogTitle), 0, 185),
            'og_description' => mb_substr(trim($ogDesc), 0, 500),
            'twitter_title' => mb_substr(trim($ogTitle), 0, 185),
            'twitter_description' => mb_substr(trim($ogDesc), 0, 500),
            'canonical_url' => $canonicalUrl ? mb_substr($canonicalUrl, 0, 185) : null,
            'robots' => 'index,follow',
            'is_ai_generated' => $isAi,
        ];
    }

    /**
     * Enrich SEO metadata for a Blog Category record.
     *
     * @param  array<string, mixed>  $categoryData
     * @return array<string, mixed>
     */
    public function enhanceCategorySeo(array $categoryData, string $baseUrl = ''): array
    {
        $name = (string) ($categoryData['name'] ?? 'Category');
        $desc = (string) ($categoryData['description'] ?? '');
        $slug = (string) ($categoryData['slug'] ?? Str::slug($name));

        $metaTitle = "{$name} Articles & Notes | Examtube";
        $metaDesc = trim($desc) !== ''
            ? strip_tags($desc)
            : "Explore latest articles, study notes, and questions about {$name} on Examtube.";
        $keywords = implode(', ', array_filter([$name, 'Examtube', 'Study Notes', 'Biology', 'Preparation']));

        $canonicalUrl = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/blog/category/' . mb_substr($slug, 0, 100) : null;

        return [
            'meta_title' => mb_substr(trim($metaTitle), 0, 185),
            'meta_description' => mb_substr(trim($metaDesc), 0, 500),
            'meta_keywords' => mb_substr(trim($keywords), 0, 185),
            'og_title' => mb_substr(trim($name), 0, 185),
            'og_description' => mb_substr(trim($metaDesc), 0, 500),
            'twitter_title' => mb_substr(trim($name), 0, 185),
            'twitter_description' => mb_substr(trim($metaDesc), 0, 500),
            'canonical_url' => $canonicalUrl ? mb_substr($canonicalUrl, 0, 185) : null,
            'robots' => 'index,follow',
        ];
    }

    protected function buildMetaTitle(string $title, string $existing): string
    {
        $cleanExisting = trim(strip_tags($existing));
        if ($cleanExisting !== '' && strlen($cleanExisting) >= 10 && strlen($cleanExisting) <= 70) {
            return $cleanExisting;
        }

        $base = trim($title);
        if (! str_contains(strtolower($base), 'examtube')) {
            $base .= ' | Examtube';
        }

        return $base;
    }

    protected function buildMetaDescription(string $content, string $existing): string
    {
        $cleanExisting = trim(strip_tags($existing));
        if ($cleanExisting !== '' && strlen($cleanExisting) >= 30) {
            return $cleanExisting;
        }

        return $this->contentEnhancer->generateExcerpt($content, 160);
    }

    protected function buildKeywords(string $title, string $tags): string
    {
        $termList = [];
        if ($tags !== '') {
            $exploded = explode(',', $tags);
            foreach ($exploded as $tag) {
                $t = trim($tag);
                // Filter out sentence tags (if tag is longer than 50 chars, treat as text)
                if ($t !== '' && strlen($t) <= 50) {
                    $termList[] = $t;
                }
            }
        }

        // Add words from title
        $words = preg_split('/\s+/', $title);
        foreach ($words as $word) {
            $w = trim(preg_replace('/[^a-zA-Z0-9-]/', '', $word));
            if (strlen($w) > 3 && ! in_array(strtolower($w), ['with', 'from', 'that', 'this', 'have', 'were'], true)) {
                $termList[] = $w;
            }
        }

        $unique = array_unique($termList);
        return implode(', ', array_slice($unique, 0, 8));
    }
}

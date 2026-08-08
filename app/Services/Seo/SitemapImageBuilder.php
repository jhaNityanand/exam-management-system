<?php

namespace App\Services\Seo;

use App\Models\Blog;
use App\Models\Cms\SitePage;
use App\Models\Exam;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Builds Google image-sitemap entries from gallery media attached to public pages.
 *
 * @phpstan-type SitemapImage array{loc: string, title: ?string, caption: ?string}
 */
class SitemapImageBuilder
{
    /**
     * @return list<SitemapImage>
     */
    public function forQuestion(Question $question): array
    {
        $title = $this->contentTitle($question);
        $caption = $this->contentCaption($question->meta_description ?? $question->body ?? null, $title);

        $images = [
            $this->fromGallery($question->ogImage, $title, $caption),
        ];

        foreach ([$question->body, $question->explanation] as $field) {
            if ($field && preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $field, $matches)) {
                foreach ($matches[1] as $src) {
                    $images[] = $this->fromUrl((string) $src, $title, $caption);
                }
            }
        }

        return $this->unique($images);
    }

    /**
     * @return list<SitemapImage>
     */
    public function forBlog(Blog $blog): array
    {
        $title = $this->contentTitle($blog);
        $caption = $this->contentCaption($blog->excerpt ?? null, $title);

        $images = [
            $this->fromGallery($blog->ogImage, $title, $caption),
            $this->fromGallery($blog->bannerImage, $title, $caption),
            ...$blog->banners->map(fn (Gallery $gallery) => $this->fromGallery($gallery, $title, $caption))->all(),
            ...$blog->galleryAttachments
                ->filter(fn (Gallery $gallery) => $this->isIndexableImage($gallery))
                ->map(fn (Gallery $gallery) => $this->fromGallery($gallery, $title, $caption))
                ->all(),
        ];

        if ($blog->content && preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $blog->content, $matches)) {
            foreach ($matches[1] as $src) {
                $images[] = $this->fromUrl((string) $src, $title, $caption);
            }
        }

        return $this->unique($images);
    }

    /**
     * @return list<SitemapImage>
     */
    public function forNews(News $news): array
    {
        $title = $this->contentTitle($news);
        $caption = $this->contentCaption($news->excerpt ?? null, $title);

        $images = [
            $this->fromGallery($news->ogImage, $title, $caption),
            $this->fromGallery($news->featuredImage, $title, $caption),
            $this->fromGallery($news->bannerImage, $title, $caption),
            ...$news->banners->map(fn (Gallery $gallery) => $this->fromGallery($gallery, $title, $caption))->all(),
            ...$news->galleryAttachments
                ->filter(fn (Gallery $gallery) => $this->isIndexableImage($gallery))
                ->map(fn (Gallery $gallery) => $this->fromGallery($gallery, $title, $caption))
                ->all(),
        ];

        if ($news->content && preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $news->content, $matches)) {
            foreach ($matches[1] as $src) {
                $images[] = $this->fromUrl((string) $src, $title, $caption);
            }
        }

        return $this->unique($images);
    }

    /**
     * @return list<SitemapImage>
     */
    public function forExam(Exam $exam): array
    {
        $title = $this->contentTitle($exam);
        $caption = $this->contentCaption($exam->meta_description ?? $exam->description ?? null, $title);

        $images = [
            $this->fromGallery($exam->ogImage, $title, $caption),
            $this->fromGallery($exam->bannerImage, $title, $caption),
        ];

        if ($exam->description && preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $exam->description, $matches)) {
            foreach ($matches[1] as $src) {
                $images[] = $this->fromUrl((string) $src, $title, $caption);
            }
        }

        return $this->unique($images);
    }

    /**
     * @return list<SitemapImage>
     */
    public function forCategory(Model $category): array
    {
        $title = $this->contentTitle($category);
        $caption = $this->contentCaption($category->description ?? $category->meta_description ?? null, $title);
        $og = $category->ogImage ?? null;

        return $this->unique([
            $this->fromGallery($og instanceof Gallery ? $og : null, $title, $caption),
        ]);
    }

    /**
     * @return list<SitemapImage>
     */
    public function forPage(SitePage $page): array
    {
        $title = $this->contentTitle($page);
        $caption = $this->contentCaption($page->excerpt ?? $page->seo_description ?? null, $title);

        $images = [
            $this->fromGallery($page->bannerImage, $title, $caption),
        ];

        if ($page->content && preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $page->content, $matches)) {
            foreach ($matches[1] as $src) {
                $images[] = $this->fromUrl((string) $src, $title, $caption);
            }
        }

        return $this->unique($images);
    }

    /**
     * @return SitemapImage|null
     */
    public function forGallery(Gallery $gallery, ?string $fallbackTitle = null): ?array
    {
        $title = filled($gallery->alt_text)
            ? (string) $gallery->alt_text
            : (filled($gallery->original_name) ? pathinfo((string) $gallery->original_name, PATHINFO_FILENAME) : ($fallbackTitle ?: 'Media asset'));

        $caption = filled($gallery->description)
            ? (string) $gallery->description
            : (filled($gallery->alt_text) ? (string) $gallery->alt_text : $title);

        return $this->fromGallery($gallery, $title, $caption);
    }

    /**
     * @return SitemapImage|null
     */
    public function fromUrl(string $url, string $fallbackTitle, ?string $fallbackCaption = null): ?array
    {
        $loc = trim($url);
        if ($loc === '') {
            return null;
        }
        if (str_starts_with($loc, '//')) {
            $loc = 'https:'.$loc;
        } elseif (str_starts_with($loc, '/')) {
            $loc = rtrim((string) config('app.url'), '/').$loc;
        }
        if (! str_starts_with($loc, 'http://') && ! str_starts_with($loc, 'https://')) {
            return null;
        }

        $title = $this->clip($fallbackTitle, 200);
        $caption = $this->clip($fallbackCaption ?: $fallbackTitle, 512);

        return [
            'loc' => $loc,
            'title' => $title !== '' ? $title : null,
            'caption' => $caption !== '' ? $caption : null,
        ];
    }

    /**
     * @return SitemapImage|null
     */
    public function fromGallery(?Gallery $gallery, string $fallbackTitle, ?string $fallbackCaption = null): ?array
    {
        if (! $gallery || ! $this->isIndexableImage($gallery)) {
            return null;
        }

        $loc = trim((string) ($gallery->file_url ?? ''));
        if ($loc === '') {
            return null;
        }
        if (str_starts_with($loc, '//')) {
            $loc = 'https:'.$loc;
        } elseif (str_starts_with($loc, '/')) {
            $loc = rtrim((string) config('app.url'), '/').$loc;
        }
        if (! str_starts_with($loc, 'http://') && ! str_starts_with($loc, 'https://')) {
            return null;
        }

        $title = $this->clip(
            filled($gallery->alt_text) ? (string) $gallery->alt_text : $fallbackTitle,
            200
        );
        $caption = $this->clip(
            filled($gallery->description)
                ? (string) $gallery->description
                : (filled($gallery->alt_text) ? (string) $gallery->alt_text : ($fallbackCaption ?: $fallbackTitle)),
            512
        );

        return [
            'loc' => $loc,
            'title' => $title !== '' ? $title : null,
            'caption' => $caption !== '' ? $caption : null,
        ];
    }

    public function isIndexableImage(Gallery $gallery): bool
    {
        if ($gallery->trashed()) {
            return false;
        }

        $kind = strtolower((string) ($gallery->kind ?: 'image'));
        if ($kind !== '' && $kind !== 'image') {
            return false;
        }

        $mime = strtolower((string) ($gallery->mime_type ?? ''));
        if ($mime !== '' && ! str_starts_with($mime, 'image/')) {
            return false;
        }

        return filled($gallery->file_path)
            || filled($gallery->original_file_path)
            || filled($gallery->file_url)
            || $gallery->displayPath() !== '';
    }

    /**
     * @param  list<SitemapImage|null>  $images
     * @return list<SitemapImage>
     */
    public function unique(array $images): array
    {
        $seen = [];
        $out = [];

        foreach ($images as $image) {
            if (! is_array($image) || empty($image['loc'])) {
                continue;
            }
            $key = strtolower((string) $image['loc']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'loc' => (string) $image['loc'],
                'title' => isset($image['title']) && $image['title'] !== '' ? (string) $image['title'] : null,
                'caption' => isset($image['caption']) && $image['caption'] !== '' ? (string) $image['caption'] : null,
            ];
        }

        return $out;
    }

    protected function contentTitle(Model $model): string
    {
        foreach (['seo_title', 'og_title', 'meta_title', 'title', 'name'] as $field) {
            $value = trim((string) ($model->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'Examtube';
    }

    protected function contentCaption(mixed $excerpt, string $fallback): string
    {
        $text = trim(strip_tags((string) ($excerpt ?? '')));
        if ($text === '') {
            return $fallback;
        }

        return Str::limit($text, 280);
    }

    protected function clip(string $value, int $max): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $value) ?? ''), $max, '');
    }
}

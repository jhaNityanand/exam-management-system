<?php

namespace Database\Seeders\Support;

use App\Models\Gallery;
use App\Services\GalleryService;
use App\Support\SeoImage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores seeded gallery media from production frontend defaults
 * (public/frontend/images/seo/*.png and public/images/brand/*).
 */
class SeedImageLibrary
{
    public function __construct(
        private readonly GalleryService $gallery = new GalleryService
    ) {}

    /**
     * Remove previously seeded gallery rows and their files for an organization.
     */
    public function purge(int $organizationId, ?string $module = null): int
    {
        $query = Gallery::query()
            ->withTrashed()
            ->where('organization_id', $organizationId)
            ->where(function ($builder) {
                $builder->where('source', 'seeder')
                    ->orWhere('original_name', 'like', 'img-%')
                    ->orWhere('file_name', 'like', 'img-%');
            });

        if ($module !== null) {
            $query->where('module', $module);
        }

        $removed = 0;
        foreach ($query->get() as $item) {
            $this->gallery->forceDelete($item);
            $removed++;
        }

        return $removed;
    }

    /**
     * Copy a typed SEO default PNG into the organization gallery.
     *
     * @param  array<string, mixed>  $meta
     */
    public function storeSeoDefault(
        int $organizationId,
        string $type,
        ?int $userId = null,
        string $module = 'content',
        array $meta = []
    ): Gallery {
        $key = SeoImage::TYPES[$type] ?? SeoImage::TYPES['default'];

        return $this->storeFromFrontend(
            $organizationId,
            'seo/'.$key.'.png',
            $userId,
            $module,
            $meta
        );
    }

    /**
     * Copy a file from public/frontend/images/{relativePath} into the gallery.
     *
     * @param  array<string, mixed>  $meta
     */
    public function storeFromFrontend(
        int $organizationId,
        string $relativePath,
        ?int $userId = null,
        string $module = 'content',
        array $meta = []
    ): Gallery {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolute = public_path('frontend/images/'.$relativePath);

        if (! is_file($absolute)) {
            throw new RuntimeException("Frontend image missing: public/frontend/images/{$relativePath}");
        }

        $contents = file_get_contents($absolute);
        if ($contents === false || $contents === '') {
            throw new RuntimeException("Unable to read frontend image: public/frontend/images/{$relativePath}");
        }

        $basename = basename($relativePath);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION) ?: 'png');
        $slug = Str::slug(pathinfo($basename, PATHINFO_FILENAME)) ?: 'seed-image';
        $unique = isset($meta['slug_suffix']) ? $slug.'-'.Str::slug((string) $meta['slug_suffix']) : $slug;
        $filename = 'img-'.$unique.'.'.$extension;
        $mime = match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return $this->gallery->uploadFromContents($contents, $filename, $organizationId, [
            'source' => 'seeder',
            'module' => $module,
            'kind' => 'image',
            'original_name' => $filename,
            'alt_text' => $meta['alt_text'] ?? Str::headline(str_replace('-', ' ', $slug)),
            'description' => $meta['description'] ?? 'Seeded demo media from frontend defaults',
            'uploaded_by' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'mime_type' => $mime,
        ]);
    }

    /**
     * @deprecated Use storeFromFrontend() / storeSeoDefault().
     *
     * @param  array<string, mixed>  $meta
     */
    public function storeFromPublicSeed(
        int $organizationId,
        string $relativePath,
        ?int $userId = null,
        string $module = 'content',
        array $meta = []
    ): Gallery {
        // Legacy seed paths under public/seed → map to SEO defaults.
        $mapped = $this->mapLegacySeedPath($relativePath);

        if ($mapped['kind'] === 'seo') {
            return $this->storeSeoDefault($organizationId, $mapped['type'], $userId, $module, $meta);
        }

        return $this->storeFromBrand($organizationId, $mapped['path'], $userId, $module, $meta);
    }

    /**
     * Copy a file from public/images/brand/{relativePath} into the gallery.
     *
     * @param  array<string, mixed>  $meta
     */
    public function storeFromBrand(
        int $organizationId,
        string $relativePath,
        ?int $userId = null,
        string $module = 'content',
        array $meta = []
    ): Gallery {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolute = public_path('images/brand/'.$relativePath);

        if (! is_file($absolute)) {
            throw new RuntimeException("Brand image missing: public/images/brand/{$relativePath}");
        }

        $contents = file_get_contents($absolute);
        if ($contents === false || $contents === '') {
            throw new RuntimeException("Unable to read brand image: public/images/brand/{$relativePath}");
        }

        $basename = basename($relativePath);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION) ?: 'svg');
        $slug = Str::slug(pathinfo($basename, PATHINFO_FILENAME)) ?: 'seed-brand';
        $unique = isset($meta['slug_suffix']) ? $slug.'-'.Str::slug((string) $meta['slug_suffix']) : $slug;
        $filename = 'img-'.$unique.'.'.$extension;
        $mime = match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return $this->gallery->uploadFromContents($contents, $filename, $organizationId, [
            'source' => 'seeder',
            'module' => $module,
            'kind' => 'image',
            'original_name' => $filename,
            'alt_text' => $meta['alt_text'] ?? Str::headline(str_replace('-', ' ', $slug)),
            'description' => $meta['description'] ?? 'Seeded demo media from brand assets',
            'uploaded_by' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'mime_type' => $mime,
        ]);
    }

    /**
     * Store a gallery image for a content slug using a typed SEO default.
     *
     * @param  array<string, mixed>  $meta
     */
    public function store(
        int $organizationId,
        string $slug,
        ?int $userId = null,
        string $module = 'content',
        array $meta = []
    ): Gallery {
        if (! empty($meta['frontend_path'])) {
            return $this->storeFromFrontend(
                $organizationId,
                (string) $meta['frontend_path'],
                $userId,
                $module,
                array_merge($meta, ['slug_suffix' => $slug])
            );
        }

        if (! empty($meta['seed_path'])) {
            return $this->storeFromPublicSeed(
                $organizationId,
                (string) $meta['seed_path'],
                $userId,
                $module,
                array_merge($meta, ['slug_suffix' => $slug])
            );
        }

        $type = (string) ($meta['seo_type'] ?? match ($module) {
            'blog' => 'blog',
            'news' => 'news',
            'exam', 'demo-media' => 'exam',
            'profile' => 'profile',
            default => 'home',
        });

        return $this->storeSeoDefault(
            $organizationId,
            $type,
            $userId,
            $module,
            array_merge($meta, ['slug_suffix' => $slug])
        );
    }

    /**
     * @return array{kind:'seo',type:string}|array{kind:'file',path:string}
     */
    private function mapLegacySeedPath(string $relativePath): array
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        return match (true) {
            str_starts_with($relativePath, 'brand/logo') => ['kind' => 'file', 'path' => 'logo.svg'],
            str_starts_with($relativePath, 'brand/favicon') => ['kind' => 'file', 'path' => 'favicon.svg'],
            str_starts_with($relativePath, 'brand/og') => ['kind' => 'seo', 'type' => 'home'],
            str_starts_with($relativePath, 'heroes/') => ['kind' => 'seo', 'type' => 'home'],
            str_starts_with($relativePath, 'exams/') => ['kind' => 'seo', 'type' => 'exam'],
            str_starts_with($relativePath, 'ads/') => ['kind' => 'seo', 'type' => 'home'],
            str_starts_with($relativePath, 'partners/') => ['kind' => 'seo', 'type' => 'organization'],
            str_starts_with($relativePath, 'avatars/') => ['kind' => 'seo', 'type' => 'profile'],
            str_starts_with($relativePath, 'gallery/') => ['kind' => 'seo', 'type' => 'organization'],
            str_contains($relativePath, 'page-about') => ['kind' => 'seo', 'type' => 'about'],
            str_contains($relativePath, 'page-contact') => ['kind' => 'seo', 'type' => 'contact'],
            str_contains($relativePath, 'page-privacy') => ['kind' => 'seo', 'type' => 'privacy'],
            str_contains($relativePath, 'page-terms') => ['kind' => 'seo', 'type' => 'terms'],
            str_contains($relativePath, 'page-help'),
            str_contains($relativePath, 'page-careers') => ['kind' => 'seo', 'type' => 'about'],
            default => ['kind' => 'seo', 'type' => 'home'],
        };
    }
}

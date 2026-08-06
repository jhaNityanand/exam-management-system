<?php

namespace App\Services\Migration;

use App\Models\Gallery;
use App\Services\GalleryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LegacyImageImporter
{
    /** @var list<string> */
    protected array $searchDirectories = [];

    public function __construct(
        protected GalleryService $galleryService
    ) {
        $this->searchDirectories = array_filter([
            base_path('public/old-application/images'),
            base_path('public/old-examtube/images'),
            base_path('public/old-application'),
            base_path('public/old-examtube'),
        ], 'is_dir');
    }

    /**
     * Import a legacy image by path or URL into central Gallery storage.
     *
     * @param  array<string, mixed>  $meta
     */
    public function importImage(string $imageRef, int $organizationId, array $meta = []): ?Gallery
    {
        $localPath = $this->resolveLocalImagePath($imageRef);
        if (! $localPath || ! file_exists($localPath) || ! is_file($localPath)) {
            Log::warning("LegacyImageImporter: Source image file not found for '{$imageRef}'");
            return null;
        }

        $contents = file_get_contents($localPath);
        if ($contents === false || strlen($contents) === 0) {
            return null;
        }

        $filename = basename($localPath);
        $module = $meta['module'] ?? 'blog';
        $folder = $meta['folder'] ?? 'gallery';

        try {
            return $this->galleryService->uploadFromContents(
                $contents,
                $filename,
                $organizationId,
                array_merge([
                    'source' => 'import',
                    'module' => $module,
                    'folder' => $folder,
                    'alt_text' => $meta['alt_text'] ?? pathinfo($filename, PATHINFO_FILENAME),
                ], $meta)
            );
        } catch (\Throwable $e) {
            Log::error("LegacyImageImporter error importing '{$imageRef}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Rewrite image URLs inside HTML content to point to newly imported Gallery files.
     *
     * @return array{content: string, imported_count: int}
     */
    public function rewriteContentImages(string $html, int $organizationId): array
    {
        if (trim($html) === '') {
            return ['content' => $html, 'imported_count' => 0];
        }

        $importedCount = 0;
        $pattern = '/<img\b([^>]*?)\bsrc=["\']([^"\']+)["\']([^>]*?)>/i';

        $updatedHtml = preg_replace_callback($pattern, function ($matches) use ($organizationId, &$importedCount) {
            $prefix = $matches[1];
            $srcUrl = $matches[2];
            $suffix = $matches[3];

            // If it's already a modern storage path or app URL, keep it
            if (str_contains($srcUrl, '/storage/') || str_contains($srcUrl, '/gallery/')) {
                return $matches[0];
            }

            $gallery = $this->importImage($srcUrl, $organizationId, [
                'source' => 'import',
                'module' => 'blog',
                'folder' => 'content',
            ]);

            if ($gallery && $gallery->file_url) {
                $importedCount++;
                return '<img' . $prefix . 'src="' . e($gallery->file_url) . '"' . $suffix . '>';
            }

            return $matches[0];
        }, $html) ?? $html;

        return [
            'content' => $updatedHtml,
            'imported_count' => $importedCount,
        ];
    }

    /**
     * Resolve legacy relative image path or URL to an absolute filesystem path.
     */
    public function resolveLocalImagePath(string $imageRef): ?string
    {
        $cleanRef = trim($imageRef);
        if ($cleanRef === '') {
            return null;
        }

        // If it's a URL like https://examtube.in/public/assets/images/content/xyz.webp
        if (str_starts_with($cleanRef, 'http://') || str_starts_with($cleanRef, 'https://')) {
            $pathParts = parse_url($cleanRef, PHP_URL_PATH);
            $cleanRef = is_string($pathParts) ? ltrim($pathParts, '/') : basename($cleanRef);
        }

        // Strip prefixes like "public/assets/images/" or "assets/images/"
        $cleanRef = preg_replace('/^(public\/)?(assets\/)?images\//', '', $cleanRef) ?? $cleanRef;
        $cleanRef = ltrim($cleanRef, '/');

        // Check search directories
        foreach ($this->searchDirectories as $dir) {
            $candidate = $dir . '/' . $cleanRef;
            if (file_exists($candidate) && is_file($candidate)) {
                return $candidate;
            }

            // Check basename inside subfolders like content/, blog/, category/
            $filename = basename($cleanRef);
            $subFolders = ['blog', 'content', 'category', 'users', 'banner', 'ads'];
            foreach ($subFolders as $sub) {
                $subCandidate = $dir . '/' . $sub . '/' . $filename;
                if (file_exists($subCandidate) && is_file($subCandidate)) {
                    return $subCandidate;
                }
            }

            // Check directly in dir root
            $rootCandidate = $dir . '/' . $filename;
            if (file_exists($rootCandidate) && is_file($rootCandidate)) {
                return $rootCandidate;
            }
        }

        return null;
    }
}

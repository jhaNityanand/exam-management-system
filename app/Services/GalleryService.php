<?php

namespace App\Services;

use App\Models\Gallery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryService
{
    public function paginate(int $organizationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? config('gallery.per_page_default', 24));
        $allowed = config('gallery.per_page_options', [12, 24, 48, 96]);
        if (! in_array($perPage, $allowed, true)) {
            $perPage = (int) config('gallery.per_page_default', 24);
        }

        $query = Gallery::query()->forOrg($organizationId)->with(['uploader:id,name']);

        $trash = ($filters['trash'] ?? 'active') === 'bin';
        if ($trash) {
            $query->onlyTrashed();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['kind']) && $filters['kind'] !== 'all') {
            $query->where('kind', $filters['kind']);
        }

        if (! empty($filters['mime'])) {
            $query->where('mime_type', 'like', $filters['mime'].'%');
        }

        if (! empty($filters['folder'])) {
            $query->where('folder', $filters['folder']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'name_asc' => $query->orderBy('original_name'),
            'name_desc' => $query->orderByDesc('original_name'),
            'size_asc' => $query->orderBy('file_size'),
            'size_desc' => $query->orderByDesc('file_size'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function stats(int $organizationId): array
    {
        return [
            'total' => Gallery::query()->forOrg($organizationId)->count(),
            'images' => Gallery::query()->forOrg($organizationId)->where('kind', 'image')->count(),
            'videos' => Gallery::query()->forOrg($organizationId)->where('kind', 'video')->count(),
            'documents' => Gallery::query()->forOrg($organizationId)->where('kind', 'document')->count(),
            'bin' => Gallery::query()->forOrg($organizationId)->onlyTrashed()->count(),
            'total_bytes' => (int) Gallery::query()->forOrg($organizationId)->sum('file_size'),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function upload(UploadedFile $file, int $organizationId, array $meta = []): Gallery
    {
        $kind = $this->detectKind($file, $meta['kind'] ?? null);
        $disk = (string) config('gallery.disk', 'public');
        $directory = trim((string) config('gallery.directory', 'gallery'), '/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $baseSlug = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $unique = Str::uuid()->toString();
        $fileName = "{$unique}_{$baseSlug}.{$extension}";
        $relativeDir = sprintf('%s/%d/%s', $directory, $organizationId, now()->format('Y/m'));
        $relativePath = "{$relativeDir}/{$fileName}";

        Storage::disk($disk)->putFileAs($relativeDir, $file, $fileName);

        if (! Storage::disk($disk)->exists($relativePath)) {
            throw new \RuntimeException('Failed to store gallery file on disk.');
        }

        [$width, $height] = $this->readImageDimensions($file, $kind);

        return Gallery::create([
            'organization_id' => $organizationId,
            'parent_id' => $meta['parent_id'] ?? null,
            'variant' => $meta['variant'] ?? Gallery::VARIANT_ORIGINAL,
            'original_name' => $meta['original_name'] ?? $file->getClientOriginalName(),
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_url' => $this->publicUrl($disk, $relativePath),
            'file_extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'kind' => $kind,
            'file_size' => $file->getSize() ?: 0,
            'width' => $width,
            'height' => $height,
            'folder' => $meta['folder'] ?? $directory,
            'disk' => $disk,
            'alt_text' => $meta['alt_text'] ?? null,
            'description' => $meta['description'] ?? null,
            'status' => 'active',
            'source' => $meta['source'] ?? 'gallery',
            'uploaded_by' => Auth::id(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Editor upload: always stores a gallery row for the insertable file.
     * When an original file is also provided (images), stores two rows:
     * original + adjusted, linked by parent_id.
     *
     * @param  array<string, mixed>  $meta
     * @return array{primary: Gallery, original: Gallery, adjusted: ?Gallery}
     */
    public function uploadForEditor(UploadedFile $file, int $organizationId, ?UploadedFile $original = null, array $meta = []): array
    {
        $meta = array_merge($meta, [
            'source' => 'editor',
            'folder' => $meta['folder'] ?? config('gallery.directory', 'gallery'),
        ]);

        $kind = $this->detectKind($file, $meta['kind'] ?? null);
        $isImagePair = $kind === 'image' && $original instanceof UploadedFile && $original->isValid();

        if ($isImagePair) {
            $originalRow = $this->upload($original, $organizationId, array_merge($meta, [
                'kind' => 'image',
                'variant' => Gallery::VARIANT_ORIGINAL,
                'parent_id' => null,
            ]));

            $adjustedRow = $this->upload($file, $organizationId, array_merge($meta, [
                'kind' => 'image',
                'variant' => Gallery::VARIANT_ADJUSTED,
                'parent_id' => $originalRow->id,
                'original_name' => (string) ($meta['display_name']
                    ?? $original->getClientOriginalName()
                    ?? $file->getClientOriginalName()),
            ]));

            return [
                'primary' => $adjustedRow->fresh(),
                'original' => $originalRow->fresh(),
                'adjusted' => $adjustedRow->fresh(),
            ];
        }

        // Non-image or single-file upload: one original row.
        $row = $this->upload($file, $organizationId, array_merge($meta, [
            'variant' => Gallery::VARIANT_ORIGINAL,
            'parent_id' => null,
        ]));

        return [
            'primary' => $row,
            'original' => $row,
            'adjusted' => null,
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return list<Gallery>
     */
    public function uploadMany(array $files, int $organizationId, array $meta = []): array
    {
        $created = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $created[] = $this->upload($file, $organizationId, $meta);
            }
        }

        return $created;
    }

    public function rename(Gallery $gallery, string $newName): Gallery
    {
        $newName = trim($newName);
        if ($newName === '') {
            throw new \InvalidArgumentException('File name cannot be empty.');
        }

        // Keep extension if user omitted it.
        $extension = $gallery->file_extension;
        if ($extension && ! str_ends_with(strtolower($newName), '.'.strtolower($extension))) {
            $newName .= '.'.$extension;
        }

        $gallery->update([
            'original_name' => $newName,
            'alt_text' => $gallery->alt_text ?: pathinfo($newName, PATHINFO_FILENAME),
        ]);

        return $gallery->fresh(['uploader']);
    }

    public function updateMeta(Gallery $gallery, array $data): Gallery
    {
        $gallery->update(array_filter([
            'alt_text' => $data['alt_text'] ?? $gallery->alt_text,
            'description' => $data['description'] ?? $gallery->description,
            'status' => $data['status'] ?? $gallery->status,
        ], static fn ($v) => $v !== null));

        return $gallery->fresh(['uploader']);
    }

    public function softDelete(Gallery $gallery): Gallery
    {
        if ($gallery->trashed()) {
            return $gallery;
        }

        return DB::transaction(function () use ($gallery) {
            $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');
            $binRoot = trim((string) config('gallery.bin_directory', 'bin'), '/');
            $currentPath = $gallery->file_path;

            $binPath = sprintf(
                '%s/%d/%s/%s',
                $binRoot,
                $gallery->organization_id,
                now()->format('Y/m'),
                basename($currentPath)
            );

            // Ensure unique bin name if collision.
            if (Storage::disk($disk)->exists($binPath)) {
                $binPath = sprintf(
                    '%s/%d/%s/%s_%s',
                    $binRoot,
                    $gallery->organization_id,
                    now()->format('Y/m'),
                    Str::uuid()->toString(),
                    basename($currentPath)
                );
            }

            if (Storage::disk($disk)->exists($currentPath)) {
                Storage::disk($disk)->makeDirectory(dirname($binPath));
                try {
                    Storage::disk($disk)->move($currentPath, $binPath);
                } catch (\Throwable) {
                    // Fallback copy+delete for drivers that dislike move.
                    Storage::disk($disk)->copy($currentPath, $binPath);
                    Storage::disk($disk)->delete($currentPath);
                }
            }

            $gallery->forceFill([
                'original_path' => $gallery->original_path ?: $currentPath,
                'bin_path' => $binPath,
                'file_path' => $binPath,
                'file_url' => $this->publicUrl($disk, $binPath),
                'updated_by' => Auth::id(),
            ])->save();

            $gallery->delete();

            // Soft-delete linked adjusted variants with the original.
            if (! $gallery->parent_id) {
                Gallery::query()
                    ->where('parent_id', $gallery->id)
                    ->get()
                    ->each(fn (Gallery $child) => $this->softDelete($child));
            }

            return $gallery->fresh();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function softDeleteMany(int $organizationId, array $ids): int
    {
        $items = Gallery::query()->forOrg($organizationId)->whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $item) {
            $this->softDelete($item);
            $count++;
        }

        return $count;
    }

    public function restore(Gallery $gallery): Gallery
    {
        if (! $gallery->trashed()) {
            return $gallery;
        }

        return DB::transaction(function () use ($gallery) {
            $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');
            $binPath = $gallery->bin_path ?: $gallery->file_path;
            $restorePath = $gallery->original_path;

            if (! $restorePath) {
                $directory = trim((string) config('gallery.directory', 'gallery'), '/');
                $restorePath = sprintf(
                    '%s/%d/%s/%s',
                    $directory,
                    $gallery->organization_id,
                    now()->format('Y/m'),
                    $gallery->file_name
                );
            }

            if (Storage::disk($disk)->exists($restorePath) && $restorePath !== $binPath) {
                $restorePath = sprintf(
                    '%s/%s_%s',
                    dirname($restorePath),
                    Str::uuid()->toString(),
                    basename($restorePath)
                );
            }

            if ($binPath && Storage::disk($disk)->exists($binPath)) {
                Storage::disk($disk)->makeDirectory(dirname($restorePath));
                Storage::disk($disk)->move($binPath, $restorePath);
            }

            $gallery->restore();

            $gallery->forceFill([
                'file_path' => $restorePath,
                'file_url' => $this->publicUrl($disk, $restorePath),
                'bin_path' => null,
                'restored_at' => now(),
                'updated_by' => Auth::id(),
            ])->save();

            return $gallery->fresh(['uploader']);
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function restoreMany(int $organizationId, array $ids): int
    {
        $items = Gallery::query()->forOrg($organizationId)->onlyTrashed()->whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $item) {
            $this->restore($item);
            $count++;
        }

        return $count;
    }

    public function forceDelete(Gallery $gallery): bool
    {
        return DB::transaction(function () use ($gallery) {
            $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');
            $paths = array_filter([
                $gallery->file_path,
                $gallery->bin_path,
                $gallery->original_path,
            ]);

            foreach (array_unique($paths) as $path) {
                if ($path && Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            }

            return (bool) $gallery->forceDelete();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(int $organizationId, array $ids): int
    {
        $items = Gallery::query()->forOrg($organizationId)->withTrashed()->whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $item) {
            $this->forceDelete($item);
            $count++;
        }

        return $count;
    }

    public function toArray(Gallery $gallery): array
    {
        return [
            'id' => $gallery->id,
            'parent_id' => $gallery->parent_id,
            'variant' => $gallery->variant ?: Gallery::VARIANT_ORIGINAL,
            'original_name' => $gallery->original_name,
            'file_name' => $gallery->file_name,
            'file_path' => $gallery->file_path,
            'file_url' => $this->freshPublicUrl($gallery),
            'file_extension' => $gallery->file_extension,
            'mime_type' => $gallery->mime_type,
            'kind' => $gallery->kind,
            'file_size' => $gallery->file_size,
            'human_size' => $gallery->humanSize(),
            'width' => $gallery->width,
            'height' => $gallery->height,
            'dimensions' => $gallery->dimensionsLabel(),
            'folder' => $gallery->folder,
            'alt_text' => $gallery->alt_text,
            'description' => $gallery->description,
            'status' => $gallery->status,
            'source' => $gallery->source,
            'uploaded_by' => $gallery->uploaded_by,
            'uploader_name' => $gallery->uploader?->name,
            'created_at' => optional($gallery->created_at)?->toIso8601String(),
            'created_at_human' => optional($gallery->created_at)?->diffForHumans(),
            'created_at_formatted' => optional($gallery->created_at)?->format('M j, Y g:i A'),
            'deleted_at' => optional($gallery->deleted_at)?->toIso8601String(),
            'is_trashed' => $gallery->trashed(),
            'is_image' => $gallery->isImage(),
            'is_adjusted' => $gallery->isAdjusted(),
        ];
    }

    /**
     * TinyMCE / editor JSON shape.
     *
     * @param  array{primary: Gallery, original: Gallery, adjusted: ?Gallery}  $pair
     * @return array<string, mixed>
     */
    public function editorUploadResponse(array $pair): array
    {
        $primary = $pair['primary'];
        $original = $pair['original'];
        $adjusted = $pair['adjusted'];
        $location = $this->freshPublicUrl($primary);

        return [
            'location' => $location,
            'url' => $location,
            'id' => $primary->id,
            'kind' => $primary->kind,
            'name' => $primary->original_name,
            'mime' => $primary->mime_type,
            'size' => $primary->file_size,
            'width' => $primary->width,
            'height' => $primary->height,
            'original' => [
                'id' => $original->id,
                'url' => $this->freshPublicUrl($original),
                'path' => $original->file_path,
                'size' => $original->file_size,
                'width' => $original->width,
                'height' => $original->height,
            ],
            'adjusted' => $adjusted ? [
                'id' => $adjusted->id,
                'url' => $this->freshPublicUrl($adjusted),
                'path' => $adjusted->file_path,
                'size' => $adjusted->file_size,
                'width' => $adjusted->width,
                'height' => $adjusted->height,
            ] : null,
        ];
    }

    /**
     * Extract public storage URLs that belong to gallery media.
     *
     * @return list<string>
     */
    public function extractUrlsFromHtml(?string $html): array
    {
        if (! is_string($html) || $html === '') {
            return [];
        }

        $urls = [];
        $patterns = [
            '/src=["\']([^"\']+)["\']/i',
            '/href=["\']([^"\']+)["\']/i',
            '/data=["\']([^"\']+)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $url) {
                    $normalized = $this->normalizeUrl($url);
                    if ($normalized && $this->looksLikeGalleryUrl($normalized)) {
                        $urls[] = $normalized;
                    }
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param  list<string>|string|null  $htmlParts
     */
    public function syncForModel(Model $model, array|string|null $htmlParts, ?int $organizationId = null): void
    {
        $orgId = $organizationId
            ?? (int) ($model->getAttribute('organization_id') ?? current_organization_id() ?? 0);

        if ($orgId < 1) {
            return;
        }

        $parts = is_array($htmlParts) ? $htmlParts : [$htmlParts];
        $urls = [];
        foreach ($parts as $part) {
            if (is_array($part)) {
                $urls = array_merge($urls, $this->extractUrlsFromValue($part));
            } else {
                $urls = array_merge($urls, $this->extractUrlsFromHtml(is_string($part) ? $part : null));
            }
        }
        $urls = array_values(array_unique($urls));

        $previous = Gallery::query()
            ->where('organization_id', $orgId)
            ->where('attachable_type', $model->getMorphClass())
            ->where('attachable_id', $model->getKey())
            ->get();

        $keepIds = [];

        if ($urls) {
            $matched = Gallery::query()
                ->where('organization_id', $orgId)
                ->where(function ($query) use ($urls) {
                    foreach ($urls as $url) {
                        $path = ltrim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
                        $query->orWhere('file_url', $url)
                            ->orWhere('file_url', 'like', '%'.($path !== '' ? $path : $url).'%')
                            ->orWhere('file_path', $path)
                            ->orWhere('file_path', 'like', '%'.basename($path).'%');
                    }
                })
                ->get();

            foreach ($matched as $media) {
                $media->forceFill([
                    'attachable_type' => $model->getMorphClass(),
                    'attachable_id' => $model->getKey(),
                    'last_referenced_at' => now(),
                ])->save();
                $keepIds[] = $media->id;

                // Keep linked original/adjusted siblings attached together.
                if ($media->parent_id) {
                    $parent = Gallery::query()->find($media->parent_id);
                    if ($parent) {
                        $parent->forceFill([
                            'attachable_type' => $model->getMorphClass(),
                            'attachable_id' => $model->getKey(),
                            'last_referenced_at' => now(),
                        ])->save();
                        $keepIds[] = $parent->id;
                    }
                }

                Gallery::query()
                    ->where('parent_id', $media->id)
                    ->get()
                    ->each(function (Gallery $child) use ($model, &$keepIds) {
                        $child->forceFill([
                            'attachable_type' => $model->getMorphClass(),
                            'attachable_id' => $model->getKey(),
                            'last_referenced_at' => now(),
                        ])->save();
                        $keepIds[] = $child->id;
                    });
            }
        }

        foreach ($previous as $media) {
            if (in_array($media->id, $keepIds, true)) {
                continue;
            }

            $media->forceFill([
                'attachable_type' => null,
                'attachable_id' => null,
            ])->save();
        }
    }

    public function purgeForModel(Model $model): void
    {
        $items = Gallery::query()
            ->where('attachable_type', $model->getMorphClass())
            ->where('attachable_id', $model->getKey())
            ->get();

        foreach ($items as $media) {
            try {
                if (! $media->trashed()) {
                    $this->softDelete($media);
                }
            } catch (\Throwable) {
                // Continue detaching even if bin move fails.
            }
        }
    }

    public function pruneOrphans(?int $olderThanHours = null): int
    {
        $hours = $olderThanHours ?? (int) config('editor.orphan_ttl_hours', 24);
        $cutoff = now()->subHours(max(1, $hours));

        $orphans = Gallery::query()
            ->where('source', 'editor')
            ->whereNull('attachable_id')
            ->where('created_at', '<', $cutoff)
            ->whereNull('parent_id') // prune from originals; softDelete cascades adjusted
            ->get();

        $count = 0;
        foreach ($orphans as $media) {
            try {
                $this->softDelete($media);
                $count++;
            } catch (\Throwable) {
                // skip
            }
        }

        return $count;
    }

    /**
     * Remove Base64 data-URI embeds so HTML never stores raw file payloads.
     */
    public function stripDataUris(?string $html): ?string
    {
        if (! is_string($html) || $html === '') {
            return $html;
        }

        $cleaned = preg_replace(
            '/\s(?:src|href)=([\'"])data:[^\'"]*\1/i',
            '',
            $html
        );

        return is_string($cleaned) ? $cleaned : $html;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public function sanitizeHtmlFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            if (is_string($data[$field])) {
                $data[$field] = $this->stripDataUris($data[$field]);
            } elseif (is_array($data[$field])) {
                $data[$field] = $this->sanitizeNestedHtml($data[$field]);
            }
        }

        return $data;
    }

    /**
     * @param  array<mixed>  $items
     * @return array<mixed>
     */
    protected function sanitizeNestedHtml(array $items): array
    {
        foreach ($items as $key => $item) {
            if (is_string($item)) {
                $items[$key] = $this->stripDataUris($item);
            } elseif (is_array($item)) {
                $items[$key] = $this->sanitizeNestedHtml($item);
            }
        }

        return $items;
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    protected function extractUrlsFromValue(mixed $value): array
    {
        if (is_string($value)) {
            return $this->extractUrlsFromHtml($value);
        }

        if (! is_array($value)) {
            return [];
        }

        $urls = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $urls = array_merge($urls, $this->extractUrlsFromHtml($item));
            } elseif (is_array($item)) {
                foreach (['text', 'body', 'html', 'content', 'label'] as $key) {
                    if (! empty($item[$key]) && is_string($item[$key])) {
                        $urls = array_merge($urls, $this->extractUrlsFromHtml($item[$key]));
                    }
                }
            }
        }

        return $urls;
    }

    protected function looksLikeGalleryUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $galleryDirectory = trim((string) config('gallery.directory', 'gallery'), '/');

        return str_contains($path, '/storage/'.$galleryDirectory.'/')
            || str_contains($path, '/'.$galleryDirectory.'/');
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = html_entity_decode(trim($url));
        if ($url === '' || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:')) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            $url = (request()->isSecure() ? 'https:' : 'http:').$url;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        return $url;
    }

    public function freshPublicUrl(Gallery $gallery): string
    {
        return $this->publicUrl($gallery->disk ?: 'public', $gallery->file_path);
    }

    public function publicUrl(string $disk, string $path): string
    {
        $path = str_replace('\\', '/', ltrim((string) $path, '/'));

        if ($path === '') {
            return url('/');
        }

        // Always resolve public-disk URLs against the current request root.
        // Storage::url() uses APP_URL (often http://localhost) which breaks when
        // the app is served via php artisan serve on 127.0.0.1:8000.
        if ($disk === 'public' || $disk === '') {
            return url('storage/'.$path);
        }

        $url = Storage::disk($disk)->url($path);
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return url($url);
        }

        return $url;
    }

    protected function detectKind(UploadedFile $file, mixed $preferred = null): string
    {
        if (is_string($preferred) && in_array($preferred, ['image', 'video', 'document', 'file'], true)) {
            return $preferred;
        }

        $mime = (string) $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        if (str_starts_with($mime, 'image/') || in_array($ext, config('gallery.image_mimes', []), true)) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/') || in_array($ext, config('gallery.video_mimes', []), true)) {
            return 'video';
        }
        if (in_array($ext, config('gallery.document_mimes', []), true)) {
            return 'document';
        }

        return 'file';
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected function readImageDimensions(UploadedFile $file, string $kind): array
    {
        if ($kind !== 'image') {
            return [null, null];
        }

        try {
            $size = @getimagesize($file->getRealPath());
            if (is_array($size)) {
                return [(int) ($size[0] ?? 0) ?: null, (int) ($size[1] ?? 0) ?: null];
            }
        } catch (\Throwable) {
            // ignore
        }

        return [null, null];
    }
}

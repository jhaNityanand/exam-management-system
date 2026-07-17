<?php

namespace App\Console\Commands;

use App\Models\Gallery;
use App\Services\GalleryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Move gallery files into public/media and refresh URLs from APP_URL.
 */
class GalleryMigrateToMediaDisk extends Command
{
    protected $signature = 'gallery:migrate-media
                            {--force : Overwrite files already present in public/media}';

    protected $description = 'Copy gallery files into public/media and point records at the media disk using APP_URL';

    public function handle(GalleryService $galleryService): int
    {
        $mediaRoot = public_path('media');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.DIRECTORY_SEPARATOR.'.gitkeep', '');

        $sourceRoots = [
            storage_path('app/public'),
            public_path('storage'),
        ];

        $moved = 0;
        $updated = 0;
        $missing = 0;

        Gallery::query()->withTrashed()->orderBy('id')->chunkById(100, function ($items) use (
            $galleryService,
            $sourceRoots,
            &$moved,
            &$updated,
            &$missing
        ) {
            foreach ($items as $item) {
                $paths = array_values(array_unique(array_filter([
                    $item->file_path,
                    $item->original_file_path,
                    $item->modified_file_path,
                    $item->thumbnail_path,
                    $item->original_path,
                    $item->bin_path,
                ])));

                foreach ($paths as $relative) {
                    $relative = str_replace('\\', '/', ltrim((string) $relative, '/'));
                    if ($relative === '') {
                        continue;
                    }

                    $destination = public_path('media/'.$relative);
                    if (is_file($destination) && ! $this->option('force')) {
                        continue;
                    }

                    $copied = false;
                    foreach ($sourceRoots as $root) {
                        $candidate = rtrim($root, '\\/').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
                        if (! is_file($candidate)) {
                            continue;
                        }

                        File::ensureDirectoryExists(dirname($destination));
                        File::copy($candidate, $destination);
                        $copied = true;
                        $moved++;
                        break;
                    }

                    if (! $copied && ! is_file($destination) && ! Storage::disk('media')->exists($relative)) {
                        // Also try current disk if already media/public.
                        $disk = $item->disk ?: 'public';
                        if (Storage::disk($disk)->exists($relative)) {
                            File::ensureDirectoryExists(dirname($destination));
                            File::copy(Storage::disk($disk)->path($relative), $destination);
                            $copied = true;
                            $moved++;
                        }
                    }

                    if (! $copied && ! is_file($destination)) {
                        $missing++;
                    }
                }

                $displayPath = $item->displayPath();
                $item->forceFill([
                    'disk' => 'media',
                    'file_url' => $galleryService->publicUrl('media', $displayPath),
                ])->saveQuietly();
                $updated++;
            }
        });

        $this->info("Gallery media migration complete.");
        $this->line("  Files copied : {$moved}");
        $this->line("  Rows updated : {$updated}");
        $this->line("  Missing files: {$missing}");
        $this->line('  Media root   : '.$mediaRoot);
        $this->line('  APP_URL      : '.config('app.url'));

        return self::SUCCESS;
    }
}

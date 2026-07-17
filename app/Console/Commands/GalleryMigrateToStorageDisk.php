<?php

namespace App\Console\Commands;

use App\Models\Gallery;
use App\Services\GalleryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Move gallery files from legacy public/media into storage/app/public
 * and refresh URLs to use /storage via APP_URL.
 */
class GalleryMigrateToStorageDisk extends Command
{
    protected $signature = 'gallery:migrate-to-storage
                            {--force : Overwrite files already present in storage}
                            {--delete-legacy : Remove public/media after a successful copy}';

    protected $description = 'Copy gallery files from public/media into storage/app/public and point records at the public disk';

    public function handle(GalleryService $galleryService): int
    {
        $storageRoot = storage_path('app/public');
        File::ensureDirectoryExists($storageRoot);

        $link = public_path('storage');
        if (! File::exists($link) && ! is_link($link)) {
            Artisan::call('storage:link');
            $this->info('Created storage link at public/storage.');
        }

        $sourceRoots = [
            public_path('media'),
            storage_path('app/public'),
        ];

        $moved = 0;
        $updated = 0;
        $missing = 0;

        Gallery::query()->withTrashed()->orderBy('id')->chunkById(100, function ($items) use (
            $galleryService,
            $sourceRoots,
            $storageRoot,
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

                    $destination = $storageRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
                    if (is_file($destination) && ! $this->option('force')) {
                        continue;
                    }

                    $copied = false;
                    foreach ($sourceRoots as $root) {
                        $candidate = rtrim($root, '\\/').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
                        if (! is_file($candidate)) {
                            continue;
                        }

                        // Skip no-op copy when source is already storage.
                        if (realpath($candidate) === realpath($destination)) {
                            $copied = true;
                            break;
                        }

                        File::ensureDirectoryExists(dirname($destination));
                        File::copy($candidate, $destination);
                        $copied = true;
                        $moved++;
                        break;
                    }

                    if (! $copied && Storage::disk('public')->exists($relative)) {
                        $copied = true;
                    }

                    if (! $copied && ! is_file($destination)) {
                        $missing++;
                    }
                }

                $displayPath = $item->displayPath();
                $item->forceFill([
                    'disk' => 'public',
                    'file_url' => $galleryService->publicUrl('public', $displayPath),
                ])->saveQuietly();
                $updated++;
            }
        });

        if ($this->option('delete-legacy')) {
            $legacy = public_path('media');
            if (is_dir($legacy)) {
                File::deleteDirectory($legacy);
                $this->info('Removed legacy public/media directory.');
            }
        }

        $this->info('Gallery storage migration complete.');
        $this->line("  Files copied : {$moved}");
        $this->line("  Rows updated : {$updated}");
        $this->line("  Missing files: {$missing}");
        $this->line('  Storage root : '.$storageRoot);
        $this->line('  APP_URL      : '.config('app.url'));

        return self::SUCCESS;
    }
}

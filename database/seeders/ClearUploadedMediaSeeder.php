<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Clears uploaded media from storage before (re)seeding demo content.
 * Also removes the legacy public/media folder if it still exists.
 */
class ClearUploadedMediaSeeder extends Seeder
{
    public function run(): void
    {
        $diskRoot = storage_path('app/public');
        File::ensureDirectoryExists($diskRoot);

        $relativeDirs = (array) config('gallery.seed_clean_paths', [
            'gallery',
            'bin',
            'avatars',
            'editor',
            'organizations',
        ]);

        $removed = 0;
        foreach ($relativeDirs as $relative) {
            $path = $diskRoot.DIRECTORY_SEPARATOR.trim((string) $relative, '\\/');
            if (! is_dir($path)) {
                continue;
            }
            File::deleteDirectory($path);
            $removed++;
        }

        $legacyMedia = public_path('media');
        if (is_dir($legacyMedia)) {
            File::deleteDirectory($legacyMedia);
            $removed++;
            $this->command?->info('ClearUploadedMediaSeeder: removed legacy public/media directory.');
        }

        // Ensure public/storage points at storage/app/public.
        $link = public_path('storage');
        if (! File::exists($link) && ! is_link($link)) {
            Artisan::call('storage:link');
            $this->command?->info('ClearUploadedMediaSeeder: created storage link.');
        }

        $this->command?->info("ClearUploadedMediaSeeder: cleared {$removed} media director".($removed === 1 ? 'y' : 'ies').' under storage/app/public.');
    }
}

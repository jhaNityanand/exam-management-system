<?php

namespace App\Console\Commands;

use App\Services\GalleryService;
use Illuminate\Console\Command;

class PruneGalleryOrphansCommand extends Command
{
    protected $signature = 'gallery:prune-orphans {--hours= : Orphan TTL hours override}';

    protected $description = 'Soft-delete unreferenced editor gallery uploads older than the configured TTL';

    public function handle(GalleryService $galleryService): int
    {
        $hours = $this->option('hours');
        $deleted = $galleryService->pruneOrphans($hours !== null ? (int) $hours : null);
        $this->info("Pruned {$deleted} orphaned gallery upload(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Migration\ExamtubeMigrationService;
use Illuminate\Console\Command;

class ImportLegacyExamtubeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-examtube
                            {--file= : Optional path to custom legacy SQL backup file}
                            {--org=1 : Target Organization ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import legacy Examtube database records (blogs, categories, images, newsletter emails, comments) into the new application';

    /**
     * Execute the console command.
     */
    public function handle(ExamtubeMigrationService $migrationService): int
    {
        $this->info('=====================================================');
        $this->info('   Examtube Legacy Data & Media Migration Tool      ');
        $this->info('=====================================================');

        $customFile = $this->option('file');
        $orgId = (int) $this->option('org');

        try {
            $logger = $migrationService->migrate($customFile, $this, $orgId);
            $this->info('Migration process completed successfully.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}

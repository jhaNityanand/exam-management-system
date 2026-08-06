<?php

namespace Database\Seeders;

use App\Services\Migration\ExamtubeMigrationService;
use Illuminate\Database\Seeder;

class ExamtubeLegacyDataSeeder extends Seeder
{
    /**
     * Run the Examtube legacy data migration seeder.
     */
    public function run(): void
    {
        $this->command->info('Starting Legacy Examtube Data Migration...');

        $migrationService = app(ExamtubeMigrationService::class);
        $logger = $migrationService->migrate(null, $this->command);

        $this->command->info('Legacy Examtube Data Migration finished successfully!');
        $this->command->comment('Log output saved to: ' . $logger->getLogFilePath());
    }
}

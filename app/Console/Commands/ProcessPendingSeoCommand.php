<?php

namespace App\Console\Commands;

use App\Services\Llm\SeoBatchProcessor;
use Illuminate\Console\Command;

class ProcessPendingSeoCommand extends Command
{
    protected $signature = 'llm:process-seo
                            {--sync : Process the batch immediately instead of queueing}';

    protected $description = 'Find pending Create/Improve With AI records and process a cost-efficient SEO batch';

    public function handle(SeoBatchProcessor $processor): int
    {
        if ($this->option('sync')) {
            $pending = $processor->findPendingBatch();
            if (! $pending['found']) {
                $this->info('No pending SEO records found (or LLM unavailable).');

                return self::SUCCESS;
            }

            $processed = $processor->processBatch($pending['type'], $pending['ids']);
            $this->info(sprintf(
                'Processed %d record(s) for [%s] (%d failed).',
                $processed['processed'],
                $pending['type'],
                $processed['failed']
            ));

            return self::SUCCESS;
        }

        $result = $processor->dispatchPendingBatch();

        if (! $result['dispatched']) {
            $this->info('No pending SEO batch dispatched.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Dispatched SEO batch for [%s] (%d record(s)).',
            $result['type'],
            $result['count']
        ));

        return self::SUCCESS;
    }
}

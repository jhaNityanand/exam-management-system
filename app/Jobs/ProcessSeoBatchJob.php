<?php

namespace App\Jobs;

use App\Services\Llm\SeoBatchProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSeoBatchJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 180;

    /**
     * @param  list<int>  $ids
     */
    public function __construct(
        public string $type,
        public array $ids,
    ) {
        $this->onQueue('llm-seo');
    }

    public function uniqueId(): string
    {
        $sorted = $this->ids;
        sort($sorted);

        return $this->type.':'.implode(',', $sorted);
    }

    public function handle(SeoBatchProcessor $processor): void
    {
        $result = $processor->processBatch($this->type, $this->ids);

        Log::channel('llm')->info('ProcessSeoBatchJob finished', [
            'type' => $this->type,
            'ids' => $this->ids,
            'processed' => $result['processed'],
            'failed' => $result['failed'],
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::channel('llm')->error('ProcessSeoBatchJob failed', [
            'type' => $this->type,
            'ids' => $this->ids,
            'error' => $exception?->getMessage(),
        ]);
    }
}

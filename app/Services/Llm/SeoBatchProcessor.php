<?php

namespace App\Services\Llm;

use App\Jobs\ProcessSeoBatchJob;
use App\Models\SeoProcessingLog;
use App\Services\Llm\Exceptions\LlmException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SeoBatchProcessor
{
    public function __construct(
        protected LlmService $llm,
        protected SeoFieldMapper $mapper,
    ) {}

    /**
     * @return array{found: bool, type: ?string, ids: list<int>, count: int}
     */
    public function findPendingBatch(): array
    {
        if (! $this->llm->isAvailable()) {
            Log::channel('llm')->warning('SEO batch skipped: LLM provider not configured');

            return ['found' => false, 'type' => null, 'ids' => [], 'count' => 0];
        }

        $batchSize = max(1, min(20, (int) config('llm.batch_size', 6)));

        foreach (config('llm.seo_types', []) as $type => $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            /** @var Model $model */
            $model = new $class;
            if (! $model->isFillable('is_ai_generated')) {
                continue;
            }

            $ids = $class::query()
                ->pendingAiSeo()
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($ids === []) {
                continue;
            }

            return [
                'found' => true,
                'type' => $type,
                'ids' => $ids,
                'count' => count($ids),
            ];
        }

        return ['found' => false, 'type' => null, 'ids' => [], 'count' => 0];
    }

    /**
     * Discover pending records across SEO types and dispatch one batch job.
     *
     * @return array{dispatched: bool, type: ?string, ids: list<int>, count: int}
     */
    public function dispatchPendingBatch(): array
    {
        $lock = Cache::lock('llm:seo:dispatch', 55);

        if (! $lock->get()) {
            return ['dispatched' => false, 'type' => null, 'ids' => [], 'count' => 0];
        }

        try {
            $pending = $this->findPendingBatch();
            if (! $pending['found']) {
                return ['dispatched' => false, 'type' => null, 'ids' => [], 'count' => 0];
            }

            ProcessSeoBatchJob::dispatch($pending['type'], $pending['ids']);

            return [
                'dispatched' => true,
                'type' => $pending['type'],
                'ids' => $pending['ids'],
                'count' => $pending['count'],
            ];
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Process a claimed batch of records for one content type.
     *
     * @param  list<int>  $ids
     * @return array{processed: int, failed: int}
     */
    public function processBatch(string $type, array $ids): array
    {
        $class = config("llm.seo_types.{$type}");
        if (! is_string($class) || ! class_exists($class)) {
            throw LlmException::invalidResponse('llm', "Unknown SEO type [{$type}].");
        }

        if (! $this->llm->isAvailable()) {
            Log::channel('llm')->warning('SEO batch aborted: provider unavailable', [
                'type' => $type,
                'ids' => $ids,
            ]);

            return ['processed' => 0, 'failed' => count($ids)];
        }

        $lockKey = 'llm:seo:batch:'.$type.':'.md5(implode(',', $ids));
        $lock = Cache::lock($lockKey, (int) config('llm.timeout', 60) + 30);

        if (! $lock->get()) {
            Log::channel('llm')->info('SEO batch already running', ['type' => $type, 'ids' => $ids]);

            return ['processed' => 0, 'failed' => 0];
        }

        $start = microtime(true);
        $batchErrorSummary = null;

        try {
            $records = $class::query()
                ->whereIn('id', $ids)
                ->pendingAiSeo()
                ->orderBy('id')
                ->get();

            if ($records->isEmpty()) {
                return ['processed' => 0, 'failed' => 0];
            }

            $items = [];
            foreach ($records as $record) {
                $mode = $record->prefersAiImprove() ? 'improve' : 'generate';
                $items[] = [
                    'id' => (string) $record->getKey(),
                    'mode' => $mode,
                    'content' => $this->mapper->contentPayload($record, $type),
                    'existing_seo' => $this->mapper->existingSeoPayload($record),
                ];
            }

            $results = [];
            try {
                $results = $this->llm->generateSEOBatch($items);
            } catch (Throwable $e) {
                $batchErrorSummary = $e->getMessage();
                Log::channel('llm')->error('SEO batch LLM call failed', [
                    'type' => $type,
                    'ids' => $ids,
                    'error' => $e->getMessage(),
                ]);
            }

            $processed = 0;
            $failed = 0;

            foreach ($records as $record) {
                $seo = $results[(string) $record->getKey()] ?? null;
                if (! $seo) {
                    $failed++;
                    continue;
                }

                try {
                    DB::transaction(function () use ($record, $seo) {
                        $fresh = $record->newQuery()
                            ->whereKey($record->getKey())
                            ->where('is_ai_generated', false)
                            ->lockForUpdate()
                            ->first();

                        if (! $fresh) {
                            return;
                        }

                        $fresh->fill($this->mapper->toModelAttributes($fresh, $seo));
                        $fresh->save();
                    });
                    $processed++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::channel('llm')->error('SEO batch update failed', [
                        'type' => $type,
                        'id' => $record->getKey(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $lastUsed = $this->llm->getLastUsedAccount();
            $executionMs = round((microtime(true) - $start) * 1000, 2);

            SeoProcessingLog::create([
                'run_at' => now(),
                'seo_type' => $type,
                'processed_records_count' => count($ids),
                'successful_count' => $processed,
                'failed_count' => $failed,
                'provider_used' => $lastUsed?->provider ?? 'Default',
                'account_used' => $lastUsed?->account_name ?? 'Default Account',
                'execution_time_ms' => $executionMs,
                'error_summary' => $batchErrorSummary ?? ($failed > 0 ? "{$failed} records failed update" : null),
                'processed_record_ids' => array_values($ids),
            ]);

            return ['processed' => $processed, 'failed' => $failed];
        } finally {
            optional($lock)->release();
        }
    }
}

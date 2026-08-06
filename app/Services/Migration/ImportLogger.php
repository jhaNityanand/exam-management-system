<?php

namespace App\Services\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImportLogger
{
    protected ?string $logFilePath = null;
    protected float $startTime;
    /** @var array<string, array{total: int, success: int, failed: int, skipped: int, duplicate: int, errors: list<string>, start_time: float, end_time: ?float}> */
    protected array $stats = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
        $directory = storage_path('logs');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        $this->logFilePath = $directory . '/migration-' . date('Y-m-d_H-i-s') . '.log';
    }

    public function startTable(string $table, int $totalRecords): void
    {
        $this->stats[$table] = [
            'total' => $totalRecords,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'duplicate' => 0,
            'errors' => [],
            'start_time' => microtime(true),
            'end_time' => null,
        ];

        $this->writeLog("Starting import for table: {$table} (Total: {$totalRecords} records)");
    }

    public function recordSuccess(string $table): void
    {
        if (isset($this->stats[$table])) {
            $this->stats[$table]['success']++;
        }
    }

    public function recordSkipped(string $table, ?string $reason = null): void
    {
        if (isset($this->stats[$table])) {
            $this->stats[$table]['skipped']++;
            if ($reason) {
                $this->writeLog("[SKIP] [{$table}] {$reason}");
            }
        }
    }

    public function recordDuplicate(string $table, ?string $identifier = null): void
    {
        if (isset($this->stats[$table])) {
            $this->stats[$table]['duplicate']++;
            $msg = $identifier ? "Duplicate record skipped: {$identifier}" : "Duplicate record skipped";
            $this->writeLog("[DUPLICATE] [{$table}] {$msg}");
        }
    }

    public function recordFailed(string $table, string $error, ?array $context = []): void
    {
        if (isset($this->stats[$table])) {
            $this->stats[$table]['failed']++;
            $this->stats[$table]['errors'][] = $error;
            $ctxStr = ! empty($context) ? ' ' . json_encode($context) : '';
            $this->writeLog("[ERROR] [{$table}] {$error}{$ctxStr}");
        }
    }

    public function endTable(string $table): void
    {
        if (isset($this->stats[$table])) {
            $this->stats[$table]['end_time'] = microtime(true);
            $duration = round($this->stats[$table]['end_time'] - $this->stats[$table]['start_time'], 2);
            $st = $this->stats[$table];
            $this->writeLog("Completed table: {$table} in {$duration}s (Success: {$st['success']}, Skipped: {$st['skipped']}, Duplicates: {$st['duplicate']}, Failed: {$st['failed']})");
        }
    }

    public function writeLog(string $message): void
    {
        $formatted = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        if ($this->logFilePath) {
            File::append($this->logFilePath, $formatted);
        }
        Log::channel('single')->info('[MIGRATION] ' . $message);
    }

    /**
     * Render a pretty table summary to console if command is active.
     */
    public function displaySummary(?Command $command = null): void
    {
        $totalDuration = round(microtime(true) - $this->startTime, 2);
        $this->writeLog("Overall Migration Completed in {$totalDuration} seconds.");

        if (! $command) {
            return;
        }

        $command->info('');
        $command->info("=== MIGRATION SUMMARY REPORT ===");
        $rows = [];
        foreach ($this->stats as $table => $st) {
            $duration = isset($st['end_time']) ? round($st['end_time'] - $st['start_time'], 2) . 's' : 'N/A';
            $rows[] = [
                $table,
                $st['total'],
                $st['success'],
                $st['skipped'],
                $st['duplicate'],
                $st['failed'],
                $duration,
            ];
        }

        $command->table(
            ['Table', 'Total', 'Success', 'Skipped', 'Duplicates', 'Failed', 'Duration'],
            $rows
        );

        $command->info("Total Execution Time: {$totalDuration}s");
        $command->info("Detailed log file: {$this->logFilePath}");
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getLogFilePath(): ?string
    {
        return $this->logFilePath;
    }
}

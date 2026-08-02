<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('exam_attempts', 'exam_attempts_org_status_idx', ['organization_id', 'status']);
        $this->addIndexIfMissing('exam_attempts', 'exam_attempts_org_created_idx', ['organization_id', 'created_at']);
        $this->addIndexIfMissing('exam_attempts', 'exam_attempts_user_status_idx', ['user_id', 'status']);
        $this->addIndexIfMissing('exam_attempts', 'exam_attempts_status_expires_idx', ['status', 'expires_at']);

        $this->addIndexIfMissing('exams', 'exams_org_visibility_idx', ['organization_id', 'visibility']);
        $this->addIndexIfMissing('exams', 'exams_org_mode_idx', ['organization_id', 'exam_mode']);
        $this->addIndexIfMissing('exams', 'exams_org_difficulty_idx', ['organization_id', 'difficulty_level']);
        $this->addIndexIfMissing('exams', 'exams_org_scheduled_idx', ['organization_id', 'scheduled_start']);

        if (Schema::hasTable('exam_attempt_answers')) {
            $this->addIndexIfMissing('exam_attempt_answers', 'eaa_attempt_grading_idx', ['exam_attempt_id', 'grading_status']);
            $this->addIndexIfMissing('exam_attempt_answers', 'eaa_attempt_answered_idx', ['exam_attempt_id', 'is_answered']);
        }

        if (Schema::hasTable('ad_placements')) {
            // Stacked slots (e.g. 3× left_sidebar) share page/position with different sort_order.
            $duplicates = DB::table('ad_placements')
                ->select('organization_id', 'page_key', 'position_key', 'sort_order', DB::raw('MAX(id) as keep_id'))
                ->groupBy('organization_id', 'page_key', 'position_key', 'sort_order')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $dup) {
                DB::table('ad_placements')
                    ->where('organization_id', $dup->organization_id)
                    ->where('page_key', $dup->page_key)
                    ->where('position_key', $dup->position_key)
                    ->where('sort_order', $dup->sort_order)
                    ->where('id', '!=', $dup->keep_id)
                    ->delete();
            }

            $this->addUniqueIfMissing(
                'ad_placements',
                'ad_placements_org_page_pos_sort_uq',
                ['organization_id', 'page_key', 'position_key', 'sort_order']
            );
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('exam_attempts', 'exam_attempts_org_status_idx');
        $this->dropIndexIfExists('exam_attempts', 'exam_attempts_org_created_idx');
        $this->dropIndexIfExists('exam_attempts', 'exam_attempts_user_status_idx');
        $this->dropIndexIfExists('exam_attempts', 'exam_attempts_status_expires_idx');

        $this->dropIndexIfExists('exams', 'exams_org_visibility_idx');
        $this->dropIndexIfExists('exams', 'exams_org_mode_idx');
        $this->dropIndexIfExists('exams', 'exams_org_difficulty_idx');
        $this->dropIndexIfExists('exams', 'exams_org_scheduled_idx');

        $this->dropIndexIfExists('exam_attempt_answers', 'eaa_attempt_grading_idx');
        $this->dropIndexIfExists('exam_attempt_answers', 'eaa_attempt_answered_idx');
        $this->dropIndexIfExists('ad_placements', 'ad_placements_org_page_pos_sort_uq');
    }

    /**
     * @param  list<string>  $columns
     */
    protected function addIndexIfMissing(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    protected function addUniqueIfMissing(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->unique($columns, $name);
        });
    }

    protected function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropIndex($name);
        });
    }

    protected function indexExists(string $table, string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = Schema::getIndexes($table);

            foreach ($indexes as $index) {
                if (($index['name'] ?? null) === $name) {
                    return true;
                }
            }

            return false;
        }

        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'select 1 as ok from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $name]
        );

        return (bool) $row;
    }
};

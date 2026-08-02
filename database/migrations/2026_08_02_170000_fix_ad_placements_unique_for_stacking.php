<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ad_placements')) {
            return;
        }

        // Sidebar stacking uses multiple rows per page/position with different sort_order.
        // Unique must include sort_order — not only (org, page, position).
        if ($this->indexExists('ad_placements', 'ad_placements_org_page_position_uq')) {
            Schema::table('ad_placements', function (Blueprint $table) {
                $table->dropUnique('ad_placements_org_page_position_uq');
            });
        }

        if (! $this->indexExists('ad_placements', 'ad_placements_org_page_pos_sort_uq')) {
            Schema::table('ad_placements', function (Blueprint $table) {
                $table->unique(
                    ['organization_id', 'page_key', 'position_key', 'sort_order'],
                    'ad_placements_org_page_pos_sort_uq'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ad_placements')) {
            return;
        }

        if ($this->indexExists('ad_placements', 'ad_placements_org_page_pos_sort_uq')) {
            Schema::table('ad_placements', function (Blueprint $table) {
                $table->dropUnique('ad_placements_org_page_pos_sort_uq');
            });
        }

        if (! $this->indexExists('ad_placements', 'ad_placements_org_page_position_uq')) {
            Schema::table('ad_placements', function (Blueprint $table) {
                $table->unique(
                    ['organization_id', 'page_key', 'position_key'],
                    'ad_placements_org_page_position_uq'
                );
            });
        }
    }

    protected function indexExists(string $table, string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (Schema::getIndexes($table) as $index) {
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

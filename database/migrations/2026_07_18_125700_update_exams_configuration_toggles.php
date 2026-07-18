<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns existing exams tables with the Create-page configuration toggles.
 * Fresh installs already get these columns from the create_exams_table migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'total_categories')) {
                $table->dropColumn('total_categories');
            }

            if (! Schema::hasColumn('exams', 'fixed_questions')) {
                $table->boolean('fixed_questions')->default(false)->after('total_questions');
            }

            if (! Schema::hasColumn('exams', 'fixed_paper_set')) {
                $table->boolean('fixed_paper_set')->default(false)->after('fixed_questions');
            }

            if (! Schema::hasColumn('exams', 'shuffle_categories')) {
                $table->boolean('shuffle_categories')->default(false)->after('shuffle_questions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'total_categories')) {
                $table->unsignedSmallInteger('total_categories')->nullable()->after('total_questions');
            }

            $drop = [];
            foreach (['fixed_questions', 'fixed_paper_set', 'shuffle_categories'] as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};

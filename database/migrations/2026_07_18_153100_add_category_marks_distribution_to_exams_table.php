<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'fix_category_marks')) {
                $table->boolean('fix_category_marks')->default(false)->after('fix_category_questions');
            }

            if (! Schema::hasColumn('exams', 'extra_marks_allocations')) {
                $table->json('extra_marks_allocations')->nullable()->after('extra_questions_allocations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $drop = [];
            foreach (['fix_category_marks', 'extra_marks_allocations'] as $column) {
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

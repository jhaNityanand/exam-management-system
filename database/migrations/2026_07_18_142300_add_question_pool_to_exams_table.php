<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'use_question_pool')) {
                $table->boolean('use_question_pool')->default(false)->after('total_questions');
            }

            if (! Schema::hasColumn('exams', 'maximum_questions')) {
                $table->unsignedSmallInteger('maximum_questions')->nullable()->after('use_question_pool');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $drop = [];
            foreach (['use_question_pool', 'maximum_questions'] as $column) {
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

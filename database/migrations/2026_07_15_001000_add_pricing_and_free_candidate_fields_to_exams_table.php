<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'pricing_option')) {
                $table->string('pricing_option')->default('free')->after('visibility');
            }
            if (! Schema::hasColumn('exams', 'exam_currency')) {
                $table->string('exam_currency', 10)->nullable()->after('pricing_option');
            }
            if (! Schema::hasColumn('exams', 'exam_amount')) {
                $table->decimal('exam_amount', 12, 2)->nullable()->after('exam_currency');
            }
            if (! Schema::hasColumn('exams', 'selected_discounts')) {
                $table->json('selected_discounts')->nullable()->after('exam_amount');
            }
            if (! Schema::hasColumn('exams', 'custom_discounts')) {
                $table->json('custom_discounts')->nullable()->after('selected_discounts');
            }
            if (! Schema::hasColumn('exams', 'free_imported_candidates')) {
                $table->json('free_imported_candidates')->nullable()->after('manual_candidate_emails');
            }
            if (! Schema::hasColumn('exams', 'free_manual_candidate_emails')) {
                $table->json('free_manual_candidate_emails')->nullable()->after('free_imported_candidates');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $columns = [
                'pricing_option',
                'exam_currency',
                'exam_amount',
                'selected_discounts',
                'custom_discounts',
                'free_imported_candidates',
                'free_manual_candidate_emails',
            ];

            $existing = array_values(array_filter(
                $columns,
                static fn (string $column) => Schema::hasColumn('exams', $column)
            ));

            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_instruction_rules')) {
            Schema::create('exam_instruction_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->string('status')->default('active'); // active | inactive
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('icon')->nullable();
                $table->string('category')->nullable(); // submission | integrity | environment | monitoring | other
                $table->boolean('is_default')->default(false);
                $table->boolean('is_required')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('updated_by_history')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'slug'], 'exam_instr_rules_org_slug_uq');
                $table->index(['organization_id', 'status', 'sort_order'], 'exam_instr_rules_org_status_sort_idx');
            });
        }

        if (! Schema::hasColumn('exams', 'predefined_instruction_rules')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->json('predefined_instruction_rules')->nullable()->after('instructions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exams', 'predefined_instruction_rules')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('predefined_instruction_rules');
            });
        }

        Schema::dropIfExists('exam_instruction_rules');
    }
};

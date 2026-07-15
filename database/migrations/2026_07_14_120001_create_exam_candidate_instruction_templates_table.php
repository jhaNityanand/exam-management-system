<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_candidate_instruction_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->string('status')->default('active'); // active | inactive
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('template_type')->nullable(); // general | proctored | coding | certification
            $table->string('version')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug'], 'exam_cand_instr_tpl_org_slug_uq');
            $table->index(['organization_id', 'status', 'sort_order'], 'exam_cand_instr_tpl_org_status_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_candidate_instruction_templates');
    }
};

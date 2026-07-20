<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_instruction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('rule_key', 64);
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active | inactive
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('icon')->nullable();
            $table->string('category')->nullable(); // submission | integrity | environment | monitoring | other
            $table->boolean('is_default')->default(false);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_actionable')->default(false);
            $table->json('requirements')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug'], 'exam_instr_rules_org_slug_uq');
            $table->unique(['organization_id', 'rule_key'], 'exam_instr_rules_org_key_uq');
            $table->index(['organization_id', 'status', 'sort_order'], 'exam_instr_rules_org_status_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_instruction_rules');
    }
};

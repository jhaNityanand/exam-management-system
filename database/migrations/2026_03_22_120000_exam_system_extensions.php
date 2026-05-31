<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates supplementary tables that depend on the core tables being present:
 *  - exam_question  (pivot)
 *  - user_app_settings
 *  - Adds updated_by_history to user_organizations
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── exam_question pivot ───────────────────────────────────────────────
        Schema::create('exam_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('marks_override')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['exam_id', 'question_id']);
        });

        // ── user_app_settings ─────────────────────────────────────────────────
        Schema::create('user_app_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme', 16)->default('system');
            $table->boolean('sidebar_collapsed')->default(false);
            $table->json('preferences')->nullable();
            $table->timestamps();
        });

        // ── user_organizations: add audit history column ───────────────────────
        Schema::table('user_organizations', function (Blueprint $table) {
            $table->json('updated_by_history')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('user_organizations', function (Blueprint $table) {
            $table->dropColumn('updated_by_history');
        });

        Schema::dropIfExists('user_app_settings');
        Schema::dropIfExists('exam_question');
    }
};

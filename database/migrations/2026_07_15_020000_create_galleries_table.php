<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central media library. Editor uploads and gallery UI share this table.
 *
 * Content modules sync HTML references via morph attachable_* columns.
 * Blogs also use explicit FKs (banner_image_id / og_image_id) + blog_attachments.
 *
 * Dual-path images:
 * - original_file_path = untouched upload
 * - modified_file_path = edited version (nullable)
 * - file_path / file_url  = display path (modified when present, else original)
 *
 * Soft-delete recycle pointers:
 * - original_path = pre-bin restore destination for the display file
 * - bin_path = current bin location of the display file
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_url', 500);
            $table->string('original_file_path')->nullable();
            $table->string('modified_file_path')->nullable();
            $table->string('original_path')->nullable();
            $table->string('bin_path')->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('kind', 20)->default('image'); // image | video | document | file
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('folder')->default('gallery');
            $table->string('disk', 50)->default('public');
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('active'); // active | archived
            $table->string('source', 50)->nullable(); // gallery | editor | import | news | blog
            $table->string('module', 50)->nullable(); // originating app module (news, blog, profile, …)
            $table->string('thumbnail_path')->nullable();
            $table->nullableMorphs('attachable');
            $table->timestamp('last_referenced_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'kind']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'source']);
            $table->index(['organization_id', 'module']);
            $table->index(['organization_id', 'deleted_at']);
            $table->index(['organization_id', 'created_at']);
            $table->index('file_name');
        });

        // Early SEO tables declare og_image_id without a constraint (created before galleries).
        foreach (['questions', 'exams', 'question_categories', 'exam_categories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('og_image_id')->references('id')->on('galleries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['exam_categories', 'question_categories', 'exams', 'questions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['og_image_id']);
            });
        }

        Schema::dropIfExists('galleries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->string('variant', 20)->default('original'); // original|adjusted
            $table->string('original_name');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_url');
            $table->string('original_path')->nullable();
            $table->string('bin_path')->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('kind', 20)->default('image'); // image|video|document|file
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('folder')->default('gallery');
            $table->string('disk', 50)->default('public');
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('active'); // active|archived
            $table->string('source', 50)->nullable(); // gallery|editor|import
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
            $table->index(['organization_id', 'deleted_at']);
            $table->index(['organization_id', 'mime_type']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'variant']);
            $table->index('file_name');
            $table->index(['organization_id', 'file_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};

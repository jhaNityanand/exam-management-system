<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('original_file_name');
            $table->string('file_path');
            $table->string('file_type', 10);
            $table->string('mime_type')->nullable();
            $table->string('disk', 50)->default('local');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status', 30)->default('processing');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('import_logs')->nullable();
            $table->json('errors')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'imported_at']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('import_question_id')
                ->nullable()
                ->after('category_id')
                ->constrained('import_questions')
                ->nullOnDelete();

            $table->index(['organization_id', 'import_question_id']);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'import_question_id']);
            $table->dropConstrainedForeignId('import_question_id');
        });

        Schema::dropIfExists('import_questions');
    }
};

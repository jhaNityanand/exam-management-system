<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('llm_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // mistral, groq, gemini, openrouter
            $table->string('account_name');
            $table->text('api_key');
            $table->string('model');
            $table->string('base_url')->nullable();
            $table->string('organization_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1);
            $table->integer('daily_request_limit')->nullable();
            $table->integer('daily_token_limit')->nullable();
            $table->integer('requests_today')->default(0);
            $table->integer('tokens_today')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->integer('error_count')->default(0);
            $table->timestamp('cooldown_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active', 'priority']);
        });

        Schema::create('seo_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('run_at');
            $table->string('seo_type')->nullable();
            $table->integer('processed_records_count')->default(0);
            $table->integer('successful_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->string('provider_used')->nullable();
            $table->string('account_used')->nullable();
            $table->float('execution_time_ms')->default(0);
            $table->integer('api_tokens_used')->nullable();
            $table->text('error_summary')->nullable();
            $table->json('processed_record_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('sitemap_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('run_at');
            $table->integer('total_records_processed')->default(0);
            $table->integer('total_urls_generated')->default(0);
            $table->float('processing_time_ms')->default(0);
            $table->text('errors')->nullable();
            $table->json('generated_urls')->nullable();
            $table->string('status')->default('success');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('llm_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->foreignId('account_id')->nullable()->constrained('llm_accounts')->nullOnDelete();
            $table->string('account_name')->nullable();
            $table->string('model')->nullable();
            $table->string('request_type')->default('chat');
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->integer('http_status')->nullable();
            $table->mediumText('response_body')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('failed_at');
            $table->timestamps();

            $table->index(['provider', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_error_logs');
        Schema::dropIfExists('sitemap_logs');
        Schema::dropIfExists('seo_processing_logs');
        Schema::dropIfExists('llm_accounts');
    }
};

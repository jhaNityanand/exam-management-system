<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();

            $table->foreignId('banner_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->nullable();

            $table->string('status')->default('published'); // draft|pending_review|published|archived
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->nullable()->default('index,follow');
            $table->longText('schema_markup')->nullable();

            $table->boolean('ai_generated')->default(false);
            $table->boolean('ai_improve')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'blog_category_id']);
            $table->index(['organization_id', 'published_at']);
            $table->index(['organization_id', 'author_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};

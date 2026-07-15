<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the news table.
 *
 * Extends the blog content shape with short description, featured image,
 * expiry, visibility, sort order, and featured/breaking/trending flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('news_category_id')->nullable()->constrained('news_categories')->nullOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('short_description')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();

            $table->foreignId('banner_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->foreignId('featured_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->nullable();

            $table->string('status')->default('published'); // draft | pending_review | published | archived
            $table->string('visibility')->default('public'); // public | private | unlisted
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_breaking')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('breaking_until')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
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
            $table->index(['organization_id', 'news_category_id']);
            $table->index(['organization_id', 'published_at']);
            $table->index(['organization_id', 'expires_at']);
            $table->index(['organization_id', 'author_id']);
            $table->index(['organization_id', 'visibility']);
            $table->index(['organization_id', 'is_featured']);
            $table->index(['organization_id', 'is_breaking']);
            $table->index(['organization_id', 'is_trending']);
            $table->index(['organization_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};

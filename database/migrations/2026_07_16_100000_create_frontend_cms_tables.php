<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public CMS + marketing content for Examtube.in frontend.
 * All navigational, hero, FAQ, testimonial, and page content is database-driven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('group', 50)->default('general');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 40)->default('string'); // string|text|boolean|integer|json|image
            $table->string('label', 150)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'group', 'key'], 'site_settings_org_group_key_uq');
            $table->index(['organization_id', 'group'], 'site_settings_org_group_idx');
        });

        Schema::create('site_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('location', 60); // header | footer | footer_legal | mobile
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'location']);
        });

        Schema::create('site_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('site_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('site_menu_items')->nullOnDelete();
            $table->string('label');
            $table->string('type', 30)->default('route'); // route | url | page
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('page_slug')->nullable();
            $table->string('icon')->nullable();
            $table->string('target', 20)->default('_self');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'sort_order']);
        });

        Schema::create('hero_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('primary_cta_label')->nullable();
            $table->string('primary_cta_url')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->foreignId('mobile_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->string('theme', 40)->default('default');
            $table->boolean('show_search')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status', 'sort_order']);
        });

        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('key'); // hero|stats|featured_exams|categories|blogs|news|testimonials|faqs|newsletter|partners|cta
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
            $table->index(['organization_id', 'is_enabled', 'sort_order']);
        });

        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('template', 60)->default('default'); // default|contact|help|careers
            $table->longText('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->foreignId('banner_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('status', 20)->default('published');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('company')->nullable();
            $table->text('quote');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->foreignId('avatar_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status', 'sort_order']);
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('faq_category_id')->nullable()->constrained('faq_categories')->nullOnDelete();
            $table->string('question');
            $table->longText('answer');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status', 'sort_order']);
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('platform'); // twitter|linkedin|youtube|instagram|facebook|telegram
            $table->string('label');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_visible', 'sort_order']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('status', 20)->default('subscribed'); // subscribed|unsubscribed
            $table->string('source', 60)->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'email']);
        });

        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('placement', 60); // home_sidebar|exam_list|blog_list|news_list|footer
            $table->string('headline')->nullable();
            $table->text('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'placement', 'status']);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type', 30)->default('info'); // info|success|warning|danger
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('is_dismissible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status', 'sort_order']);
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('url')->nullable();
            $table->foreignId('logo_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status', 'sort_order']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new'); // new|read|replied|archived
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending'); // pending|approved|spam|rejected
            $table->timestamps();
            $table->softDeletes();

            $table->index(['blog_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('site_pages');
        Schema::dropIfExists('home_sections');
        Schema::dropIfExists('hero_banners');
        Schema::dropIfExists('site_menu_items');
        Schema::dropIfExists('site_menus');
        Schema::dropIfExists('site_settings');
    }
};

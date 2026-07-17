<?php

use App\Models\Gallery;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'News Editor']);
    $this->organization = Organization::create([
        'name' => 'News Org',
        'slug' => 'news-org-'.$this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->category = NewsCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Laravel',
        'slug' => 'laravel',
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);
});

test('authenticated user can view news list page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.news.index'))
        ->assertOk()
        ->assertViewIs('backend.news.index')
        ->assertSee('name="filters[is_featured][]"', false)
        ->assertSee('name="filters[created_from]"', false)
        ->assertSee('data-date-preset-select', false)
        ->assertSee('This Quarter', false);
});

test('authenticated user can view news categories page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.news.categories.index'))
        ->assertOk()
        ->assertViewIs('backend.news-categories.index');
});

test('user can create news with multiple banner images', function () {
    Storage::fake('public');

    $bannerOne = Gallery::create([
        'organization_id' => $this->organization->id,
        'original_name' => 'banner-1.png',
        'file_name' => 'banner-1.png',
        'file_path' => 'gallery/1/banner-1.png',
        'file_url' => '/storage/gallery/1/banner-1.png',
        'original_file_path' => 'gallery/1/banner-1.png',
        'mime_type' => 'image/png',
        'kind' => 'image',
        'file_size' => 1200,
        'status' => 'active',
        'source' => 'gallery_ui',
        'module' => 'news',
        'uploaded_by' => $this->user->id,
        'created_by' => $this->user->id,
    ]);
    $bannerTwo = Gallery::create([
        'organization_id' => $this->organization->id,
        'original_name' => 'banner-2.png',
        'file_name' => 'banner-2.png',
        'file_path' => 'gallery/1/banner-2.png',
        'file_url' => '/storage/gallery/1/banner-2.png',
        'original_file_path' => 'gallery/1/banner-2.png',
        'mime_type' => 'image/png',
        'kind' => 'image',
        'file_size' => 1400,
        'status' => 'active',
        'source' => 'gallery_ui',
        'module' => 'news',
        'uploaded_by' => $this->user->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('admin.news.store'), [
            'title' => 'Headline Showcase',
            'slug' => 'headline-showcase',
            'content' => '<p>With several banners.</p>',
            'status' => 'published',
            'banner_ids' => [$bannerOne->id, $bannerTwo->id],
        ]);

    $news = News::query()->where('slug', 'headline-showcase')->first();
    expect($news)->not->toBeNull();
    expect($news->banner_image_id)->toBe($bannerOne->id);
    expect($news->banners()->count())->toBe(2);

    $response->assertRedirect(route('admin.news.show', $news));
});

test('user can create a published news item', function () {
    $response = $this->actingAs($this->user)
        ->post(route('admin.news.store'), [
            'title' => 'Breaking Campus Update',
            'slug' => 'breaking-campus-update',
            'news_category_id' => $this->category->id,
            'excerpt' => 'A practical guide to caching in Laravel.',
            'content' => '<p>Use Redis for session and cache stores.</p>',
            'author_name' => 'News Editor',
            'status' => 'published',
            'tags' => ['Laravel', 'Performance'],
            'meta_title' => 'Breaking Campus Update',
            'meta_description' => 'Learn Laravel caching patterns.',
            'robots' => 'index,follow',
        ]);

    $news = News::query()->where('slug', 'breaking-campus-update')->first();
    expect($news)->not->toBeNull();
    expect($news->status)->toBe('published');
    expect($news->seo_title)->toBe('Breaking Campus Update');
    expect($news->tags)->toHaveCount(2);

    $response->assertRedirect(route('admin.news.show', $news));
});

test('news table api returns paginated rows', function () {
    News::create([
        'organization_id' => $this->organization->id,
        'news_category_id' => $this->category->id,
        'title' => 'Campus Wire Tips',
        'slug' => 'campus-wire-tips',
        'content' => '<p>Use consistent status codes.</p>',
        'author_id' => $this->user->id,
        'author_name' => 'News Editor',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('admin.internal-api.news-table', ['per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonFragment(['title' => 'Campus Wire Tips']);
});

test('news table supports multi-select flags and inclusive date ranges', function () {
    foreach ([
        ['title' => 'Featured News', 'is_featured' => true, 'published_at' => '2026-07-10 12:00:00'],
        ['title' => 'Standard News', 'is_featured' => false, 'published_at' => '2026-07-12 08:00:00'],
        ['title' => 'Old News', 'is_featured' => true, 'published_at' => '2026-06-30 23:59:59'],
    ] as $index => $attributes) {
        News::create([
            'organization_id' => $this->organization->id,
            'news_category_id' => $this->category->id,
            'title' => $attributes['title'],
            'slug' => 'filtered-news-'.$index,
            'content' => '<p>Filter test.</p>',
            'author_id' => $this->user->id,
            'author_name' => 'News Editor',
            'status' => 'published',
            'is_featured' => $attributes['is_featured'],
            'published_at' => $attributes['published_at'],
            'created_by' => $this->user->id,
        ]);
    }

    $this->actingAs($this->user)
        ->getJson(route('admin.internal-api.news-table', [
            'filters' => [
                'is_featured' => ['0', '1'],
                'date_from' => '2026-07-10',
                'date_to' => '2026-07-12',
            ],
        ]))
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonFragment(['title' => 'Featured News'])
        ->assertJsonFragment(['title' => 'Standard News'])
        ->assertJsonMissing(['title' => 'Old News']);
});

test('user can soft delete and restore news', function () {
    $news = News::create([
        'organization_id' => $this->organization->id,
        'news_category_id' => $this->category->id,
        'title' => 'Temp News',
        'slug' => 'temp-news',
        'content' => '<p>Temporary</p>',
        'author_id' => $this->user->id,
        'author_name' => 'News Editor',
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->delete(route('admin.news.destroy', $news))
        ->assertRedirect();

    expect($news->fresh()->trashed())->toBeTrue();

    $this->actingAs($this->user)
        ->patch(route('admin.news.restore', $news->id))
        ->assertRedirect();

    expect(News::query()->find($news->id))->not->toBeNull();
});

<?php

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Blog Editor']);
    $this->organization = Organization::create([
        'name' => 'Blog Org',
        'slug' => 'blog-org-'.$this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->category = BlogCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Laravel',
        'slug' => 'laravel',
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);
});

test('authenticated user can view blog list page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.blogs.index'))
        ->assertOk()
        ->assertViewIs('backend.blogs.index');
});

test('authenticated user can view blog categories page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.blogs.categories.index'))
        ->assertOk()
        ->assertViewIs('backend.blog-categories.index');
});

test('user can create a published blog post', function () {
    $response = $this->actingAs($this->user)
        ->post(route('admin.blogs.store'), [
            'title' => 'Laravel Caching Strategies',
            'slug' => 'laravel-caching-strategies',
            'blog_category_id' => $this->category->id,
            'excerpt' => 'A practical guide to caching in Laravel.',
            'content' => '<p>Use Redis for session and cache stores.</p>',
            'author_name' => 'Blog Editor',
            'status' => 'published',
            'tags' => ['Laravel', 'Performance'],
            'meta_title' => 'Laravel Caching Strategies',
            'meta_description' => 'Learn Laravel caching patterns.',
            'robots' => 'index,follow',
        ]);

    $blog = Blog::query()->where('slug', 'laravel-caching-strategies')->first();
    expect($blog)->not->toBeNull();
    expect($blog->status)->toBe('published');
    expect($blog->seo_title)->toBe('Laravel Caching Strategies');
    expect($blog->tags)->toHaveCount(2);

    $response->assertRedirect(route('admin.blogs.show', $blog));
});

test('blogs table api returns paginated rows', function () {
    Blog::create([
        'organization_id' => $this->organization->id,
        'blog_category_id' => $this->category->id,
        'title' => 'API Design Tips',
        'slug' => 'api-design-tips',
        'content' => '<p>Use consistent status codes.</p>',
        'author_id' => $this->user->id,
        'author_name' => 'Blog Editor',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('admin.internal-api.blogs-table', ['per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonFragment(['title' => 'API Design Tips']);
});

test('user can soft delete and restore a blog', function () {
    $blog = Blog::create([
        'organization_id' => $this->organization->id,
        'blog_category_id' => $this->category->id,
        'title' => 'Temp Post',
        'slug' => 'temp-post',
        'content' => '<p>Temporary</p>',
        'author_id' => $this->user->id,
        'author_name' => 'Blog Editor',
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->delete(route('admin.blogs.destroy', $blog))
        ->assertRedirect();

    expect($blog->fresh()->trashed())->toBeTrue();

    $this->actingAs($this->user)
        ->patch(route('admin.blogs.restore', $blog->id))
        ->assertRedirect();

    expect(Blog::query()->find($blog->id))->not->toBeNull();
});

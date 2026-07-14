<?php

use App\Models\Gallery;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Gallery Org',
        'slug' => 'gallery-org-' . $this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
});

test('authenticated user can view gallery page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.gallery.index'))
        ->assertOk()
        ->assertViewIs('backend.gallery.index');
});

test('user can upload image to gallery via ajax', function () {
    $file = UploadedFile::fake()->image('banner.png', 200, 120);

    $response = $this->actingAs($this->user)
        ->postJson(route('admin.gallery.store'), [
            'files' => [$file],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.0.original_name', 'banner.png')
        ->assertJsonStructure(['data', 'stats', 'message']);

    $this->assertDatabaseHas('galleries', [
        'organization_id' => $this->organization->id,
        'original_name' => 'banner.png',
        'kind' => 'image',
        'source' => 'gallery',
    ]);

    $filePath = (string) $response->json('data.0.file_path');
    expect($filePath)->not->toBeEmpty();
    Storage::disk('public')->assertExists($filePath);

    $fileUrl = (string) $response->json('data.0.file_url');
    expect($fileUrl)->toStartWith(rtrim(url('/'), '/'));
    expect(parse_url($fileUrl, PHP_URL_PATH))->toContain('/storage/gallery/');
});

test('user can soft delete gallery item into bin and restore it', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 80);

    $upload = $this->actingAs($this->user)
        ->postJson(route('admin.gallery.store'), ['files' => [$file]])
        ->assertCreated();

    $galleryId = (int) $upload->json('data.0.id');
    $gallery = Gallery::query()->findOrFail($galleryId);
    $originalPath = $gallery->file_path;

    $this->actingAs($this->user)
        ->deleteJson(route('admin.gallery.destroy', ['id' => $galleryId]))
        ->assertOk()
        ->assertJsonFragment(['message' => 'File moved to bin.']);

    $gallery->refresh();
    expect($gallery->trashed())->toBeTrue();
    expect($gallery->bin_path)->not->toBeEmpty();
    Storage::disk('public')->assertMissing($originalPath);
    Storage::disk('public')->assertExists($gallery->file_path);

    $this->actingAs($this->user)
        ->patchJson(route('admin.gallery.restore', ['id' => $galleryId]))
        ->assertOk();

    $gallery->refresh();
    expect($gallery->trashed())->toBeFalse();
    expect($gallery->bin_path)->toBeNull();
    Storage::disk('public')->assertExists($gallery->file_path);
});

test('user can permanently delete a binned gallery item', function () {
    $file = UploadedFile::fake()->image('temp.png', 40, 40);

    $upload = $this->actingAs($this->user)
        ->postJson(route('admin.gallery.store'), ['files' => [$file]])
        ->assertCreated();

    $galleryId = (int) $upload->json('data.0.id');
    $gallery = Gallery::query()->findOrFail($galleryId);

    $this->actingAs($this->user)
        ->deleteJson(route('admin.gallery.destroy', ['id' => $galleryId]))
        ->assertOk();

    $gallery->refresh();
    $binPath = $gallery->file_path;

    $this->actingAs($this->user)
        ->deleteJson(route('admin.gallery.force-destroy', ['id' => $galleryId]))
        ->assertOk();

    $this->assertDatabaseMissing('galleries', ['id' => $galleryId]);
    Storage::disk('public')->assertMissing($binPath);
});

test('gallery data endpoint supports search and trash filter', function () {
    $this->actingAs($this->user)
        ->postJson(route('admin.gallery.store'), [
            'files' => [UploadedFile::fake()->image('alpha.png')],
        ])->assertCreated();

    $this->actingAs($this->user)
        ->postJson(route('admin.gallery.store'), [
            'files' => [UploadedFile::fake()->image('beta.png')],
        ])->assertCreated();

    $beta = Gallery::where('original_name', 'beta.png')->first();
    $this->actingAs($this->user)->deleteJson(route('admin.gallery.destroy', $beta->id))->assertOk();

    $this->actingAs($this->user)
        ->getJson(route('admin.gallery.data', ['search' => 'alpha']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.original_name', 'alpha.png');

    $this->actingAs($this->user)
        ->getJson(route('admin.gallery.data', ['trash' => 'bin']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.original_name', 'beta.png');
});

test('editor upload also creates gallery original and adjusted records', function () {
    $original = UploadedFile::fake()->image('editor-shot.png', 120, 80);
    $adjusted = UploadedFile::fake()->image('editor-shot.jpg', 80, 60);

    $this->actingAs($this->user)
        ->postJson(route('admin.editor.media.store'), [
            'kind' => 'image',
            'file' => $adjusted,
            'original' => $original,
        ])
        ->assertOk()
        ->assertJsonStructure(['location', 'id', 'original', 'adjusted']);

    $this->assertDatabaseHas('galleries', [
        'organization_id' => $this->organization->id,
        'source' => 'editor',
        'variant' => 'original',
    ]);

    $this->assertDatabaseHas('galleries', [
        'organization_id' => $this->organization->id,
        'source' => 'editor',
        'variant' => 'adjusted',
    ]);

    expect(
        Gallery::query()
            ->where('organization_id', $this->organization->id)
            ->where('variant', 'adjusted')
            ->whereNotNull('parent_id')
            ->count()
    )->toBe(1);
});

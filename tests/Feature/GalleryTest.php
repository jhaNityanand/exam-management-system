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
        ->assertJsonPath('data.0.has_modification', false)
        ->assertJsonStructure(['data', 'stats', 'message']);

    $this->assertDatabaseHas('galleries', [
        'organization_id' => $this->organization->id,
        'original_name' => 'banner.png',
        'kind' => 'image',
        'source' => 'gallery',
    ]);

    $filePath = (string) $response->json('data.0.file_path');
    $originalPath = (string) $response->json('data.0.original_file_path');
    expect($filePath)->not->toBeEmpty();
    expect($originalPath)->toBe($filePath);
    expect($response->json('data.0.modified_file_path'))->toBeNull();
    Storage::disk('public')->assertExists($filePath);

    $fileUrl = (string) $response->json('data.0.file_url');
    expect($fileUrl)->toStartWith(rtrim(url('/'), '/'));
    expect(parse_url($fileUrl, PHP_URL_PATH))->toContain('/storage/gallery/');
});

test('user can commit a staged file with optional edited original', function () {
    $original = UploadedFile::fake()->image('staged.png', 240, 180);
    $edited = UploadedFile::fake()->image('staged-edited.jpg', 120, 120);

    $response = $this->actingAs($this->user)
        ->postJson(route('admin.gallery.commit'), [
            'file' => $edited,
            'original' => $original,
        ])
        ->assertCreated()
        ->assertJsonPath('data.has_modification', true);

    $row = Gallery::query()->findOrFail((int) $response->json('data.id'));
    expect($row->original_file_path)->not->toBeEmpty();
    expect($row->modified_file_path)->not->toBeEmpty();
    expect($row->original_file_path)->not->toBe($row->modified_file_path);
    Storage::disk('public')->assertExists($row->original_file_path);
    Storage::disk('public')->assertExists($row->modified_file_path);
});

test('user can commit a staged file without editing', function () {
    $file = UploadedFile::fake()->image('plain.png', 80, 60);

    $response = $this->actingAs($this->user)
        ->postJson(route('admin.gallery.commit'), [
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.has_modification', false);

    $row = Gallery::query()->findOrFail((int) $response->json('data.id'));
    expect($row->original_file_path)->toBe($row->file_path);
    expect($row->modified_file_path)->toBeNull();
});

test('user can save an edited image while keeping the original', function () {
    $upload = $this->actingAs($this->user)
        ->postJson(route('admin.gallery.store'), [
            'files' => [UploadedFile::fake()->image('photo.png', 200, 120)],
        ])
        ->assertCreated();

    $galleryId = (int) $upload->json('data.0.id');
    $originalPath = (string) $upload->json('data.0.original_file_path');

    $edited = UploadedFile::fake()->image('photo-edited.jpg', 100, 80);

    $this->actingAs($this->user)
        ->postJson(route('admin.gallery.edit', ['id' => $galleryId]), [
            'file' => $edited,
        ])
        ->assertOk()
        ->assertJsonPath('data.has_modification', true);

    $gallery = Gallery::query()->findOrFail($galleryId);
    expect($gallery->original_file_path)->toBe($originalPath);
    expect($gallery->modified_file_path)->not->toBeEmpty();
    expect($gallery->modified_file_path)->not->toBe($originalPath);
    expect($gallery->file_path)->toBe($gallery->modified_file_path);
    Storage::disk('public')->assertExists($originalPath);
    Storage::disk('public')->assertExists($gallery->modified_file_path);

    $oldModified = $gallery->modified_file_path;

    $this->actingAs($this->user)
        ->postJson(route('admin.gallery.edit', ['id' => $galleryId]), [
            'file' => UploadedFile::fake()->image('photo-edited-2.jpg', 90, 70),
        ])
        ->assertOk();

    $gallery->refresh();
    expect($gallery->original_file_path)->toBe($originalPath);
    expect($gallery->modified_file_path)->not->toBe($oldModified);
    Storage::disk('public')->assertExists($originalPath);
    Storage::disk('public')->assertExists($gallery->modified_file_path);
    Storage::disk('public')->assertMissing($oldModified);

    $this->actingAs($this->user)
        ->postJson(route('admin.gallery.revert', ['id' => $galleryId]))
        ->assertOk()
        ->assertJsonPath('data.has_modification', false);

    $gallery->refresh();
    expect($gallery->modified_file_path)->toBeNull();
    expect($gallery->file_path)->toBe($originalPath);
    Storage::disk('public')->assertExists($originalPath);
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

test('editor upload stores original and modified on one gallery row', function () {
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

    expect(Gallery::query()->where('organization_id', $this->organization->id)->count())->toBe(1);

    $row = Gallery::query()->where('organization_id', $this->organization->id)->first();
    expect($row->source)->toBe('editor');
    expect($row->original_file_path)->not->toBeEmpty();
    expect($row->modified_file_path)->not->toBeEmpty();
    Storage::disk('public')->assertExists($row->original_file_path);
    Storage::disk('public')->assertExists($row->modified_file_path);
});

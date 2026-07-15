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
        'name' => 'Editor Org',
        'slug' => 'editor-org-' . $this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
});

test('editor image upload stores original and modified on one gallery row', function () {
    $original = UploadedFile::fake()->image('diagram.png', 400, 300);
    $adjusted = UploadedFile::fake()->image('diagram.jpg', 200, 150);

    $response = $this->actingAs($this->user)
        ->postJson(route('admin.editor.media.store'), [
            'kind' => 'image',
            'file' => $adjusted,
            'original' => $original,
            'display_name' => 'diagram.png',
        ]);

    $response->assertOk()
        ->assertJsonStructure(['location', 'url', 'id', 'kind', 'original', 'adjusted']);

    $path = parse_url($response->json('location'), PHP_URL_PATH);
    expect($path)->toContain('/storage/gallery/');

    expect(Gallery::query()->where('organization_id', $this->organization->id)->count())->toBe(1);

    $row = Gallery::query()->where('organization_id', $this->organization->id)->first();
    expect($row->original_file_path)->not->toBeEmpty();
    expect($row->modified_file_path)->not->toBeEmpty();
    expect($row->modified_file_path)->not->toBe($row->original_file_path);
    expect($response->json('id'))->toBe($row->id);
    expect($response->json('adjusted.id'))->toBe($row->id);
    expect($response->json('original.url'))->not->toBeEmpty();
    Storage::disk('public')->assertExists($row->original_file_path);
    Storage::disk('public')->assertExists($row->modified_file_path);
});

test('editor upload rejects disallowed mime types', function () {
    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream');

    $this->actingAs($this->user)
        ->postJson(route('admin.editor.media.store'), [
            'kind' => 'file',
            'file' => $file,
        ])
        ->assertStatus(422);
});

test('editor non-image upload creates a single gallery row', function () {
    $file = UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf');

    $this->actingAs($this->user)
        ->postJson(route('admin.editor.media.store'), [
            'kind' => 'file',
            'file' => $file,
        ])
        ->assertOk()
        ->assertJsonPath('kind', 'document');

    expect(Gallery::query()->where('organization_id', $this->organization->id)->count())->toBe(1);
    $row = Gallery::query()->where('organization_id', $this->organization->id)->first();
    expect($row->original_file_path)->not->toBeEmpty();
    expect($row->modified_file_path)->toBeNull();
});

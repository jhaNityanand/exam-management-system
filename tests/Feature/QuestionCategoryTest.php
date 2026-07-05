<?php

use App\Models\Organization;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;

beforeEach(function () {
    // Set up test user and organization
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Test Org',
        'slug' => 'test-org-' . $this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
});

test('unauthenticated users are redirected to login', function () {
    $this->get(route('admin.questions.categories.index'))
        ->assertRedirect('/login');
});

test('authenticated user can view categories list shell', function () {
    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.categories.index'));

    $response->assertOk()
        ->assertViewIs('backend.question-categories.index');
});

test('user can fetch categories tree via ajax', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Science',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.categories.index'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

    $response->assertOk()
        ->assertSee('Science');
});

test('user can store a new category tree', function () {
    $payload = [
        'categories' => [
            'node-0' => [
                'name' => 'Physics',
                'description' => 'Physics description',
            ],
            'node-1' => [
                'name' => 'Quantum mechanics',
                'description' => 'Quantum description',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('admin.questions.categories.store'), $payload);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.categories.index'));

    $this->assertDatabaseHas('question_categories', [
        'name' => 'Physics',
        'parent_id' => null,
        'organization_id' => $this->organization->id,
    ]);

    $physics = QuestionCategory::where('name', 'Physics')->first();

    $this->assertDatabaseHas('question_categories', [
        'name' => 'Quantum mechanics',
        'parent_id' => $physics->id,
        'organization_id' => $this->organization->id,
    ]);
});

test('user can fetch category JSON details', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Chemistry',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.categories.show', $category));

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'Chemistry',
        ]);
});

test('user cannot view category from another organization', function () {
    $otherOrg = Organization::create([
        'name' => 'Other Org',
        'slug' => 'other-org-' . uniqid(),
        'status' => 'active',
    ]);

    $otherCategory = QuestionCategory::create([
        'organization_id' => $otherOrg->id,
        'name' => 'Secret Category',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.categories.show', $otherCategory));

    $response->assertStatus(403);
});

test('user can edit category hierarchy and persist modifications', function () {
    $physics = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Physics',
        'status' => 'active',
    ]);

    $quantum = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id' => $physics->id,
        'name' => 'Quantum',
        'status' => 'active',
    ]);

    // Rename physics to Classical Physics, add Relativity under Classical Physics, and delete Quantum
    $payload = [
        'categories' => [
            'node-0' => [
                'id' => $physics->id,
                'name' => 'Classical Physics',
                'description' => 'Updated physics',
            ],
            'node-1' => [
                'name' => 'Relativity',
                'description' => 'New child',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $response = $this->actingAs($this->user)
        ->put(route('admin.questions.categories.update', $physics), $payload);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.categories.edit', $physics));

    $this->assertDatabaseHas('question_categories', [
        'id' => $physics->id,
        'name' => 'Classical Physics',
    ]);

    // Check Relativity was created under Classical Physics
    $this->assertDatabaseHas('question_categories', [
        'name' => 'Relativity',
        'parent_id' => $physics->id,
    ]);

    // Check Quantum was soft-deleted
    $this->assertSoftDeleted('question_categories', [
        'id' => $quantum->id,
    ]);
});

test('user can delete a category', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Biology',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('admin.questions.categories.destroy', $category));

    $response->assertRedirect(route('admin.questions.categories.index'));
    $this->assertSoftDeleted('question_categories', [
        'id' => $category->id,
    ]);
});

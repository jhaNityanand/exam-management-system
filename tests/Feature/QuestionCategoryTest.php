<?php

use App\Models\Organization;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name'   => 'Test Org',
        'slug'   => 'test-org-' . $this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id'         => $this->user->id,
        'organization_id' => $this->organization->id,
        'role'            => 'admin',
        'status'          => 'active',
    ]);
});

test('unauthenticated users are redirected to login', function () {
    $this->get(route('admin.questions.categories.index'))
        ->assertRedirect('/login');
});

test('authenticated user can view categories list shell', function () {
    $this->actingAs($this->user)
        ->get(route('admin.questions.categories.index'))
        ->assertOk()
        ->assertViewIs('backend.question-categories.index');
});

test('user can fetch categories tree via ajax', function () {
    QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Science',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.questions.categories.index'))
        ->assertOk()
        ->assertSee('Science');
});

test('user can search categories via ajax', function () {
    QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Mathematics',
        'status'          => 'active',
    ]);
    QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'History',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.questions.categories.index', ['search' => 'Math']))
        ->assertOk()
        ->assertSee('Mathematics')
        ->assertDontSee('History');
});

test('user can filter categories by status via ajax', function () {
    QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Active Cat',
        'status'          => 'active',
    ]);
    QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Suspended Cat',
        'status'          => 'suspended',
    ]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.questions.categories.index', ['status' => 'suspended']))
        ->assertOk()
        ->assertSee('Suspended Cat')
        ->assertDontSee('Active Cat');
});

test('user can store a new category tree', function () {
    $payload = [
        'categories' => [
            'node-0' => [
                'name'        => 'Physics',
                'description' => 'Physics description',
            ],
            'node-1' => [
                'name'        => 'Quantum mechanics',
                'description' => 'Quantum description',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $this->actingAs($this->user)
        ->post(route('admin.questions.categories.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.categories.index'));

    $this->assertDatabaseHas('question_categories', [
        'name'            => 'Physics',
        'parent_id'       => null,
        'organization_id' => $this->organization->id,
    ]);

    $physics = QuestionCategory::where('name', 'Physics')->first();

    $this->assertDatabaseHas('question_categories', [
        'name'            => 'Quantum mechanics',
        'parent_id'       => $physics->id,
        'organization_id' => $this->organization->id,
    ]);
});

test('store rejects empty category names', function () {
    $this->actingAs($this->user)
        ->post(route('admin.questions.categories.store'), [
            'categories' => [
                'node-0' => ['name' => '', 'description' => ''],
            ],
            'status' => 'active',
        ])
        ->assertSessionHasErrors('categories.node-0.name');
});

test('user can fetch category JSON details', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Chemistry',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.questions.categories.show', $category))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Chemistry']);
});

test('user cannot view category from another organization', function () {
    $otherOrg = Organization::create([
        'name'   => 'Other Org',
        'slug'   => 'other-org-' . uniqid(),
        'status' => 'active',
    ]);

    $otherCategory = QuestionCategory::create([
        'organization_id' => $otherOrg->id,
        'name'            => 'Secret Category',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.questions.categories.show', $otherCategory))
        ->assertStatus(403);
});

test('user can edit category hierarchy and persist modifications', function () {
    $physics = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Physics',
        'status'          => 'active',
    ]);

    $quantum = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id'       => $physics->id,
        'name'            => 'Quantum',
        'status'          => 'active',
    ]);

    $payload = [
        'categories' => [
            'node-0' => [
                'id'          => $physics->id,
                'name'        => 'Classical Physics',
                'description' => 'Updated physics',
            ],
            'node-1' => [
                'name'        => 'Relativity',
                'description' => 'New child',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $this->actingAs($this->user)
        ->put(route('admin.questions.categories.update', $physics), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.categories.index'));

    $this->assertDatabaseHas('question_categories', [
        'id'   => $physics->id,
        'name' => 'Classical Physics',
    ]);

    $this->assertDatabaseHas('question_categories', [
        'name'      => 'Relativity',
        'parent_id' => $physics->id,
    ]);

    $this->assertSoftDeleted('question_categories', [
        'id' => $quantum->id,
    ]);
});

test('editing a nested category preserves its parent', function () {
    $science = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Science',
        'status'          => 'active',
    ]);

    $physics = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id'       => $science->id,
        'name'            => 'Physics',
        'status'          => 'active',
    ]);

    $mechanics = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id'       => $physics->id,
        'name'            => 'Mechanics',
        'status'          => 'active',
    ]);

    $payload = [
        'categories' => [
            'node-0' => [
                'id'   => $physics->id,
                'name' => 'Physics Updated',
            ],
            'node-1' => [
                'id'   => $mechanics->id,
                'name' => 'Mechanics',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $this->actingAs($this->user)
        ->put(route('admin.questions.categories.update', $physics), $payload)
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('question_categories', [
        'id'        => $physics->id,
        'name'      => 'Physics Updated',
        'parent_id' => $science->id,
    ]);
});

test('user cannot edit or update category from another organization', function () {
    $otherOrg = Organization::create([
        'name'   => 'Other Org',
        'slug'   => 'other-org-upd-' . uniqid(),
        'status' => 'active',
    ]);

    $otherCategory = QuestionCategory::create([
        'organization_id' => $otherOrg->id,
        'name'            => 'Foreign',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.questions.categories.edit', $otherCategory))
        ->assertStatus(403);

    $this->actingAs($this->user)
        ->put(route('admin.questions.categories.update', $otherCategory), [
            'categories' => [
                'node-0' => [
                    'name' => 'Hacked',
                ],
            ],
            'status' => 'active',
        ])
        ->assertStatus(403);
});

test('user can delete a category', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Biology',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->delete(route('admin.questions.categories.destroy', $category))
        ->assertRedirect(route('admin.questions.categories.index'));

    $this->assertSoftDeleted('question_categories', [
        'id' => $category->id,
    ]);
});

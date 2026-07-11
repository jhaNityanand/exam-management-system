<?php

use App\Models\ExamCategory;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name'   => 'Test Org',
        'slug'   => 'test-org-ec-' . $this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id'         => $this->user->id,
        'organization_id' => $this->organization->id,
        'role'            => 'admin',
        'status'          => 'active',
    ]);
});

// ── Auth ──────────────────────────────────────────────────────────────────────

test('unauthenticated users are redirected to login from exam categories', function () {
    $this->get(route('admin.exams.categories.index'))
        ->assertRedirect('/login');
});

// ── List ──────────────────────────────────────────────────────────────────────

test('authenticated user can view exam categories list shell', function () {
    $this->actingAs($this->user)
        ->get(route('admin.exams.categories.index'))
        ->assertOk()
        ->assertViewIs('backend.exam-categories.index');
});

test('user can fetch exam categories tree via ajax', function () {
    ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Corporate Hiring',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.exams.categories.index'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ])
        ->assertOk()
        ->assertSee('Corporate Hiring');
});

// ── Store ─────────────────────────────────────────────────────────────────────

test('user can store a new exam category tree', function () {
    $payload = [
        'categories' => [
            'node-0' => [
                'name'        => 'Academic',
                'description' => 'Academic exams',
            ],
            'node-1' => [
                'name'        => 'University Entrance',
                'description' => 'University level',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $this->actingAs($this->user)
        ->post(route('admin.exams.categories.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.exams.categories.index'));

    $this->assertDatabaseHas('exam_categories', [
        'name'            => 'Academic',
        'parent_id'       => null,
        'organization_id' => $this->organization->id,
    ]);

    $academic = ExamCategory::where('name', 'Academic')->first();

    $this->assertDatabaseHas('exam_categories', [
        'name'            => 'University Entrance',
        'parent_id'       => $academic->id,
        'organization_id' => $this->organization->id,
    ]);
});

// ── Show ──────────────────────────────────────────────────────────────────────

test('user can fetch exam category JSON details', function () {
    $category = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Finance & Accounting',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.exams.categories.show', $category))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Finance & Accounting']);
});

test('user cannot view exam category from another organization', function () {
    $otherOrg = Organization::create([
        'name'   => 'Other Org',
        'slug'   => 'other-org-ec-' . uniqid(),
        'status' => 'active',
    ]);

    $otherCategory = ExamCategory::create([
        'organization_id' => $otherOrg->id,
        'name'            => 'Secret Category',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.exams.categories.show', $otherCategory))
        ->assertStatus(403);
});

// ── Update ────────────────────────────────────────────────────────────────────

test('user can edit exam category hierarchy and persist modifications', function () {
    $parent = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'IT Certifications',
        'status'          => 'active',
    ]);

    $child = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id'       => $parent->id,
        'name'            => 'Networking',
        'status'          => 'active',
    ]);

    $payload = [
        'categories' => [
            'node-0' => [
                'id'          => $parent->id,
                'name'        => 'IT Certifications (Updated)',
                'description' => 'Updated description',
            ],
            'node-1' => [
                'name'        => 'Cloud & DevOps',
                'description' => 'New child node',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $this->actingAs($this->user)
        ->put(route('admin.exams.categories.update', $parent), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.exams.categories.edit', $parent));

    $this->assertDatabaseHas('exam_categories', [
        'id'   => $parent->id,
        'name' => 'IT Certifications (Updated)',
    ]);

    $this->assertDatabaseHas('exam_categories', [
        'name'      => 'Cloud & DevOps',
        'parent_id' => $parent->id,
    ]);

    // Networking was not in the updated payload, so it should be soft-deleted
    $this->assertSoftDeleted('exam_categories', [
        'id' => $child->id,
    ]);
});

// ── Destroy ───────────────────────────────────────────────────────────────────

test('user can delete an exam category', function () {
    $category = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'To Be Deleted',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->delete(route('admin.exams.categories.destroy', $category))
        ->assertRedirect(route('admin.exams.categories.index'));

    $this->assertSoftDeleted('exam_categories', [
        'id' => $category->id,
    ]);
});

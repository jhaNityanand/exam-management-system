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

test('unauthenticated users are redirected to login from exam categories', function () {
    $this->get(route('admin.exams.categories.index'))
        ->assertRedirect('/login');
});

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
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.exams.categories.index'))
        ->assertOk()
        ->assertSee('Corporate Hiring');
});

test('user can search exam categories via ajax', function () {
    ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Academic Examinations',
        'status'          => 'active',
    ]);
    ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Corporate Hiring',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.exams.categories.index', ['search' => 'Academic']))
        ->assertOk()
        ->assertSee('Academic Examinations')
        ->assertDontSee('Corporate Hiring');
});

test('user can filter exam categories by status via ajax', function () {
    ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Active Exam Cat',
        'status'          => 'active',
    ]);
    ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Suspended Exam Cat',
        'status'          => 'suspended',
    ]);

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.exams.categories.index', ['status' => 'suspended']))
        ->assertOk()
        ->assertSee('Suspended Exam Cat')
        ->assertDontSee('Active Exam Cat');
});

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

test('store rejects empty exam category names', function () {
    $this->actingAs($this->user)
        ->post(route('admin.exams.categories.store'), [
            'categories' => [
                'node-0' => ['name' => '', 'description' => ''],
            ],
            'status' => 'active',
        ])
        ->assertSessionHasErrors('categories.node-0.name');
});

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
        ->assertRedirect(route('admin.exams.categories.index'));

    $this->assertDatabaseHas('exam_categories', [
        'id'   => $parent->id,
        'name' => 'IT Certifications (Updated)',
    ]);

    $this->assertDatabaseHas('exam_categories', [
        'name'      => 'Cloud & DevOps',
        'parent_id' => $parent->id,
    ]);

    $this->assertSoftDeleted('exam_categories', [
        'id' => $child->id,
    ]);
});

test('editing a nested exam category preserves its parent', function () {
    $academic = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Academic Examinations',
        'status'          => 'active',
    ]);

    $entrance = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id'       => $academic->id,
        'name'            => 'University Entrance',
        'status'          => 'active',
    ]);

    $science = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id'       => $entrance->id,
        'name'            => 'Science Stream',
        'status'          => 'active',
    ]);

    $payload = [
        'categories' => [
            'node-0' => [
                'id'   => $entrance->id,
                'name' => 'University Entrance Updated',
            ],
            'node-1' => [
                'id'   => $science->id,
                'name' => 'Science Stream',
            ],
        ],
        '_parent_map' => json_encode([
            'node-1' => 'node-0',
        ]),
        'status' => 'active',
    ];

    $this->actingAs($this->user)
        ->put(route('admin.exams.categories.update', $entrance), $payload)
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('exam_categories', [
        'id'        => $entrance->id,
        'name'      => 'University Entrance Updated',
        'parent_id' => $academic->id,
    ]);
});

test('user cannot edit or update exam category from another organization', function () {
    $otherOrg = Organization::create([
        'name'   => 'Other Org',
        'slug'   => 'other-org-ec-upd-' . uniqid(),
        'status' => 'active',
    ]);

    $otherCategory = ExamCategory::create([
        'organization_id' => $otherOrg->id,
        'name'            => 'Foreign Exam Cat',
        'status'          => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.exams.categories.edit', $otherCategory))
        ->assertStatus(403);

    $this->actingAs($this->user)
        ->put(route('admin.exams.categories.update', $otherCategory), [
            'categories' => [
                'node-0' => [
                    'name' => 'Hacked',
                ],
            ],
            'status' => 'active',
        ])
        ->assertStatus(403);
});

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

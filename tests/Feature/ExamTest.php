<?php

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name'   => 'Test Org',
        'slug'   => 'test-org-exam-' . $this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id'         => $this->user->id,
        'organization_id' => $this->organization->id,
        'role'            => 'admin',
        'status'          => 'active',
    ]);
});

// ── Helper ────────────────────────────────────────────────────────────────────

function makeExam(int $orgId, array $overrides = []): Exam
{
    return Exam::create(array_merge([
        'organization_id' => $orgId,
        'title'           => 'Sample Exam ' . uniqid(),
        'status'          => 'draft',
        'exam_mode'       => 'standard',
        'exam_format'     => ['mcq'],
        'visibility'      => 'public',
        'duration'        => 60,
        'pass_percentage' => 50,
        'max_attempts'    => 1,
    ], $overrides));
}

// ── Auth ──────────────────────────────────────────────────────────────────────

test('unauthenticated users are redirected to login from exams index', function () {
    $this->get(route('admin.exams.index'))
        ->assertRedirect('/login');
});

// ── List ──────────────────────────────────────────────────────────────────────

test('authenticated user can view exams list page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.exams.index'))
        ->assertOk()
        ->assertViewIs('backend.exams.index')
        ->assertSee('name="filters[exam_mode][]"', false)
        ->assertSee('name="filters[created_from]"', false)
        ->assertSee('data-date-preset-select', false)
        ->assertSee('This Quarter', false)
        ->assertSee('Custom Range', false);
});

test('user can query exams table via internal API', function () {
    $exam = makeExam($this->organization->id, ['title' => 'Laravel Proficiency Test']);

    $this->actingAs($this->user)
        ->get(route('admin.internal-api.exams-table'))
        ->assertOk()
        ->assertJsonFragment(['title' => 'Laravel Proficiency Test']);
});

test('user can search and filter exams via internal API', function () {
    makeExam($this->organization->id, ['title' => 'Published Cloud Exam', 'status' => 'published']);
    makeExam($this->organization->id, ['title' => 'Draft Physics Test',   'status' => 'draft']);

    // Search returns only the matching exam
    $this->actingAs($this->user)
        ->get(route('admin.internal-api.exams-table', ['search' => 'Cloud']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Published Cloud Exam']);

    // Filter by status
    $this->actingAs($this->user)
        ->get(route('admin.internal-api.exams-table', ['filters' => ['status' => 'draft']]))
        ->assertOk()
        ->assertJsonFragment(['status' => 'draft']);
});

// ── Show ──────────────────────────────────────────────────────────────────────

test('user can view exam details page', function () {
    $exam = makeExam($this->organization->id, ['title' => 'Visible Exam']);

    $this->actingAs($this->user)
        ->get(route('admin.exams.show', $exam))
        ->assertOk()
        ->assertViewIs('backend.exams.show')
        ->assertSee('Visible Exam');
});

test('user cannot view exam from another organization', function () {
    $otherOrg = Organization::create([
        'name'   => 'Other Org',
        'slug'   => 'other-org-exam-' . uniqid(),
        'status' => 'active',
    ]);

    $exam = makeExam($otherOrg->id, ['title' => 'Secret Exam']);

    $this->actingAs($this->user)
        ->get(route('admin.exams.show', $exam))
        ->assertStatus(403);
});

// ── Create / Store ────────────────────────────────────────────────────────────

test('user can view the exam create page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.exams.create'))
        ->assertOk()
        ->assertViewIs('backend.exams.create');
});

test('user can store a new exam', function () {
    $category = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Aptitude Tests',
        'status'          => 'active',
    ]);

    $payload = [
        'title'                   => 'Java Developer Assessment',
        'description'             => '<p>A technical screening for Java candidates.</p>',
        'exam_category_id'        => $category->id,
        'status'                  => 'draft',
        'exam_mode'               => 'standard',
        'exam_format'             => ['mcq'],
        'visibility'              => 'public',
        'exam_duration_minutes'   => 90,
        'enable_exam_timer'       => 1,
        'auto_submit_on_timer_end'=> 1,
        'schedule_type'           => 'any_time',
        'attempt_limit_type'      => 'once',
        'total_questions'         => 30,
        'total_categories'        => 2,
        'total_marks'             => 60,
        'passing_marks'           => 36,
        'paper_sets'              => 1,
    ];

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirectContains('exams');

    $this->assertDatabaseHas('exams', [
        'title'           => 'Java Developer Assessment',
        'status'          => 'draft',
        'duration'        => 90,
        'organization_id' => $this->organization->id,
        'category_id'     => $category->id,
    ]);
});

test('store exam accepts all exam formats including true false and fill blank', function () {
    $payload = [
        'title'                 => 'Mixed Format Exam',
        'status'                => 'draft',
        'exam_mode'             => 'standard',
        'exam_format'           => ['mcq', 'true_false', 'written', 'fill_blank', 'multi_select'],
        'visibility'            => 'public',
        'exam_duration_minutes' => 60,
        'schedule_type'         => 'any_time',
        'attempt_limit_type'    => 'fixed_count',
        'attempt_limit_count'   => 3,
        'total_questions'       => 20,
        'total_categories'      => 1,
        'total_marks'           => 40,
        'passing_marks'         => 20,
        'paper_sets'            => 1,
    ];

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirectContains('exams');

    $exam = Exam::where('title', 'Mixed Format Exam')->first();
    expect($exam)->not->toBeNull();
    expect($exam->exam_format)->toEqualCanonicalizing([
        'mcq', 'true_false', 'written', 'fill_blank', 'multi_select',
    ]);
    expect($exam->max_attempts)->toBe(3);
});

test('exams table can filter by multiple categories and formats', function () {
    $catA = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Filter Cat A',
        'status'          => 'active',
    ]);
    $catB = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Filter Cat B',
        'status'          => 'active',
    ]);

    makeExam($this->organization->id, [
        'title'       => 'TF Exam',
        'category_id' => $catA->id,
        'exam_format' => ['true_false'],
    ]);
    makeExam($this->organization->id, [
        'title'       => 'Fill Exam',
        'category_id' => $catB->id,
        'exam_format' => ['fill_blank'],
    ]);
    makeExam($this->organization->id, [
        'title'       => 'Other Exam',
        'category_id' => $catA->id,
        'exam_format' => ['mcq'],
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.internal-api.exams-table', [
            'filters' => [
                'category_id' => [$catA->id, $catB->id],
                'exam_format' => ['true_false', 'fill_blank'],
            ],
        ]))
        ->assertOk()
        ->assertJsonFragment(['title' => 'TF Exam'])
        ->assertJsonFragment(['title' => 'Fill Exam'])
        ->assertJsonMissing(['title' => 'Other Exam']);
});

// ── Edit / Update ─────────────────────────────────────────────────────────────

test('user can view the exam edit page', function () {
    $exam = makeExam($this->organization->id, ['title' => 'Editable Exam']);

    $this->actingAs($this->user)
        ->get(route('admin.exams.edit', $exam))
        ->assertOk()
        ->assertViewIs('backend.exams.edit')
        ->assertSee('Editable Exam');
});

test('user can update an exam', function () {
    $exam = makeExam($this->organization->id, ['title' => 'Old Title']);

    $payload = [
        'title'      => 'Updated Exam Title',
        'status'     => 'published',
        'exam_mode'  => 'practice',
        'exam_format'=> ['mcq'],
        'visibility' => 'private',
        'duration'   => 45,
        'total_questions' => 20,
        'total_categories'=> 1,
        'total_marks'     => 40,
        'passing_marks'   => 25,
        'paper_sets'      => 1,
        'schedule_type'   => 'any_time',
        'attempt_limit_type' => 'unlimited',
    ];

    $this->actingAs($this->user)
        ->put(route('admin.exams.update', $exam), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.exams.show', $exam));

    $this->assertDatabaseHas('exams', [
        'id'     => $exam->id,
        'title'  => 'Updated Exam Title',
        'status' => 'published',
    ]);
});

// ── Destroy ───────────────────────────────────────────────────────────────────

test('user can soft-delete an exam', function () {
    $exam = makeExam($this->organization->id, ['title' => 'Exam to Delete']);

    $this->actingAs($this->user)
        ->delete(route('admin.exams.destroy', $exam))
        ->assertRedirect(route('admin.exams.index'));

    $this->assertSoftDeleted('exams', [
        'id' => $exam->id,
    ]);
});

// ── Publish ───────────────────────────────────────────────────────────────────

test('user can publish an exam', function () {
    $exam = makeExam($this->organization->id, ['title' => 'Draft Exam', 'status' => 'draft']);

    $this->actingAs($this->user)
        ->patch(route('admin.exams.publish', $exam))
        ->assertRedirect(route('admin.exams.show', $exam));

    $this->assertDatabaseHas('exams', [
        'id'     => $exam->id,
        'status' => 'published',
    ]);
});

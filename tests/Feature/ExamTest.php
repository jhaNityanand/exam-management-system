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

function makeQuestionCategory(int $orgId, string $name = 'QB Category'): \App\Models\QuestionCategory
{
    return \App\Models\QuestionCategory::create([
        'organization_id' => $orgId,
        'name' => $name.' '.uniqid(),
        'status' => 'active',
    ]);
}

function baseExamStorePayload(int $orgId, array $overrides = []): array
{
    $category = makeQuestionCategory($orgId);

    return array_merge([
        'title' => 'Base Exam '.uniqid(),
        'status' => 'draft',
        'exam_mode' => 'standard',
        'exam_format' => ['mcq'],
        'visibility' => 'public',
        'exam_duration_minutes' => 60,
        'schedule_type' => 'any_time',
        'attempt_limit_type' => 'once',
        'total_questions' => 10,
        'total_marks' => 20,
        'passing_marks' => 10,
        'selected_categories' => [$category->id],
        'question_marks_filter' => [1, 2],
        'fixed_questions' => 0,
        'use_question_pool' => 0,
        'paper_sets' => 1,
    ], $overrides);
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
    $examCategory = ExamCategory::create([
        'organization_id' => $this->organization->id,
        'name'            => 'Aptitude Tests',
        'status'          => 'active',
    ]);
    $questionCategory = makeQuestionCategory($this->organization->id, 'Store QB');

    $payload = baseExamStorePayload($this->organization->id, [
        'title'                   => 'Java Developer Assessment',
        'description'             => '<p>A technical screening for Java candidates.</p>',
        'exam_category_id'        => $examCategory->id,
        'exam_duration_minutes'   => 90,
        'enable_exam_timer'       => 1,
        'auto_submit_on_timer_end'=> 1,
        'total_questions'         => 30,
        'total_marks'             => 60,
        'passing_marks'           => 36,
        'selected_categories'     => [$questionCategory->id],
        'question_marks_filter'   => [1, 2],
        'fixed_paper_set'         => 1,
        'paper_sets'              => 3,
        'shuffle_questions'       => 1,
        'shuffle_categories'      => 1,
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirectContains('exams');

    $this->assertDatabaseHas('exams', [
        'title'           => 'Java Developer Assessment',
        'status'          => 'draft',
        'duration'        => 90,
        'organization_id' => $this->organization->id,
        'category_id'     => $examCategory->id,
        'fixed_questions' => false,
        'fixed_paper_set' => true,
        'paper_sets' => 3,
        'shuffle_questions' => true,
        'shuffle_categories' => true,
    ]);

    $exam = Exam::where('title', 'Java Developer Assessment')->first();
    expect($exam->questions()->count())->toBe(0);
});

test('question pool requires a larger maximum and disables fixed questions', function () {
    $category = makeQuestionCategory($this->organization->id, 'Pool Category');

    $questionIds = [];
    for ($i = 1; $i <= 3; $i++) {
        $questionIds[] = \App\Models\Question::create([
            'organization_id' => $this->organization->id,
            'category_id' => $category->id,
            'body' => "Pool question {$i}",
            'type' => 'true_false',
            'correct_answer' => 'True',
            'difficulty' => 'easy',
            'marks_type' => 'single',
            'marks' => 1,
            'status' => 'active',
        ])->id;
    }

    $payload = baseExamStorePayload($this->organization->id, [
        'title' => 'Question Pool Exam',
        'total_questions' => 2,
        'total_marks' => 4,
        'passing_marks' => 2,
        'use_question_pool' => 1,
        'maximum_questions' => 3,
        'fixed_questions' => 1,
        'selected_categories' => [$category->id],
        'question_marks_filter' => [1],
        'question_ids' => $questionIds,
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), $payload)
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('exams', [
        'title' => 'Question Pool Exam',
        'use_question_pool' => true,
        'maximum_questions' => 3,
        'fixed_questions' => false,
    ]);

    $exam = Exam::where('title', 'Question Pool Exam')->first();
    expect($exam->questions()->count())->toBe(3);

    $payload['title'] = 'Invalid Question Pool Exam';
    $payload['maximum_questions'] = 2;

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), $payload)
        ->assertSessionHasErrors('maximum_questions');
});

test('fixed questions mode requires exact selected question count', function () {
    $category = makeQuestionCategory($this->organization->id, 'Fixed Category');

    $questionIds = [];
    for ($i = 1; $i <= 2; $i++) {
        $questionIds[] = \App\Models\Question::create([
            'organization_id' => $this->organization->id,
            'category_id' => $category->id,
            'body' => "Fixed question {$i}",
            'type' => 'true_false',
            'correct_answer' => 'True',
            'difficulty' => 'easy',
            'marks_type' => 'single',
            'marks' => 1,
            'status' => 'active',
        ])->id;
    }

    $base = baseExamStorePayload($this->organization->id, [
        'title' => 'Fixed Questions Exam',
        'exam_duration_minutes' => 30,
        'total_questions' => 2,
        'total_marks' => 2,
        'passing_marks' => 1,
        'fixed_questions' => 1,
        'selected_categories' => [$category->id],
        'question_marks_filter' => [1],
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), array_merge($base, [
            'title' => 'Fixed Questions Incomplete',
            'question_ids' => [$questionIds[0]],
        ]))
        ->assertSessionHasErrors('question_ids');

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), array_merge($base, [
            'question_ids' => $questionIds,
        ]))
        ->assertSessionHasNoErrors();

    $exam = Exam::where('title', 'Fixed Questions Exam')->first();
    expect($exam)->not->toBeNull();
    expect($exam->questions()->count())->toBe(2);
});

test('fixed category marks requires exact total allocation', function () {
    $categories = collect(['Math', 'Physics', 'Chemistry'])->map(fn (string $name) =>
        \App\Models\QuestionCategory::create([
            'organization_id' => $this->organization->id,
            'name' => "Marks {$name}",
            'status' => 'active',
        ])
    );

    $base = baseExamStorePayload($this->organization->id, [
        'title' => 'Category Marks Exam',
        'exam_duration_minutes' => 30,
        'total_questions' => 6,
        'total_marks' => 10,
        'passing_marks' => 4,
        'selected_categories' => $categories->pluck('id')->all(),
        'question_marks_filter' => [1, 2],
        'fix_category_marks' => 1,
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), array_merge($base, [
            'title' => 'Invalid Category Marks Exam',
            'extra_marks_allocations' => [],
        ]))
        ->assertSessionHasErrors('extra_marks_allocations');

    // 10 marks / 3 categories => 3 base + 1 leftover on the first category.
    $allocation = [
        (string) $categories[0]->id => 4,
        (string) $categories[1]->id => 3,
        (string) $categories[2]->id => 3,
    ];
    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), array_merge($base, [
            'extra_marks_allocations' => $allocation,
        ]))
        ->assertSessionHasNoErrors();

    $exam = Exam::where('title', 'Category Marks Exam')->first();
    expect($exam)->not->toBeNull();
    expect($exam->fix_category_marks)->toBeTrue();
    expect($exam->extra_marks_allocations)->toEqual($allocation);
});

test('store exam accepts all exam formats including true false and fill blank', function () {
    $payload = baseExamStorePayload($this->organization->id, [
        'title'                 => 'Mixed Format Exam',
        'exam_format'           => ['mcq', 'true_false', 'written', 'fill_blank', 'multi_select'],
        'attempt_limit_type'    => 'fixed_count',
        'attempt_limit_count'   => 3,
        'total_questions'       => 20,
        'total_marks'           => 40,
        'passing_marks'         => 20,
        'paper_sets'            => 1,
    ]);

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



test('dynamic mode clears exam_question and rejects selected question ids', function () {
    $category = makeQuestionCategory($this->organization->id, 'Dynamic Category');
    $questionId = \App\Models\Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $category->id,
        'body' => 'Dynamic question',
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ])->id;

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), baseExamStorePayload($this->organization->id, [
            'title' => 'Dynamic Reject Ids',
            'selected_categories' => [$category->id],
            'question_marks_filter' => [1],
            'fixed_questions' => 0,
            'use_question_pool' => 0,
            'question_ids' => [$questionId],
        ]))
        ->assertSessionHasErrors('question_ids');

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), baseExamStorePayload($this->organization->id, [
            'title' => 'Dynamic Mode Exam',
            'selected_categories' => [$category->id],
            'question_marks_filter' => [1],
            'fixed_questions' => 0,
            'use_question_pool' => 0,
            'question_ids' => [],
        ]))
        ->assertSessionHasNoErrors();

    $exam = Exam::where('title', 'Dynamic Mode Exam')->first();
    expect($exam)->not->toBeNull();
    expect($exam->fixed_questions)->toBeFalse();
    expect($exam->use_question_pool)->toBeFalse();
    expect($exam->questions()->count())->toBe(0);
});

test('passing marks cannot exceed total marks', function () {
    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), baseExamStorePayload($this->organization->id, [
            'title' => 'Invalid Passing Marks',
            'total_marks' => 20,
            'passing_marks' => 25,
        ]))
        ->assertSessionHasErrors('passing_marks');
});

test('mode transition from fixed to dynamic clears attached questions', function () {
    $category = makeQuestionCategory($this->organization->id, 'Transition Category');
    $questionIds = [];
    for ($i = 1; $i <= 2; $i++) {
        $questionIds[] = \App\Models\Question::create([
            'organization_id' => $this->organization->id,
            'category_id' => $category->id,
            'body' => "Transition question {$i}",
            'type' => 'true_false',
            'correct_answer' => 'True',
            'difficulty' => 'easy',
            'marks_type' => 'single',
            'marks' => 1,
            'status' => 'active',
        ])->id;
    }

    $this->actingAs($this->user)
        ->post(route('admin.exams.store'), baseExamStorePayload($this->organization->id, [
            'title' => 'Mode Transition Exam',
            'total_questions' => 2,
            'total_marks' => 2,
            'passing_marks' => 1,
            'selected_categories' => [$category->id],
            'question_marks_filter' => [1],
            'fixed_questions' => 1,
            'question_ids' => $questionIds,
        ]))
        ->assertSessionHasNoErrors();

    $exam = Exam::where('title', 'Mode Transition Exam')->first();
    expect($exam->questions()->count())->toBe(2);

    $this->actingAs($this->user)
        ->put(route('admin.exams.update', $exam), [
            'title' => 'Mode Transition Exam',
            'status' => 'draft',
            'exam_mode' => 'standard',
            'exam_format' => ['mcq'],
            'visibility' => 'public',
            'duration' => 60,
            'total_questions' => 2,
            'total_marks' => 2,
            'passing_marks' => 1,
            'schedule_type' => 'any_time',
            'attempt_limit_type' => 'once',
            'selected_categories' => [$category->id],
            'question_marks_filter' => [1],
            'fixed_questions' => 0,
            'use_question_pool' => 0,
            'question_ids' => [],
            'paper_sets' => 1,
        ])
        ->assertSessionHasNoErrors();

    expect($exam->fresh()->questions()->count())->toBe(0);
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
    $questionCategory = makeQuestionCategory($this->organization->id, 'Update QB');

    $payload = [
        'title'      => 'Updated Exam Title',
        'status'     => 'published',
        'exam_mode'  => 'practice',
        'exam_format'=> ['mcq'],
        'visibility' => 'private',
        'duration'   => 45,
        'total_questions' => 20,
        'total_marks'     => 40,
        'passing_marks'   => 25,
        'paper_sets'      => 1,
        'schedule_type'   => 'any_time',
        'attempt_limit_type' => 'unlimited',
        'selected_categories' => [$questionCategory->id],
        'question_marks_filter' => [1, 2],
        'fixed_questions' => 0,
        'use_question_pool' => 0,
        'question_ids' => [],
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

test('edit page hydrates shared create form and examFormConfig', function () {
    $category = makeQuestionCategory($this->organization->id, 'Hydrate Cat');
    $exam = makeExam($this->organization->id, [
        'title' => 'Hydration Exam',
        'fixed_questions' => true,
        'use_question_pool' => false,
        'selected_categories' => [$category->id],
        'question_marks_filter' => [1, 2],
        'total_questions' => 2,
        'total_marks' => 4,
        'passing_marks' => 2,
        'shuffle_questions' => true,
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.exams.edit', $exam))
        ->assertOk()
        ->assertSee('exam-create-page', false)
        ->assertSee('window.examFormConfig', false)
        ->assertSee('Hydration Exam')
        ->assertDontSee('exam-edit.js', false);
});

test('edit round trip preserves configuration sections and mode transitions', function () {
    $category = makeQuestionCategory($this->organization->id, 'Roundtrip Cat');
    $q1 = \App\Models\Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $category->id,
        'body' => 'Roundtrip Q1',
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);
    $q2 = \App\Models\Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $category->id,
        'body' => 'Roundtrip Q2',
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);
    $q3 = \App\Models\Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $category->id,
        'body' => 'Roundtrip Q3',
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);

    $exam = makeExam($this->organization->id, [
        'title' => 'Roundtrip Exam',
        'status' => 'draft',
        'selected_categories' => [$category->id],
        'question_marks_filter' => [1],
        'total_questions' => 2,
        'total_marks' => 2,
        'passing_marks' => 1,
        'fixed_questions' => true,
        'shuffle_questions' => true,
        'enable_negative_marking' => false,
    ]);
    $exam->questions()->sync([
        $q1->id => ['sort_order' => 0, 'status' => 'active'],
        $q2->id => ['sort_order' => 1, 'status' => 'active'],
    ]);

    // Fixed -> pool transition
    $this->actingAs($this->user)
        ->put(route('admin.exams.update', $exam), [
            'title' => 'Roundtrip Exam',
            'status' => 'draft',
            'exam_mode' => 'standard',
            'exam_format' => ['mcq'],
            'visibility' => 'public',
            'duration' => 60,
            'total_questions' => 2,
            'total_marks' => 2,
            'passing_marks' => 1,
            'schedule_type' => 'any_time',
            'attempt_limit_type' => 'once',
            'selected_categories' => [$category->id],
            'question_marks_filter' => [1],
            'fixed_questions' => 0,
            'use_question_pool' => 1,
            'maximum_questions' => 3,
            'question_ids' => [$q1->id, $q2->id, $q3->id],
            'shuffle_questions' => 0,
            'paper_sets' => 1,
        ])
        ->assertSessionHasNoErrors();

    $exam->refresh();
    expect($exam->use_question_pool)->toBeTrue();
    expect($exam->fixed_questions)->toBeFalse();
    expect($exam->shuffle_questions)->toBeFalse();
    expect($exam->questions()->count())->toBe(3);

    // Pool -> dynamic clears question ids
    $this->actingAs($this->user)
        ->put(route('admin.exams.update', $exam), [
            'title' => 'Roundtrip Exam',
            'status' => 'draft',
            'exam_mode' => 'standard',
            'exam_format' => ['mcq'],
            'visibility' => 'public',
            'duration' => 60,
            'total_questions' => 2,
            'total_marks' => 2,
            'passing_marks' => 1,
            'schedule_type' => 'any_time',
            'attempt_limit_type' => 'once',
            'selected_categories' => [$category->id],
            'question_marks_filter' => [1],
            'fixed_questions' => 0,
            'use_question_pool' => 0,
            'question_ids' => [],
            'paper_sets' => 1,
        ])
        ->assertSessionHasNoErrors();

    expect($exam->fresh()->questions()->count())->toBe(0);
});

test('validation errors redisplay edit form without losing shared layout', function () {
    $exam = makeExam($this->organization->id, ['title' => 'Validation Exam']);
    $category = makeQuestionCategory($this->organization->id, 'Validation Cat');

    $this->actingAs($this->user)
        ->from(route('admin.exams.edit', $exam))
        ->put(route('admin.exams.update', $exam), [
            'title' => '',
            'status' => 'draft',
            'exam_mode' => 'standard',
            'exam_format' => ['mcq'],
            'visibility' => 'public',
            'duration' => 30,
            'total_questions' => 2,
            'total_marks' => 2,
            'passing_marks' => 5,
            'schedule_type' => 'any_time',
            'attempt_limit_type' => 'once',
            'selected_categories' => [$category->id],
            'question_marks_filter' => [1],
            'fixed_questions' => 0,
            'use_question_pool' => 0,
            'paper_sets' => 1,
        ])
        ->assertSessionHasErrors(['title', 'passing_marks'])
        ->assertRedirect(route('admin.exams.edit', $exam));
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

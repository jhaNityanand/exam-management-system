<?php

use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;

beforeEach(function () {
    // Set up test user and organization
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Test Org',
        'slug' => 'test-org-'.$this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
});

test('unauthenticated users are redirected to login from questions index', function () {
    $this->get(route('admin.questions.index'))
        ->assertRedirect('/login');
});

test('authenticated user can view questions list page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.index'));

    $response->assertOk()
        ->assertViewIs('backend.questions.index')
        ->assertSee('name="filters[type][]"', false)
        ->assertSee('name="filters[created_from]"', false)
        ->assertSee('data-filter-multiple', false)
        ->assertSee('data-date-preset-select', false);
});

test('user can query questions table data', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Math',
        'status' => 'active',
    ]);

    $question = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $category->id,
        'body' => '<p>What is 2+2?</p>',
        'type' => 'short_answer',
        'correct_answer' => '4',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 2,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table'));

    $response->assertOk()
        ->assertJsonFragment([
            'body' => '<p>What is 2+2?</p>',
            'correct_answer' => '4',
        ]);
});

test('user can search and filter questions', function () {
    $category1 = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Math',
        'status' => 'active',
    ]);

    $category2 = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Physics',
        'status' => 'active',
    ]);

    $q1 = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $category1->id,
        'body' => 'Find the derivative of x^2',
        'type' => 'short_answer',
        'correct_answer' => '2x',
        'difficulty' => 'medium',
        'marks_type' => 'single',
        'marks' => 3,
        'status' => 'active',
    ]);

    $q2 = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $category2->id,
        'body' => 'Define gravity',
        'type' => 'short_answer',
        'correct_answer' => 'force',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);

    // Search query
    $response = $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', ['search' => 'derivative']));
    $response->assertJsonCount(1, 'data');

    // Filter by difficulty
    $response = $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', ['filters' => ['difficulty' => 'easy']]));
    $response->assertJsonCount(1, 'data')
        ->assertJsonFragment(['difficulty' => 'easy']);

    // Filter by multiple difficulty values
    $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', [
            'filters' => ['difficulty' => ['easy', 'medium']],
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // Filter by marks
    $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', ['filters' => ['marks' => 3]]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['marks' => 3]);

    // Filter by multiple marks values
    $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', ['filters' => ['marks' => [1, 3]]]))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // Filter by category
    $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', ['filters' => ['category_id' => $category2->id]]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['body' => 'Define gravity']);

    // Sort by difficulty ascending
    $sorted = $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', [
            'sort' => 'difficulty',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->json('data');

    expect(collect($sorted)->pluck('difficulty')->all())
        ->toBe(['easy', 'medium']);
});

test('user can store a new MCQ question', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'CS',
        'status' => 'active',
    ]);

    $payload = [
        'category_id' => $category->id,
        'type' => 'mcq',
        'difficulty' => 'medium',
        'marks_type' => 'single',
        'marks' => 2,
        'body' => 'Which is a OOP language?',
        'allows_multiple' => 0,
        'options' => [
            ['text' => 'C'],
            ['text' => 'Java'],
        ],
        'correct_answer' => 'Java',
        'status' => 'active',
        'reference' => 'UPSC Prelims 2023',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('admin.questions.store'), $payload);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.index'));

    $this->assertDatabaseHas('questions', [
        'body' => 'Which is a OOP language?',
        'correct_answer' => 'Java',
        'difficulty' => 'medium',
        'organization_id' => $this->organization->id,
        'reference' => 'UPSC Prelims 2023',
    ]);
});

test('user can view question details', function () {
    $question = Question::create([
        'organization_id' => $this->organization->id,
        'body' => 'Kinematics question',
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.show', $question));

    $response->assertOk()
        ->assertViewIs('backend.questions.show')
        ->assertSee('Kinematics question');
});

test('user cannot access question from other organization', function () {
    $otherOrg = Organization::create([
        'name' => 'Other Org',
        'slug' => 'other-org-'.uniqid(),
        'status' => 'active',
    ]);

    $question = Question::create([
        'organization_id' => $otherOrg->id,
        'body' => 'Secret question',
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.show', $question));

    $response->assertStatus(403);
});

test('user can update a question', function () {
    $question = Question::create([
        'organization_id' => $this->organization->id,
        'body' => 'Original content',
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);

    $payload = [
        'type' => 'true_false',
        'difficulty' => 'hard',
        'marks_type' => 'single',
        'marks' => 5,
        'body' => 'Updated content',
        'correct_answer' => 'False',
        'status' => 'active',
        'reference' => 'GATE 2022',
    ];

    $response = $this->actingAs($this->user)
        ->put(route('admin.questions.update', $question), $payload);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.index'));

    $this->assertDatabaseHas('questions', [
        'id' => $question->id,
        'body' => 'Updated content',
        'correct_answer' => 'False',
        'difficulty' => 'hard',
        'reference' => 'GATE 2022',
    ]);
});

test('user can store fill blank and long answer questions', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'English',
        'status' => 'active',
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'type' => 'fill_blank',
            'difficulty' => 'medium',
            'marks_type' => 'single',
            'marks' => 2,
            'body' => 'The capital of France is ________.',
            'correct_answer' => 'Paris',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.index'));

    $this->assertDatabaseHas('questions', [
        'body' => 'The capital of France is ________.',
        'type' => 'fill_blank',
        'correct_answer' => 'Paris',
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'type' => 'long_answer',
            'difficulty' => 'very_hard',
            'marks_type' => 'single',
            'marks' => 8,
            'body' => 'Discuss the causes of the Industrial Revolution.',
            'correct_answer' => '<p>Include technological, economic, and social factors.</p>',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('questions', [
        'type' => 'long_answer',
        'difficulty' => 'very_hard',
        'marks' => 8,
    ]);
});

test('questions table supports pagination and per page', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Paginated Cat',
        'status' => 'active',
    ]);

    for ($i = 1; $i <= 15; $i++) {
        Question::create([
            'organization_id' => $this->organization->id,
            'category_id' => $category->id,
            'body' => "Pagination question {$i}",
            'type' => 'short_answer',
            'correct_answer' => 'ok',
            'difficulty' => 'easy',
            'marks_type' => 'single',
            'marks' => 1,
            'status' => 'active',
        ]);
    }

    $this->actingAs($this->user)
        ->get(route('admin.internal-api.questions-table', ['per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonCount(10, 'data');
});

test('question create page accepts exam create query defaults', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Exam Prefill Category',
        'status' => 'active',
    ]);

    $otherOrg = Organization::create([
        'name' => 'Foreign Org',
        'slug' => 'foreign-org-'.uniqid(),
        'status' => 'active',
    ]);
    $foreignCategory = QuestionCategory::create([
        'organization_id' => $otherOrg->id,
        'name' => 'Foreign Category',
        'status' => 'active',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.questions.create', [
            'source' => 'exam-create',
            'category_id' => $category->id,
            'marks' => [1, 2, 3],
            'formats' => ['multi_select'],
            'difficulty' => 'hard',
        ]))
        ->assertOk()
        ->assertViewIs('backend.questions.create')
        ->assertViewHas('defaults', function (array $defaults) {
            return $defaults['marks_type'] === 'multiple'
                && $defaults['marks'] === 1
                && $defaults['marks_list'] === [1, 2, 3];
        })
        ->assertSee('Create Question for Exam', false)
        ->assertSee('value="exam-create"', false)
        ->assertSee('value="'.$category->id.'"', false)
        ->assertSee('<option value="multiple" selected>Multiple Marks</option>', false);

    $this->actingAs($this->user)
        ->get(route('admin.questions.create', [
            'source' => 'exam-create',
            'category_id' => $foreignCategory->id,
            'marks' => [99],
            'formats' => ['not-a-format'],
        ]))
        ->assertOk()
        ->assertViewHas('defaults', function (array $defaults) {
            return $defaults['source'] === 'exam-create'
                && $defaults['category_id'] === null
                && $defaults['marks'] === null
                && $defaults['formats'] === [];
        });
});

test('exam create sourced question store flashes opener payload', function () {
    $category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Return Category',
        'status' => 'active',
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.questions.store'), [
            'source' => 'exam-create',
            'category_id' => $category->id,
            'type' => 'true_false',
            'difficulty' => 'medium',
            'marks_type' => 'single',
            'marks' => 2,
            'body' => 'Exam-linked question body',
            'correct_answer' => 'True',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.questions.create', ['source' => 'exam-create']))
        ->assertSessionHas('exam_create_question_created')
        ->assertSessionHas('success');

    $this->assertDatabaseHas('questions', [
        'body' => 'Exam-linked question body',
        'marks' => 2,
        'category_id' => $category->id,
    ]);
});

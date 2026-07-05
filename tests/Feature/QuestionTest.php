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

test('unauthenticated users are redirected to login from questions index', function () {
    $this->get(route('admin.questions.index'))
        ->assertRedirect('/login');
});

test('authenticated user can view questions list page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('admin.questions.index'));

    $response->assertOk()
        ->assertViewIs('backend.questions.index');
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
        'slug' => 'other-org-' . uniqid(),
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

test('user can delete a question', function () {
    $question = Question::create([
        'organization_id' => $this->organization->id,
        'body' => 'To be deleted',
        'type' => 'short_answer',
        'correct_answer' => 'answer',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('admin.questions.destroy', $question));

    $response->assertRedirect(route('admin.questions.index'));
    $this->assertSoftDeleted('questions', [
        'id' => $question->id,
    ]);
});

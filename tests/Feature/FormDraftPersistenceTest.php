<?php

use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Draft Test Org',
        'slug' => 'draft-org-'.uniqid(),
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->admin->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Draft Category',
        'status' => 'active',
    ]);
});

test('exam create view renders data-auto-draft exam_create_form and draft banner partial', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.exams.create'))
        ->assertOk()
        ->assertSee('data-auto-draft="exam_create_form"', false)
        ->assertSee('data-draft-banner', false)
        ->assertSee('form-utils.js', false);
});

test('question create view renders data-auto-draft and draft banner partial', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.questions.create'))
        ->assertOk()
        ->assertSee('data-auto-draft="question_create"', false)
        ->assertSee('data-draft-banner', false);
});

test('question edit view renders unique data-auto-draft key and draft banner', function () {
    $question = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
        'body' => 'Draft question body',
        'type' => 'mcq',
        'options' => ['A' => '1', 'B' => '2'],
        'correct_answer' => 'A',
        'difficulty' => 'easy',
        'marks' => 2,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.questions.edit', $question))
        ->assertOk()
        ->assertSee('data-auto-draft="question_edit_'.$question->id.'"', false)
        ->assertSee('data-draft-banner', false);
});

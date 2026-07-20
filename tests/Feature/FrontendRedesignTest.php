<?php

use App\Models\Exam;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::create([
        'name' => 'Redesign Org',
        'slug' => 'redesign-org-'.uniqid(),
        'status' => 'active',
    ]);

    $this->category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Public GK',
        'slug' => 'public-gk-'.uniqid(),
        'status' => 'active',
        'is_public' => true,
    ]);

    $this->publicQuestion = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
        'title' => 'Capital of France',
        'body' => '<p>What is the capital of France?</p>',
        'type' => 'mcq',
        'allows_multiple' => false,
        'options' => [
            ['key' => 'A', 'text' => 'Berlin'],
            ['key' => 'B', 'text' => 'Paris'],
            ['key' => 'C', 'text' => 'Madrid'],
        ],
        'correct_answer' => 'SECRET_TRUE',
        'correct_answers' => ['SECRET_TRUE'],
        'explanation' => '<p>Paris is the capital.</p>',
        'difficulty' => 'easy',
        'marks' => 1,
        'status' => 'active',
        'is_public' => true,
        'show_explanation_publicly' => true,
        'slug' => 'capital-of-france-'.uniqid(),
    ]);

    $this->exam = Exam::create([
        'organization_id' => $this->organization->id,
        'title' => 'Public Mock Exam',
        'slug' => 'public-mock-exam-'.uniqid(),
        'status' => 'published',
        'exam_mode' => 'standard',
        'exam_format' => ['mcq'],
        'visibility' => 'public',
        'pricing_option' => 'free',
        'duration' => 30,
        'enable_exam_timer' => true,
        'auto_submit_on_timer_end' => true,
        'total_questions' => 1,
        'total_marks' => 1,
        'passing_marks' => 1,
        'pass_percentage' => 50,
        'schedule_type' => 'any_time',
        'attempt_limit_type' => 'unlimited',
        'max_attempts' => 0,
        'fixed_questions' => true,
        'language' => 'en',
        'timezone' => 'UTC',
        'result_release_mode' => 'immediate',
    ]);
});

test('questions index is available', function () {
    $this->get(route('frontend.questions.index'))
        ->assertOk()
        ->assertSee('Questions', false);
});

test('questions categories page is available', function () {
    $this->get(route('frontend.questions.categories'))
        ->assertOk();
});

test('singular question urls redirect to plural', function () {
    $this->get('/question/categories')->assertRedirect('/questions/categories');
    $this->get('/question/'.$this->publicQuestion->slug)
        ->assertRedirect(route('frontend.questions.show', $this->publicQuestion));
});

test('about and contact aliases redirect', function () {
    $this->get('/about')->assertRedirect('/about-us');
    $this->get('/contact')->assertRedirect('/contact-us');
});

test('search suggest returns urls and questions group', function () {
    $response = $this->getJson(route('frontend.search.suggest', ['q' => 'capital']))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'exams',
                'blogs',
                'news',
                'categories',
                'questions',
            ],
        ]);

    $questions = $response->json('data.questions');
    expect($questions)->toBeArray();
    if (count($questions) > 0) {
        expect($questions[0])->toHaveKey('url');
        expect($questions[0]['url'])->toContain('/questions/');
    }
});

test('exams load more endpoint returns html', function () {
    $this->getJson(route('frontend.exams.index', ['page' => 1, 'per_page' => 6]))
        ->assertOk()
        ->assertJsonStructure([
            'html',
            'meta' => ['current_page', 'last_page', 'has_more'],
        ]);
});

test('questions load more endpoint returns html', function () {
    $this->getJson(route('frontend.questions.index', ['page' => 1, 'per_page' => 6]))
        ->assertOk()
        ->assertJsonStructure([
            'html',
            'meta' => ['current_page', 'last_page', 'has_more'],
        ]);
});

test('public question show hides correct answers', function () {
    $this->get(route('frontend.questions.show', $this->publicQuestion))
        ->assertOk()
        ->assertDontSee('SECRET_TRUE', false)
        ->assertSee('Capital of France', false)
        ->assertSee('Paris', false);
});

test('inactive private question is not public', function () {
    $this->publicQuestion->update([
        'is_public' => false,
        'status' => 'inactive',
    ]);

    $this->get(route('frontend.questions.show', $this->publicQuestion))->assertNotFound();
});

test('error pages render without crashing', function () {
    $this->get('/this-route-should-404-examtube')
        ->assertNotFound();
});

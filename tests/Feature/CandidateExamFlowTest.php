<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamEntitlement;
use App\Models\ExamProctoringPolicy;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\CandidateExam\ExamGradingService;
use App\Services\CandidateExam\ExamSessionService;

beforeEach(function () {
    $this->organization = Organization::create([
        'name' => 'Candidate Org',
        'slug' => 'candidate-org-'.uniqid(),
        'status' => 'active',
    ]);

    $this->admin = User::factory()->create();
    UserOrganization::create([
        'user_id' => $this->admin->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->candidate = User::factory()->create();
    UserOrganization::create([
        'user_id' => $this->candidate->id,
        'organization_id' => $this->organization->id,
        'role' => 'candidate',
        'status' => 'active',
    ]);

    $this->category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Cand Cat',
        'slug' => 'cand-cat-'.uniqid(),
        'status' => 'active',
    ]);

    $this->q1 = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
        'body' => '2 + 2 = ?',
        'type' => 'mcq',
        'allows_multiple' => false,
        'options' => ['A' => '3', 'B' => '4', 'C' => '5'],
        'correct_answer' => 'B',
        'correct_answers' => ['B'],
        'difficulty' => 'easy',
        'marks' => 2,
        'status' => 'active',
    ]);

    $this->q2 = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
        'body' => 'True or false: PHP is a language',
        'type' => 'true_false',
        'options' => ['True', 'False'],
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks' => 2,
        'status' => 'active',
    ]);

    $this->exam = Exam::create([
        'organization_id' => $this->organization->id,
        'title' => 'Candidate Flow Exam',
        'slug' => 'candidate-flow-exam-'.uniqid(),
        'status' => 'published',
        'exam_mode' => 'standard',
        'exam_format' => ['mcq', 'true_false'],
        'visibility' => 'public',
        'pricing_option' => 'free',
        'duration' => 20,
        'enable_exam_timer' => true,
        'auto_submit_on_timer_end' => true,
        'total_questions' => 2,
        'total_marks' => 4,
        'passing_marks' => 2,
        'pass_percentage' => 50,
        'schedule_type' => 'any_time',
        'attempt_limit_type' => 'unlimited',
        'max_attempts' => 0,
        'fixed_questions' => true,
        'language' => 'en',
        'timezone' => 'UTC',
        'result_release_mode' => 'immediate',
        'selected_categories' => [$this->category->id],
        'question_marks_filter' => [2],
        'instructions' => '<p>Follow the rules.</p>',
    ]);

    $this->exam->questions()->sync([
        $this->q1->id => ['sort_order' => 0, 'status' => 'active'],
        $this->q2->id => ['sort_order' => 1, 'status' => 'active'],
    ]);

    ExamProctoringPolicy::create([
        'exam_id' => $this->exam->id,
        'require_fullscreen' => false,
        'detect_tab_switch' => true,
        'focus_violation_limit' => 3,
        'focus_violation_action' => 'warn',
    ]);
});

test('public exam detail hides instructions and shows public fields', function () {
    $this->get(route('frontend.exams.show', $this->exam))
        ->assertOk()
        ->assertSee($this->exam->title)
        ->assertSee('Minutes')
        ->assertDontSee('Follow the rules.', false);
});

test('candidate cannot access admin panel', function () {
    $this->actingAs($this->candidate)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('frontend.account.dashboard'));
});

test('guest attempting rules is redirected to login with intended url', function () {
    $this->get(route('frontend.exams.rules', $this->exam))
        ->assertRedirect('/login');
});

test('candidate can start attempt save answers and submit for grading', function () {
    $this->actingAs($this->candidate);

    $this->get(route('frontend.exams.rules', $this->exam))->assertOk();
    $this->get(route('frontend.exams.prepare', $this->exam))->assertOk();

    $start = $this->post(route('frontend.exams.attempts.start', $this->exam), [
        'preferences' => [
            'theme' => 'light',
            'font_size' => 'md',
            'language' => 'en',
            'palette_position' => 'right',
            'timezone' => 'UTC',
        ],
        'device' => [
            'browser' => 'phpunit',
            'device_type' => 'desktop',
            'os' => 'test',
            'screen_resolution' => '1920x1080',
            'timezone' => 'UTC',
        ],
    ]);

    $start->assertRedirect();
    $attempt = ExamAttempt::query()->where('user_id', $this->candidate->id)->first();
    expect($attempt)->not->toBeNull();
    expect($attempt->expires_at)->not->toBeNull();
    expect($attempt->attemptQuestions)->toHaveCount(2);

    $qRows = $attempt->attemptQuestions()->orderBy('position')->get();
    $this->patchJson(route('frontend.attempts.answers', $attempt), [
        'answers' => [
            [
                'exam_attempt_question_id' => $qRows[0]->id,
                'answer_value' => 'B',
                'is_visited' => true,
            ],
            [
                'exam_attempt_question_id' => $qRows[1]->id,
                'answer_value' => 'True',
                'is_visited' => true,
            ],
        ],
    ])->assertOk()->assertJsonPath('saved', 2);

    expect(ExamAttemptAnswer::query()->where('exam_attempt_id', $attempt->id)->count())->toBe(2);

    $this->post(route('frontend.attempts.submit', $attempt))->assertRedirect(route('frontend.attempts.result', $attempt));

    $attempt->refresh();
    expect($attempt->status)->toBeIn(['submitted', 'expired']);
    expect((float) $attempt->score)->toBe(4.0);
    expect($attempt->passed)->toBeTrue();

    $this->get(route('frontend.attempts.result', $attempt))->assertOk()->assertSee('Pass');
    $this->get(route('frontend.attempts.review', $attempt))->assertOk()->assertSee('Correct answer');
});

test('paid exam requires entitlement before prepare', function () {
    $this->exam->update([
        'pricing_option' => 'paid',
        'exam_currency' => 'INR',
        'exam_amount' => 100,
    ]);

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.prepare', $this->exam))
        ->assertRedirect(route('frontend.exams.rules', $this->exam));

    $this->actingAs($this->candidate)
        ->post(route('frontend.exams.purchase', $this->exam))
        ->assertRedirect(route('frontend.exams.rules', $this->exam));

    expect(ExamEntitlement::query()->where('exam_id', $this->exam->id)->where('user_id', $this->candidate->id)->exists())->toBeTrue();

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.prepare', $this->exam))
        ->assertOk();
});

test('timer expiry auto submits attempt', function () {
    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume($this->exam, $this->candidate, ['theme' => 'light'], ['browser' => 'test']);
    $attempt->update(['expires_at' => now()->subMinute()]);

    $attempt = $service->expireIfNeeded($attempt->fresh());
    expect($attempt->status)->toBe('expired');
    expect($attempt->submission_reason)->toBe('timer_expired');
});

test('results remain hidden when release mode is never', function () {
    $this->exam->update(['result_release_mode' => 'never']);
    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume($this->exam, $this->candidate);
    $attempt = app(ExamGradingService::class)->submit($attempt);

    expect(app(ExamGradingService::class)->resultsVisible($attempt))->toBeFalse();

    $this->actingAs($this->candidate)
        ->get(route('frontend.attempts.review', $attempt))
        ->assertForbidden();
});

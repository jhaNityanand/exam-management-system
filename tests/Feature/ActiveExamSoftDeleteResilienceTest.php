<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamPart;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\CandidateExam\ExamAnswerService;
use App\Services\CandidateExam\ExamGradingService;
use App\Services\CandidateExam\ExamReviewPresenter;
use App\Services\CandidateExam\ExamSessionService;
use App\Services\ExamAttemptService;

beforeEach(function () {
    $this->organization = Organization::create([
        'name' => 'Resilience Org',
        'slug' => 'resilience-org-'.uniqid(),
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
        'name' => 'Resilience Category',
        'status' => 'active',
    ]);

    $this->q1 = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
        'body' => 'What is 10 + 10?',
        'type' => 'mcq',
        'options' => ['A' => '15', 'B' => '20', 'C' => '25'],
        'correct_answer' => 'B',
        'difficulty' => 'easy',
        'marks' => 5,
        'status' => 'active',
    ]);

    $this->q2 = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
        'body' => 'Capital of France?',
        'type' => 'mcq',
        'options' => ['A' => 'London', 'B' => 'Paris', 'C' => 'Berlin'],
        'correct_answer' => 'B',
        'difficulty' => 'easy',
        'marks' => 5,
        'status' => 'active',
    ]);

    $this->exam = Exam::create([
        'organization_id' => $this->organization->id,
        'title' => 'Resilience Test Exam',
        'slug' => 'resilience-exam-'.uniqid(),
        'status' => 'published',
        'exam_mode' => 'standard',
        'exam_format' => ['mcq'],
        'visibility' => 'public',
        'duration' => 30,
        'enable_exam_timer' => true,
        'total_questions' => 2,
        'total_marks' => 10,
        'passing_marks' => 5,
        'pass_percentage' => 50,
        'schedule_type' => 'any_time',
        'attempt_limit_type' => 'unlimited',
        'max_attempts' => 0,
        'result_release_mode' => 'immediate',
        'selected_categories' => [$this->category->id],
        'question_marks_filter' => [5],
    ]);

    $part = ExamPart::create([
        'exam_id' => $this->exam->id,
        'name' => 'Part 1',
        'sort_order' => 0,
        'is_default' => true,
        'total_questions' => 2,
        'total_marks' => 10,
        'fixed_questions' => true,
        'selected_categories' => [$this->category->id],
        'question_marks_filter' => [5],
    ]);

    $part->questions()->sync([
        $this->q1->id => ['sort_order' => 0, 'status' => 'active'],
        $this->q2->id => ['sort_order' => 1, 'status' => 'active'],
    ]);
});

test('active exam attempt remains uninterrupted when source questions and categories are soft deleted', function () {
    $sessionService = app(ExamSessionService::class);
    $attempt = $sessionService->startOrResume($this->exam, $this->candidate);

    expect($attempt->status)->toBe('in_progress');
    expect($attempt->attemptQuestions)->toHaveCount(2);

    // Background action: Admin edits body of Q1 and soft deletes Q1 & QuestionCategory
    $this->q1->update(['body' => 'UPDATED BODY THAT SHOULD NOT AFFECT ACTIVE SESSION']);
    $this->q1->delete(); // Soft delete
    $this->category->delete(); // Soft delete

    // Candidate refreshes/resumes active exam session
    $resumed = $sessionService->startOrResume($this->exam, $this->candidate);
    expect($resumed->id)->toBe($attempt->id);

    // Candidate fetches questions payload
    $startPayload = app(ExamAttemptService::class)->toCandidateStartPayload($resumed);
    expect($startPayload['questions'])->toHaveCount(2);

    // Verify assigned question body still retains original snapshotted content
    $q1Payload = collect($startPayload['questions'])->firstWhere('question_id', $this->q1->id);
    expect($q1Payload['question']['body'])->toBe('What is 10 + 10?');

    // Candidate saves answer for Q1
    $answerService = app(ExamAnswerService::class);
    $q1AttemptRow = $resumed->attemptQuestions->firstWhere('question_id', $this->q1->id);

    $answerService->saveBatch($resumed, [
        [
            'exam_attempt_question_id' => $q1AttemptRow->id,
            'answer_value' => 'B',
            'is_answered' => true,
        ],
    ]);

    // Submit exam
    $gradingService = app(ExamGradingService::class);
    $graded = $gradingService->submit($resumed, 'manual');

    expect($graded->status)->toBe('submitted');
    expect((float) $graded->score)->toBe(5.0);

    // Presenter for review page works seamlessly
    $presenter = app(ExamReviewPresenter::class);
    $reviewData = $presenter->present($graded);

    expect($reviewData['questions'])->toHaveCount(2);
    expect($reviewData['summary']['score'])->toBe(5.0);
});

test('retake logic works correctly when previous attempt questions are soft deleted', function () {
    $sessionService = app(ExamSessionService::class);
    $attempt1 = $sessionService->startOrResume($this->exam, $this->candidate);

    // Candidate submits attempt 1
    app(ExamGradingService::class)->submit($attempt1);

    // Soft delete Q1
    $this->q1->delete();

    // Create a new active question Q3 in the category
    $q3 = Question::create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
        'body' => 'What is 5 x 5?',
        'type' => 'mcq',
        'options' => ['A' => '20', 'B' => '25', 'C' => '30'],
        'correct_answer' => 'B',
        'difficulty' => 'easy',
        'marks' => 5,
        'status' => 'active',
    ]);

    // Retake exam (dynamic selection)
    $attempt2 = app(ExamAttemptService::class)->start($this->exam, $this->candidate);

    expect($attempt2->id)->not->toBe($attempt1->id);
    expect($attempt2->attemptQuestions)->not->toBeEmpty();
});

test('full frontend exam flow: prepare -> start -> navigate -> answer -> refresh -> submit -> review', function () {
    // 1. Prepare page
    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.prepare', $this->exam))
        ->assertOk();

    // 2. Obtain challenge token
    $challenge = \App\Models\ExamVerificationChallenge::query()
        ->where('exam_id', $this->exam->id)
        ->where('user_id', $this->candidate->id)
        ->latest('id')
        ->first();

    expect($challenge)->not->toBeNull();

    // 3. Start exam session
    $startResponse = $this->actingAs($this->candidate)
        ->post(route('frontend.exams.attempts.start', $this->exam), [
            'challenge_token' => $challenge->token,
            'checks' => [
                'webcam' => true,
                'microphone' => true,
                'fullscreen' => true,
                'rules_agreed' => true,
            ],
            'device' => [
                'browser' => 'phpunit',
                'device_type' => 'desktop',
                'os' => 'test',
                'timezone' => 'UTC',
                'session_token' => 'resilience-session-token',
            ],
        ]);

    $startResponse->assertRedirect();
    $attempt = ExamAttempt::query()->where('user_id', $this->candidate->id)->first();
    expect($attempt)->not->toBeNull();
    expect($attempt->attemptQuestions)->toHaveCount(2);

    // 4. Save answer to question 1
    $qRows = $attempt->attemptQuestions()->orderBy('position')->get();
    $this->actingAs($this->candidate)
        ->patchJson(route('frontend.attempts.answers', $attempt), [
            'answers' => [
                [
                    'exam_attempt_question_id' => $qRows[0]->id,
                    'answer_value' => 'B',
                    'is_visited' => true,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('saved', 1);

    // 5. Refresh page / resume runner
    $this->actingAs($this->candidate)
        ->get(route('frontend.attempts.show', $attempt))
        ->assertRedirect(route('frontend.exams.started', $attempt->exam));

    // 6. Submit exam
    $this->actingAs($this->candidate)
        ->post(route('frontend.attempts.submit', $attempt))
        ->assertRedirect(route('frontend.attempts.result', $attempt));

    // 7. View results page
    $this->actingAs($this->candidate)
        ->get(route('frontend.attempts.result', $attempt))
        ->assertOk();

    // 8. View review page
    $this->actingAs($this->candidate)
        ->get(route('frontend.attempts.review', $attempt))
        ->assertOk();
});

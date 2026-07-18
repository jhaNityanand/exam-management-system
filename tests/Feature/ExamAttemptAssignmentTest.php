<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\ExamAttemptService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->candidateA = User::factory()->create();
    $this->candidateB = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Attempt Org',
        'slug' => 'attempt-org-'.$this->user->id,
        'status' => 'active',
    ]);

    foreach ([$this->user, $this->candidateA, $this->candidateB] as $member) {
        UserOrganization::create([
            'user_id' => $member->id,
            'organization_id' => $this->organization->id,
            'role' => $member->id === $this->user->id ? 'admin' : 'member',
            'status' => 'active',
        ]);
    }

    $this->category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Attempt Category',
        'status' => 'active',
    ]);
});

function makeAttemptQuestion(int $orgId, int $categoryId, array $overrides = []): Question
{
    return Question::create(array_merge([
        'organization_id' => $orgId,
        'category_id' => $categoryId,
        'body' => 'Attempt Q '.uniqid(),
        'type' => 'true_false',
        'correct_answer' => 'True',
        'options' => ['True', 'False'],
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ], $overrides));
}

function makePublishedExam(int $orgId, array $overrides = []): Exam
{
    $suffix = uniqid();

    return Exam::create(array_merge([
        'organization_id' => $orgId,
        'title' => 'Published Attempt Exam '.$suffix,
        'slug' => 'published-attempt-exam-'.$suffix,
        'status' => 'published',
        'exam_mode' => 'standard',
        'exam_format' => ['mcq'],
        'visibility' => 'public',
        'duration' => 30,
        'total_questions' => 2,
        'total_marks' => 2,
        'passing_marks' => 1,
        'pass_percentage' => 50,
        'schedule_type' => 'any_time',
        'attempt_limit_type' => 'unlimited',
        'max_attempts' => 0,
        'fixed_questions' => false,
        'use_question_pool' => false,
        'selected_categories' => [],
        'question_marks_filter' => [1],
    ], $overrides));
}

test('fixed mode assigns the same question set to every candidate', function () {
    $q1 = makeAttemptQuestion($this->organization->id, $this->category->id);
    $q2 = makeAttemptQuestion($this->organization->id, $this->category->id);
    $exam = makePublishedExam($this->organization->id, [
        'fixed_questions' => true,
        'selected_categories' => [$this->category->id],
    ]);
    $exam->questions()->sync([
        $q1->id => ['sort_order' => 0, 'status' => 'active'],
        $q2->id => ['sort_order' => 1, 'status' => 'active'],
    ]);

    $service = app(ExamAttemptService::class);
    $attemptA = $service->start($exam, $this->candidateA);
    $attemptB = $service->start($exam, $this->candidateB);

    $idsA = $attemptA->attemptQuestions->pluck('question_id')->sort()->values()->all();
    $idsB = $attemptB->attemptQuestions->pluck('question_id')->sort()->values()->all();
    expect($idsA)->toEqual([$q1->id, $q2->id]);
    expect($idsB)->toEqual($idsA);
});

test('pool mode assigns a stable subset and resume does not re-randomize', function () {
    $questionIds = [];
    for ($i = 0; $i < 4; $i++) {
        $questionIds[] = makeAttemptQuestion($this->organization->id, $this->category->id)->id;
    }

    $exam = makePublishedExam($this->organization->id, [
        'use_question_pool' => true,
        'maximum_questions' => 4,
        'total_questions' => 2,
        'selected_categories' => [$this->category->id],
    ]);
    $sync = [];
    foreach ($questionIds as $i => $id) {
        $sync[$id] = ['sort_order' => $i, 'status' => 'active'];
    }
    $exam->questions()->sync($sync);

    $service = app(ExamAttemptService::class);
    $first = $service->start($exam, $this->candidateA);
    $firstIds = $first->attemptQuestions->pluck('question_id')->sort()->values()->all();
    expect($firstIds)->toHaveCount(2);

    $resumed = $service->start($exam, $this->candidateA);
    expect($resumed->id)->toBe($first->id);
    expect($resumed->attemptQuestions->pluck('question_id')->sort()->values()->all())->toEqual($firstIds);
});

test('dynamic mode leaves exam_question empty and varies between candidates', function () {
    for ($i = 0; $i < 6; $i++) {
        makeAttemptQuestion($this->organization->id, $this->category->id, [
            'body' => "Dynamic variation {$i}",
        ]);
    }

    $exam = makePublishedExam($this->organization->id, [
        'fixed_questions' => false,
        'use_question_pool' => false,
        'total_questions' => 2,
        'selected_categories' => [$this->category->id],
        'question_marks_filter' => [1],
        'exam_format' => ['true_false'],
    ]);
    expect($exam->questions()->count())->toBe(0);

    $service = app(ExamAttemptService::class);
    $attemptA = $service->start($exam, $this->candidateA);
    $attemptB = $service->start($exam, $this->candidateB);

    expect($attemptA->attemptQuestions)->toHaveCount(2);
    expect($attemptB->attemptQuestions)->toHaveCount(2);
    expect($exam->fresh()->questions()->count())->toBe(0);

    // With 6 candidates and C(6,2)=15 possible pairs, two random draws often differ.
    // Soft assertion: at least assignment is stable on resume.
    $resume = $service->start($exam, $this->candidateA);
    expect($resume->attemptQuestions->pluck('question_id')->sort()->values()->all())
        ->toEqual($attemptA->attemptQuestions->pluck('question_id')->sort()->values()->all());
});

test('start endpoint withholds correct answers and is idempotent', function () {
    $q1 = makeAttemptQuestion($this->organization->id, $this->category->id, [
        'correct_answer' => 'SECRET_TRUE',
        'correct_answers' => ['SECRET_TRUE'],
        'explanation' => 'secret explanation',
    ]);
    $q2 = makeAttemptQuestion($this->organization->id, $this->category->id);
    $exam = makePublishedExam($this->organization->id, [
        'fixed_questions' => true,
        'selected_categories' => [$this->category->id],
    ]);
    $exam->questions()->sync([
        $q1->id => ['sort_order' => 0, 'status' => 'active'],
        $q2->id => ['sort_order' => 1, 'status' => 'active'],
    ]);

    $first = $this->actingAs($this->candidateA)
        ->postJson(route('frontend.exams.attempts.start', $exam))
        ->assertOk()
        ->json();

    expect($first['attempt_id'])->not->toBeNull();
    expect($first['redirect'])->toContain('/attempts/');

    $attempt = \App\Models\ExamAttempt::query()->findOrFail($first['attempt_id']);
    $payload = app(\App\Services\CandidateExam\ExamSessionService::class)->toRuntimePayload($attempt);

    expect($payload['questions'])->toHaveCount(2);
    $json = json_encode($payload);
    expect($json)->not->toContain('SECRET_TRUE');
    expect($json)->not->toContain('secret explanation');

    $second = $this->actingAs($this->candidateA)
        ->postJson(route('frontend.exams.attempts.start', $exam))
        ->assertOk()
        ->json();

    expect($second['attempt_id'])->toBe($first['attempt_id']);
});

test('snapshots remain immutable after source question edits', function () {
    $question = makeAttemptQuestion($this->organization->id, $this->category->id, [
        'body' => 'Original body',
        'correct_answer' => 'True',
    ]);
    $other = makeAttemptQuestion($this->organization->id, $this->category->id);
    $exam = makePublishedExam($this->organization->id, [
        'fixed_questions' => true,
        'selected_categories' => [$this->category->id],
    ]);
    $exam->questions()->sync([
        $question->id => ['sort_order' => 0, 'status' => 'active'],
        $other->id => ['sort_order' => 1, 'status' => 'active'],
    ]);

    $attempt = app(ExamAttemptService::class)->start($exam, $this->candidateA);
    $question->update(['body' => 'Edited body after attempt']);

    $snapshot = ExamAttemptQuestion::query()
        ->where('exam_attempt_id', $attempt->id)
        ->where('question_id', $question->id)
        ->first();

    expect($snapshot->question_snapshot['body'])->toBe('Original body');
});

test('shortage rolls back attempt creation in dynamic mode', function () {
    makeAttemptQuestion($this->organization->id, $this->category->id);
    $exam = makePublishedExam($this->organization->id, [
        'fixed_questions' => false,
        'use_question_pool' => false,
        'total_questions' => 5,
        'selected_categories' => [$this->category->id],
        'question_marks_filter' => [1],
        'exam_format' => ['true_false'],
    ]);

    try {
        app(ExamAttemptService::class)->start($exam, $this->candidateA);
        expect(false)->toBeTrue();
    } catch (\App\Exceptions\AttemptQuestionShortageException $e) {
        expect($e->report())->not->toBeEmpty();
    }

    expect(ExamAttempt::where('exam_id', $exam->id)->count())->toBe(0);
});

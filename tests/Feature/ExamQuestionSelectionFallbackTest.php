<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\ExamAttemptService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->candidate = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Fallback Test Org',
        'slug' => 'fallback-org-'.uniqid(),
        'status' => 'active',
    ]);

    foreach ([$this->user, $this->candidate] as $member) {
        UserOrganization::create([
            'user_id' => $member->id,
            'organization_id' => $this->organization->id,
            'role' => $member->id === $this->user->id ? 'admin' : 'member',
            'status' => 'active',
        ]);
    }

    $this->categoryA = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Programming',
        'status' => 'active',
    ]);

    $this->categoryB = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'History',
        'status' => 'active',
    ]);
});

function createTestQuestion(int $orgId, int $catId, array $overrides = []): Question
{
    return Question::create(array_merge([
        'organization_id' => $orgId,
        'category_id' => $catId,
        'body' => 'Question '.uniqid(),
        'type' => 'true_false',
        'correct_answer' => 'True',
        'options' => ['True', 'False'],
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 2,
        'status' => 'active',
    ], $overrides));
}

function createTestExam(int $orgId, array $overrides = []): Exam
{
    $suffix = uniqid();
    $exam = Exam::create(array_merge([
        'organization_id' => $orgId,
        'title' => 'Fallback Test Exam '.$suffix,
        'slug' => 'fallback-test-exam-'.$suffix,
        'status' => 'published',
        'exam_mode' => 'standard',
        'exam_format' => ['true_false'],
        'visibility' => 'public',
        'duration' => 30,
        'total_questions' => 5,
        'total_marks' => 10,
        'passing_marks' => 5,
        'pass_percentage' => 50,
        'schedule_type' => 'any_time',
        'attempt_limit_type' => 'unlimited',
        'max_attempts' => 0,
    ], $overrides));

    $exam->parts()->create([
        'name' => 'Part A',
        'is_default' => true,
        'total_questions' => $overrides['total_questions'] ?? 5,
        'total_marks' => $overrides['total_marks'] ?? 10,
        'selected_categories' => $overrides['selected_categories'] ?? [],
        'question_marks_filter' => $overrides['question_marks_filter'] ?? [],
        'fixed_questions' => (bool) ($overrides['fixed_questions'] ?? false),
        'use_question_pool' => (bool) ($overrides['use_question_pool'] ?? false),
        'fix_category_questions' => (bool) ($overrides['fix_category_questions'] ?? false),
        'extra_questions_allocations' => $overrides['extra_questions_allocations'] ?? null,
    ]);

    return $exam->fresh(['parts']);
}

test('relaxes marks filter within same category when exact marks count is insufficient', function () {
    // 2-mark questions (required filter) only has 2 questions
    $q1 = createTestQuestion($this->organization->id, $this->categoryA->id, ['marks' => 2]);
    $q2 = createTestQuestion($this->organization->id, $this->categoryA->id, ['marks' => 2]);

    // Same category has 1-mark questions
    $q3 = createTestQuestion($this->organization->id, $this->categoryA->id, ['marks' => 1]);
    $q4 = createTestQuestion($this->organization->id, $this->categoryA->id, ['marks' => 1]);
    $q5 = createTestQuestion($this->organization->id, $this->categoryA->id, ['marks' => 1]);

    // Unrelated category has questions
    $qUnrelated = createTestQuestion($this->organization->id, $this->categoryB->id, ['marks' => 2]);

    $exam = createTestExam($this->organization->id, [
        'total_questions' => 5,
        'selected_categories' => [$this->categoryA->id],
        'question_marks_filter' => [2],
    ]);

    $service = app(ExamAttemptService::class);
    $attempt = $service->start($exam, $this->candidate);

    expect($attempt->attemptQuestions)->toHaveCount(5);

    $assignedQuestionIds = $attempt->attemptQuestions->pluck('question_id')->all();
    expect($assignedQuestionIds)->toContain($q1->id, $q2->id, $q3->id, $q4->id, $q5->id);
    expect($assignedQuestionIds)->not->toContain($qUnrelated->id);

    // Verify metadata for relaxed marks fallback
    $relaxedMeta = $attempt->attemptQuestions
        ->whereIn('question_id', [$q3->id, $q4->id, $q5->id])
        ->pluck('selection_meta')
        ->all();

    foreach ($relaxedMeta as $meta) {
        expect($meta['fallback_used'])->toBeTrue();
        expect($meta['fallback_type'])->toBe('marks_relaxed');
    }
});

test('applies controlled repetition fallback when unique questions are fewer than required', function () {
    $q1 = createTestQuestion($this->organization->id, $this->categoryA->id);
    $q2 = createTestQuestion($this->organization->id, $this->categoryA->id);
    $q3 = createTestQuestion($this->organization->id, $this->categoryA->id);

    $exam = createTestExam($this->organization->id, [
        'total_questions' => 5,
        'selected_categories' => [$this->categoryA->id],
    ]);

    $service = app(ExamAttemptService::class);
    $attempt = $service->start($exam, $this->candidate);

    expect($attempt->attemptQuestions)->toHaveCount(5);

    $repeatedCount = $attempt->attemptQuestions
        ->filter(fn ($aq) => ($aq->selection_meta['is_repeated'] ?? false) === true)
        ->count();

    expect($repeatedCount)->toBe(2);
});

test('retakes prioritize unattempted questions over previously answered questions', function () {
    $q1 = createTestQuestion($this->organization->id, $this->categoryA->id, ['body' => 'Q1']);
    $q2 = createTestQuestion($this->organization->id, $this->categoryA->id, ['body' => 'Q2']);
    $q3 = createTestQuestion($this->organization->id, $this->categoryA->id, ['body' => 'Q3']);
    $q4 = createTestQuestion($this->organization->id, $this->categoryA->id, ['body' => 'Q4']);

    $exam = createTestExam($this->organization->id, [
        'total_questions' => 2,
        'selected_categories' => [$this->categoryA->id],
    ]);

    $service = app(ExamAttemptService::class);

    // Attempt 1: candidate gets 2 questions (e.g. q1 & q2)
    $attempt1 = $service->start($exam, $this->candidate);
    $attempt1Ids = $attempt1->attemptQuestions->pluck('question_id')->all();

    // Mark attempt 1 as submitted
    $attempt1->update(['status' => 'submitted']);

    // Attempt 2: candidate should receive the remaining unattempted questions (q3 & q4)
    $attempt2 = $service->start($exam, $this->candidate);
    $attempt2Ids = $attempt2->attemptQuestions->pluck('question_id')->all();

    expect(array_intersect($attempt1Ids, $attempt2Ids))->toBeEmpty();
    expect(count(array_unique(array_merge($attempt1Ids, $attempt2Ids))))->toBe(4);
});

test('retakes prioritize previously incorrect questions over previously correct questions', function () {
    $q1 = createTestQuestion($this->organization->id, $this->categoryA->id, ['body' => 'Q1']);
    $q2 = createTestQuestion($this->organization->id, $this->categoryA->id, ['body' => 'Q2']);
    $q3 = createTestQuestion($this->organization->id, $this->categoryA->id, ['body' => 'Q3']);

    $exam = createTestExam($this->organization->id, [
        'total_questions' => 2,
        'selected_categories' => [$this->categoryA->id],
    ]);

    $service = app(ExamAttemptService::class);

    // Attempt 1: candidate gets 2 questions
    $attempt1 = $service->start($exam, $this->candidate);
    $attempt1Questions = $attempt1->attemptQuestions->sortBy('position')->values();
    $aqCorrect = $attempt1Questions[0];
    $aqIncorrect = $attempt1Questions[1];

    // Record answer: 1 correct, 1 incorrect
    ExamAttemptAnswer::create([
        'exam_attempt_id' => $attempt1->id,
        'exam_attempt_question_id' => $aqCorrect->id,
        'is_correct' => true,
        'answer_value' => ['True'],
    ]);

    ExamAttemptAnswer::create([
        'exam_attempt_id' => $attempt1->id,
        'exam_attempt_question_id' => $aqIncorrect->id,
        'is_correct' => false,
        'answer_value' => ['False'],
    ]);

    $attempt1->update(['status' => 'submitted']);

    // Attempt 2 requires 2 questions.
    // Unattempted: 1 question ($q3)
    // Previously incorrect: 1 question ($aqIncorrect->question_id)
    // Previously correct: 1 question ($aqCorrect->question_id)
    // Expect attempt 2 to select $q3 (unattempted) and $aqIncorrect->question_id (previously incorrect)
    $attempt2 = $service->start($exam, $this->candidate);
    $attempt2Ids = $attempt2->attemptQuestions->pluck('question_id')->all();

    expect($attempt2Ids)->toContain($q3->id);
    expect($attempt2Ids)->toContain($aqIncorrect->question_id);
    expect($attempt2Ids)->not->toContain($aqCorrect->question_id);
});

test('preserves category distribution ratios in multi-category exams under fallback', function () {
    // Category A has 2 questions, Category B has 2 questions
    $qA1 = createTestQuestion($this->organization->id, $this->categoryA->id);
    $qA2 = createTestQuestion($this->organization->id, $this->categoryA->id);

    $qB1 = createTestQuestion($this->organization->id, $this->categoryB->id);
    $qB2 = createTestQuestion($this->organization->id, $this->categoryB->id);

    $exam = createTestExam($this->organization->id, [
        'total_questions' => 6,
        'fix_category_questions' => true,
        'selected_categories' => [$this->categoryA->id, $this->categoryB->id],
        'extra_questions_allocations' => [
            $this->categoryA->id => 3,
            $this->categoryB->id => 3,
        ],
    ]);

    $service = app(ExamAttemptService::class);
    $attempt = $service->start($exam, $this->candidate);

    expect($attempt->attemptQuestions)->toHaveCount(6);

    $categoryAAssigned = $attempt->attemptQuestions->where('category_id', $this->categoryA->id)->count();
    $categoryBAssigned = $attempt->attemptQuestions->where('category_id', $this->categoryB->id)->count();

    expect($categoryAAssigned)->toBe(3);
    expect($categoryBAssigned)->toBe(3);
});

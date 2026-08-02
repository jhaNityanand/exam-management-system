<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamEntitlement;
use App\Models\ExamPart;
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

    $part = ExamPart::create([
        'exam_id' => $this->exam->id,
        'name' => 'Part A',
        'sort_order' => 0,
        'is_default' => true,
        'total_questions' => 2,
        'total_marks' => 4,
        'fixed_questions' => true,
        'selected_categories' => [$this->category->id],
        'question_marks_filter' => [2],
    ]);
    $part->questions()->sync([
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

test('authenticated exam show renders previous attempt cards with details', function () {
    $service = app(\App\Services\CandidateExam\ExamSessionService::class);
    $attempt = $service->startOrResume($this->exam, $this->candidate, [], [
        'browser' => 'Chrome',
        'device_type' => 'desktop',
        'session_token' => 'prev-attempts-ui',
    ]);
    $attempt->update([
        'correct_count' => 1,
        'wrong_count' => 1,
        'unanswered_count' => 0,
        'score' => 2,
        'percentage' => 50,
        'passed' => true,
        'submission_reason' => 'manual',
        'timezone' => 'Asia/Kolkata',
    ]);
    app(\App\Services\CandidateExam\ExamGradingService::class)->submit($attempt->fresh(), reason: 'manual', auto: false);

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.show', $this->exam))
        ->assertOk()
        ->assertSee('id="previous-attempts"', false)
        ->assertSee('pa-card is-collapsed is-latest', false)
        ->assertSee('Show details', false)
        ->assertSee('Attempt #')
        ->assertSee('View attempt details', false)
        ->assertSee('Review answers', false)
        ->assertSee('Timing', false)
        ->assertSee('Questions', false)
        ->assertSee('Marks &amp; performance', false)
        ->assertSee('Submission', false);
});

test('authenticated exam show shows empty previous attempts state', function () {
    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.show', $this->exam))
        ->assertOk()
        ->assertSee('No previous attempts found.', false)
        ->assertSee('Start your first attempt to track your exam history here.', false);
});

test('candidate cannot access admin panel', function () {
    $this->actingAs($this->candidate)
        ->get(route('admin.dashboard'))
        ->assertForbidden()
        ->assertSee('You do not have permission to access this page.', false)
        ->assertSee('Home', false)
        ->assertSee('My account', false)
        ->assertDontSee('Whoops', false);
});

test('guest attempting rules is redirected to login with intended url', function () {
    $this->get(route('frontend.exams.rules', $this->exam))
        ->assertRedirect('/login');
});

test('candidate can start attempt save answers and submit for grading', function () {
    $this->actingAs($this->candidate);

    $this->get(route('frontend.exams.rules', $this->exam))->assertOk();
    $prepare = $this->get(route('frontend.exams.prepare', $this->exam))->assertOk();
    $prepare->assertDontSee('Your preferences', false);

    $challenge = \App\Models\ExamVerificationChallenge::query()
        ->where('exam_id', $this->exam->id)
        ->where('user_id', $this->candidate->id)
        ->latest('id')
        ->first();
    expect($challenge)->not->toBeNull();

    $start = $this->post(route('frontend.exams.attempts.start', $this->exam), [
        'challenge_token' => $challenge->token,
        'checks' => [
            'webcam' => true,
            'microphone' => true,
            'fullscreen' => true,
            'selfie' => true,
            'rules_agreed' => true,
        ],
        'device' => [
            'browser' => 'phpunit',
            'device_type' => 'desktop',
            'os' => 'test',
            'screen_resolution' => '1920x1080',
            'timezone' => 'UTC',
            'session_token' => 'test-session-token-1',
        ],
    ]);

    $start->assertRedirect();
    $attempt = ExamAttempt::query()->where('user_id', $this->candidate->id)->first();
    expect($attempt)->not->toBeNull();
    expect($attempt->expires_at)->not->toBeNull();
    expect($attempt->policy_snapshot)->toBeArray();
    expect($attempt->attemptQuestions)->toHaveCount(2);
    $meta = $attempt->attemptQuestions()->orderBy('position')->first()?->selection_meta;
    expect($meta)->toBeArray();
    expect($meta['part_id'] ?? null)->not->toBeNull();
    expect($meta['part_name'] ?? null)->toBe('Part A');

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

    $this->get(route('frontend.attempts.result', $attempt))
        ->assertOk()
        ->assertSee('id="rs-page"', false)
        ->assertSee('Exam result');
    $this->getJson(route('frontend.attempts.result.data', $attempt))
        ->assertOk()
        ->assertJsonPath('visible', true)
        ->assertJsonPath('summary.passed', true)
        ->assertJsonPath('summary.status_label', 'Pass');
    $this->get(route('frontend.attempts.review', $attempt))
        ->assertOk()
        ->assertSee('id="rv-page"', false)
        ->assertSee('Question review');
    $this->getJson(route('frontend.attempts.review.data', $attempt))
        ->assertOk()
        ->assertJsonPath('summary.passed', true)
        ->assertJsonStructure([
            'summary' => [
                'total_questions',
                'attempted',
                'correct',
                'incorrect',
                'unanswered',
                'score',
                'passing_marks',
                'percentage',
                'passed',
            ],
            'questions' => [
                ['position', 'status', 'candidate_labels', 'correct_labels', 'options'],
            ],
        ]);
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
        ->get(route('frontend.exams.rules', $this->exam))
        ->assertOk()
        ->assertSee('rules-purchase-btn', false)
        ->assertSee('Purchase Exam', false)
        ->assertDontSee('Continue to verification', false);

    $this->actingAs($this->candidate)
        ->post(route('frontend.exams.purchase', $this->exam))
        ->assertRedirect(route('frontend.exams.rules', $this->exam));

    expect(ExamEntitlement::query()->where('exam_id', $this->exam->id)->where('user_id', $this->candidate->id)->exists())->toBeTrue();

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.rules', $this->exam))
        ->assertOk()
        ->assertSee('Continue to verification', false)
        ->assertDontSee('rules-purchase-btn', false);

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.prepare', $this->exam))
        ->assertOk();
});

test('start without challenge token is rejected', function () {
    $this->actingAs($this->candidate)
        ->postJson(route('frontend.exams.attempts.start', $this->exam), [
            'device' => ['browser' => 'phpunit'],
        ])
        ->assertStatus(422);
});

test('required selfie blocks start when identity rule is enabled', function () {
    $this->exam->update([
        'predefined_instruction_rules' => ['id_verification_required', 'webcam_monitoring_enabled'],
    ]);

    \App\Models\ExamInstructionRule::query()->create([
        'organization_id' => $this->organization->id,
        'rule_key' => 'id_verification_required',
        'slug' => 'id_verification_required',
        'title' => 'Identity verification is required before exam start.',
        'description' => 'Selfie required',
        'status' => 'active',
        'is_actionable' => true,
        'requirements' => [
            'require_webcam' => true,
            'require_photo_verification' => true,
            'require_identity_verification' => true,
        ],
    ]);
    \App\Models\ExamInstructionRule::query()->create([
        'organization_id' => $this->organization->id,
        'rule_key' => 'webcam_monitoring_enabled',
        'slug' => 'webcam_monitoring_enabled',
        'title' => 'Webcam monitoring is enabled.',
        'description' => 'Webcam required',
        'status' => 'active',
        'is_actionable' => true,
        'requirements' => ['require_webcam' => true],
    ]);

    app(\App\Services\CandidateExam\ExamRequirementResolver::class)->syncPolicy($this->exam);

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.prepare', $this->exam))
        ->assertOk()
        ->assertSee('Identity selfie', false);

    $challenge = \App\Models\ExamVerificationChallenge::query()
        ->where('exam_id', $this->exam->id)
        ->where('user_id', $this->candidate->id)
        ->latest('id')
        ->first();

    $this->actingAs($this->candidate)
        ->postJson(route('frontend.exams.attempts.start', $this->exam), [
            'challenge_token' => $challenge->token,
            'checks' => [
                'webcam' => true,
                'microphone' => true,
                'fullscreen' => true,
                'selfie' => true,
                'rules_agreed' => true,
            ],
            'device' => ['browser' => 'phpunit', 'session_token' => 'tok-2'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['selfie']);
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

test('attempt runner page exposes redesigned shell and answer acknowledgements', function () {
    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume($this->exam, $this->candidate, [], [
        'browser' => 'phpunit',
        'session_token' => 'runner-ui-token',
    ]);
    $attempt->update([
        'policy_snapshot' => array_merge($attempt->policy_snapshot ?? [], [
            'require_webcam' => true,
        ]),
    ]);

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.started', $this->exam))
        ->assertOk()
        ->assertSee('id="cx-rail"', false)
        ->assertSee('id="cx-drawer-toggle"', false)
        ->assertSee('id="cx-toast"', false)
        ->assertSee('id="cx-webcam"', false)
        ->assertSee('id="cx-violation-modal"', false)
        ->assertSee('id="cx-timer"', false)
        ->assertSee('Clear selection', false)
        ->assertSee('Save &amp; next', false)
        ->assertSee('Mark for review &amp; next', false)
        ->assertSee('Final submit', false)
        ->assertDontSee('cx-side--info', false)
        ->assertDontSee('id="cx-mark-review"', false);

    $this->actingAs($this->candidate)
        ->get(route('frontend.attempts.show', $attempt))
        ->assertRedirect(route('frontend.exams.started', $this->exam));

    $qid = $attempt->attemptQuestions()->orderBy('position')->value('id');

    $save = $this->actingAs($this->candidate)
        ->postJson(route('frontend.attempts.answers', $attempt), [
            'revision' => 0,
            'answers' => [
                [
                    'exam_attempt_question_id' => $qid,
                    'answer_value' => 'B',
                    'is_visited' => true,
                    'is_marked_for_review' => false,
                ],
                [
                    'exam_attempt_question_id' => 999999,
                    'answer_value' => 'X',
                    'is_visited' => true,
                ],
            ],
        ])
        ->assertOk()
        ->json();

    expect($save['saved'])->toBe(1);
    expect($save['requested'])->toBe(2);
    expect($save['skipped'])->toContain(999999);
    expect($save['revision'])->toBeGreaterThan(0);

    $this->actingAs($this->candidate)
        ->postJson(route('frontend.attempts.events', $attempt), [
            'event' => 'media_lost',
            'payload' => ['reason' => 'track_ended'],
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    app(ExamGradingService::class)->submit($attempt->fresh());

    $this->actingAs($this->candidate)
        ->postJson(route('frontend.attempts.answers', $attempt->fresh()), [
            'answers' => [
                [
                    'exam_attempt_question_id' => $qid,
                    'answer_value' => 'A',
                    'is_visited' => true,
                ],
            ],
        ])
        ->assertStatus(422);
});

test('proctoring auto submit stores readable violation reason', function () {
    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume(
        $this->exam,
        $this->candidate,
        [],
        ['browser' => 'phpunit', 'session_token' => 'violation-reason-token'],
        null,
        [
            'detect_tab_switch' => true,
            'focus_violation_limit' => 2,
            'focus_violation_action' => 'auto_submit',
            'auto_submit_on_violation' => true,
            'require_webcam' => false,
            'require_microphone' => false,
            'require_fullscreen' => false,
            'block_copy_paste' => false,
            'block_context_menu' => false,
            'detect_devtools' => false,
            'block_page_refresh' => false,
        ]
    );

    $proctoring = app(\App\Services\CandidateExam\ExamProctoringService::class);
    $first = $proctoring->recordEvent($attempt, 'tab_switch');
    expect($first['auto_submitted'])->toBeFalse();
    expect($first['violation_count'])->toBe(1);

    $this->travel(3)->seconds();

    $second = $proctoring->recordEvent($attempt->fresh(), 'tab_switch');
    expect($second['auto_submitted'])->toBeTrue();
    expect($second['action'])->toBe('auto_submit');
    expect($second['submission_reason'])->toBe('violation_tab_switch');
    expect($second['submission_message'])->toBe('Maximum tab switches exceeded');

    $attempt->refresh();
    expect($attempt->status)->toBe('submitted');
    expect($attempt->submission_reason)->toBe('violation_tab_switch');
    expect($attempt->submissionReasonLabel())->toBe('Maximum tab switches exceeded');

    $summary = app(\App\Services\CandidateExam\ExamReviewPresenter::class)->presentSummary($attempt);
    expect($summary['submission_label'])->toBe('Maximum tab switches exceeded');
});

test('right click is soft warn and does not auto submit', function () {
    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume(
        $this->exam,
        $this->candidate,
        [],
        ['browser' => 'phpunit', 'session_token' => 'soft-right-click'],
        null,
        [
            'block_context_menu' => true,
            'detect_tab_switch' => true,
            'focus_violation_limit' => 1,
            'focus_violation_action' => 'auto_submit',
            'auto_submit_on_violation' => true,
        ]
    );

    $proctoring = app(\App\Services\CandidateExam\ExamProctoringService::class);
    $result = $proctoring->recordEvent($attempt, 'right_click');

    expect($result['action'])->toBe('warn');
    expect($result['auto_submitted'])->toBeFalse();
    expect($result['violation_count'])->toBe(0);
    expect($attempt->fresh()->status)->toBeIn(['active', 'in_progress']);
});

test('media grace expiry auto submits without waiting for focus limit', function () {
    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume(
        $this->exam,
        $this->candidate,
        [],
        ['browser' => 'phpunit', 'session_token' => 'media-grace'],
        null,
        [
            'require_webcam' => true,
            'focus_violation_limit' => 99,
            'focus_violation_action' => 'warn',
            'auto_submit_on_violation' => false,
        ]
    );

    $proctoring = app(\App\Services\CandidateExam\ExamProctoringService::class);
    $lost = $proctoring->recordEvent($attempt, 'media_lost', ['reason' => 'video_ended']);
    expect($lost['auto_submitted'])->toBeFalse();
    expect($lost['action'])->toBe('warn');

    $expired = $proctoring->recordEvent($attempt->fresh(), 'media_grace_expired', ['reason' => 'video_ended']);
    expect($expired['auto_submitted'])->toBeTrue();
    expect($attempt->fresh()->submission_reason)->toBe('violation_camera_disabled');
});

test('candidate question payload strips correct answers', function () {
    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume($this->exam, $this->candidate);
    $payload = $service->toRuntimePayload($attempt->fresh());

    expect($payload['questions'])->not->toBeEmpty();
    foreach ($payload['questions'] as $question) {
        expect($question['question'] ?? [])->not->toHaveKey('correct_answer');
        expect($question['question'] ?? [])->not->toHaveKey('correct_answers');
        expect($question['question'] ?? [])->not->toHaveKey('explanation');
    }
});

test('untimed exams expose started_at and runtime parts metadata', function () {
    $this->exam->update(['enable_exam_timer' => false]);

    $partB = ExamPart::create([
        'exam_id' => $this->exam->id,
        'name' => 'Part B',
        'sort_order' => 1,
        'is_default' => false,
        'total_questions' => 1,
        'total_marks' => 2,
        'fixed_questions' => true,
        'selected_categories' => [$this->category->id],
    ]);
    $partB->questions()->sync([
        $this->q2->id => ['sort_order' => 0, 'status' => 'active'],
    ]);
    // Keep only q1 on default part A.
    $partA = ExamPart::query()->where('exam_id', $this->exam->id)->where('is_default', true)->first();
    $partA->update(['total_questions' => 1, 'total_marks' => 2]);
    $partA->questions()->sync([
        $this->q1->id => ['sort_order' => 0, 'status' => 'active'],
    ]);

    $service = app(ExamSessionService::class);
    $attempt = $service->startOrResume($this->exam, $this->candidate);
    expect($attempt->expires_at)->toBeNull();

    $payload = $service->toRuntimePayload($attempt->fresh());
    expect($payload['exam']['enable_exam_timer'])->toBeFalse();
    expect($payload['attempt']['started_at'])->not->toBeNull();
    expect($payload['parts'])->toHaveCount(2);
    expect(collect($payload['questions'])->pluck('part_name')->unique()->sort()->values()->all())
        ->toBe(['Part A', 'Part B']);
});

test('start returns runner html for in-place modal mount and started resumes', function () {
    $this->actingAs($this->candidate);

    $prepare = $this->get(route('frontend.exams.prepare', $this->exam))->assertOk();
    $prepare->assertSee('id="cx-runner-host"', false);

    $challenge = \App\Models\ExamVerificationChallenge::query()
        ->where('exam_id', $this->exam->id)
        ->where('user_id', $this->candidate->id)
        ->latest('id')
        ->first();

    $start = $this->postJson(route('frontend.exams.attempts.start', $this->exam), [
        'challenge_token' => $challenge->token,
        'checks' => [
            'webcam' => true,
            'microphone' => true,
            'fullscreen' => true,
            'selfie' => true,
            'rules_agreed' => true,
        ],
        'device' => [
            'browser' => 'phpunit',
            'device_type' => 'desktop',
            'session_token' => 'modal-start-token',
        ],
    ])->assertOk()->json();

    expect($start['ok'])->toBeTrue();
    expect($start['started_url'])->toContain('/started');
    expect($start['runner_html'])->toContain('id="cx-exam"');
    expect($start['runner_html'])->toContain('Save &amp; next');
    expect($start['runner_html'])->toContain('Mark for review &amp; next');

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.started', $this->exam))
        ->assertOk()
        ->assertSee('id="cx-exam"', false);

    // Without an active attempt after submit, started redirects back to prepare.
    $attempt = \App\Models\ExamAttempt::query()->findOrFail($start['attempt_id']);
    app(ExamGradingService::class)->submit($attempt);

    $this->actingAs($this->candidate)
        ->get(route('frontend.exams.started', $this->exam))
        ->assertRedirect(route('frontend.attempts.result', $attempt));
});

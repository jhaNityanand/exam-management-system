<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamEntitlement;
use App\Models\ExamPayment;
use App\Models\Feedback;
use App\Models\User;
use App\Services\CandidateExam\ExamGradingService;
use App\Services\CandidateExam\ExamPaymentPlaceholderService;
use App\Services\CandidateExam\ExamSessionService;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Throwable;

/**
 * Seeds realistic graded exam attempts for all three demo users.
 * Paid entitlements are created only for attempt plans that need them,
 * so unpaid users can still exercise the Purchase → Verification flow.
 */
class ExamAttemptSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        if (! $org) {
            $this->command?->warn('ExamAttemptSeeder: demo-org missing. Skipping.');

            return;
        }

        $users = User::query()
            ->whereIn('email', [
                \Database\Seeders\Support\SeederContact::EMAIL_ADMIN,
                \Database\Seeders\Support\SeederContact::EMAIL_INFO,
                'candidate@examtube.in',
            ])
            ->get()
            ->keyBy('email');

        if ($users->count() < 3) {
            $this->command?->warn('ExamAttemptSeeder: expected three demo users. Skipping.');

            return;
        }

        $this->purgeExistingAttempts($org->id, $users->pluck('id')->all());
        $this->seedEntitlements($org->id, $users);

        $plans = $this->attemptPlans($users);
        $session = app(ExamSessionService::class);
        $grader = app(ExamGradingService::class);

        $created = 0;
        $failed = 0;

        foreach ($plans as $plan) {
            /** @var User $user */
            $user = $plan['user'];
            $exam = $this->findExam($org->id, (string) $plan['exam_title']);

            if (! $exam) {
                $this->command?->warn("ExamAttemptSeeder: exam [{$plan['exam_title']}] not found.");
                $failed++;

                continue;
            }

            try {
                if (($plan['requires_entitlement'] ?? false) && ! app(ExamPaymentPlaceholderService::class)->hasActiveEntitlement($exam, $user)) {
                    app(ExamPaymentPlaceholderService::class)->completePlaceholderPurchase($exam, $user);
                }

                $attempt = $session->startOrResume($exam, $user, [
                    'timezone' => 'Asia/Kolkata',
                    'theme' => 'light',
                ], [
                    'source' => 'seeder',
                    'user_agent' => 'ExamAttemptSeeder/1.0',
                ]);

                if (($plan['leave_in_progress'] ?? false) === true) {
                    $this->answerPortion($attempt, (float) ($plan['correct_ratio'] ?? 0.35));
                    $startedAt = now()->subMinutes((int) ($plan['minutes_ago'] ?? 12));
                    $attempt->forceFill([
                        'started_at' => $startedAt,
                        'heartbeat_at' => now()->subMinute(),
                        'last_saved_at' => now()->subMinute(),
                        'time_spent_seconds' => max(60, (int) $startedAt->diffInSeconds(now())),
                    ])->save();
                    $created++;

                    continue;
                }

                $this->answerPortion($attempt, (float) $plan['correct_ratio']);
                $attempt = $grader->submit($attempt->fresh(['attemptQuestions', 'attemptAnswers', 'exam']), 'manual');

                $submittedAt = now()->subDays((int) ($plan['days_ago'] ?? 1))->subMinutes(random_int(5, 90));
                $durationMinutes = max(8, min((int) ($exam->duration ?: 45), random_int(12, (int) ($exam->duration ?: 45))));
                $startedAt = $submittedAt->copy()->subMinutes($durationMinutes);

                $attempt->forceFill([
                    'started_at' => $startedAt,
                    'submitted_at' => $submittedAt,
                    'result_released_at' => $submittedAt,
                    'heartbeat_at' => $submittedAt,
                    'last_saved_at' => $submittedAt,
                    'time_spent_seconds' => $durationMinutes * 60,
                    'status' => 'graded',
                ])->save();

                $this->seedFeedback($org->id, $user, $exam, $attempt);

                $created++;
            } catch (Throwable $e) {
                $failed++;
                $this->command?->warn("ExamAttemptSeeder: {$user->email} / {$plan['exam_title']}: {$e->getMessage()}");
            }
        }

        $this->command?->info("ExamAttemptSeeder: created {$created} attempt(s); {$failed} failed.");
    }

    private function findExam(int $orgId, string $title): ?Exam
    {
        $normalized = $this->normalizeTitle($title);

        $match = Exam::query()
            ->where('organization_id', $orgId)
            ->where('status', 'published')
            ->get(['id', 'title'])
            ->first(fn (Exam $exam) => $this->normalizeTitle((string) $exam->title) === $normalized);

        return $match
            ? Exam::query()->with(['parts.questions', 'parts.selectedQuestionCategories', 'proctoringPolicy'])->find($match->id)
            : null;
    }

    private function normalizeTitle(string $title): string
    {
        $title = str_replace(["\u{2014}", "\u{2013}", '—', '–'], '-', $title);
        $title = preg_replace('/\s+/', ' ', trim($title)) ?? trim($title);

        return mb_strtolower($title);
    }

    /**
     * @param  list<int>  $userIds
     */
    private function purgeExistingAttempts(int $orgId, array $userIds): void
    {
        $attempts = ExamAttempt::withTrashed()
            ->where('organization_id', $orgId)
            ->whereIn('user_id', $userIds)
            ->get();

        foreach ($attempts as $attempt) {
            ExamAttemptAnswer::query()->where('exam_attempt_id', $attempt->id)->delete();
            ExamAttemptQuestion::query()->where('exam_attempt_id', $attempt->id)->delete();
            $attempt->events()->delete();
            $attempt->violations()->delete();
            $attempt->snapshots()->delete();
            $attempt->device()->delete();
            $attempt->forceDelete();
        }

        Feedback::query()
            ->where('organization_id', $orgId)
            ->whereIn('user_id', $userIds)
            ->where('source', 'seeder')
            ->forceDelete();
    }

    /**
     * Clear demo payment rows so the Pay → Verification flow stays testable.
     * Entitlements needed for seeded graded attempts are granted per-plan below
     * (requires_entitlement), not for every demo user on every paid exam.
     *
     * @param  \Illuminate\Support\Collection<string, User>  $users
     */
    private function seedEntitlements(int $orgId, $users): void
    {
        ExamPayment::query()->where('organization_id', $orgId)->whereIn('user_id', $users->pluck('id'))->delete();
        ExamEntitlement::query()->where('organization_id', $orgId)->whereIn('user_id', $users->pluck('id'))->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<string, User>  $users
     * @return list<array<string, mixed>>
     */
    private function attemptPlans($users): array
    {
        $candidate = $users->get('candidate@examtube.in');
        $orgAdmin = $users->get(\Database\Seeders\Support\SeederContact::EMAIL_INFO);
        $appAdmin = $users->get(\Database\Seeders\Support\SeederContact::EMAIL_ADMIN);

        return [
            // Candidate — rich history for /account demos
            [
                'user' => $candidate,
                'exam_title' => 'First Round Interview - Aptitude Screening',
                'correct_ratio' => 0.42,
                'days_ago' => 18,
            ],
            [
                'user' => $candidate,
                'exam_title' => 'First Round Interview - Aptitude Screening',
                'correct_ratio' => 0.82,
                'days_ago' => 11,
            ],
            [
                'user' => $candidate,
                'exam_title' => 'Unlimited Technical Mock Interview',
                'correct_ratio' => 0.74,
                'days_ago' => 7,
            ],
            [
                'user' => $candidate,
                'exam_title' => 'Rapid 20-Minute Developer Screening',
                'correct_ratio' => 0.58,
                'days_ago' => 4,
            ],
            [
                'user' => $candidate,
                'exam_title' => 'Paid Aptitude Practice Pack',
                'correct_ratio' => 0.78,
                'days_ago' => 2,
                'requires_entitlement' => true,
            ],
            [
                'user' => $candidate,
                'exam_title' => 'Unlimited Technical Mock Interview',
                'correct_ratio' => 0.3,
                'leave_in_progress' => true,
                'minutes_ago' => 9,
            ],

            // Org admin
            [
                'user' => $orgAdmin,
                'exam_title' => 'First Round Interview - Aptitude Screening',
                'correct_ratio' => 0.88,
                'days_ago' => 14,
            ],
            [
                'user' => $orgAdmin,
                'exam_title' => 'PHP Backend Technical Screening',
                'correct_ratio' => 0.38,
                'days_ago' => 9,
            ],
            [
                'user' => $orgAdmin,
                'exam_title' => 'Unlimited Technical Mock Interview',
                'correct_ratio' => 0.7,
                'days_ago' => 3,
            ],
            [
                'user' => $orgAdmin,
                'exam_title' => 'Paid Full Stack Skill Certification',
                'correct_ratio' => 0.66,
                'days_ago' => 1,
                'requires_entitlement' => true,
            ],

            // App admin
            [
                'user' => $appAdmin,
                'exam_title' => 'First Round Interview - Aptitude Screening',
                'correct_ratio' => 0.76,
                'days_ago' => 12,
            ],
            [
                'user' => $appAdmin,
                'exam_title' => 'Programming Fundamentals Screening',
                'correct_ratio' => 0.45,
                'days_ago' => 6,
            ],
            [
                'user' => $appAdmin,
                'exam_title' => 'Frontend JavaScript Interview Assessment',
                'correct_ratio' => 0.81,
                'days_ago' => 2,
            ],
        ];
    }

    private function answerPortion(ExamAttempt $attempt, float $correctRatio): void
    {
        $attempt->loadMissing('attemptQuestions');
        $questions = $attempt->attemptQuestions->sortBy('position')->values();
        $total = max(1, $questions->count());
        $correctTarget = (int) round($total * max(0, min(1, $correctRatio)));

        foreach ($questions as $index => $question) {
            $wantCorrect = $index < $correctTarget;
            [$value, $answered] = $this->buildAnswerValue($question, $wantCorrect);

            if (! $answered) {
                // Leave a few unanswered near the end for realism when ratio is mid-range.
                if ($index >= $correctTarget && ($index % 7 === 0)) {
                    ExamAttemptAnswer::query()->updateOrCreate(
                        [
                            'exam_attempt_id' => $attempt->id,
                            'exam_attempt_question_id' => $question->id,
                        ],
                        [
                            'answer_value' => null,
                            'is_marked_for_review' => false,
                            'is_visited' => true,
                            'is_answered' => false,
                            'answered_at' => null,
                            'revision' => 1,
                        ]
                    );

                    continue;
                }

                // Non-auto-gradable written questions still store a sample response.
                $value = 'Seeded written response covering approach, trade-offs, and final recommendation.';
            }

            ExamAttemptAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'exam_attempt_question_id' => $question->id,
                ],
                [
                    'answer_value' => $value,
                    'is_marked_for_review' => $index % 11 === 0,
                    'is_visited' => true,
                    'is_answered' => true,
                    'answered_at' => now()->subMinutes(max(1, $total - $index)),
                    'revision' => 1,
                ]
            );
        }
    }

    /**
     * @return array{0:mixed,1:bool} [answer_value, is_auto_gradable]
     */
    private function buildAnswerValue(ExamAttemptQuestion $question, bool $wantCorrect): array
    {
        $snapshot = is_array($question->question_snapshot) ? $question->question_snapshot : [];
        $type = (string) ($snapshot['type'] ?? 'mcq');
        $allowsMultiple = (bool) ($snapshot['allows_multiple'] ?? false);

        if (in_array($type, ['short_answer', 'long_answer', 'written'], true)) {
            return [
                $wantCorrect
                    ? 'Clear structured answer with examples and a concise conclusion.'
                    : 'Partial answer missing key steps.',
                false,
            ];
        }

        if ($type === 'fill_blank') {
            $expected = (string) ($snapshot['correct_answer'] ?? ($snapshot['correct_answers'][0] ?? 'answer'));

            return [$wantCorrect ? $expected : 'incorrect-'.$expected, true];
        }

        if ($type === 'true_false') {
            $expected = (string) ($snapshot['correct_answer'] ?? 'True');
            $wrong = strcasecmp($expected, 'True') === 0 ? 'False' : 'True';

            return [$wantCorrect ? $expected : $wrong, true];
        }

        $expected = $snapshot['correct_answers'] ?? $snapshot['correct_answer'] ?? [];
        if (! is_array($expected)) {
            $expected = [$expected];
        }
        $expected = array_values(array_filter(array_map('strval', $expected), fn ($v) => $v !== ''));

        $optionValues = $this->optionValues($snapshot['options'] ?? []);

        if ($wantCorrect) {
            if ($allowsMultiple) {
                return [$expected !== [] ? $expected : array_slice($optionValues, 0, 2), true];
            }

            $value = $expected[0] ?? ($optionValues[0] ?? 'A');

            return [$value, true];
        }

        $wrongPool = array_values(array_filter($optionValues, fn ($v) => ! in_array((string) $v, $expected, true)));
        if ($wrongPool === []) {
            $wrongPool = ['seed-wrong-'.Str::lower(Str::random(4))];
        }

        if ($allowsMultiple) {
            return [[$wrongPool[0]], true];
        }

        return [$wrongPool[0], true];
    }

    /**
     * @return list<string>
     */
    private function optionValues(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $values = [];
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $text = (string) ($option['text'] ?? $option['label'] ?? $option['value'] ?? $key);
                // Prefer text because seeded correct_answer stores option text.
                $values[] = $text !== '' ? $text : (string) $key;
            } else {
                $values[] = (string) $option;
            }
        }

        return array_values(array_unique(array_filter($values, fn ($v) => $v !== '')));
    }

    private function seedFeedback(int $orgId, User $user, Exam $exam, ExamAttempt $attempt): void
    {
        if (! $attempt->passed && random_int(0, 1) === 1) {
            return;
        }

        Feedback::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'user_id' => $user->id,
                'exam_attempt_id' => $attempt->id,
            ],
            [
                'exam_id' => $exam->id,
                'feedbackable_type' => $exam->getMorphClass(),
                'feedbackable_id' => $exam->id,
                'rating' => $attempt->passed ? random_int(4, 5) : random_int(2, 3),
                'title' => $attempt->passed ? 'Solid practice paper' : 'Tough but useful',
                'message' => $attempt->passed
                    ? 'The timer and question mix felt close to a real interview screen. Results were clear.'
                    : 'I struggled on a few topics, but the review helped me spot weak areas quickly.',
                'status' => Feedback::STATUS_ACTIVE,
                'is_public' => true,
                'source' => 'seeder',
                'locale' => 'en',
                'created_by' => $user->id,
            ]
        );
    }
}

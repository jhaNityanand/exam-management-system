<?php

namespace App\Services\CandidateExam;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ExamEligibilityService
{
    public function __construct(
        protected ExamPaymentPlaceholderService $payments
    ) {}

    public function isPubliclyListable(Exam $exam): bool
    {
        return $exam->status === 'published' && $exam->visibility === 'public';
    }

    public function canViewPublicDetail(Exam $exam, ?User $user = null): bool
    {
        if ($exam->status !== 'published') {
            return false;
        }

        if ($exam->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $this->isInvitedOrEntitled($exam, $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(Exam $exam, User $user): array
    {
        $reasons = [];
        $activeAttempt = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'in_progress'])
            ->latest('id')
            ->first();

        if ($exam->status !== 'published') {
            $reasons[] = 'This exam is not published.';
        }

        if ($exam->registration_deadline && now()->gt($exam->registration_deadline) && ! $activeAttempt) {
            $reasons[] = 'Registration deadline has passed.';
        }

        if ($exam->schedule_type === 'fixed_window') {
            if ($exam->scheduled_start && now()->lt($exam->scheduled_start) && ! $activeAttempt) {
                $reasons[] = 'This exam has not started yet.';
            }
            if ($exam->scheduled_end && now()->gt($exam->scheduled_end) && ! $activeAttempt) {
                $reasons[] = 'This exam window has ended.';
            }
        }

        if (! $this->isInvitedOrEntitled($exam, $user) && $exam->visibility !== 'public') {
            $reasons[] = 'You are not invited to this exam.';
        }

        $paidRequired = $this->requiresPayment($exam);
        $hasEntitlement = $this->payments->hasActiveEntitlement($exam, $user);
        if ($paidRequired && ! $hasEntitlement && ! $this->isFreeForUser($exam, $user)) {
            $reasons[] = 'Payment is required before attempting this exam.';
        }

        $attemptLimitReached = $this->hasReachedAttemptLimit($exam, $user) && ! $activeAttempt;
        if ($attemptLimitReached) {
            $reasons[] = 'You have reached the maximum number of attempts.';
        }

        $requiresPayment = $paidRequired && ! $hasEntitlement && ! $this->isFreeForUser($exam, $user);

        return [
            'can_attempt' => $reasons === [] && ! $requiresPayment,
            'can_continue' => $activeAttempt !== null,
            'requires_payment' => $requiresPayment,
            'has_entitlement' => $hasEntitlement || $this->isFreeForUser($exam, $user) || ! $paidRequired,
            'active_attempt_id' => $activeAttempt?->id,
            'reasons' => $reasons,
            'attempts_used' => $this->completedAttemptCount($exam, $user),
            'attempts_allowed' => $this->allowedAttempts($exam),
        ];
    }

    public function assertCanStart(Exam $exam, User $user): void
    {
        $result = $this->evaluate($exam, $user);
        if (! empty($result['can_continue'])) {
            return;
        }
        if (! empty($result['can_attempt']) && empty($result['requires_payment'])) {
            return;
        }

        throw ValidationException::withMessages([
            'exam' => $result['reasons'][0] ?? 'You are not eligible to start this exam.',
        ]);
    }

    public function requiresPayment(Exam $exam): bool
    {
        $option = $exam->pricing_option ?: 'free';
        if ($option === 'free') {
            return false;
        }

        return (float) ($exam->exam_amount ?? 0) > 0 || $option === 'paid';
    }

    public function isFreeForUser(Exam $exam, User $user): bool
    {
        if (($exam->pricing_option ?: 'free') === 'free') {
            return true;
        }

        $email = strtolower((string) $user->email);
        $freeLists = collect([
            $exam->free_imported_candidates ?? [],
            $exam->free_manual_candidate_emails ?? [],
        ])->flatten(1);

        return $this->emailInCandidateList($freeLists, $email);
    }

    public function isInvitedOrEntitled(Exam $exam, User $user): bool
    {
        if ($exam->visibility === 'public') {
            return true;
        }

        if ($this->payments->hasActiveEntitlement($exam, $user)) {
            return true;
        }

        $email = strtolower((string) $user->email);
        $lists = collect([
            $exam->imported_candidates ?? [],
            $exam->manual_candidate_emails ?? [],
            $exam->free_imported_candidates ?? [],
            $exam->free_manual_candidate_emails ?? [],
        ])->flatten(1);

        return $this->emailInCandidateList($lists, $email);
    }

    public function hasReachedAttemptLimit(Exam $exam, User $user): bool
    {
        $allowed = $this->allowedAttempts($exam);
        if ($allowed === null) {
            return false;
        }

        return $this->completedAttemptCount($exam, $user) >= $allowed;
    }

    public function allowedAttempts(Exam $exam): ?int
    {
        $limitType = $exam->attempt_limit_type ?: 'once';
        if ($limitType === 'unlimited' || (int) ($exam->max_attempts ?? 0) === 0) {
            return null;
        }

        return $limitType === 'once' ? 1 : max(1, (int) $exam->max_attempts);
    }

    public function completedAttemptCount(Exam $exam, User $user): int
    {
        return ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'abandoned', 'expired', 'graded'])
            ->count();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $list
     */
    protected function emailInCandidateList(Collection|array $list, string $email): bool
    {
        foreach (Collection::wrap($list) as $entry) {
            if (is_string($entry) && strtolower(trim($entry)) === $email) {
                return true;
            }
            if (is_array($entry)) {
                $candidateEmail = strtolower(trim((string) ($entry['email'] ?? $entry['Email'] ?? '')));
                if ($candidateEmail !== '' && $candidateEmail === $email) {
                    return true;
                }
            }
        }

        return false;
    }
}

<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptSnapshot;
use App\Models\ExamAttemptViolation;
use App\Models\User;
use App\Support\UserAvatar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ExamAttempterService
{
    public function findExamForOrg(int $examId, int $organizationId): Exam
    {
        return Exam::query()
            ->forOrg($organizationId)
            ->findOrFail($examId);
    }

    public function findUserWhoAttempted(Exam $exam, int $userId): User
    {
        return User::query()
            ->whereKey($userId)
            ->whereHas('examAttempts', fn (Builder $q) => $q->where('exam_id', $exam->id))
            ->firstOrFail();
    }

    /**
     * Paginate unique users who have attempted the exam.
     */
    public function paginateAttempters(Exam $exam, Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->query('search', ''));
        $filters = $request->query('filters', []);
        if (! is_array($filters)) {
            $filters = [];
        }

        $statusFilter = (string) ($filters['status'] ?? '');
        $resultFilter = (string) ($filters['result'] ?? '');
        $emailVerified = $filters['email_verified'] ?? null;

        $sort = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $request->query('sort', 'last_attempt_at')) ?: 'last_attempt_at';
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'email', 'attempts_count', 'last_attempt_at', 'id'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'last_attempt_at';
        }

        $attemptsScope = fn (Builder $q) => $q->where('exam_attempts.exam_id', $exam->id);

        $query = User::query()
            ->whereHas('examAttempts', $attemptsScope)
            ->with(['profile'])
            ->withCount(['examAttempts as attempts_count' => $attemptsScope])
            ->addSelect([
                'last_attempt_at' => ExamAttempt::query()
                    ->selectRaw('MAX(COALESCE(submitted_at, started_at, created_at))')
                    ->whereColumn('exam_attempts.user_id', 'users.id')
                    ->where('exam_attempts.exam_id', $exam->id),
            ]);

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('users.name', 'like', '%'.$search.'%')
                    ->orWhere('users.email', 'like', '%'.$search.'%')
                    ->orWhere('users.username', 'like', '%'.$search.'%')
                    ->orWhereHas('profile', fn (Builder $p) => $p->where('phone', 'like', '%'.$search.'%'));
            });
        }

        if ($emailVerified === '1' || $emailVerified === 1 || $emailVerified === true) {
            $query->whereNotNull('email_verified_at');
        } elseif ($emailVerified === '0' || $emailVerified === 0) {
            $query->whereNull('email_verified_at');
        }

        if ($statusFilter !== '' || $resultFilter !== '') {
            $query->whereIn('users.id', $this->userIdsMatchingLatestAttemptFilters($exam->id, $statusFilter, $resultFilter));
        }

        if ($sort === 'last_attempt_at') {
            $query->orderByRaw('last_attempt_at IS NULL')
                ->orderBy('last_attempt_at', $direction);
        } elseif ($sort === 'attempts_count') {
            $query->orderBy('attempts_count', $direction);
        } else {
            $query->orderBy('users.'.$sort, $direction);
        }

        $perPage = min(100, max(5, (int) $request->query('per_page', 10)));
        $paginator = $query->paginate($perPage);

        $userIds = collect($paginator->items())->pluck('id')->all();
        $latestByUser = $this->latestAttemptsForUsers($exam->id, $userIds);
        $violationCounts = $this->violationCountsForUsers($exam->id, $userIds);

        $paginator->setCollection(
            collect($paginator->items())->map(function (User $user) use ($latestByUser, $violationCounts) {
                $latest = $latestByUser->get($user->id);
                $avatar = UserAvatar::resolve($user);
                $display = $latest ? $this->displayStatus($latest) : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'phone' => $user->profile?->phone,
                    'candidate_id' => $user->id,
                    'registered_at' => optional($user->created_at)->toIso8601String(),
                    'registered_at_label' => optional($user->created_at)->format('d M Y'),
                    'email_verified' => filled($user->email_verified_at),
                    'mobile_provided' => filled($user->profile?->phone),
                    'identity_verified' => ($violationCounts['identity'][$user->id] ?? false),
                    'avatar_url' => $avatar['url'],
                    'initials' => $avatar['initials'],
                    'avatar_color' => $avatar['color'],
                    'attempts_count' => (int) ($user->attempts_count ?? 0),
                    'violation_count' => (int) ($violationCounts['counts'][$user->id] ?? 0),
                    'has_verification_docs' => ($violationCounts['has_docs'][$user->id] ?? false),
                    'profile_url' => route('admin.candidates.show', $user->id),
                    'latest_attempt' => $latest ? $this->serializeAttempt($latest, $display) : null,
                ];
            })
        );

        return $paginator;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function attemptHistory(Exam $exam, User $user): array
    {
        $attempts = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->with('violations')
            ->orderByDesc('attempt_no')
            ->orderByDesc('id')
            ->get();

        return $attempts->map(function (ExamAttempt $attempt) {
            $display = $this->displayStatus($attempt);

            return $this->serializeAttempt($attempt, $display) + [
                'submission_type' => $this->submissionType($attempt),
                'submission_reason' => $attempt->submission_reason,
                'submission_reason_label' => $attempt->submissionReasonLabel(),
                'violations' => $attempt->violationsList(),
            ];
        })->values()->all();
    }

    /**
     * @return array{user:array<string,mixed>, documents:list<array<string,mixed>>}
     */
    public function verificationPayload(Exam $exam, User $user): array
    {
        $user->loadMissing('profile');
        $avatar = UserAvatar::resolve($user);

        $docs = [];
        $avatarUrl = $avatar['url'];
        if ($avatarUrl) {
            $docs[] = [
                'key' => 'profile_image',
                'label' => 'Profile Image',
                'type' => 'profile',
                'status' => 'uploaded',
                'url' => $avatarUrl,
                'download_url' => $avatarUrl,
            ];
        }

        $snapshots = ExamAttemptSnapshot::query()
            ->whereHas('attempt', function (Builder $q) use ($exam, $user) {
                $q->where('exam_id', $exam->id)->where('user_id', $user->id);
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        foreach ($snapshots as $snapshot) {
            $label = match ($snapshot->type) {
                'selfie' => 'Selfie Verification',
                'webcam' => 'Webcam Snapshot',
                'identity' => 'Identity Document',
                default => ucfirst((string) $snapshot->type).' Snapshot',
            };

            $docs[] = [
                'key' => 'snapshot_'.$snapshot->id,
                'label' => $label,
                'type' => $snapshot->type,
                'status' => $snapshot->verification_status ?: 'captured',
                'url' => route('admin.candidates.snapshots.show', [$user->id, $snapshot->id]),
                'download_url' => route('admin.candidates.snapshots.download', [$user->id, $snapshot->id]),
                'meta' => [
                    'captured_at' => optional($snapshot->created_at)->format('d M Y H:i'),
                ],
            ];
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => filled($user->email_verified_at),
                'mobile_provided' => filled($user->profile?->phone),
                'phone' => $user->profile?->phone,
                'identity_on_file' => $snapshots->contains(fn ($s) => in_array($s->type, ['selfie', 'webcam', 'identity'], true)),
                'avatar_url' => $avatarUrl,
                'initials' => $avatar['initials'],
                'avatar_color' => $avatar['color'],
            ],
            'documents' => $docs,
        ];
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, ExamAttempt>
     */
    protected function latestAttemptsForUsers(int $examId, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return ExamAttempt::query()
            ->where('exam_id', $examId)
            ->whereIn('user_id', $userIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => $rows->first());
    }

    /**
     * @param  list<int>  $userIds
     * @return array{counts:array<int,int>, has_docs:array<int,bool>, identity:array<int,bool>}
     */
    protected function violationCountsForUsers(int $examId, array $userIds): array
    {
        $counts = [];
        $hasDocs = [];
        $identity = [];

        if ($userIds === []) {
            return compact('counts') + ['has_docs' => $hasDocs, 'identity' => $identity];
        }

        if (Schema::hasTable('exam_attempt_violations')) {
            $rows = ExamAttemptViolation::query()
                ->selectRaw('exam_attempts.user_id, COUNT(*) as aggregate')
                ->join('exam_attempts', 'exam_attempts.id', '=', 'exam_attempt_violations.exam_attempt_id')
                ->where('exam_attempts.exam_id', $examId)
                ->whereIn('exam_attempts.user_id', $userIds)
                ->groupBy('exam_attempts.user_id')
                ->pluck('aggregate', 'user_id');

            foreach ($rows as $userId => $count) {
                $counts[(int) $userId] = (int) $count;
            }
        }

        $snapshotUsers = ExamAttemptSnapshot::query()
            ->whereHas('attempt', function (Builder $q) use ($examId, $userIds) {
                $q->where('exam_id', $examId)->whereIn('user_id', $userIds);
            })
            ->with('attempt:id,user_id')
            ->get();

        foreach ($snapshotUsers as $snapshot) {
            $uid = (int) $snapshot->attempt?->user_id;
            if (! $uid) {
                continue;
            }
            $hasDocs[$uid] = true;
            if (in_array($snapshot->type, ['selfie', 'webcam', 'identity'], true)) {
                $identity[$uid] = true;
            }
        }

        // Profile avatars also count as verification docs presence for the modal.
        User::query()
            ->whereIn('id', $userIds)
            ->with('profile')
            ->get()
            ->each(function (User $user) use (&$hasDocs) {
                if (filled($user->profile?->avatar)) {
                    $hasDocs[$user->id] = true;
                }
            });

        return [
            'counts' => $counts,
            'has_docs' => $hasDocs,
            'identity' => $identity,
        ];
    }

    /**
     * @return list<int>
     */
    protected function userIdsMatchingLatestAttemptFilters(int $examId, string $statusFilter, string $resultFilter): array
    {
        $latestIds = ExamAttempt::query()
            ->selectRaw('MAX(id)')
            ->where('exam_id', $examId)
            ->groupBy('user_id');

        $query = ExamAttempt::query()
            ->where('exam_id', $examId)
            ->whereIn('id', $latestIds);

        if ($resultFilter === 'passed') {
            $query->where('passed', true);
        } elseif ($resultFilter === 'failed') {
            $query->where('passed', false);
        }

        if ($statusFilter !== '') {
            match ($statusFilter) {
                'passed' => $query->where('passed', true),
                'failed' => $query->where('passed', false)
                    ->whereNotIn('status', [ExamAttempt::STATUS_ACTIVE, ExamAttempt::STATUS_IN_PROGRESS]),
                'in_progress' => $query->whereIn('status', [ExamAttempt::STATUS_ACTIVE, ExamAttempt::STATUS_IN_PROGRESS]),
                'auto_submitted' => $query->where(function (Builder $q) {
                    $q->where('status', ExamAttempt::STATUS_EXPIRED)
                        ->orWhere('submission_reason', 'timer_expired');
                }),
                'completed' => $query->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_GRADED])
                    ->whereNull('passed'),
                'abandoned' => $query->where('status', ExamAttempt::STATUS_ABANDONED),
                default => $query->where('status', $statusFilter),
            };
        }

        return $query->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * @return array{key:string,label:string,badge:string}
     */
    public function displayStatus(ExamAttempt $attempt): array
    {
        if ($attempt->passed === true) {
            return ['key' => 'passed', 'label' => 'Passed', 'badge' => 'success'];
        }

        if (in_array($attempt->status, [ExamAttempt::STATUS_ACTIVE, ExamAttempt::STATUS_IN_PROGRESS], true)) {
            return ['key' => 'in_progress', 'label' => 'In Progress', 'badge' => 'info'];
        }

        if ($attempt->status === ExamAttempt::STATUS_EXPIRED
            || in_array((string) $attempt->submission_reason, ['timer_expired'], true)) {
            return ['key' => 'auto_submitted', 'label' => 'Auto Submitted', 'badge' => 'warning'];
        }

        if ($attempt->passed === false) {
            return ['key' => 'failed', 'label' => 'Failed', 'badge' => 'danger'];
        }

        if (in_array($attempt->status, [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_GRADED], true)) {
            return ['key' => 'completed', 'label' => 'Completed', 'badge' => 'success'];
        }

        if ($attempt->status === ExamAttempt::STATUS_ABANDONED) {
            return ['key' => 'abandoned', 'label' => 'Abandoned', 'badge' => 'muted'];
        }

        return [
            'key' => (string) $attempt->status,
            'label' => ucwords(str_replace('_', ' ', (string) $attempt->status)),
            'badge' => 'muted',
        ];
    }

    public function submissionType(ExamAttempt $attempt): string
    {
        $reason = (string) $attempt->submission_reason;

        return match (true) {
            $reason === 'manual' => 'Manual',
            $reason === 'timer_expired' => 'Time Expired',
            str_starts_with($reason, 'violation_') => 'Rule Violation',
            $attempt->status === ExamAttempt::STATUS_EXPIRED => 'Auto Submitted',
            $reason !== '' => 'Auto Submitted',
            default => '—',
        };
    }

    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '—';
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        if ($h > 0) {
            return sprintf('%dh %02dm', $h, $m);
        }
        if ($m > 0) {
            return sprintf('%dm %02ds', $m, $s);
        }

        return sprintf('%ds', $s);
    }

    /**
     * @param  array{key:string,label:string,badge:string}|null  $display
     * @return array<string, mixed>
     */
    protected function serializeAttempt(ExamAttempt $attempt, ?array $display = null): array
    {
        $display ??= $this->displayStatus($attempt);

        return [
            'id' => $attempt->id,
            'attempt_no' => (int) $attempt->attempt_no,
            'status' => $attempt->status,
            'status_key' => $display['key'],
            'status_label' => $display['label'],
            'status_badge' => $display['badge'],
            'score' => $attempt->score,
            'percentage' => $attempt->percentage !== null ? round((float) $attempt->percentage, 1) : null,
            'passed' => $attempt->passed,
            'result_label' => $attempt->passed === true ? 'Pass' : ($attempt->passed === false ? 'Fail' : '—'),
            'started_at' => optional($attempt->started_at)->toIso8601String(),
            'started_at_label' => optional($attempt->started_at)->format('d M Y H:i'),
            'ended_at' => optional($attempt->submitted_at)->toIso8601String(),
            'ended_at_label' => optional($attempt->submitted_at)->format('d M Y H:i'),
            'time_taken' => $this->formatDuration($attempt->time_spent_seconds),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'last_attempt_at_label' => optional($attempt->submitted_at ?? $attempt->started_at ?? $attempt->created_at)->format('d M Y H:i'),
        ];
    }
}

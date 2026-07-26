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
        $attemptsByUser = $this->attemptsForUsers($exam, $userIds);
        $violationCounts = $this->violationCountsForUsers($exam->id, $userIds);

        $paginator->setCollection(
            collect($paginator->items())->map(function (User $user) use ($latestByUser, $attemptsByUser, $violationCounts, $exam) {
                $latest = $latestByUser->get($user->id);
                $avatar = UserAvatar::resolve($user);
                $display = $latest ? $this->displayStatus($latest) : null;
                $attempts = $attemptsByUser->get($user->id, collect())
                    ->map(fn (ExamAttempt $a) => $this->serializeAttemptDetailed($a, $exam, $this->displayStatus($a)))
                    ->values()
                    ->all();

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
                    'latest_attempt' => $latest ? $this->serializeAttemptDetailed($latest, $exam, $display) : null,
                    'attempts' => $attempts,
                ];
            })
        );

        return $paginator;
    }

    /**
     * All attempts for export: grouped by candidate, each candidate's attempts by started_at ASC.
     *
     * @return list<array<string, mixed>>
     */
    public function exportRows(Exam $exam): array
    {
        $attempts = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->with(['user.profile'])
            ->get()
            ->sortBy(function (ExamAttempt $a) {
                return sprintf(
                    '%s|%020d|%010d',
                    mb_strtolower((string) ($a->user?->name ?? 'zzz')),
                    $a->started_at?->timestamp ?? PHP_INT_MAX,
                    $a->id
                );
            })
            ->values();

        $zero = static fn (mixed $value): int|float => is_numeric($value) ? 0 + $value : 0;

        $rows = [];
        foreach ($attempts as $attempt) {
            $user = $attempt->user;
            if (! $user) {
                continue;
            }
            $detail = $this->serializeAttemptDetailed($attempt, $exam, $this->displayStatus($attempt));
            $rows[] = [
                'candidate_name' => $user->name,
                'candidate_email' => $user->email,
                'candidate_id' => $user->id,
                'attempt_no' => $zero($detail['attempt_no']),
                'status' => $detail['status_label'] ?: '—',
                'total_questions' => $zero($detail['total_questions']),
                'attempted' => $zero($detail['attempted']),
                'right' => $zero($detail['right']),
                'wrong' => $zero($detail['wrong']),
                'unanswered' => $zero($detail['unanswered']),
                'total_marks' => $zero($detail['total_marks']),
                'neg_marks' => filled($detail['neg_marks_label']) ? $detail['neg_marks_label'] : '0',
                'scored' => $zero($detail['score']),
                'percentage' => $zero($detail['percentage']),
                'result' => ($detail['result_label'] && $detail['result_label'] !== '—') ? $detail['result_label'] : '—',
                'started_at' => $detail['started_at_label'] ?: '—',
                'ended_at' => $detail['ended_at_label'] ?: '—',
                'duration' => $detail['time_taken'] ?: '0',
                'exam_duration' => ($detail['exam_duration_label'] && $detail['exam_duration_label'] !== '—')
                    ? $detail['exam_duration_label']
                    : '0',
            ];
        }

        return $rows;
    }

    public function exportAttemptsExcel(Exam $exam): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = $this->exportRows($exam);
        $filename = 'exam-'.$exam->id.'-attempts-'.now()->format('Ymd-His').'.xlsx';
        $lastCol = 'R';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Exam Attempts');

        $durationLabel = $exam->enable_exam_timer
            ? ((int) $exam->duration).' min'
            : 'No timer';
        $attemptsLimit = ($exam->attempt_limit_type === 'unlimited' || (int) $exam->max_attempts === 0)
            ? 'Unlimited'
            : (string) (int) $exam->max_attempts;
        $details = sprintf(
            'Duration: %s  ·  Total Marks: %s  ·  Pass Marks: %s  ·  Questions: %s  ·  Attempt Limit: %s  ·  Candidates: %s  ·  Attempts: %s  ·  Exported: %s',
            $durationLabel,
            (int) ($exam->total_marks ?? 0),
            (int) ($exam->passing_marks ?? 0),
            (int) ($exam->total_questions ?? 0),
            $attemptsLimit,
            collect($rows)->pluck('candidate_id')->unique()->count(),
            count($rows),
            now()->format('d M Y H:i')
        );

        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->setCellValue('A1', (string) $exam->title);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E1B4B'], 'size' => 18],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEF2FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->setCellValue('A2', $details);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['color' => ['rgb' => '475569'], 'size' => 10],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);

        $headers = [
            'A3' => 'Candidate Name',
            'B3' => 'Email',
            'C3' => 'Attempt #',
            'D3' => 'Status',
            'E3' => 'Total Qs',
            'F3' => 'Attempted',
            'G3' => 'Right',
            'H3' => 'Wrong',
            'I3' => 'Unanswered',
            'J3' => 'Total Marks',
            'K3' => 'Neg. Marks',
            'L3' => 'Scored',
            'M3' => 'Percentage',
            'N3' => 'Result',
            'O3' => 'Start',
            'P3' => 'End',
            'Q3' => 'Duration',
            'R3' => 'Exam Duration',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $headerRange = 'A3:'.$lastCol.'3';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        $r = 4;
        $prevCandidate = null;
        $groupIndex = -1;
        foreach ($rows as $row) {
            if ($prevCandidate !== $row['candidate_id']) {
                $groupIndex++;
                $prevCandidate = $row['candidate_id'];
            }

            $sheet->fromArray([
                $row['candidate_name'],
                $row['candidate_email'],
                $row['attempt_no'],
                $row['status'],
                $row['total_questions'],
                $row['attempted'],
                $row['right'],
                $row['wrong'],
                $row['unanswered'],
                $row['total_marks'],
                $row['neg_marks'],
                $row['scored'],
                $row['percentage'].'%',
                $row['result'],
                $row['started_at'],
                $row['ended_at'],
                $row['duration'],
                $row['exam_duration'],
            ], null, 'A'.$r, true);

            $groupFill = $groupIndex % 2 === 0 ? 'EEF2FF' : 'F8FAFC';
            $sheet->getStyle('A'.$r.':'.$lastCol.$r)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($groupFill);

            $result = (string) $row['result'];
            if ($result === 'Pass') {
                $sheet->getStyle('N'.$r)->getFont()->getColor()->setRGB('059669');
                $sheet->getStyle('N'.$r)->getFont()->setBold(true);
            } elseif ($result === 'Fail') {
                $sheet->getStyle('N'.$r)->getFont()->getColor()->setRGB('E11D48');
                $sheet->getStyle('N'.$r)->getFont()->setBold(true);
            }

            $r++;
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:'.$lastCol.'3');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<int, ExamAttempt>>
     */
    protected function attemptsForUsers(Exam $exam, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->whereIn('user_id', $userIds)
            ->with(['snapshots' => fn ($q) => $q->orderByDesc('id')])
            ->orderBy('started_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');
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
            ->with(['snapshots' => fn ($q) => $q->orderByDesc('id')])
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
        return $this->serializeAttemptDetailed($attempt, $attempt->exam, $display);
    }

    /**
     * @param  array{key:string,label:string,badge:string}|null  $display
     * @return array<string, mixed>
     */
    public function serializeAttemptDetailed(ExamAttempt $attempt, ?Exam $exam = null, ?array $display = null): array
    {
        $display ??= $this->displayStatus($attempt);
        $exam ??= $attempt->relationLoaded('exam') ? $attempt->exam : Exam::query()->find($attempt->exam_id);
        $config = is_array($attempt->exam_config_snapshot) ? $attempt->exam_config_snapshot : [];

        $totalQuestions = (int) ($config['total_questions'] ?? $exam?->total_questions ?? 0);
        $totalMarks = $config['total_marks'] ?? $exam?->total_marks;
        $examDurationMin = (int) ($config['duration'] ?? $exam?->duration ?? 0);
        $negEnabled = (bool) ($config['enable_negative_marking'] ?? $exam?->enable_negative_marking);
        $negPerQ = $config['negative_mark_per_question'] ?? $exam?->negative_mark_per_question;
        $negLabel = $negEnabled
            ? (filled($negPerQ) ? rtrim(rtrim(number_format((float) $negPerQ, 2, '.', ''), '0'), '.').'/Q' : 'On')
            : 'Off';

        $right = $attempt->correct_count;
        $wrong = $attempt->wrong_count;
        $unanswered = $attempt->unanswered_count;
        $attemptedQs = ($right !== null || $wrong !== null)
            ? (int) $right + (int) $wrong
            : (($totalQuestions > 0 && $unanswered !== null) ? max(0, $totalQuestions - (int) $unanswered) : null);

        $verification = [];
        if ($attempt->relationLoaded('snapshots')) {
            foreach ($attempt->snapshots as $snap) {
                $typeLabel = match ($snap->type) {
                    'selfie' => 'Selfie',
                    'webcam' => 'Webcam',
                    'identity' => 'Identity',
                    default => ucfirst((string) $snap->type),
                };
                $verification[] = [
                    'id' => $snap->id,
                    'type' => $snap->type,
                    'type_label' => $typeLabel,
                    'status' => $snap->verification_status ?: 'captured',
                    'url' => route('admin.candidates.snapshots.show', [$attempt->user_id, $snap->id]),
                    'download_url' => route('admin.candidates.snapshots.download', [$attempt->user_id, $snap->id]),
                    'captured_at' => optional($snap->created_at)->format('d M Y H:i'),
                ];
            }
        }

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
            'total_questions' => $totalQuestions > 0 ? $totalQuestions : null,
            'attempted' => $attemptedQs,
            'right' => $right,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'total_marks' => $totalMarks,
            'neg_marks_label' => $negLabel,
            'exam_duration_min' => $examDurationMin > 0 ? $examDurationMin : null,
            'exam_duration_label' => $examDurationMin > 0 ? $examDurationMin.' min' : '—',
            'verification' => $verification,
        ];
    }
}

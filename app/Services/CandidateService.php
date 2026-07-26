<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptSnapshot;
use App\Models\Feedback;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Support\OrganizationRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateService
{
    public function __construct(
        protected ProfileAvatarService $avatars,
    ) {}

    /**
     * List users who have exam attempts in the organization (any role).
     * Optionally scoped to a specific exam.
     */
    public function queryForOrganization(int $organizationId, string $trash = 'active', ?int $examId = null): Builder
    {
        $attemptsConstraint = function (Builder $q) use ($organizationId, $examId) {
            $q->where('exam_attempts.organization_id', $organizationId);
            if ($examId) {
                $q->where('exam_attempts.exam_id', $examId);
            }
        };

        $query = User::query()
            ->whereHas('examAttempts', $attemptsConstraint)
            ->with(['profile', 'organizations' => function ($q) use ($organizationId) {
                $q->where('organizations.id', $organizationId);
            }])
            ->withCount(['examAttempts as attempts_count' => $attemptsConstraint]);

        if ($trash === 'bin') {
            $query->onlyTrashed();
        }

        return $query;
    }

    /**
     * Resolve a user for candidate admin screens: org member or anyone with
     * attempts in this organization (admins/editors who sat an exam, etc.).
     */
    public function findForOrganization(int $candidateId, int $organizationId, bool $withTrashed = false): User
    {
        $query = User::query()
            ->when($withTrashed, fn (Builder $q) => $q->withTrashed())
            ->where(function (Builder $q) use ($organizationId) {
                $q->whereHas('organizations', function (Builder $org) use ($organizationId) {
                    $org->where('organizations.id', $organizationId);
                })->orWhereHas('examAttempts', function (Builder $attempts) use ($organizationId) {
                    $attempts->where('exam_attempts.organization_id', $organizationId);
                });
            })
            ->with(['profile', 'organizations' => function ($q) use ($organizationId) {
                $q->where('organizations.id', $organizationId);
            }]);

        return $query->findOrFail($candidateId);
    }

    public function setStatus(User $candidate, int $organizationId, string $status): User
    {
        $status = $status === 'active' ? 'active' : 'inactive';
        $candidate->status = $status;
        $candidate->save();

        $candidate->organizations()->updateExistingPivot($organizationId, [
            'status' => $status,
        ]);

        $this->logActivity(
            $candidate->id,
            'candidate_status_changed',
            'account',
            'Candidate status changed',
            'Status set to '.$status.'.'
        );

        return $candidate->fresh(['profile']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $organizationId): User
    {
        return DB::transaction(function () use ($data, $organizationId) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'] ?: null,
                'email' => strtolower((string) $data['email']),
                'password' => $data['password'],
                'status' => $data['status'] ?? 'active',
                'email_verified_at' => ! empty($data['email_verified']) ? now() : null,
            ]);

            $profileData = $this->profilePayload($data);
            $profileData['id'] = $user->id;
            $profileData['status'] = 'active';
            $profileData['default_organization_id'] = $organizationId;

            if (! empty($data['cropped_avatar'])) {
                try {
                    $profileData['avatar'] = $this->avatars->storeFromBase64(
                        (string) $data['cropped_avatar'],
                        $user->id,
                        $organizationId,
                    );
                } catch (\Throwable $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cropped_avatar' => $e->getMessage() ?: 'Unable to upload the profile photo.',
                    ]);
                }
            }

            Profile::create($profileData);

            $user->organizations()->attach($organizationId, [
                'role' => OrganizationRoles::CANDIDATE,
                'status' => ($data['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
            ]);

            $this->logActivity(
                $user->id,
                'candidate_created',
                'account',
                'Candidate account created',
                'Admin created this candidate account.'
            );

            return $user->fresh(['profile']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $candidate, array $data, int $organizationId): User
    {
        return DB::transaction(function () use ($candidate, $data, $organizationId) {
            $candidate->fill([
                'name' => $data['name'],
                'username' => $data['username'] ?: null,
                'email' => strtolower((string) $data['email']),
                'status' => $data['status'] ?? $candidate->status,
            ]);

            if ($candidate->isDirty('email')) {
                $candidate->email_verified_at = ! empty($data['email_verified']) ? now() : null;
            } elseif (array_key_exists('email_verified', $data)) {
                $candidate->email_verified_at = ! empty($data['email_verified'])
                    ? ($candidate->email_verified_at ?? now())
                    : null;
            }

            if (! empty($data['password'])) {
                $candidate->password = $data['password'];
            }

            $candidate->save();

            $profileData = $this->profilePayload($data);

            if (! empty($data['remove_avatar'])) {
                $this->avatars->delete($candidate->profile?->avatar);
                $profileData['avatar'] = null;
            } elseif (! empty($data['cropped_avatar'])) {
                try {
                    $existing = $candidate->profile?->avatar;
                    $profileData['avatar'] = $this->avatars->storeFromBase64(
                        (string) $data['cropped_avatar'],
                        $candidate->id,
                        $organizationId,
                    );
                    $this->avatars->delete($existing);
                } catch (\Throwable $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cropped_avatar' => $e->getMessage() ?: 'Unable to upload the profile photo.',
                    ]);
                }
            }

            Profile::updateOrCreate(['id' => $candidate->id], $profileData);

            if ($candidate->belongsToOrganization($organizationId)) {
                $candidate->organizations()->updateExistingPivot($organizationId, [
                    'status' => ($data['status'] ?? $candidate->status) === 'active' ? 'active' : 'inactive',
                ]);
            }

            $this->logActivity(
                $candidate->id,
                'candidate_updated',
                'account',
                'Candidate profile updated',
                'Admin updated this candidate account.'
            );

            return $candidate->fresh(['profile']);
        });
    }

    public function delete(User $candidate): void
    {
        $candidate->delete();

        $this->logActivity(
            $candidate->id,
            'candidate_deleted',
            'account',
            'Candidate account deleted',
            'Admin moved this candidate to the bin.'
        );
    }

    public function restore(User $candidate): User
    {
        $candidate->restore();

        $this->logActivity(
            $candidate->id,
            'candidate_restored',
            'account',
            'Candidate account restored',
            'Admin restored this candidate from the bin.'
        );

        return $candidate->fresh(['profile']);
    }

    public function toggleStatus(User $candidate, int $organizationId): User
    {
        $next = $candidate->status === 'active' ? 'inactive' : 'active';
        $candidate->status = $next;
        $candidate->save();

        $candidate->organizations()->updateExistingPivot($organizationId, [
            'status' => $next,
        ]);

        $this->logActivity(
            $candidate->id,
            'candidate_status_changed',
            'account',
            'Candidate status changed',
            'Status set to '.$next.'.'
        );

        return $candidate->fresh(['profile']);
    }

    public function resetPassword(User $candidate, ?string $password = null): string
    {
        $plain = $password ?: Str::password(12);
        $candidate->password = $plain;
        $candidate->save();

        $this->logActivity(
            $candidate->id,
            'password_reset',
            'security',
            'Password reset by admin',
            'An administrator reset the candidate password.'
        );

        return $plain;
    }

    /**
     * @return array<string, mixed>
     */
    public function examStats(User $candidate): array
    {
        $attempts = ExamAttempt::query()->where('user_id', $candidate->id);

        $total = (clone $attempts)->count();
        $completed = (clone $attempts)->whereIn('status', [
            ExamAttempt::STATUS_SUBMITTED,
            ExamAttempt::STATUS_EXPIRED,
            ExamAttempt::STATUS_GRADED,
        ])->count();
        $ongoing = (clone $attempts)->whereIn('status', [
            ExamAttempt::STATUS_ACTIVE,
            ExamAttempt::STATUS_IN_PROGRESS,
        ])->count();
        $upcoming = 0;
        $passed = (clone $attempts)->where('passed', true)->count();
        $failed = (clone $attempts)->where('passed', false)->count();
        $avgScore = (clone $attempts)
            ->whereNotNull('percentage')
            ->avg('percentage');

        return [
            'total' => $total,
            'completed' => $completed,
            'ongoing' => $ongoing,
            'upcoming' => $upcoming,
            'passed' => $passed,
            'failed' => $failed,
            'average_score' => $avgScore !== null ? round((float) $avgScore, 1) : null,
            'total_attempts' => $total,
        ];
    }

    /**
     * @return array{
     *     avatar_url:?string,
     *     last_login:?string,
     *     membership_status:?string,
     *     exam_stats:array<string,mixed>,
     *     recent_attempts:\Illuminate\Support\Collection,
     *     verification_documents:list<array<string,mixed>>,
     *     activity_logs:\Illuminate\Support\Collection,
     *     feedback:\Illuminate\Support\Collection,
     *     violation_count:int,
     *     feedback_count:int,
     *     completion:array<string,mixed>
     * }
     */
    public function detailsPayload(User $candidate, int $organizationId): array
    {
        $candidate->loadMissing('profile');

        $recentAttempts = ExamAttempt::query()
            ->where('user_id', $candidate->id)
            ->with([
                'exam:id,title,slug,status,total_questions,total_marks,duration,negative_mark_per_question,enable_negative_marking',
                'snapshots' => fn ($q) => $q->orderByDesc('id'),
            ])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $violationCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('exam_attempt_violations')) {
            $violationCount = \App\Models\ExamAttemptViolation::query()
                ->whereHas('attempt', fn (Builder $q) => $q->where('user_id', $candidate->id))
                ->count();
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('exam_attempts', 'violations_summary')) {
            $violationCount = (int) ExamAttempt::query()
                ->where('user_id', $candidate->id)
                ->get()
                ->sum(fn (ExamAttempt $a) => is_array($a->violations_summary) ? count($a->violations_summary) : 0);
        }

        $feedback = Feedback::query()
            ->where('user_id', $candidate->id)
            ->with(['exam:id,title'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $activityLogs = UserActivityLog::query()
            ->where('user_id', $candidate->id)
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $lastLogin = DB::table('sessions')
            ->where('user_id', $candidate->id)
            ->orderByDesc('last_activity')
            ->value('last_activity');

        $membership = $candidate->organizations()
            ->where('organizations.id', $organizationId)
            ->first();

        return [
            'avatar_url' => $this->avatars->url($candidate->profile?->avatar),
            'last_login' => $lastLogin ? date('d M Y H:i', (int) $lastLogin) : null,
            'membership_status' => $membership?->pivot?->status,
            'exam_stats' => $this->examStats($candidate),
            'recent_attempts' => $recentAttempts,
            'verification_documents' => $this->verificationDocuments($candidate),
            'activity_logs' => $activityLogs,
            'feedback' => $feedback,
            'violation_count' => (int) $violationCount,
            'feedback_count' => (int) Feedback::query()->where('user_id', $candidate->id)->count(),
            'completion' => $this->profileCompletion($candidate),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function verificationDocuments(User $candidate): array
    {
        $docs = [];

        $avatarUrl = $this->avatars->url($candidate->profile?->avatar);
        if ($avatarUrl) {
            $docs[] = [
                'key' => 'profile_image',
                'label' => 'Profile Image',
                'type' => 'profile',
                'type_label' => 'Profile Image',
                'status' => 'uploaded',
                'url' => $avatarUrl,
                'download_url' => $avatarUrl,
                'can_replace' => true,
                'meta' => null,
            ];
        }

        $snapshots = ExamAttemptSnapshot::query()
            ->whereHas('attempt', fn (Builder $q) => $q->where('user_id', $candidate->id))
            ->with(['attempt:id,exam_id,user_id,attempt_no', 'attempt.exam:id,title'])
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        foreach ($snapshots as $snapshot) {
            $typeLabel = match ($snapshot->type) {
                'selfie' => 'Selfie Verification',
                'webcam' => 'Webcam Snapshot',
                'identity' => 'Identity Document',
                default => ucfirst((string) $snapshot->type).' Snapshot',
            };

            $examTitle = $snapshot->attempt?->exam?->title;
            $label = $examTitle ? $typeLabel.' — '.$examTitle : $typeLabel;

            $docs[] = [
                'key' => 'snapshot_'.$snapshot->id,
                'label' => $label,
                'type' => $snapshot->type,
                'type_label' => $typeLabel,
                'status' => $snapshot->verification_status ?: 'captured',
                'url' => route('admin.candidates.snapshots.show', [$candidate, $snapshot]),
                'download_url' => route('admin.candidates.snapshots.download', [$candidate, $snapshot]),
                'can_replace' => false,
                'meta' => [
                    'attempt_id' => $snapshot->exam_attempt_id,
                    'attempt_no' => $snapshot->attempt?->attempt_no,
                    'exam_id' => $snapshot->attempt?->exam_id,
                    'exam_title' => $examTitle,
                    'captured_at' => optional($snapshot->created_at)->format('d M Y H:i'),
                ],
            ];
        }

        return $docs;
    }

    public function streamSnapshot(User $candidate, ExamAttemptSnapshot $snapshot): StreamedResponse
    {
        $this->assertSnapshotBelongsToCandidate($candidate, $snapshot);

        $disk = $snapshot->disk ?: 'local';
        $path = (string) $snapshot->path;

        abort_unless($path !== '' && Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, basename($path), [
            'Content-Type' => Storage::disk($disk)->mimeType($path) ?: 'image/jpeg',
        ]);
    }

    public function downloadSnapshot(User $candidate, ExamAttemptSnapshot $snapshot): StreamedResponse
    {
        $this->assertSnapshotBelongsToCandidate($candidate, $snapshot);

        $disk = $snapshot->disk ?: 'local';
        $path = (string) $snapshot->path;

        abort_unless($path !== '' && Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path, basename($path));
    }

    /**
     * @return array{percent:int,filled:int,total:int,missing:list<string>}
     */
    public function profileCompletion(User $candidate): array
    {
        $profile = $candidate->profile;
        $checks = [
            'Full name' => filled($candidate->name),
            'Username' => filled($candidate->username),
            'Email' => filled($candidate->email),
            'Phone' => filled($profile?->phone),
            'Date of birth' => filled($profile?->date_of_birth),
            'Gender' => filled($profile?->gender),
            'Bio' => filled($profile?->bio),
            'Profile photo' => filled($profile?->avatar),
            'Address' => filled($profile?->address_line1) || filled($profile?->city),
            'Social links' => ! empty($profile?->social_links),
        ];

        $filled = count(array_filter($checks));
        $total = count($checks);

        return [
            'percent' => (int) round(($filled / max(1, $total)) * 100),
            'filled' => $filled,
            'total' => $total,
            'missing' => array_keys(array_filter($checks, fn ($ok) => ! $ok)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function profilePayload(array $data): array
    {
        return [
            'phone' => $data['phone'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'bio' => $data['bio'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'state_region' => $data['state_region'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
            'social_links' => collect($data['social_links'] ?? [])
                ->map(fn ($value) => filled($value) ? trim((string) $value) : null)
                ->filter()
                ->all(),
        ];
    }

    protected function assertSnapshotBelongsToCandidate(User $candidate, ExamAttemptSnapshot $snapshot): void
    {
        $belongs = ExamAttempt::query()
            ->where('id', $snapshot->exam_attempt_id)
            ->where('user_id', $candidate->id)
            ->exists();

        abort_unless($belongs, 404);
    }

    protected function logActivity(
        int $userId,
        string $event,
        string $category,
        string $title,
        ?string $description = null
    ): void {
        try {
            UserActivityLog::query()->create([
                'user_id' => $userId,
                'event' => $event,
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
            ]);
        } catch (\Throwable) {
            // Activity logging should never block candidate CRUD.
        }
    }
}

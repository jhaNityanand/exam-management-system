<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Profile;
use App\Models\UserActivityLog;
use App\Services\ProfileAvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        protected ProfileAvatarService $avatars,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');
        $attempts = ExamAttempt::query()->where('user_id', $user->id);

        $stats = [
            'attempts' => (clone $attempts)->count(),
            'completed' => (clone $attempts)->whereIn('status', ['submitted', 'expired', 'graded'])->count(),
            'passed' => (clone $attempts)->where('passed', true)->count(),
            'avg_score' => (int) round((clone $attempts)->whereNotNull('percentage')->avg('percentage') ?? 0),
        ];

        $recent = $user->examAttempts()
            ->with(['exam:id,title,slug'])
            ->latest('id')
            ->limit(5)
            ->get();

        $failed = max(0, (int) $stats['completed'] - (int) $stats['passed']);
        $inProgress = max(0, (int) $stats['attempts'] - (int) $stats['completed']);

        $scoreSeries = $user->examAttempts()
            ->whereNotNull('percentage')
            ->latest('submitted_at')
            ->limit(7)
            ->get(['percentage', 'submitted_at'])
            ->reverse()
            ->values();

        $charts = $this->dashboardCharts($stats, $failed, $inProgress, $scoreSeries);

        return view('frontend.account.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recent' => $recent,
            'completion' => $this->profileCompletion($user),
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
            'charts' => $charts,
        ]);
    }

    public function exams(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        $attempts = $user->examAttempts()
            ->with(['exam:id,title,slug,difficulty_level,duration,total_questions,pricing_option,exam_amount,status'])
            ->latest('id')
            ->paginate(12);

        return view('frontend.account.exams', [
            'user' => $user,
            'attempts' => $attempts,
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
        ]);
    }

    public function results(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return view('frontend.account.results', [
            'user' => $user,
            'results' => $user->examAttempts()
                ->with(['exam:id,title,slug,pass_percentage,passing_marks,result_release_mode'])
                ->whereNotNull('submitted_at')
                ->latest('submitted_at')
                ->paginate(12),
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
        ]);
    }

    public function profile(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return view('frontend.account.profile', [
            'user' => $user,
            'dataUrl' => route('frontend.account.profile.data'),
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
            'completion' => $this->profileCompletion($user),
        ]);
    }

    public function profileData(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('profile');
        $profile = $user->profile;
        $social = $profile?->social_links ?? [];

        $attempts = ExamAttempt::query()->where('user_id', $user->id);

        return response()->json([
            'ok' => true,
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'profile' => [
                'phone' => $profile?->phone,
                'date_of_birth' => optional($profile?->date_of_birth)->format('Y-m-d'),
                'gender' => $profile?->gender,
                'bio' => $profile?->bio,
                'address_line1' => $profile?->address_line1,
                'address_line2' => $profile?->address_line2,
                'city' => $profile?->city,
                'state_region' => $profile?->state_region,
                'postal_code' => $profile?->postal_code,
                'country' => $profile?->country,
                'social_links' => [
                    'website' => $social['website'] ?? '',
                    'linkedin' => $social['linkedin'] ?? '',
                    'twitter' => $social['twitter'] ?? '',
                    'github' => $social['github'] ?? '',
                    'facebook' => $social['facebook'] ?? '',
                ],
            ],
            'avatar_url' => $this->avatars->url($profile?->avatar),
            'completion' => $this->profileCompletion($user),
            'stats' => [
                'attempts' => (clone $attempts)->count(),
                'completed' => (clone $attempts)->whereIn('status', ['submitted', 'expired', 'graded'])->count(),
                'passed' => (clone $attempts)->where('passed', true)->count(),
                'member_since' => optional($user->created_at)->format('d M Y'),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->ensureCandidateMembership();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:64', 'alpha_dash', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state_region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:2'],
            'social_links' => ['nullable', 'array'],
            'social_links.website' => ['nullable', 'string', 'max:255'],
            'social_links.linkedin' => ['nullable', 'string', 'max:255'],
            'social_links.twitter' => ['nullable', 'string', 'max:255'],
            'social_links.github' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'cropped_avatar' => ['nullable', 'string'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'] ?: null,
            'email' => $data['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $profileData = [
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

        if ($request->boolean('remove_avatar')) {
            $this->avatars->delete($user->profile?->avatar);
            $profileData['avatar'] = null;
        } elseif ($request->filled('cropped_avatar')) {
            $existing = $user->profile?->avatar;
            $orgId = current_organization_id()
                ?: $user->organizations()->value('organizations.id')
                ?: \App\Models\Organization::query()->value('id');
            if (! $orgId) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cropped_avatar' => 'Unable to upload avatar because no organization is available.',
                ]);
            }
            $profileData['avatar'] = $this->avatars->storeFromBase64(
                $request->string('cropped_avatar')->toString(),
                $user->id,
                (int) $orgId,
            );
            $this->avatars->delete($existing);
        }

        Profile::updateOrCreate(
            ['id' => $user->id],
            array_merge(['id' => $user->id, 'status' => 'active'], $profileData)
        );

        $this->logActivity($user->id, 'profile_updated', 'profile', 'Profile updated', 'Account profile details were saved.');

        $user->refresh()->load('profile');

        return response()->json([
            'ok' => true,
            'message' => 'Profile updated successfully.',
            'avatar_url' => $this->avatars->url($user->profile?->avatar),
            'completion' => $this->profileCompletion($user),
        ]);
    }

    public function settings(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return view('frontend.account.settings', [
            'user' => $user,
            'dataUrl' => route('frontend.account.settings.data'),
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
        ]);
    }

    public function settingsData(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('profile');
        $profile = $user->profile;
        $notifications = array_merge($this->defaultNotifications(), $profile?->notification_preferences ?? []);
        $privacy = array_merge($this->defaultPrivacy(), $profile?->privacy_settings ?? []);

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->limit(8)
            ->get()
            ->map(function ($session) use ($request) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'device' => $this->summarizeAgent($session->user_agent),
                    'last_activity' => $session->last_activity
                        ? date('d M Y H:i', (int) $session->last_activity)
                        : null,
                    'is_current' => $session->id === $request->session()->getId(),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ],
            'notifications' => $notifications,
            'privacy' => $privacy,
            'sessions' => $sessions,
            'security' => [
                'email_verified' => (bool) $user->email_verified_at,
                'has_password' => filled($user->password),
                'two_factor' => false,
            ],
            'password_reset_url' => route('password.request'),
        ]);
    }

    public function updateAccountSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'notifications' => ['nullable', 'array'],
            'notifications.exam_reminders' => ['nullable', 'boolean'],
            'notifications.result_alerts' => ['nullable', 'boolean'],
            'notifications.marketing' => ['nullable', 'boolean'],
            'notifications.security_alerts' => ['nullable', 'boolean'],
            'privacy' => ['nullable', 'array'],
            'privacy.show_profile' => ['nullable', 'boolean'],
            'privacy.show_results' => ['nullable', 'boolean'],
            'privacy.allow_messages' => ['nullable', 'boolean'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ])->save();

        $notifications = array_merge($this->defaultNotifications(), [
            'exam_reminders' => (bool) data_get($data, 'notifications.exam_reminders', true),
            'result_alerts' => (bool) data_get($data, 'notifications.result_alerts', true),
            'marketing' => (bool) data_get($data, 'notifications.marketing', false),
            'security_alerts' => (bool) data_get($data, 'notifications.security_alerts', true),
        ]);

        $privacy = array_merge($this->defaultPrivacy(), [
            'show_profile' => (bool) data_get($data, 'privacy.show_profile', true),
            'show_results' => (bool) data_get($data, 'privacy.show_results', false),
            'allow_messages' => (bool) data_get($data, 'privacy.allow_messages', true),
        ]);

        Profile::updateOrCreate(
            ['id' => $user->id],
            [
                'id' => $user->id,
                'status' => 'active',
                'notification_preferences' => $notifications,
                'privacy_settings' => $privacy,
            ]
        );

        $this->logActivity($user->id, 'settings_updated', 'settings', 'Settings updated', 'Account preferences were saved.');

        return response()->json([
            'ok' => true,
            'message' => 'Account settings saved.',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $data['password'],
        ]);

        $this->logActivity($user->id, 'password_changed', 'security', 'Password changed', 'Account password was updated.');

        return response()->json([
            'ok' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function destroyAccount(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'redirect' => url('/'),
                'message' => 'Your account has been deleted.',
            ]);
        }

        return redirect('/');
    }

    /** @deprecated Kept for older forms; redirects to JSON-capable settings. */
    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);
        $user->fill($data)->save();

        return back()->with('success', 'Settings updated successfully.');
    }

    public function invoices(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return view('frontend.account.invoices', [
            'user' => $user,
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
        ]);
    }

    public function activity(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return view('frontend.account.activity', [
            'user' => $user,
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
        ]);
    }

    /**
     * Build dashboard chart payloads (real data when available, polished static fallbacks).
     *
     * @param  array<string, int>  $stats
     * @param  \Illuminate\Support\Collection<int, mixed>  $scoreSeries
     * @return array<string, mixed>
     */
    protected function dashboardCharts(array $stats, int $failed, int $inProgress, $scoreSeries): array
    {
        $hasOutcomeData = ((int) $stats['passed'] + $failed + $inProgress) > 0;

        $pie = $hasOutcomeData
            ? [
                ['label' => 'Passed', 'value' => (int) $stats['passed'], 'color' => '#059669'],
                ['label' => 'Failed', 'value' => $failed, 'color' => '#dc2626'],
                ['label' => 'In progress', 'value' => $inProgress, 'color' => '#0f766e'],
            ]
            : [
                ['label' => 'Passed', 'value' => 42, 'color' => '#059669'],
                ['label' => 'Failed', 'value' => 18, 'color' => '#dc2626'],
                ['label' => 'In progress', 'value' => 12, 'color' => '#0f766e'],
            ];

        $weekly = [
            ['label' => 'Mon', 'value' => 45],
            ['label' => 'Tue', 'value' => 62],
            ['label' => 'Wed', 'value' => 38],
            ['label' => 'Thu', 'value' => 74],
            ['label' => 'Fri', 'value' => 58],
            ['label' => 'Sat', 'value' => 81],
            ['label' => 'Sun', 'value' => 67],
        ];

        if ($scoreSeries->isNotEmpty()) {
            $weekly = $scoreSeries->values()->map(function ($row, $index) {
                $label = optional($row->submitted_at)->format('D') ?: ('T'.($index + 1));

                return [
                    'label' => $label,
                    'value' => (int) round((float) $row->percentage),
                ];
            })->all();
        }

        $lineValues = array_column($weekly, 'value');
        if (count($lineValues) < 2) {
            $lineValues = [32, 48, 41, 66, 58, 72, 69];
        }

        return [
            'demo' => ! $hasOutcomeData && $scoreSeries->isEmpty(),
            'pie' => $pie,
            'bars' => $weekly,
            'line' => $lineValues,
            'categories' => [
                ['label' => 'Aptitude', 'value' => 78],
                ['label' => 'Technical', 'value' => 64],
                ['label' => 'Verbal', 'value' => 71],
                ['label' => 'Coding', 'value' => 55],
            ],
        ];
    }

    /**
     * @return array{percent:int, filled:int, total:int, missing:list<string>}
     */
    protected function profileCompletion($user): array
    {
        $profile = $user->profile;
        $checks = [
            'Full name' => filled($user->name),
            'Username' => filled($user->username),
            'Email' => filled($user->email),
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
        $missing = array_keys(array_filter($checks, fn ($ok) => ! $ok));

        return [
            'percent' => (int) round(($filled / max(1, $total)) * 100),
            'filled' => $filled,
            'total' => $total,
            'missing' => $missing,
        ];
    }

    /**
     * @return array<string, bool>
     */
    protected function defaultNotifications(): array
    {
        return [
            'exam_reminders' => true,
            'result_alerts' => true,
            'marketing' => false,
            'security_alerts' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    protected function defaultPrivacy(): array
    {
        return [
            'show_profile' => true,
            'show_results' => false,
            'allow_messages' => true,
        ];
    }

    protected function summarizeAgent(?string $agent): string
    {
        $agent = (string) $agent;
        if ($agent === '') {
            return 'Unknown device';
        }

        $browser = str_contains($agent, 'Edg/') ? 'Edge'
            : (str_contains($agent, 'Chrome/') ? 'Chrome'
                : (str_contains($agent, 'Firefox/') ? 'Firefox'
                    : (str_contains($agent, 'Safari/') ? 'Safari' : 'Browser')));

        $os = str_contains($agent, 'Windows') ? 'Windows'
            : (str_contains($agent, 'Mac OS') ? 'macOS'
                : (str_contains($agent, 'Android') ? 'Android'
                    : (str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') ? 'iOS' : 'Device')));

        return $browser.' on '.$os;
    }

    protected function logActivity(int $userId, string $event, string $category, string $title, ?string $description = null): void
    {
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
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

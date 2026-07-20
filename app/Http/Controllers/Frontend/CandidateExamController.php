<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamVerificationChallenge;
use App\Services\CandidateExam\ExamEligibilityService;
use App\Services\CandidateExam\ExamPaymentPlaceholderService;
use App\Services\CandidateExam\ExamProctoringService;
use App\Services\CandidateExam\ExamRequirementResolver;
use App\Services\CandidateExam\ExamSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CandidateExamController extends Controller
{
    public function __construct(
        protected ExamEligibilityService $eligibility,
        protected ExamSessionService $sessions,
        protected ExamPaymentPlaceholderService $payments,
        protected ExamProctoringService $proctoring,
        protected ExamRequirementResolver $requirements,
    ) {}

    public function rules(Request $request, Exam $exam): View|RedirectResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        abort_unless($this->eligibility->canViewPublicDetail($exam, $user), 403);

        $user->ensureCandidateMembership((int) $exam->organization_id);
        $evaluation = $this->eligibility->evaluate($exam, $user);
        $policy = $this->requirements->syncPolicy($exam);
        $exam->setRelation('proctoringPolicy', $policy);

        $rules = $this->requirements->rulesForExam(
            $exam,
            array_values(array_filter(array_map('strval', $exam->predefined_instruction_rules ?? [])))
        );

        return view('frontend.candidate.exams.rules', [
            'exam' => $exam,
            'evaluation' => $evaluation,
            'rules' => $rules,
            'policy' => $policy,
        ]);
    }

    public function prepare(Request $request, Exam $exam): View|RedirectResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        abort_unless($this->eligibility->canViewPublicDetail($exam, $user), 403);

        $evaluation = $this->eligibility->evaluate($exam, $user);
        if (! empty($evaluation['requires_payment'])) {
            return redirect()
                ->route('frontend.exams.rules', $exam)
                ->with('error', 'Payment is required before preparing this exam.');
        }

        $policy = $this->requirements->syncPolicy($exam);
        $exam->setRelation('proctoringPolicy', $policy);
        $checks = $this->requirements->readinessChecks($policy->toRuntimeArray());
        $challenge = $this->createChallenge($exam, $user->id, $policy->toRuntimeArray());

        return view('frontend.candidate.exams.prepare', [
            'exam' => $exam,
            'evaluation' => $evaluation,
            'policy' => $policy,
            'checks' => $checks,
            'challenge' => $challenge,
        ]);
    }

    public function storeVerification(Request $request, Exam $exam): JsonResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        abort_unless($this->eligibility->canViewPublicDetail($exam, $user), 403);

        $data = $request->validate([
            'challenge_token' => ['required', 'string', 'max:64'],
            'completed_checks' => ['nullable', 'array'],
            'completed_checks.*' => ['string', 'max:64'],
            'selfie' => ['nullable', 'image', 'max:5120'],
        ]);

        $challenge = ExamVerificationChallenge::query()
            ->where('token', $data['challenge_token'])
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $challenge || ! $challenge->isValid()) {
            throw ValidationException::withMessages([
                'challenge_token' => 'Verification session expired. Refresh the prepare page.',
            ]);
        }

        $completed = array_values(array_unique(array_merge(
            $challenge->completed_checks ?? [],
            $data['completed_checks'] ?? []
        )));

        if ($request->hasFile('selfie')) {
            if ($challenge->selfie_path) {
                Storage::disk($challenge->selfie_disk ?: 'local')->delete($challenge->selfie_path);
            }

            $path = $request->file('selfie')->store(
                'exam-verification/'.$exam->id.'/'.$user->id,
                'local'
            );
            $challenge->selfie_path = $path;
            $challenge->selfie_disk = 'local';
            $completed[] = 'selfie';
            $completed[] = 'webcam';
        }

        $challenge->completed_checks = array_values(array_unique($completed));
        $challenge->save();

        return response()->json([
            'ok' => true,
            'completed_checks' => $challenge->completed_checks,
            'has_selfie' => filled($challenge->selfie_path),
        ]);
    }

    public function start(Request $request, Exam $exam): RedirectResponse|JsonResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        $user->ensureCandidateMembership((int) $exam->organization_id);

        try {
            $policy = $this->requirements->syncPolicy($exam);
            $policyArray = $policy->toRuntimeArray();

            $data = $request->validate([
                'challenge_token' => ['required', 'string', 'max:64'],
                'device' => ['nullable', 'array'],
                'device.browser' => ['nullable', 'string', 'max:120'],
                'device.device_type' => ['nullable', 'string', 'max:64'],
                'device.os' => ['nullable', 'string', 'max:120'],
                'device.screen_resolution' => ['nullable', 'string', 'max:32'],
                'device.timezone' => ['nullable', 'string', 'max:64'],
                'device.local_time' => ['nullable', 'string', 'max:64'],
                'device.session_token' => ['nullable', 'string', 'max:64'],
                'checks' => ['nullable', 'array'],
                'checks.webcam' => ['nullable', 'boolean'],
                'checks.microphone' => ['nullable', 'boolean'],
                'checks.fullscreen' => ['nullable', 'boolean'],
                'checks.selfie' => ['nullable', 'boolean'],
                'selfie' => ['nullable', 'image', 'max:5120'],
            ]);

            $challenge = ExamVerificationChallenge::query()
                ->where('token', $data['challenge_token'])
                ->where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $challenge || ! $challenge->isValid()) {
                throw ValidationException::withMessages([
                    'challenge_token' => 'Verification session expired. Refresh and try again.',
                ]);
            }

            $this->assertReadyToStart($policyArray, $data, $challenge, $request);

            $attempt = DB::transaction(function () use ($exam, $user, $data, $request, $policyArray, $challenge) {
                if ($request->hasFile('selfie') && ! $challenge->selfie_path) {
                    $challenge->selfie_path = $request->file('selfie')->store(
                        'exam-verification/'.$exam->id.'/'.$user->id,
                        'local'
                    );
                    $challenge->selfie_disk = 'local';
                    $challenge->save();
                }

                $sessionToken = (string) ($data['device']['session_token'] ?? Str::random(40));
                $attempt = $this->sessions->startOrResume(
                    $exam,
                    $user,
                    [
                        'timezone' => $data['device']['timezone'] ?? $exam->timezone ?? config('app.timezone'),
                    ],
                    array_merge($data['device'] ?? [], ['session_token' => $sessionToken]),
                    $request,
                    $policyArray,
                    $sessionToken
                );

                if (
                    (! empty($policyArray['require_photo_verification']) || ! empty($policyArray['require_identity_verification']))
                    && $challenge->selfie_path
                ) {
                    $this->proctoring->attachVerificationSelfie(
                        $attempt,
                        $challenge->selfie_path,
                        $challenge->selfie_disk ?: 'local',
                        $challenge->token
                    );
                }

                $challenge->consumed_at = now();
                $challenge->save();

                return $attempt;
            });
        } catch (ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first() ?: 'Unable to start exam.',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (\App\Exceptions\AttemptQuestionShortageException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'shortages' => $e->report(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unable to prepare this exam right now. Please try again.',
                ], 500);
            }

            return back()->with('error', 'Unable to prepare this exam right now. Please try again.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            $payload = $this->sessions->toRuntimePayload($attempt);
            $startedUrl = route('frontend.exams.started', $exam);
            $runnerHtml = view('frontend.candidate.attempts.partials.runner', [
                'attempt' => $attempt,
                'exam' => $exam,
                'payload' => $payload,
                'asOverlay' => true,
            ])->render();

            return response()->json([
                'ok' => true,
                'attempt_id' => $attempt->id,
                'started_url' => $startedUrl,
                'redirect' => $startedUrl,
                'runner_html' => $runnerHtml,
            ]);
        }

        return redirect()->route('frontend.exams.started', $exam);
    }

    public function started(Request $request, Exam $exam): View|RedirectResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        abort_unless($this->eligibility->canViewPublicDetail($exam, $user), 403);

        $attempt = \App\Models\ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'in_progress'])
            ->latest('id')
            ->first();

        if (! $attempt) {
            $latest = \App\Models\ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($latest && ! $latest->isOpen()) {
                return redirect()->route('frontend.attempts.result', $latest);
            }

            return redirect()
                ->route('frontend.exams.prepare', $exam)
                ->with('error', 'No active exam session found. Complete preparation to start.');
        }

        $attempt = $this->sessions->expireIfNeeded($attempt);

        if (! $attempt->isOpen()) {
            return redirect()->route('frontend.attempts.result', $attempt);
        }

        $payload = $this->sessions->toRuntimePayload($attempt);

        return view('frontend.candidate.exams.started', [
            'attempt' => $attempt,
            'exam' => $exam,
            'payload' => $payload,
        ]);
    }

    public function purchase(Request $request, Exam $exam): RedirectResponse|JsonResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        $user->ensureCandidateMembership((int) $exam->organization_id);

        $result = $this->payments->completePlaceholderPurchase($exam, $user);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'paid',
                'reference' => $result['payment']->reference,
                'message' => 'Placeholder payment completed. You can now attempt the exam.',
            ]);
        }

        return redirect()
            ->route('frontend.exams.rules', $exam)
            ->with('success', 'Payment recorded (placeholder). You can now attempt the exam.');
    }

    protected function assertPublished(Exam $exam): void
    {
        abort_unless($exam->status === 'published', 404);
    }

    /**
     * @param  array<string, mixed>  $policy
     */
    protected function createChallenge(Exam $exam, int $userId, array $policy): ExamVerificationChallenge
    {
        ExamVerificationChallenge::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $userId)
            ->whereNull('consumed_at')
            ->delete();

        $required = [];
        if (! empty($policy['require_webcam'])) {
            $required[] = 'webcam';
        }
        if (! empty($policy['require_microphone'])) {
            $required[] = 'microphone';
        }
        if (! empty($policy['require_fullscreen'])) {
            $required[] = 'fullscreen';
        }
        if (! empty($policy['require_photo_verification']) || ! empty($policy['require_identity_verification'])) {
            $required[] = 'selfie';
        }

        return ExamVerificationChallenge::query()->create([
            'organization_id' => $exam->organization_id,
            'exam_id' => $exam->id,
            'user_id' => $userId,
            'token' => Str::random(48),
            'required_checks' => $required,
            'completed_checks' => [],
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * @param  array<string, mixed>  $policy
     * @param  array<string, mixed>  $data
     */
    protected function assertReadyToStart(array $policy, array $data, ExamVerificationChallenge $challenge, Request $request): void
    {
        $checks = $data['checks'] ?? [];
        $errors = [];

        if (! empty($policy['require_webcam']) && empty($checks['webcam'])) {
            $errors['checks.webcam'] = 'Webcam access is required before starting.';
        }
        if (! empty($policy['require_microphone']) && empty($checks['microphone'])) {
            $errors['checks.microphone'] = 'Microphone access is required before starting.';
        }
        if (! empty($policy['require_fullscreen']) && empty($checks['fullscreen'])) {
            $errors['checks.fullscreen'] = 'Fullscreen mode is required before starting.';
        }

        $needsSelfie = ! empty($policy['require_photo_verification']) || ! empty($policy['require_identity_verification']);
        if ($needsSelfie && ! $challenge->selfie_path && ! $request->hasFile('selfie')) {
            $errors['selfie'] = 'Capture a live selfie with your webcam before starting. Uploads are not allowed.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}

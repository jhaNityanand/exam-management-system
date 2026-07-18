<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamInstructionRule;
use App\Services\CandidateExam\ExamEligibilityService;
use App\Services\CandidateExam\ExamPaymentPlaceholderService;
use App\Services\CandidateExam\ExamProctoringService;
use App\Services\CandidateExam\ExamSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateExamController extends Controller
{
    public function __construct(
        protected ExamEligibilityService $eligibility,
        protected ExamSessionService $sessions,
        protected ExamPaymentPlaceholderService $payments,
        protected ExamProctoringService $proctoring,
    ) {}

    public function rules(Request $request, Exam $exam): View|RedirectResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        abort_unless($this->eligibility->canViewPublicDetail($exam, $user), 403);

        $user->ensureCandidateMembership((int) $exam->organization_id);
        $evaluation = $this->eligibility->evaluate($exam, $user);
        $exam->loadMissing(['category:id,name,slug', 'proctoringPolicy']);

        $ruleSlugs = $exam->predefined_instruction_rules ?? [];
        $rules = ExamInstructionRule::query()
            ->where('organization_id', $exam->organization_id)
            ->whereIn('slug', $ruleSlugs)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        return view('frontend.candidate.exams.rules', [
            'exam' => $exam,
            'evaluation' => $evaluation,
            'rules' => $rules,
            'policy' => $exam->proctoringPolicy,
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

        $exam->loadMissing(['proctoringPolicy', 'category:id,name,slug']);
        if (! $exam->proctoringPolicy) {
            \App\Models\ExamProctoringPolicy::query()->firstOrCreate(
                ['exam_id' => $exam->id],
                [
                    'require_webcam' => $exam->exam_mode === 'proctored',
                    'require_microphone' => $exam->exam_mode === 'proctored',
                    'require_fullscreen' => $exam->exam_mode === 'proctored'
                        || in_array('fullscreen_required', $exam->predefined_instruction_rules ?? [], true),
                    'require_photo_verification' => in_array('id_verification_required', $exam->predefined_instruction_rules ?? [], true),
                    'detect_tab_switch' => true,
                    'focus_violation_limit' => 3,
                    'focus_violation_action' => in_array('tab_switch_autosubmit', $exam->predefined_instruction_rules ?? [], true)
                        ? 'auto_submit'
                        : 'warn',
                    'auto_submit_on_violation' => in_array('tab_switch_autosubmit', $exam->predefined_instruction_rules ?? [], true),
                    'block_copy_paste' => in_array('disable_copy_paste', $exam->predefined_instruction_rules ?? [], true),
                ]
            );
            $exam->load('proctoringPolicy');
        }

        return view('frontend.candidate.exams.prepare', [
            'exam' => $exam,
            'evaluation' => $evaluation,
            'policy' => $exam->proctoringPolicy,
        ]);
    }

    public function start(Request $request, Exam $exam): RedirectResponse|JsonResponse
    {
        $this->assertPublished($exam);
        $user = $request->user();
        $user->ensureCandidateMembership((int) $exam->organization_id);

        try {
            $data = $request->validate([
                'preferences' => ['nullable', 'array'],
                'preferences.theme' => ['nullable', 'in:light,dark,system'],
                'preferences.font_size' => ['nullable', 'in:sm,md,lg'],
                'preferences.language' => ['nullable', 'string', 'max:16'],
                'preferences.palette_position' => ['nullable', 'in:left,right'],
                'preferences.timezone' => ['nullable', 'string', 'max:64'],
                'device' => ['nullable', 'array'],
                'device.browser' => ['nullable', 'string', 'max:120'],
                'device.device_type' => ['nullable', 'string', 'max:64'],
                'device.os' => ['nullable', 'string', 'max:120'],
                'device.screen_resolution' => ['nullable', 'string', 'max:32'],
                'device.timezone' => ['nullable', 'string', 'max:64'],
                'device.local_time' => ['nullable', 'string', 'max:64'],
                'photo' => ['nullable', 'image', 'max:5120'],
            ]);

            $attempt = $this->sessions->startOrResume(
                $exam,
                $user,
                $data['preferences'] ?? [],
                $data['device'] ?? [],
                $request
            );

            if ($request->hasFile('photo')) {
                $this->proctoring->storeSnapshot($attempt, $request->file('photo'), 'photo');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
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
            return response()->json([
                'ok' => true,
                'redirect' => route('frontend.attempts.show', $attempt),
                'attempt_id' => $attempt->id,
            ]);
        }

        return redirect()->route('frontend.attempts.show', $attempt);
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
}

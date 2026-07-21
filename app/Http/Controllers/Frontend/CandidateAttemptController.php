<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\CandidateExam\ExamAnswerService;
use App\Services\CandidateExam\ExamGradingService;
use App\Services\CandidateExam\ExamProctoringService;
use App\Services\CandidateExam\ExamReviewPresenter;
use App\Services\CandidateExam\ExamSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateAttemptController extends Controller
{
    public function __construct(
        protected ExamSessionService $sessions,
        protected ExamAnswerService $answers,
        protected ExamProctoringService $proctoring,
        protected ExamGradingService $grading,
        protected ExamReviewPresenter $reviewPresenter,
    ) {}

    public function show(Request $request, ExamAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $attempt = $this->sessions->expireIfNeeded($attempt);

        if (! $attempt->isOpen()) {
            return redirect()->route('frontend.attempts.result', $attempt);
        }

        $attempt->loadMissing('exam');

        if ($attempt->exam) {
            return redirect()->route('frontend.exams.started', $attempt->exam);
        }

        $payload = $this->sessions->toRuntimePayload($attempt);

        return view('frontend.candidate.attempts.show', [
            'attempt' => $attempt,
            'exam' => $attempt->exam,
            'payload' => $payload,
        ]);
    }

    public function saveAnswers(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        try {
            $data = $request->validate([
                'revision' => ['nullable', 'integer', 'min:0'],
                'answers' => ['required', 'array', 'min:1'],
                'answers.*.exam_attempt_question_id' => ['required', 'integer'],
                'answers.*.answer_value' => ['nullable'],
                'answers.*.is_marked_for_review' => ['nullable', 'boolean'],
                'answers.*.is_visited' => ['nullable', 'boolean'],
            ]);

            $result = $this->answers->saveBatch($attempt, $data['answers'], $data['revision'] ?? null);

            return response()->json([
                'ok' => true,
                'revision' => $result['revision'],
                'saved' => $result['saved'],
                'requested' => $result['requested'] ?? count($data['answers']),
                'skipped' => $result['skipped'] ?? [],
                'server_now' => now()->toIso8601String(),
                'answers' => $result['answers'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Unable to save answers.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to save answers right now. Please try again.',
            ], 500);
        }
    }

    public function heartbeat(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $attempt = $this->sessions->heartbeat($attempt);

        return response()->json([
            'ok' => true,
            'status' => $attempt->status,
            'server_now' => now()->toIso8601String(),
            'expires_at' => optional($attempt->expires_at)?->toIso8601String(),
        ]);
    }

    public function events(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        $data = $request->validate([
            'event' => ['required', 'string', 'max:64'],
            'payload' => ['nullable', 'array'],
        ]);

        $result = $this->proctoring->recordEvent($attempt, $data['event'], $data['payload'] ?? []);

        return response()->json([
            'ok' => true,
            'server_now' => now()->toIso8601String(),
            ...$result,
        ]);
    }

    public function submit(Request $request, ExamAttempt $attempt): JsonResponse|RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $attempt = $this->sessions->expireIfNeeded($attempt);

        if ($request->filled('answers') && is_array($request->input('answers'))) {
            $this->answers->saveBatch($attempt, $request->input('answers', []));
        }

        $attempt = $this->grading->submit($attempt, reason: 'manual', auto: false);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'redirect' => route('frontend.attempts.result', $attempt),
            ]);
        }

        return redirect()->route('frontend.attempts.result', $attempt);
    }

    public function result(Request $request, ExamAttempt $attempt): View
    {
        $this->authorizeAttempt($request, $attempt);
        $attempt->loadMissing(['exam.category', 'attemptAnswers', 'attemptQuestions']);

        return view('frontend.candidate.attempts.result', [
            'attempt' => $attempt,
            'exam' => $attempt->exam,
            'visible' => $this->grading->resultsVisible($attempt),
        ]);
    }

    public function review(Request $request, ExamAttempt $attempt): View
    {
        $this->authorizeAttempt($request, $attempt);
        abort_unless($this->grading->resultsVisible($attempt), 403, 'Results are not available yet.');

        $attempt->loadMissing(['exam']);

        return view('frontend.candidate.attempts.review', [
            'attempt' => $attempt,
            'exam' => $attempt->exam,
            'dataUrl' => route('frontend.attempts.review.data', $attempt),
        ]);
    }

    public function reviewData(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        abort_unless($this->grading->resultsVisible($attempt), 403, 'Results are not available yet.');

        return response()->json([
            'ok' => true,
            'result_url' => route('frontend.attempts.result', $attempt),
            'exam_url' => $attempt->exam
                ? route('frontend.exams.show', $attempt->exam)
                : route('frontend.account.results'),
            ...$this->reviewPresenter->present($attempt),
        ]);
    }

    protected function authorizeAttempt(Request $request, ExamAttempt $attempt): void
    {
        abort_unless($request->user() && (int) $attempt->user_id === (int) $request->user()->id, 403);
    }
}

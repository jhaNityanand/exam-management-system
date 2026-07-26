<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreFeedbackRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\FeedbackService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedback
    ) {}

    public function store(StoreFeedbackRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $target = $this->resolveFeedbackable($request, $data);

        $feedback = $this->feedback->storeFor($user, $target, [
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'message' => $data['message'],
            'source' => $data['source'] ?? 'web',
            'exam_attempt_id' => isset($data['exam_attempt_id']) ? (int) $data['exam_attempt_id'] : null,
            'is_public' => true,
            'status' => \App\Models\Feedback::STATUS_ACTIVE,
        ], $request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Thank you for your feedback!',
                'feedback_id' => $feedback->id,
            ]);
        }

        return back()->with('success', 'Thank you for your feedback!');
    }

    public function skip(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'exam_attempt_id' => ['required', 'integer', 'exists:exam_attempts,id'],
        ]);

        $attempt = ExamAttempt::query()->findOrFail($data['exam_attempt_id']);
        abort_unless((int) $attempt->user_id === (int) $request->user()->id, 403);

        $this->feedback->skipPrompt($request->user(), $attempt);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'skipped' => true]);
        }

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveFeedbackable(Request $request, array $data): Model
    {
        if (! empty($data['exam_id'])) {
            $exam = Exam::query()->findOrFail((int) $data['exam_id']);
            $this->assertCanFeedbackExam($request, $exam);

            return $exam;
        }

        if (! empty($data['exam_attempt_id'])) {
            $attempt = ExamAttempt::query()->with('exam')->findOrFail((int) $data['exam_attempt_id']);
            abort_unless((int) $attempt->user_id === (int) $request->user()->id, 403);
            if (! $attempt->exam) {
                throw ValidationException::withMessages([
                    'exam_attempt_id' => 'Unable to resolve the exam for this attempt.',
                ]);
            }

            return $attempt->exam;
        }

        $type = (string) ($data['feedbackable_type'] ?? '');
        $id = (int) ($data['feedbackable_id'] ?? 0);
        if ($type === Exam::class || $type === 'exam' || $type === 'App\\Models\\Exam') {
            $exam = Exam::query()->findOrFail($id);
            $this->assertCanFeedbackExam($request, $exam);

            return $exam;
        }

        throw ValidationException::withMessages([
            'feedbackable' => 'Unsupported feedback target.',
        ]);
    }

    protected function assertCanFeedbackExam(Request $request, Exam $exam): void
    {
        // Candidates may leave feedback after attempting, or while viewing a published exam they can access.
        abort_unless($exam->status === 'published', 404);

        $hasAttempt = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['submitted', 'expired', 'graded', 'abandoned'])
            ->exists();

        if (! $hasAttempt) {
            throw ValidationException::withMessages([
                'exam_id' => 'You can leave feedback after completing an attempt for this exam.',
            ]);
        }
    }
}

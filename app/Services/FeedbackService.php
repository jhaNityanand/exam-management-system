<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    /**
     * @param  array{
     *     rating:int,
     *     title?:?string,
     *     message:string,
     *     source?:?string,
     *     exam_attempt_id?:?int,
     *     is_public?:bool,
     *     status?:string
     * }  $data
     */
    public function storeFor(
        User $user,
        Model $feedbackable,
        array $data,
        ?Request $request = null
    ): Feedback {
        $rating = max(1, min(5, (int) ($data['rating'] ?? 0)));
        if ($rating < 1) {
            throw ValidationException::withMessages([
                'rating' => 'Please select a rating from 1 to 5 stars.',
            ]);
        }

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '' || mb_strlen($message) < 10) {
            throw ValidationException::withMessages([
                'message' => 'Please share at least 10 characters of feedback.',
            ]);
        }
        if (mb_strlen($message) > 2000) {
            throw ValidationException::withMessages([
                'message' => 'Feedback must be 2000 characters or fewer.',
            ]);
        }

        if ($this->userHasFeedback($user, $feedbackable)) {
            throw ValidationException::withMessages([
                'feedback' => 'You have already submitted feedback for this item.',
            ]);
        }

        $examId = null;
        $attemptId = isset($data['exam_attempt_id']) ? (int) $data['exam_attempt_id'] : null;
        $organizationId = null;

        if ($feedbackable instanceof Exam) {
            $examId = (int) $feedbackable->id;
            $organizationId = $feedbackable->organization_id;
        } elseif ($feedbackable instanceof ExamAttempt) {
            $attemptId = (int) $feedbackable->id;
            $examId = (int) $feedbackable->exam_id;
            $organizationId = $feedbackable->organization_id;
            $feedbackable = $feedbackable->exam ?: Exam::query()->findOrFail($examId);
        }

        if ($attemptId) {
            $ownsAttempt = ExamAttempt::query()
                ->where('id', $attemptId)
                ->where('user_id', $user->id)
                ->when($examId, fn ($q) => $q->where('exam_id', $examId))
                ->exists();
            if (! $ownsAttempt) {
                $attemptId = null;
            }
        }

        $status = (string) ($data['status'] ?? Feedback::STATUS_ACTIVE);
        $isPublic = array_key_exists('is_public', $data)
            ? (bool) $data['is_public']
            : ($status === Feedback::STATUS_ACTIVE);

        return DB::transaction(function () use ($user, $feedbackable, $data, $request, $rating, $message, $examId, $attemptId, $organizationId, $status, $isPublic) {
            return Feedback::query()->create([
                'organization_id' => $organizationId,
                'user_id' => $user->id,
                'exam_id' => $examId,
                'exam_attempt_id' => $attemptId,
                'feedbackable_type' => $feedbackable->getMorphClass(),
                'feedbackable_id' => $feedbackable->getKey(),
                'rating' => $rating,
                'title' => filled($data['title'] ?? null) ? mb_substr(trim((string) $data['title']), 0, 160) : null,
                'message' => $message,
                'status' => $status,
                'is_public' => $isPublic,
                'source' => $data['source'] ?? null,
                'locale' => app()->getLocale(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
            ]);
        });
    }

    public function userHasFeedback(User $user, Model $feedbackable): bool
    {
        return Feedback::query()
            ->where('user_id', $user->id)
            ->forFeedbackable($feedbackable)
            ->exists();
    }

    /**
     * @return array{
     *     average:float,
     *     total:int,
     *     breakdown:array<int,int>,
     *     items:LengthAwarePaginator
     * }
     */
    public function publicSummaryFor(Model $feedbackable, int $perPage = 8): array
    {
        $base = Feedback::query()
            ->publicVisible()
            ->forFeedbackable($feedbackable);

        $total = (clone $base)->count();
        $average = $total > 0
            ? round((float) (clone $base)->avg('rating'), 1)
            : 0.0;

        $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $rows = (clone $base)
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');
        foreach ($rows as $rating => $count) {
            $rating = (int) $rating;
            if (isset($breakdown[$rating])) {
                $breakdown[$rating] = (int) $count;
            }
        }

        $items = (clone $base)
            ->with(['user.profile'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'average' => $average,
            'total' => $total,
            'breakdown' => $breakdown,
            'items' => $items,
        ];
    }

    public function skipPrompt(User $user, ExamAttempt $attempt): void
    {
        session()->put($this->skipSessionKey($attempt), true);
    }

    public function shouldPromptAfterAttempt(User $user, ExamAttempt $attempt): bool
    {
        if (session()->get($this->skipSessionKey($attempt))) {
            return false;
        }

        $exam = $attempt->exam;
        if (! $exam) {
            return false;
        }

        return ! $this->userHasFeedback($user, $exam);
    }

    protected function skipSessionKey(ExamAttempt $attempt): string
    {
        return 'feedback_skipped_attempt_'.$attempt->id;
    }
}

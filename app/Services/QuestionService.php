<?php

namespace App\Services;

use App\Models\Question;
use App\Support\UniqueOrgSlug;
use Illuminate\Support\Facades\Auth;

class QuestionService
{
    public function __construct(protected GalleryService $gallery) {}

    public function create(array $data, ?int $actorId = null): Question
    {
        $data['created_by'] = $actorId ?? Auth::id();
        $data['updated_by'] = $actorId ?? Auth::id();
        $this->normalizeAnswers($data);
        $this->normalizeMarks($data);
        $data = $this->gallery->sanitizeHtmlFields($data, [
            'body',
            'explanation',
            'correct_answer',
            'options',
            'correct_answers',
        ]);
        $this->applyUniqueSlug($data, (int) $data['organization_id'], null, (string) ($data['body'] ?? ''));

        $question = Question::create($data);
        $this->syncGalleryMedia($question);

        return $question;
    }

    public function update(Question $question, array $data): Question
    {
        $this->normalizeAnswers($data);
        $this->normalizeMarks($data);
        $data = $this->gallery->sanitizeHtmlFields($data, [
            'body',
            'explanation',
            'correct_answer',
            'options',
            'correct_answers',
        ]);

        if (array_key_exists('slug', $data) || array_key_exists('body', $data) || empty($question->slug)) {
            $this->applyUniqueSlug(
                $data,
                (int) $question->organization_id,
                (int) $question->id,
                (string) ($data['body'] ?? $question->body),
            );
        }

        $question->update($data);
        $question = $question->fresh();
        $this->syncGalleryMedia($question);

        return $question;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyUniqueSlug(array &$data, int $orgId, ?int $ignoreId, string $fallback): void
    {
        $source = trim((string) ($data['slug'] ?? ''));
        if ($source === '') {
            $source = $fallback;
        }

        $data['slug'] = UniqueOrgSlug::forModel(Question::class, $source, $orgId, $ignoreId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function normalizeMarks(array &$data): void
    {
        if (($data['marks_type'] ?? 'single') === 'multiple') {
            $marksList = array_map('intval', array_filter($data['marks_list'] ?? []));
            $data['marks_list'] = array_values(array_unique($marksList));
            $data['marks'] = ! empty($data['marks_list']) ? $data['marks_list'][0] : 1;
        } else {
            $data['marks_list'] = null;
            $data['marks'] = isset($data['marks']) ? (int) $data['marks'] : 1;
        }
    }

    public function delete(Question $question): bool
    {
        $this->gallery->purgeForModel($question);

        return (bool) $question->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function normalizeAnswers(array &$data): void
    {
        if (! empty($data['allows_multiple'])) {
            $data['correct_answers'] = array_values(array_filter($data['correct_answers'] ?? []));
            $data['correct_answer'] = (string) ($data['correct_answers'][0] ?? '');
        } else {
            $data['correct_answers'] = null;
        }
    }

    protected function syncGalleryMedia(Question $question): void
    {
        $this->gallery->syncForModel($question, [
            $question->body,
            $question->explanation,
            $question->correct_answer,
            $question->options,
            is_array($question->correct_answers) ? $question->correct_answers : null,
        ], (int) $question->organization_id);
    }
}

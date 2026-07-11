<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Auth;

class QuestionService
{
    public function create(array $data): Question
    {
        $data['created_by'] = Auth::id();
        $this->normalizeAnswers($data);
        $this->normalizeMarks($data);

        return Question::create($data);
    }

    public function update(Question $question, array $data): Question
    {
        $this->normalizeAnswers($data);
        $this->normalizeMarks($data);
        $question->update($data);

        return $question->fresh();
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
}

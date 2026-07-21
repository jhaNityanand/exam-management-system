<?php

namespace App\Services\CandidateExam;

use App\Models\ExamAttempt;

class ExamReviewPresenter
{
    /**
     * @return array{summary: array<string, mixed>, questions: list<array<string, mixed>>}
     */
    public function present(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['exam', 'attemptAnswers', 'attemptQuestions']);
        $answers = $attempt->attemptAnswers->keyBy('exam_attempt_question_id');
        $config = $attempt->exam_config_snapshot ?: [];
        $exam = $attempt->exam;

        $questions = $attempt->attemptQuestions
            ->sortBy('position')
            ->values()
            ->map(function ($question) use ($answers) {
                $answer = $answers->get($question->id);
                $snapshot = $question->question_snapshot ?? [];
                $type = (string) ($snapshot['type'] ?? 'mcq');
                $options = $this->normalizeOptions($snapshot['options'] ?? [], $question->option_order ?? []);
                if ($options === [] && $type === 'true_false') {
                    $options = [
                        ['key' => 'True', 'letter' => 'A', 'text' => 'True'],
                        ['key' => 'False', 'letter' => 'B', 'text' => 'False'],
                    ];
                }
                $candidateRaw = $answer?->answer_value;
                $correctRaw = $snapshot['correct_answers'] ?? $snapshot['correct_answer'] ?? null;
                $answered = (bool) ($answer?->is_answered);
                $isCorrect = $answer?->is_correct;
                $status = $this->statusFor($answered, $isCorrect, $answer?->grading_status);

                return [
                    'id' => $question->id,
                    'position' => (int) $question->position,
                    'marks' => (float) ($question->marks ?: ($snapshot['marks'] ?? 0)),
                    'awarded_marks' => (float) ($answer?->awarded_marks ?? 0),
                    'is_correct' => $isCorrect,
                    'is_answered' => $answered,
                    'status' => $status,
                    'type' => $type,
                    'allows_multiple' => (bool) ($snapshot['allows_multiple'] ?? false) || $type === 'multi_select',
                    'body' => (string) ($snapshot['body'] ?? ''),
                    'explanation' => filled($snapshot['explanation'] ?? null) ? (string) $snapshot['explanation'] : null,
                    'options' => $options,
                    'candidate_keys' => $this->normalizeKeys($candidateRaw),
                    'correct_keys' => $this->resolveCorrectKeys($correctRaw, $options),
                    'candidate_labels' => $this->labelsFor($candidateRaw, $options),
                    'correct_labels' => $this->labelsFor($correctRaw, $options),
                ];
            })
            ->all();

        $totalQuestions = count($questions);
        $correct = (int) ($attempt->correct_count ?? collect($questions)->where('status', 'correct')->count());
        $incorrect = (int) ($attempt->wrong_count ?? collect($questions)->where('status', 'incorrect')->count());
        $unanswered = (int) ($attempt->unanswered_count ?? collect($questions)->where('status', 'unanswered')->count());
        $attempted = max(0, $totalQuestions - $unanswered);
        $totalMarks = (float) ($config['total_marks'] ?? $exam?->total_marks ?? 0);
        $passingMarks = (float) ($config['passing_marks'] ?? $exam?->passing_marks ?? 0);
        $score = (float) ($attempt->score ?? 0);
        $percentage = (float) ($attempt->percentage ?? 0);

        return [
            'summary' => [
                'exam_title' => (string) ($exam?->title ?? 'Exam'),
                'total_questions' => $totalQuestions,
                'attempted' => $attempted,
                'correct' => $correct,
                'incorrect' => $incorrect,
                'unanswered' => $unanswered,
                'score' => $score,
                'total_marks' => $totalMarks,
                'passing_marks' => $passingMarks,
                'percentage' => $percentage,
                'passed' => (bool) $attempt->passed,
                'status_label' => $attempt->passed ? 'Pass' : 'Fail',
                'time_spent_seconds' => (int) ($attempt->time_spent_seconds ?? 0),
                'submission_reason' => (string) ($attempt->submission_reason ?: $attempt->status),
            ],
            'questions' => $questions,
        ];
    }

    /**
     * @param  mixed  $options
     * @param  mixed  $optionOrder
     * @return list<array{key:string, letter:string, text:string}>
     */
    protected function normalizeOptions(mixed $options, mixed $optionOrder): array
    {
        $options = is_array($options) ? $options : [];
        $isList = array_is_list($options);
        $order = is_array($optionOrder) && count($optionOrder)
            ? array_values($optionOrder)
            : ($isList ? array_keys($options) : array_keys($options));

        $normalized = [];
        foreach ($order as $index => $key) {
            $key = (string) $key;
            $raw = $isList
                ? ($options[(int) $key] ?? $options[$key] ?? null)
                : ($options[$key] ?? null);

            $text = $this->optionText($raw);
            if ($text === '' && $raw === null) {
                continue;
            }

            $normalized[] = [
                'key' => $key,
                'letter' => chr(65 + ($index % 26)),
                'text' => $text !== '' ? $text : $key,
            ];
        }

        return $normalized;
    }

    protected function optionText(mixed $raw): string
    {
        if ($raw === null) {
            return '';
        }
        if (is_string($raw) || is_numeric($raw)) {
            return trim((string) $raw);
        }
        if (is_array($raw)) {
            return trim((string) ($raw['text'] ?? $raw['label'] ?? $raw['value'] ?? $raw['option'] ?? ''));
        }

        return '';
    }

    /**
     * @return list<string>
     */
    protected function normalizeKeys(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            if (array_key_exists('text', $value) || array_key_exists('value', $value)) {
                $scalar = $value['value'] ?? $value['text'] ?? null;

                return $scalar === null || $scalar === '' ? [] : [(string) $scalar];
            }

            return array_values(array_map('strval', array_filter($value, fn ($v) => $v !== null && $v !== '')));
        }

        return [(string) $value];
    }

    /**
     * @param  list<array{key:string, letter:string, text:string}>  $options
     * @return list<string>
     */
    protected function resolveCorrectKeys(mixed $correctRaw, array $options): array
    {
        $rawKeys = $this->normalizeKeys($correctRaw);
        if ($rawKeys === []) {
            return [];
        }

        $byKey = [];
        $byText = [];
        foreach ($options as $option) {
            $byKey[strtolower($option['key'])] = $option['key'];
            $byText[strtolower($option['text'])] = $option['key'];
        }

        $resolved = [];
        foreach ($rawKeys as $raw) {
            $lower = strtolower($raw);
            if (isset($byKey[$lower])) {
                $resolved[] = $byKey[$lower];
            } elseif (isset($byText[$lower])) {
                $resolved[] = $byText[$lower];
            } else {
                $resolved[] = $raw;
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * @param  list<array{key:string, letter:string, text:string}>  $options
     * @return list<string>
     */
    protected function labelsFor(mixed $value, array $options): array
    {
        $keys = $this->normalizeKeys($value);
        if ($keys === []) {
            return [];
        }

        $map = [];
        foreach ($options as $option) {
            $map[strtolower($option['key'])] = $option['letter'].'. '.$option['text'];
            $map[strtolower($option['text'])] = $option['letter'].'. '.$option['text'];
        }

        return array_map(function (string $key) use ($map) {
            return $map[strtolower($key)] ?? $key;
        }, $keys);
    }

    protected function statusFor(bool $answered, mixed $isCorrect, mixed $gradingStatus): string
    {
        if ($gradingStatus === 'manual') {
            return $answered ? 'pending' : 'unanswered';
        }
        if (! $answered) {
            return 'unanswered';
        }
        if ($isCorrect === true) {
            return 'correct';
        }
        if ($isCorrect === false) {
            return 'incorrect';
        }

        return 'pending';
    }
}

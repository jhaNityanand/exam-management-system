<?php

namespace App\Support;

/**
 * Single source of truth for exam formats and related question types.
 * Used by Exam and Question modules (validation, Blade, JS config).
 */
class ExamFormats
{
    /**
     * Exam formats shown on exam create/edit and used for question-bank filtering.
     *
     * @return list<array{id: string, label: string, description: string}>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 'mcq',
                'label' => 'MCQ',
                'description' => 'Single-correct objective questions.',
            ],
            [
                'id' => 'multi_select',
                'label' => 'Multi Select',
                'description' => 'MCQ with multiple correct choices.',
            ],
            [
                'id' => 'true_false',
                'label' => 'True / False',
                'description' => 'Binary true or false questions.',
            ],
            [
                'id' => 'written',
                'label' => 'Written',
                'description' => 'Short and long descriptive answers.',
            ],
            [
                'id' => 'fill_blank',
                'label' => 'Fill in the Blanks',
                'description' => 'Complete the missing words or phrases.',
            ],
        ];
    }

    /** @return list<string> */
    public static function ids(): array
    {
        return collect(self::all())->pluck('id')->all();
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::all())->mapWithKeys(fn (array $o) => [$o['id'] => $o['label']])->all();
    }

    /**
     * Question module types (bank storage values).
     *
     * @return list<array{id: string, label: string, description: string, badge_class: string}>
     */
    public static function questionTypes(): array
    {
        return [
            [
                'id' => 'mcq',
                'label' => 'Multiple Choice',
                'description' => 'Objective questions with one or more options.',
                'badge_class' => 'question-type-mcq',
            ],
            [
                'id' => 'true_false',
                'label' => 'True / False',
                'description' => 'Binary true or false questions.',
                'badge_class' => 'question-type-true-false',
            ],
            [
                'id' => 'short_answer',
                'label' => 'Short Answer',
                'description' => 'Brief written responses.',
                'badge_class' => 'question-type-short-answer',
            ],
            [
                'id' => 'long_answer',
                'label' => 'Long Answer',
                'description' => 'Extended descriptive answers.',
                'badge_class' => 'question-type-long-answer',
            ],
            [
                'id' => 'fill_blank',
                'label' => 'Fill in the Blanks',
                'description' => 'Complete missing words or phrases.',
                'badge_class' => 'question-type-fill-blank',
            ],
        ];
    }

    /** @return list<string> */
    public static function questionTypeIds(): array
    {
        return collect(self::questionTypes())->pluck('id')->all();
    }

    /** @return array<string, string> */
    public static function questionTypeLabels(): array
    {
        return collect(self::questionTypes())->mapWithKeys(fn (array $o) => [$o['id'] => $o['label']])->all();
    }

    /** @return array<string, string> */
    public static function questionTypeBadgeClasses(): array
    {
        return collect(self::questionTypes())->mapWithKeys(
            fn (array $o) => [$o['id'] => $o['badge_class']]
        )->all();
    }

    /**
     * Map exam format IDs to question-bank query constraints.
     *
     * @return array<string, list<array{type: string, allows_multiple: bool|null}>>
     */
    public static function questionConstraints(): array
    {
        return [
            'mcq' => [
                ['type' => 'mcq', 'allows_multiple' => false],
            ],
            'multi_select' => [
                ['type' => 'mcq', 'allows_multiple' => true],
            ],
            'true_false' => [
                ['type' => 'true_false', 'allows_multiple' => null],
            ],
            'written' => [
                ['type' => 'short_answer', 'allows_multiple' => null],
                ['type' => 'long_answer', 'allows_multiple' => null],
            ],
            'fill_blank' => [
                ['type' => 'fill_blank', 'allows_multiple' => null],
            ],
        ];
    }
}

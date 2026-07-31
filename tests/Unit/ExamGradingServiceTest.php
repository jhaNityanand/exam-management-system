<?php

use App\Services\CandidateExam\ExamGradingService;

function gradeWith(array $snapshot, mixed $answer): array
{
    $service = new ExamGradingService;
    $method = new ReflectionMethod(ExamGradingService::class, 'gradeQuestion');
    $method->setAccessible(true);

    return $method->invoke($service, $snapshot, $answer);
}

it('marks mcq correct when candidate sends option index and bank stores option text', function () {
    $snapshot = [
        'type' => 'mcq',
        'allows_multiple' => false,
        'options' => [
            ['text' => 'Not Found', 'image_path' => null],
            ['text' => 'Unauthorized', 'image_path' => null],
            ['text' => 'Server Error', 'image_path' => null],
            ['text' => 'Redirect', 'image_path' => null],
        ],
        'correct_answer' => 'Not Found',
        'correct_answers' => null,
        'marks' => 1,
    ];

    $result = gradeWith($snapshot, '0');

    expect($result)->toMatchArray(['gradable' => true, 'correct' => true]);
});

it('marks mcq correct when candidate sends option text matching bank', function () {
    $snapshot = [
        'type' => 'mcq',
        'options' => [
            ['text' => 'Not Found'],
            ['text' => 'Unauthorized'],
        ],
        'correct_answer' => 'Not Found',
    ];

    expect(gradeWith($snapshot, 'Not Found')['correct'])->toBeTrue();
});

it('marks keyed mcq correct for letter keys used by the runner', function () {
    $snapshot = [
        'type' => 'mcq',
        'options' => ['A' => '3', 'B' => '4', 'C' => '5'],
        'correct_answer' => 'B',
        'correct_answers' => ['B'],
    ];

    expect(gradeWith($snapshot, 'B')['correct'])->toBeTrue()
        ->and(gradeWith($snapshot, '4')['correct'])->toBeTrue()
        ->and(gradeWith($snapshot, 'A')['correct'])->toBeFalse();
});

it('marks multi-select correct when keys and texts are mixed', function () {
    $snapshot = [
        'type' => 'multi_select',
        'allows_multiple' => true,
        'options' => [
            ['text' => 'React'],
            ['text' => 'Vue'],
            ['text' => 'PHP'],
        ],
        'correct_answers' => ['React', 'Vue'],
    ];

    expect(gradeWith($snapshot, ['0', '1'])['correct'])->toBeTrue()
        ->and(gradeWith($snapshot, ['0'])['correct'])->toBeFalse();
});

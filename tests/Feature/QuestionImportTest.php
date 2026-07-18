<?php

use App\Models\ImportQuestion;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\QuestionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Import Test Org',
        'slug' => 'import-test-org-'.$this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
});

function beginTrackedQuestionImport($test, int $totalRows): int
{
    $response = $test->actingAs($test->user)
        ->post(route('admin.questions.imports.start'), [
            'file' => UploadedFile::fake()->create(
                'questions.xlsx',
                12,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ),
            'total_rows' => $totalRows,
            'failed_rows' => 0,
            'initial_errors_json' => '[]',
        ])
        ->assertCreated();

    return (int) $response->json('import_question_id');
}

test('question import requires authentication', function () {
    $this->postJson(route('admin.questions.import'), ['rows' => []])
        ->assertUnauthorized();
});

test('question import creates supported types and nested categories', function () {
    $rows = [
        [
            '_row' => 2,
            'question' => 'Which language powers Laravel?',
            'type' => 'mcq',
            'category' => 'Development > PHP > Laravel',
            'difficulty' => 'easy',
            'marks_type' => 'single',
            'marks' => '1',
            'option_a' => 'PHP',
            'option_b' => 'Python',
            'correct_answer' => 'A',
            'correct_answers' => '',
            'status' => 'active',
        ],
        [
            '_row' => 3,
            'question' => 'Select JavaScript libraries.',
            'type' => 'mcq',
            'category' => 'Development > JavaScript',
            'difficulty' => 'medium',
            'marks_type' => 'single',
            'marks' => '2',
            'option_a' => 'React',
            'option_b' => 'Vue',
            'option_c' => 'Laravel',
            'correct_answer' => '',
            'correct_answers' => 'A,B',
            'status' => 'active',
        ],
        [
            '_row' => 4,
            'question' => 'HTTP is stateless.',
            'type' => 'true_false',
            'category' => 'Web Fundamentals',
            'difficulty' => 'easy',
            'marks_type' => 'single',
            'marks' => '1',
            'correct_answer' => 'True',
            'status' => 'active',
        ],
        [
            '_row' => 5,
            'question' => 'The Laravel CLI is called ____.',
            'type' => 'fill_blank',
            'category' => 'Development > PHP > Laravel',
            'difficulty' => 'easy',
            'marks_type' => 'single',
            'marks' => '1',
            'correct_answer' => 'Artisan',
            'status' => 'active',
        ],
        [
            '_row' => 6,
            'question' => 'Explain dependency injection.',
            'type' => 'long_answer',
            'category' => 'Software Engineering > Architecture',
            'difficulty' => 'hard',
            'marks_type' => 'single',
            'marks' => '5',
            'correct_answer' => 'A descriptive answer.',
            'status' => 'active',
        ],
    ];

    $importId = beginTrackedQuestionImport($this, count($rows));

    $this->actingAs($this->user)
        ->postJson(route('admin.questions.import'), [
            'import_question_id' => $importId,
            'rows' => $rows,
        ])
        ->assertCreated()
        ->assertJsonPath('imported', 5)
        ->assertJsonPath('failed', 0);

    expect(Question::query()->forOrg($this->organization->id)->count())->toBe(5);
    expect(Question::query()->where('import_question_id', $importId)->count())->toBe(5);

    $this->patchJson(route('admin.questions.imports.complete', $importId))
        ->assertOk()
        ->assertJsonPath('import.status', 'completed');

    $import = ImportQuestion::findOrFail($importId);
    expect($import->successful_rows)->toBe(5)
        ->and($import->failed_rows)->toBe(0)
        ->and(Storage::disk('local')->exists($import->file_path))->toBeTrue();

    $this->getJson(route('admin.questions.imports.show', $importId))
        ->assertOk()
        ->assertJsonPath('import.original_file_name', 'questions.xlsx')
        ->assertJsonPath('import.successful_rows', 5)
        ->assertJsonPath('import.created_by', $this->user->name);

    $this->get(route('admin.questions.imports.download', $importId))
        ->assertOk()
        ->assertDownload('questions.xlsx');

    $multiple = Question::query()->where('body', 'Select JavaScript libraries.')->firstOrFail();
    expect($multiple->allows_multiple)->toBeTrue()
        ->and($multiple->correct_answers)->toBe(['React', 'Vue']);

    $laravel = QuestionCategory::query()
        ->forOrg($this->organization->id)
        ->where('name', 'Laravel')
        ->firstOrFail();
    expect($laravel->parent?->name)->toBe('PHP');
});

test('question import reports invalid rows without importing them', function () {
    $importId = beginTrackedQuestionImport($this, 1);

    $this->actingAs($this->user)
        ->postJson(route('admin.questions.import'), [
            'import_question_id' => $importId,
            'rows' => [[
                '_row' => 2,
                'question' => 'Broken MCQ',
                'type' => 'mcq',
                'category' => 'Testing',
                'difficulty' => 'easy',
                'marks_type' => 'single',
                'marks' => '1',
                'option_a' => 'Only one option',
                'correct_answer' => 'B',
                'status' => 'active',
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('imported', 0)
        ->assertJsonPath('failed', 1)
        ->assertJsonPath('results.0.status', 'failed');

    expect(Question::query()->forOrg($this->organization->id)->count())->toBe(0);
});

test('question import is scoped to the authenticated users organization', function () {
    $other = Organization::create([
        'name' => 'Other Org',
        'slug' => 'other-import-org',
        'status' => 'active',
    ]);
    QuestionCategory::create([
        'organization_id' => $other->id,
        'name' => 'Shared Name',
        'slug' => 'shared-name',
        'status' => 'active',
    ]);

    $importId = beginTrackedQuestionImport($this, 1);

    $this->actingAs($this->user)
        ->postJson(route('admin.questions.import'), [
            'import_question_id' => $importId,
            'rows' => [[
                '_row' => 2,
                'question' => 'Organization-scoped question',
                'type' => 'short_answer',
                'category' => 'Shared Name',
                'difficulty' => 'medium',
                'marks_type' => 'single',
                'marks' => '2',
                'correct_answer' => 'Answer',
                'status' => 'active',
            ]],
        ])
        ->assertCreated();

    expect(QuestionCategory::query()->forOrg($this->organization->id)->where('name', 'Shared Name')->exists())->toBeTrue();
});

test('question list can filter imported and manually created questions', function () {
    $importId = beginTrackedQuestionImport($this, 1);
    $service = app(QuestionService::class);
    $base = [
        'organization_id' => $this->organization->id,
        'type' => 'short_answer',
        'difficulty' => 'medium',
        'marks_type' => 'single',
        'marks' => 1,
        'correct_answer' => 'Answer',
        'status' => 'active',
    ];

    $imported = $service->create([
        ...$base,
        'body' => 'Imported source question',
        'import_question_id' => $importId,
    ], $this->user->id);
    $manual = $service->create([
        ...$base,
        'body' => 'Manual source question',
    ], $this->user->id);

    $this->actingAs($this->user)
        ->getJson(route('admin.internal-api.questions-table', [
            'filters' => ['import_source' => 'imported'],
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $imported->id);

    $this->getJson(route('admin.internal-api.questions-table', [
        'filters' => ['import_source' => 'manual'],
    ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $manual->id);
});

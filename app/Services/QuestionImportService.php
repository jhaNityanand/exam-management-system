<?php

namespace App\Services;

use App\Models\ImportQuestion;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Support\ExamFormats;
use App\Support\UniqueOrgSlug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class QuestionImportService
{
    /** Defaults mirror the Question Create page. */
    public const DEFAULT_DIFFICULTY = 'medium';

    public const DEFAULT_MARKS_TYPE = 'single';

    public const DEFAULT_MARKS = 1;

    public const DEFAULT_STATUS = 'active';

    /** @var array<string, int> */
    protected array $categoryCache = [];

    /** @var array<string, true> */
    protected array $seenBodies = [];

    public function __construct(protected QuestionService $questions)
    {
    }

    /**
     * @param  list<array{row: int, errors: list<string>}>  $initialErrors
     */
    public function start(
        UploadedFile $file,
        int $totalRows,
        int $failedRows,
        array $initialErrors,
        int $orgId,
        ?int $actorId,
    ): ImportQuestion {
        $extension = strtolower($file->getClientOriginalExtension());
        $disk = 'local';
        $directory = sprintf('question-imports/%d/%s', $orgId, now()->format('Y/m'));
        $storedName = Str::uuid() . '_' . (Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'questions') . '.' . $extension;
        $path = $file->storeAs($directory, $storedName, $disk);

        if (!is_string($path) || !Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('The import file could not be stored.');
        }

        try {
            return ImportQuestion::create([
                'organization_id' => $orgId,
                'original_file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => '.' . $extension,
                'mime_type' => $file->getMimeType(),
                'disk' => $disk,
                'file_size' => (int) ($file->getSize() ?: 0),
                'status' => 'processing',
                'total_rows' => $totalRows,
                'successful_rows' => 0,
                'failed_rows' => $failedRows,
                'import_logs' => [
                    [
                        'event' => 'started',
                        'message' => 'Import file uploaded and validated in the browser.',
                        'at' => now()->toIso8601String(),
                    ]
                ],
                'errors' => $initialErrors ?: null,
                'created_by' => $actorId,
                'imported_at' => now(),
            ]);
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }

    /**
     * Import one HTTP-sized chunk. Rows are isolated so one invalid row does
     * not roll back valid questions in the same request.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{imported: int, failed: int, results: list<array<string, mixed>>}
     */
    public function importChunk(
        array $rows,
        ImportQuestion $import,
        int $orgId,
        ?int $actorId,
    ): array {
        $results = [];
        $imported = 0;

        foreach ($rows as $row) {
            $sourceRow = (int) ($row['_row'] ?? 0);

            try {
                $question = DB::transaction(function () use ($row, $import, $orgId, $actorId) {
                    $payload = $this->normalizeRow($row, $orgId, $actorId);
                    $this->assertNotDuplicate($payload['body'], $orgId);

                    $validator = Validator::make($payload, $this->rules());

                    $validator->after(function ($validator) use ($payload) {
                        $this->validateSemantics($validator, $payload);
                    });

                    $validated = $validator->validate();
                    $validated['organization_id'] = $orgId;
                    $validated['import_question_id'] = $import->id;
                    $validated['ai_generated'] = true;
                    $validated['is_ai_generated'] = false;

                    $created = $this->questions->create($validated, $actorId);
                    $this->rememberBody($payload['body']);

                    return $created;
                }, 3);

                $imported++;
                $results[] = [
                    'row' => $sourceRow,
                    'status' => 'imported',
                    'question_id' => $question->id,
                ];
            } catch (ValidationException $e) {
                $results[] = [
                    'row' => $sourceRow,
                    'status' => 'failed',
                    'errors' => collect($e->errors())->flatten()->values()->all(),
                ];
            } catch (Throwable $e) {
                report($e);
                $results[] = [
                    'row' => $sourceRow,
                    'status' => 'failed',
                    'errors' => ['The row could not be imported. Check its values and try again.'],
                ];
            }
        }

        $summary = [
            'imported' => $imported,
            'failed' => count($rows) - $imported,
            'results' => $results,
        ];

        $this->recordChunk($import, $summary);

        return $summary;
    }

    /**
     * @param  list<array{row: int, errors: list<string>}>  $unrecordedErrors
     */
    public function complete(ImportQuestion $import, array $unrecordedErrors = []): ImportQuestion
    {
        $import->refresh();
        $errors = array_merge($import->errors ?? [], $unrecordedErrors);
        $failedRows = $import->failed_rows + count($unrecordedErrors);
        $status = match (true) {
            $import->successful_rows === 0 && $failedRows > 0 => 'failed',
            $failedRows > 0 => 'completed_with_errors',
            default => 'completed',
        };

        $logs = $import->import_logs ?? [];
        $logs[] = [
            'event' => 'completed',
            'message' => sprintf(
                'Import completed with %d successful and %d failed rows.',
                $import->successful_rows,
                $failedRows,
            ),
            'at' => now()->toIso8601String(),
        ];

        $import->update([
            'status' => $status,
            'failed_rows' => $failedRows,
            'import_logs' => $logs,
            'errors' => $errors ?: null,
            'completed_at' => now(),
        ]);

        return $import->fresh(['creator:id,name']);
    }

    /**
     * @param  array{imported: int, failed: int, results: list<array<string, mixed>>}  $summary
     */
    protected function recordChunk(ImportQuestion $import, array $summary): void
    {
        $import->refresh();
        $logs = $import->import_logs ?? [];
        $logs[] = [
            'event' => 'chunk_processed',
            'message' => sprintf(
                'Processed %d rows: %d imported, %d failed.',
                $summary['imported'] + $summary['failed'],
                $summary['imported'],
                $summary['failed'],
            ),
            'at' => now()->toIso8601String(),
        ];

        $errors = $import->errors ?? [];
        foreach ($summary['results'] as $result) {
            if (($result['status'] ?? null) === 'failed') {
                $errors[] = [
                    'row' => (int) ($result['row'] ?? 0),
                    'errors' => array_values($result['errors'] ?? []),
                ];
            }
        }

        $import->update([
            'successful_rows' => $import->successful_rows + $summary['imported'],
            'failed_rows' => $import->failed_rows + $summary['failed'],
            'import_logs' => $logs,
            'errors' => $errors ?: null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row, int $orgId, ?int $actorId): array
    {
        $type = $this->normalizeType((string) ($row['type'] ?? $row['question_type'] ?? ''));
        $difficulty = strtolower(trim((string) ($row['difficulty'] ?? '')));
        if (!in_array($difficulty, ['easy', 'medium', 'hard', 'very_hard'], true)) {
            $difficulty = self::DEFAULT_DIFFICULTY;
        }

        $status = strtolower(trim((string) ($row['status'] ?? '')));
        if (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
            $status = self::DEFAULT_STATUS;
        }

        $marksRaw = trim((string) ($row['marks'] ?? ''));
        $marksList = $this->integerList($marksRaw);
        $marksType = strtolower(trim((string) ($row['marks_type'] ?? '')));
        if (!in_array($marksType, ['single', 'multiple'], true)) {
            $marksType = count($marksList) > 1 ? 'multiple' : self::DEFAULT_MARKS_TYPE;
        }

        if ($marksList === []) {
            $marksList = [self::DEFAULT_MARKS];
        }

        $options = [];
        foreach (range('a', 'f') as $letter) {
            $text = trim((string) ($row['option_' . $letter] ?? ''));
            if ($text !== '') {
                $options[strtoupper($letter)] = $text;
            }
        }

        [$correctAnswer, $correctAnswers, $allowsMultiple] = $this->resolveCorrectAnswers($row, $type, $options);

        if ($type === 'true_false' && $correctAnswer !== null) {
            $correctAnswer = ucfirst(strtolower($correctAnswer));
        }

        return [
            'category_id' => $this->resolveCategoryPath(
                trim((string) ($row['category'] ?? '')),
                $orgId,
                $actorId,
            ),
            'body' => trim((string) ($row['question'] ?? '')),
            'type' => $type,
            'difficulty' => $difficulty,
            'marks_type' => $marksType,
            'marks' => $marksType === 'single' ? ($marksList[0] ?? self::DEFAULT_MARKS) : null,
            'marks_list' => $marksType === 'multiple' ? $marksList : null,
            'allows_multiple' => $allowsMultiple,
            'options' => $type === 'mcq'
                ? array_map(static fn(string $text) => ['text' => $text], array_values($options))
                : null,
            'correct_answer' => $correctAnswer,
            'correct_answers' => $correctAnswers,
            'explanation' => $this->nullableString($row['explanation'] ?? null),
            'reference' => $this->nullableString($row['reference'] ?? null),
            'status' => $status,
            'ai_generated' => true,
            'ai_improve' => false,
            'is_ai_generated' => true,
            'is_sitemap_url_created' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $options
     * @return array{0: ?string, 1: ?list<string>, 2: bool}
     */
    protected function resolveCorrectAnswers(array $row, string $type, array $options): array
    {
        $combined = trim((string) (
            $row['correct_options']
            ?? $row['correct_option']
            ?? ''
        ));
        $legacyMulti = trim((string) ($row['correct_answers'] ?? ''));
        $legacySingle = trim((string) ($row['correct_answer'] ?? ''));

        if ($type === 'mcq') {
            $raw = $combined !== '' ? $combined : ($legacyMulti !== '' ? $legacyMulti : $legacySingle);
            $labels = collect(preg_split('/[\s,;|]+/', strtoupper($raw)) ?: [])
                ->filter()
                ->unique()
                ->values()
                ->all();

            $allowsMultiple = count($labels) > 1 || $legacyMulti !== '';

            if ($allowsMultiple) {
                return [null, $this->answerLabels(implode(',', $labels), $options), true];
            }

            $label = $labels[0] ?? '';

            return [$label !== '' ? $this->answerValue($label, $options) : null, null, false];
        }

        $answer = $combined !== '' ? $combined : $legacySingle;

        return [$answer !== '' ? $answer : null, null, false];
    }

    protected function normalizeType(string $type): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', '_', trim($type))));
    }

    protected function assertNotDuplicate(string $body, int $orgId): void
    {
        $key = mb_strtolower(trim($body));
        if ($key === '') {
            return;
        }

        if (isset($this->seenBodies[$key])) {
            throw ValidationException::withMessages([
                'body' => ['Duplicate question in this import file. Each question text must be unique.'],
            ]);
        }

        $exists = Question::query()
            ->forOrg($orgId)
            ->whereRaw('LOWER(TRIM(body)) = ?', [$key])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'body' => ['A question with this text already exists in your question bank.'],
            ]);
        }
    }

    protected function rememberBody(string $body): void
    {
        $key = mb_strtolower(trim($body));
        if ($key !== '') {
            $this->seenBodies[$key] = true;
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer'],
            'body' => ['required', 'string', 'max:20000'],
            'type' => ['required', Rule::in(ExamFormats::questionTypeIds())],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard', 'very_hard'])],
            'marks_type' => ['required', Rule::in(['single', 'multiple'])],
            'marks' => ['required_if:marks_type,single', 'nullable', 'integer', 'min:1', 'max:10'],
            'marks_list' => ['required_if:marks_type,multiple', 'nullable', 'array', 'min:1'],
            'marks_list.*' => ['integer', 'min:1', 'max:10'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'allows_multiple' => ['required', 'boolean'],
            'options' => ['required_if:type,mcq', 'nullable', 'array', 'min:2'],
            'options.*.text' => ['required', 'string', 'max:2000'],
            'correct_answer' => ['required_without:correct_answers', 'nullable', 'string', 'max:2000'],
            'correct_answers' => ['nullable', 'array', 'min:1'],
            'correct_answers.*' => ['string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:20000'],
            'ai_generated' => ['nullable', 'boolean'],
            'is_ai_generated' => ['nullable', 'boolean'],
        ];
    }

    protected function validateSemantics($validator, array $payload): void
    {
        $type = $payload['type'] ?? '';

        if ($type === 'true_false' && !in_array($payload['correct_answer'] ?? '', ['True', 'False'], true)) {
            $validator->errors()->add('correct_answer', 'True/False answers must be True or False.');
        }

        if ($type === 'mcq') {
            $options = collect($payload['options'] ?? [])->pluck('text')->all();
            $answers = !empty($payload['allows_multiple'])
                ? ($payload['correct_answers'] ?? [])
                : array_filter([$payload['correct_answer'] ?? null]);

            foreach ($answers as $answer) {
                if (!in_array($answer, $options, true)) {
                    $validator->errors()->add(
                        'correct_answer',
                        'Each correct option must match an option label (for example A or A,C).',
                    );
                    break;
                }
            }
        }
    }

    protected function resolveCategoryPath(string $path, int $orgId, ?int $actorId): ?int
    {
        if ($path === '') {
            return null;
        }

        $segments = array_values(array_filter(array_map('trim', explode('>', $path))));
        if ($segments === []) {
            return null;
        }

        $parentId = null;
        $resolvedPath = '';

        foreach ($segments as $segment) {
            $resolvedPath = $resolvedPath === '' ? $segment : $resolvedPath . ' > ' . $segment;
            $cacheKey = $orgId . '|' . mb_strtolower($resolvedPath);

            if (isset($this->categoryCache[$cacheKey])) {
                $parentId = $this->categoryCache[$cacheKey];

                continue;
            }

            $category = QuestionCategory::query()
                ->forOrg($orgId)
                ->where('parent_id', $parentId)
                ->get()
                ->first(static fn(QuestionCategory $item) => strcasecmp($item->name, $segment) === 0);

            if (!$category) {
                $category = QuestionCategory::create([
                    'organization_id' => $orgId,
                    'parent_id' => $parentId,
                    'name' => $segment,
                    'slug' => UniqueOrgSlug::forModel(QuestionCategory::class, $segment, $orgId),
                    'status' => 'active',
                    'sort_order' => (int) QuestionCategory::query()
                        ->forOrg($orgId)
                        ->where('parent_id', $parentId)
                        ->max('sort_order') + 1,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            $parentId = (int) $category->id;
            $this->categoryCache[$cacheKey] = $parentId;
        }

        return $parentId;
    }

    /** @param array<string, string> $options */
    protected function answerValue(string $value, array $options): string
    {
        $label = strtoupper(trim($value));

        return $options[$label] ?? trim($value);
    }

    /**
     * @param  array<string, string>  $options
     * @return list<string>
     */
    protected function answerLabels(string $value, array $options): array
    {
        return collect(preg_split('/[\s,;|]+/', strtoupper(trim($value))) ?: [])
            ->filter()
            ->map(fn(string $label) => $options[$label] ?? $label)
            ->unique()
            ->values()
            ->all();
    }

    /** @return list<int> */
    protected function integerList(string $value): array
    {
        return collect(preg_split('/[\s,;|]+/', trim($value)) ?: [])
            ->filter(static fn($item) => is_numeric($item))
            ->map(static fn($item) => (int) $item)
            ->filter(static fn(int $item) => $item >= 1 && $item <= 10)
            ->unique()
            ->values()
            ->all();
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}

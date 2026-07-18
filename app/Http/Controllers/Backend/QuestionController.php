<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Question\ImportQuestionsRequest;
use App\Http\Requests\Backend\Question\StartQuestionImportRequest;
use App\Http\Requests\Backend\Question\StoreQuestionRequest;
use App\Http\Requests\Backend\Question\UpdateQuestionRequest;
use App\Models\ImportQuestion;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Services\QuestionCategoryService;
use App\Services\QuestionImportService;
use App\Services\QuestionService;
use App\Support\ExamFormats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestionController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected QuestionService $service,
        protected QuestionCategoryService $categoryService,
        protected QuestionImportService $importService,
    ) {}

    public function index(): View
    {
        $orgId = $this->currentOrgId();
        $categories = $this->categoryService->getHierarchicalList($orgId);

        return view('backend.questions.index', compact('categories'));
    }

    public function create(Request $request): View
    {
        $orgId = $this->currentOrgId();
        $categories = $this->categoryService->getHierarchicalList($orgId);
        $questionTypes = ExamFormats::questionTypes();
        $defaults = $this->resolveCreateDefaults($request, $orgId);
        $examCreateReturn = $defaults['source'] === 'exam-create'
            ? route('admin.exams.create')
            : null;
        $createdFromExam = session()->pull('exam_create_question_created');

        return view('backend.questions.create', compact(
            'categories',
            'questionTypes',
            'defaults',
            'examCreateReturn',
            'createdFromExam'
        ));
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $data = $request->validated();
        $data['organization_id'] = $orgId;

        $question = $this->service->create($data);
        $source = (string) $request->input('source', '');

        if ($source === 'exam-create') {
            return redirect()
                ->route('admin.questions.create', ['source' => 'exam-create'])
                ->with('success', 'Question created successfully.')
                ->with('exam_create_question_created', [
                    'id' => $question->id,
                    'categoryId' => (string) $question->category_id,
                    'marks' => (int) $question->marks,
                    'difficulty' => $question->difficulty,
                    'type' => $question->type,
                    'allowsMultiple' => (bool) $question->allows_multiple,
                    'text' => strip_tags((string) $question->body),
                ]);
        }

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question created successfully.');
    }

    public function import(ImportQuestionsRequest $request): JsonResponse
    {
        $import = ImportQuestion::query()
            ->forOrg($this->currentOrgId())
            ->findOrFail($request->integer('import_question_id'));
        abort_if($import->status !== 'processing', 409, 'This import is no longer accepting rows.');

        $result = $this->importService->importChunk(
            $request->validated('rows'),
            $import,
            $this->currentOrgId(),
            $request->user()?->id,
        );

        return response()->json([
            'message' => $result['failed'] === 0
                ? 'Question batch imported successfully.'
                : 'Question batch processed with validation errors.',
            ...$result,
        ], $result['imported'] > 0 ? 201 : 422);
    }

    public function startImport(StartQuestionImportRequest $request): JsonResponse
    {
        $import = $this->importService->start(
            $request->file('file'),
            $request->integer('total_rows'),
            $request->integer('failed_rows'),
            $request->validated('initial_errors', []),
            $this->currentOrgId(),
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Import file stored successfully.',
            'import_question_id' => $import->id,
        ], 201);
    }

    public function completeImport(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'unrecorded_errors' => ['nullable', 'array', 'max:10000'],
            'unrecorded_errors.*.row' => ['required', 'integer', 'min:2'],
            'unrecorded_errors.*.errors' => ['required', 'array', 'min:1'],
            'unrecorded_errors.*.errors.*' => ['required', 'string', 'max:1000'],
        ]);
        $import = ImportQuestion::query()
            ->forOrg($this->currentOrgId())
            ->findOrFail($id);
        abort_if($import->status !== 'processing', 409, 'This import has already been completed.');

        $import = $this->importService->complete($import, $validated['unrecorded_errors'] ?? []);

        return response()->json([
            'message' => 'Question import completed.',
            'import' => $this->importDetailsPayload($import),
        ]);
    }

    public function importDetails(int $id): JsonResponse
    {
        $import = ImportQuestion::query()
            ->forOrg($this->currentOrgId())
            ->with('creator:id,name')
            ->findOrFail($id);

        return response()->json([
            'import' => $this->importDetailsPayload($import),
        ]);
    }

    public function downloadImport(int $id): BinaryFileResponse
    {
        $import = ImportQuestion::query()
            ->forOrg($this->currentOrgId())
            ->findOrFail($id);

        abort_unless(Storage::disk($import->disk)->exists($import->file_path), 404, 'Import file not found.');

        return response()->download(
            Storage::disk($import->disk)->path($import->file_path),
            $import->original_file_name,
            ['Content-Type' => $import->mime_type ?: 'application/octet-stream'],
        );
    }

    public function show($id): View
    {
        $question = Question::with(['category', 'createdBy', 'ogImage', 'importQuestion'])->findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        return view('backend.questions.show', compact('question'));
    }

    public function edit($id): View
    {
        $question = Question::with(['ogImage'])->findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        $categories = $this->categoryService->getHierarchicalList($orgId);
        $questionTypes = ExamFormats::questionTypes();

        return view('backend.questions.edit', compact('question', 'categories', 'questionTypes'));
    }

    public function update(UpdateQuestionRequest $request, $id): RedirectResponse
    {
        $question = Question::findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        $data = $request->validated();
        $this->service->update($question, $data);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $question = Question::findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        $this->service->delete($question);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $question = Question::withTrashed()->forOrg($this->currentOrgId())->findOrFail($id);
        abort_unless($question->trashed(), 404);
        $question->restore();

        return redirect()->route('admin.questions.index', ['tab' => 'bin'])
            ->with('success', 'Question restored successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $count = Question::forOrg($this->currentOrgId())->whereIn('id', $ids)->get()
            ->each->delete()->count();

        return redirect()->route('admin.questions.index')
            ->with('success', "{$count} question(s) moved to bin.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $count = Question::onlyTrashed()->forOrg($this->currentOrgId())->whereIn('id', $ids)->restore();

        return redirect()->route('admin.questions.index', ['tab' => 'bin'])
            ->with('success', "{$count} question(s) restored.");
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);
        $count = Question::forOrg($this->currentOrgId())
            ->whereIn('id', array_unique($validated['ids']))
            ->update(['status' => $validated['status']]);

        return redirect()->route('admin.questions.index')
            ->with('success', "Status updated for {$count} question(s).");
    }

    /** @return list<int> */
    private function validatedIds(Request $request): array
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_map('intval', $validated['ids'])));
    }

    /** @return array<string, mixed> */
    private function importDetailsPayload(ImportQuestion $import): array
    {
        return [
            'id' => $import->id,
            'original_file_name' => $import->original_file_name,
            'file_type' => $import->file_type,
            'file_size' => $import->file_size,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->failed_rows,
            'import_logs' => $import->import_logs ?? [],
            'errors' => $import->errors ?? [],
            'created_by' => $import->creator?->name,
            'imported_at' => $import->imported_at?->toIso8601String(),
            'completed_at' => $import->completed_at?->toIso8601String(),
            'download_url' => route('admin.questions.imports.download', $import->id),
        ];
    }

    /**
     * @return array{
     *     source: string|null,
     *     category_id: int|null,
     *     type: string|null,
     *     allows_multiple: bool|null,
     *     difficulty: string|null,
     *     marks_type: string,
     *     marks: int|null,
     *     marks_list: list<int>,
     *     formats: list<string>
     * }
     */
    private function resolveCreateDefaults(Request $request, int $orgId): array
    {
        $source = $request->query('source') === 'exam-create' ? 'exam-create' : null;

        $categoryId = $request->integer('category_id') ?: null;
        if ($categoryId) {
            $exists = QuestionCategory::query()
                ->where('organization_id', $orgId)
                ->where('status', 'active')
                ->whereKey($categoryId)
                ->exists();
            if (! $exists) {
                $categoryId = null;
            }
        }

        $marksInput = $request->query('marks', []);
        if (! is_array($marksInput)) {
            $marksInput = [$marksInput];
        }
        $marksList = array_values(array_unique(array_filter(array_map(
            static fn ($value) => (int) $value,
            $marksInput
        ), static fn (int $value) => $value >= 1 && $value <= 10)));

        $formatsInput = $request->query('formats', []);
        if (! is_array($formatsInput)) {
            $formatsInput = [$formatsInput];
        }
        $formats = array_values(array_unique(array_filter(
            array_map('strval', $formatsInput),
            static fn (string $format) => in_array($format, ExamFormats::ids(), true)
        )));

        $type = null;
        $allowsMultiple = null;
        $constraints = ExamFormats::questionConstraints();
        if (count($formats) === 1) {
            $rules = $constraints[$formats[0]] ?? [];
            if (count($rules) === 1) {
                $type = $rules[0]['type'] ?? null;
                $allowsMultiple = array_key_exists('allows_multiple', $rules[0])
                    ? $rules[0]['allows_multiple']
                    : null;
            }
        }

        $requestedType = (string) $request->query('type', '');
        if (in_array($requestedType, ExamFormats::questionTypeIds(), true)) {
            $type = $requestedType;
        }

        $difficulty = (string) $request->query('difficulty', '');
        if (! in_array($difficulty, ['easy', 'medium', 'hard', 'very_hard'], true)) {
            $difficulty = null;
        }

        return [
            'source' => $source,
            'category_id' => $categoryId,
            'type' => $type,
            'allows_multiple' => is_bool($allowsMultiple) ? $allowsMultiple : null,
            'difficulty' => $difficulty,
            'marks_type' => count($marksList) > 1 ? 'multiple' : 'single',
            'marks' => $marksList[0] ?? null,
            'marks_list' => $marksList,
            'formats' => $formats,
        ];
    }
}

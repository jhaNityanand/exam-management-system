<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Exam\StoreExamRequest;
use App\Http\Requests\Backend\Exam\UpdateExamRequest;
use App\Models\ExamCategory;
use App\Models\Exam;
use App\Models\Question;
use App\Models\UserOrganization;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    // ── Org helper ────────────────────────────────────────────────────────────

    protected function currentOrgId(): int
    {
        if (Auth::check()) {
            $orgId = UserOrganization::where('user_id', Auth::id())
                ->where('status', 'active')
                ->value('organization_id');

            if ($orgId) {
                return (int) $orgId;
            }
        }

        $id = current_organization_id();
        abort_if($id === null, 503, 'No organization found. Please run the database seeder.');

        return $id;
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('backend.exams.index');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $categories = ExamCategory::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $questions = Question::query()
            ->orderBy('body')
            ->limit(500)
            ->get(['id', 'body', 'category_id', 'marks', 'difficulty', 'type']);

        return view('backend.exams.create', compact('categories', 'questions'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Attach current organisation
        $data['organization_id'] = current_organization_id();

        $exam = $this->examService->create($data);

        return redirect()
            ->route('admin.exams.show', $exam)
            ->with('success', 'Exam created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show($id): View
    {
        $exam = $this->resolveExam((int) $id);

        if ($exam->exists) {
            abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');
        }

        $stats = $exam->exists
            ? $this->examService->getAttemptStats($exam)
            : [
                'total'     => 0,
                'passed'    => 0,
                'failed'    => 0,
                'avg_score' => 0,
            ];

        return view('backend.exams.show', compact('exam', 'stats'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit($id): View
    {
        $exam = $this->resolveExam((int) $id);
        abort_if($exam->exists && $exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');

        $categories = ExamCategory::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $questions = Question::query()
            ->orderBy('body')
            ->limit(500)
            ->get(['id', 'body', 'category_id', 'marks', 'difficulty', 'type']);

        return view('backend.exams.edit', compact('exam', 'categories', 'questions'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(UpdateExamRequest $request, $id): RedirectResponse
    {
        $exam = Exam::findOrFail($id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');
        $this->examService->update($exam, $request->validated());

        return redirect()
            ->route('admin.exams.show', $exam)
            ->with('success', 'Exam updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy($id): RedirectResponse
    {
        $exam = Exam::findOrFail($id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');
        $this->examService->delete($exam);

        return redirect()
            ->route('admin.exams.index')
            ->with('success', 'Exam deleted successfully.');
    }

    // ── Publish ───────────────────────────────────────────────────────────────

    public function publish($id): RedirectResponse
    {
        $exam = Exam::findOrFail($id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');
        $this->examService->publish($exam);

        return redirect()
            ->route('admin.exams.show', $exam)
            ->with('success', 'Exam published successfully.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function resolveExam(int $id): Exam
    {
        $exam = Exam::query()
            ->with(['questions', 'category', 'createdBy'])
            ->find($id);

        if ($exam) {
            return $exam;
        }

        return $this->makeFallbackExam($id);
    }

    protected function makeFallbackExam(int $id): Exam
    {
        $exam = new Exam([
            'title'                      => "Demo Exam #{$id}",
            'description'                => '<p>This is a demo exam record rendered for UI preview mode.</p>',
            'duration'                   => 60,
            'pass_percentage'            => 50,
            'max_attempts'               => 1,
            'status'                     => 'draft',
            'negative_mark_per_question' => 0,
            'shuffle_questions'          => false,
            'shuffle_options'            => false,
            'exam_mode'                  => 'standard',
            'exam_format'                => 'mcq',
            'visibility'                 => 'public',
            'category_id'                => null,
            'scheduled_start'            => null,
            'scheduled_end'              => null,
        ]);

        $exam->id     = $id;
        $exam->exists = false;
        $exam->setRelation('questions', new Collection());
        $exam->setRelation('category', null);
        $exam->setRelation('createdBy', null);

        return $exam;
    }
}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    public function index(): View
    {
        return view('backend.exams.index');
    }

    public function create(): View
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $questions = Question::query()
            ->orderBy('body')
            ->limit(500)
            ->get(['id', 'body', 'category_id', 'marks', 'difficulty', 'type']);

        return view('backend.exams.create', compact('categories', 'questions'));
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam created (Dummy Mode).');
    }

    public function show($id): View
    {
        $exam = $this->resolveExam((int) $id);
        $stats = $exam->exists
            ? $this->examService->getAttemptStats($exam)
            : [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'avg_score' => 0,
            ];

        return view('backend.exams.show', compact('exam', 'stats'));
    }

    public function edit($id): View
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $questions = Question::query()
            ->orderBy('body')
            ->limit(500)
            ->get(['id', 'body', 'category_id', 'marks', 'difficulty', 'type']);
        $exam = $this->resolveExam((int) $id);

        return view('backend.exams.edit', compact('exam', 'categories', 'questions'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam updated (Dummy Mode).');
    }

    public function destroy($id): RedirectResponse
    {
        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam deleted (Dummy Mode).');
    }

    public function publish($id): RedirectResponse
    {
        return redirect()->route('admin.exams.show', $id)
            ->with('success', 'Exam published (Dummy Mode).');
    }

    protected function resolveExam(int $id): Exam
    {
        $exam = Exam::query()->with(['questions', 'category'])->find($id);

        if ($exam) {
            return $exam;
        }

        return $this->makeFallbackExam($id);
    }

    protected function makeFallbackExam(int $id): Exam
    {
        $exam = new Exam([
            'title' => "Demo Exam #{$id}",
            'description' => '<p>This is a demo exam record rendered for UI preview mode.</p>',
            'duration' => 60,
            'pass_percentage' => 50,
            'max_attempts' => 1,
            'status' => 'draft',
            'negative_mark_per_question' => 0,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'exam_mode' => 'standard',
            'category_id' => null,
            'scheduled_start' => null,
            'scheduled_end' => null,
        ]);

        $exam->id = $id;
        $exam->exists = false;
        $exam->setRelation('questions', new Collection());
        $exam->setRelation('category', null);

        return $exam;
    }
}

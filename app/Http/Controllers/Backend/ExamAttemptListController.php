<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamAttempterService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamAttemptListController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected ExamAttempterService $attempters,
    ) {}

    public function index(Exam $exam): View
    {
        $exam = $this->attempters->findExamForOrg((int) $exam->id, $this->currentOrgId());

        return view('backend.exams.attempts.index', compact('exam'));
    }

    public function export(Exam $exam): StreamedResponse
    {
        $exam = $this->attempters->findExamForOrg((int) $exam->id, $this->currentOrgId());

        return $this->attempters->exportAttemptsExcel($exam);
    }
}

<?php

namespace App\Http\Controllers\Viewer;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\View\View;

class ExamBrowseController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('exam.view'), 403);

        $orgId = current_organization_id();
        abort_if($orgId === null, 404);

        $exams = Exam::forOrg($orgId)
            ->published()
            ->orderBy('title')
            ->get();

        return view('viewer.exams.index', compact('exams'));
    }
}

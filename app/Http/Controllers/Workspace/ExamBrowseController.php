<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\View\View;

class ExamBrowseController extends Controller
{
    public function index(): View
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 404);

        $exams = Exam::forOrg($orgId)
            ->published()
            ->orderBy('title')
            ->get();

        return view('workspace.exam-browse.index', compact('exams'));
    }
}

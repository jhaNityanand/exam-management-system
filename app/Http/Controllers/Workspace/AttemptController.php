<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\View\View;

class AttemptController extends Controller
{
    public function index(): View
    {
        $attempts = ExamAttempt::query()
            ->where('user_id', auth()->id())
            ->whereHas('exam', function ($query) {
                $query->where('organization_id', current_organization_id());
            })
            ->with('exam')
            ->latest()
            ->paginate(20);

        return view('workspace.attempts.index', compact('attempts'));
    }
}

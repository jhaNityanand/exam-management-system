<?php

namespace App\Http\Controllers\Viewer;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\View\View;

class AttemptController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('attempt.view_own'), 403);

        $attempts = ExamAttempt::query()
            ->where('user_id', auth()->id())
            ->with('exam')
            ->latest()
            ->paginate(20);

        return view('viewer.attempts.index', compact('attempts'));
    }
}

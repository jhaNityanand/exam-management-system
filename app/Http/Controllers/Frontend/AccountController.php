<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $attempts = ExamAttempt::query()->where('user_id', $user->id);

        $stats = [
            'attempts' => (clone $attempts)->count(),
            'completed' => (clone $attempts)->whereIn('status', ['submitted', 'expired', 'graded'])->count(),
            'avg_score' => (int) round((clone $attempts)->whereNotNull('percentage')->avg('percentage') ?? 0),
        ];

        return view('frontend.account.dashboard', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function exams(Request $request): View
    {
        $user = $request->user();

        $attempts = $user->examAttempts()
            ->with(['exam:id,title,slug,difficulty_level,duration,total_questions,pricing_option,exam_amount,status'])
            ->latest('id')
            ->paginate(12);

        return view('frontend.account.exams', [
            'user' => $user,
            'attempts' => $attempts,
        ]);
    }

    public function results(Request $request): View
    {
        $user = $request->user();

        return view('frontend.account.results', [
            'user' => $user,
            'results' => $user->examAttempts()
                ->with(['exam:id,title,slug,pass_percentage,passing_marks,result_release_mode'])
                ->whereNotNull('submitted_at')
                ->latest('submitted_at')
                ->paginate(12),
        ]);
    }

    public function settings(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return view('frontend.account.settings', [
            'user' => $user,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->fill($data)->save();

        return back()->with('success', 'Settings updated successfully.');
    }
}

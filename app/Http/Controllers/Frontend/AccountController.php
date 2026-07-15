<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('frontend.account.dashboard', [
            'user' => $user,
        ]);
    }

    public function exams(Request $request): View
    {
        $user = $request->user();

        return view('frontend.account.exams', [
            'user' => $user,
            'attempts' => $user->examAttempts()
                ->with(['exam:id,title,slug,difficulty_level'])
                ->latest('id')
                ->paginate(12),
        ]);
    }

    public function results(Request $request): View
    {
        $user = $request->user();

        return view('frontend.account.results', [
            'user' => $user,
            'results' => $user->examAttempts()
                ->with(['exam:id,title,slug,pass_percentage'])
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
}

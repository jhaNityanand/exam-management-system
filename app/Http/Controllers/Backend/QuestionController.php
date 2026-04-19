<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(): View
    {
        return view('backend.questions.index');
    }

    public function create(): View
    {
        return view('backend.questions.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.questions.index')->with('success', 'Question created (Dummy Mode)');
    }

    public function show($id): View
    {
        return view('backend.questions.show');
    }

    public function edit($id): View
    {
        return view('backend.questions.edit');
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('admin.questions.index')->with('success', 'Question updated (Dummy Mode)');
    }

    public function destroy($id)
    {
        return redirect()->route('admin.questions.index')->with('success', 'Question deleted (Dummy Mode)');
    }
}

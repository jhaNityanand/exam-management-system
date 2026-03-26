@extends('layouts.app')

@section('title', 'Create Question')
@section('page-title', 'Create Question')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Questions', 'url' => route('editor.questions.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
    <x-page-card class="max-w-4xl">
        <form action="{{ route('editor.questions.store') }}" method="POST" class="space-y-6" data-question-form>
            @csrf
            <div>
                <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                <select id="category_id" name="category_id" class="panel-input @error('category_id') border-rose-500 @enderror">
                    <option value="">- Select category -</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="type" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Question type</label>
                <select id="type" name="type" class="panel-input" data-question-type>
                    <option value="mcq" @selected(old('type', 'mcq') === 'mcq')>Multiple Choice (MCQ)</option>
                    <option value="true_false" @selected(old('type') === 'true_false')>True / False</option>
                    <option value="short_answer" @selected(old('type') === 'short_answer')>Short Answer</option>
                </select>
            </div>

            <div>
                <label for="body" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Question text</label>
                <textarea id="body" name="body" rows="4" class="panel-input @error('body') border-rose-500 @enderror" placeholder="Enter the question...">{{ old('body') }}</textarea>
                @error('body')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            <div data-question-options>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Answer options</label>
                <div class="space-y-2">
                    @foreach (old('options', ['', '', '', '']) as $index => $option)
                        <div class="flex items-center gap-2">
                            <span class="w-5 text-right text-xs text-slate-400">{{ chr(65 + $index) }}.</span>
                            <input type="text" name="options[]" value="{{ $option }}" class="panel-input" placeholder="Option {{ chr(65 + $index) }}">
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="correct_answer" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Correct answer</label>
                <input type="text" id="correct_answer" name="correct_answer" value="{{ old('correct_answer') }}" class="panel-input @error('correct_answer') border-rose-500 @enderror" placeholder="A, True, or a short answer">
                @error('correct_answer')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="explanation" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Explanation</label>
                <textarea id="explanation" name="explanation" rows="2" class="panel-input">{{ old('explanation') }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="marks" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Marks</label>
                    <input type="number" id="marks" name="marks" value="{{ old('marks', 1) }}" min="1" class="panel-input">
                </div>
                <div>
                    <label for="difficulty" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Difficulty</label>
                    <select id="difficulty" name="difficulty" class="panel-input">
                        <option value="easy" @selected(old('difficulty') === 'easy')>Easy</option>
                        <option value="medium" @selected(old('difficulty', 'medium') === 'medium')>Medium</option>
                        <option value="hard" @selected(old('difficulty') === 'hard')>Hard</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="panel-button-primary">Save Question</button>
                <a href="{{ route('editor.questions.index') }}" class="panel-button-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
@endsection

@extends('layouts.app')

@section('title', 'Edit question')
@section('page-title', 'Edit question')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Questions', 'url' => route('workspace.questions.index')],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
    <x-page-card class="max-w-4xl">
        <form action="{{ route('workspace.questions.update', $question) }}" method="POST" class="space-y-5" data-question-form>
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                <select name="category_id" class="panel-input">
                    <option value="">- None -</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $question->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Type</label>
                <select name="type" id="type" class="panel-input" data-question-type>
                    @foreach (['mcq', 'true_false', 'short_answer'] as $type)
                        <option value="{{ $type }}" @selected(old('type', $question->type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="hidden" name="allows_multiple" value="0">
                    <input type="checkbox" name="allows_multiple" value="1" @checked(old('allows_multiple', $question->allows_multiple)) class="rounded border-slate-300 dark:border-slate-600">
                    <span>Allow multiple correct (MCQ)</span>
                </label>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Question text</label>
                <textarea name="body" rows="4" class="panel-input">{{ old('body', $question->body) }}</textarea>
                @error('body')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div data-question-options>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Options (MCQ)</label>
                <div class="space-y-2">
                    @php $options = old('options', $question->options ? array_column($question->options, 'text') : ['', '', '', '']); @endphp
                    @foreach ($options as $index => $option)
                        <input type="text" name="options[]" value="{{ is_array($option) ? ($option['text'] ?? '') : $option }}" class="panel-input" placeholder="Option {{ chr(65 + $index) }}">
                    @endforeach
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Correct answer</label>
                <input type="text" name="correct_answer" value="{{ old('correct_answer', $question->correct_answer) }}" class="panel-input">
            </div>
            @php $correctAnswers = old('correct_answers', $question->correct_answers ?? []); @endphp
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Correct answers (multi)</label>
                @foreach (range(0, max(2, count($correctAnswers) - 1)) as $index)
                    <input type="text" name="correct_answers[]" value="{{ $correctAnswers[$index] ?? '' }}" class="panel-input mb-2" placeholder="Answer {{ $index + 1 }}">
                @endforeach
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Explanation</label>
                <textarea name="explanation" rows="2" class="panel-input">{{ old('explanation', $question->explanation) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Marks</label>
                    <input type="number" name="marks" value="{{ old('marks', $question->marks) }}" min="1" class="panel-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Difficulty</label>
                    <select name="difficulty" class="panel-input">
                        @foreach (['easy', 'medium', 'hard'] as $difficulty)
                            <option value="{{ $difficulty }}" @selected(old('difficulty', $question->difficulty) === $difficulty)>{{ $difficulty }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="panel-button-primary">Save</button>
                <a href="{{ route('workspace.questions.index') }}" class="panel-button-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
@endsection


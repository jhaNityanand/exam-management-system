@extends('backend.layouts.app')

@section('title', 'New exam')
@section('page-title', 'Create exam')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Exams', 'url' => route('admin.exams.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
    <x-page-card class="max-w-5xl">
        <form action="{{ route('admin.exams.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="panel-input">
                @error('title')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                <textarea id="exam-description" name="description" rows="4" class="panel-input" data-rich-text>{{ old('description') }}</textarea>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                    <select name="category_id" class="panel-input">
                        <option value="">- None -</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Exam mode</label>
                    <input type="text" name="exam_mode" value="{{ old('exam_mode', 'standard') }}" class="panel-input" placeholder="standard, proctored...">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Duration (minutes)</label>
                    <input type="number" name="duration" value="{{ old('duration', 60) }}" min="1" class="panel-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Pass %</label>
                    <input type="number" step="0.01" name="pass_percentage" value="{{ old('pass_percentage', 50) }}" class="panel-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Max attempts</label>
                    <input type="number" name="max_attempts" value="{{ old('max_attempts', 1) }}" min="1" class="panel-input">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Scheduled start</label>
                    <input type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start') }}" class="panel-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Scheduled end</label>
                    <input type="datetime-local" name="scheduled_end" value="{{ old('scheduled_end') }}" class="panel-input">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Negative mark / question</label>
                    <input type="number" step="0.0001" name="negative_mark_per_question" value="{{ old('negative_mark_per_question', 0) }}" min="0" class="panel-input">
                </div>
                <div class="flex flex-wrap gap-4 pb-1">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions')) class="rounded border-slate-300 dark:border-slate-600">
                        <span>Shuffle questions</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options')) class="rounded border-slate-300 dark:border-slate-600">
                        <span>Shuffle options</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Questions in this exam</label>
                <select name="question_ids[]" multiple size="12" class="panel-input font-mono">
                    @foreach ($questions as $question)
                        <option value="{{ $question->id }}" @selected(collect(old('question_ids', []))->contains($question->id))>
                            #{{ $question->id }} - {{ \Illuminate\Support\Str::limit(strip_tags($question->body), 80) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                <select name="status" class="panel-input">
                    @foreach (['draft', 'published', 'active', 'inactive', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="panel-button-primary">Save</button>
                <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
@endsection


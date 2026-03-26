@extends('layouts.app')

@section('title', 'Edit exam')
@section('page-title', 'Edit exam')

@php
    $selectedQuestions = old('question_ids', $exam->questions->pluck('id')->all());
@endphp

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Exams', 'url' => route('org-admin.exams.index')],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
    <x-page-card class="max-w-5xl">
        <form action="{{ route('org-admin.exams.update', $exam) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Title</label>
                <input type="text" name="title" value="{{ old('title', $exam->title) }}" class="panel-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                <textarea id="exam-description" name="description" rows="4" class="panel-input" data-rich-text>{{ old('description', $exam->description) }}</textarea>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                    <select name="category_id" class="panel-input">
                        <option value="">- None -</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $exam->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Exam mode</label>
                    <input type="text" name="exam_mode" value="{{ old('exam_mode', $exam->exam_mode) }}" class="panel-input">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Duration</label>
                    <input type="number" name="duration" value="{{ old('duration', $exam->duration) }}" min="1" class="panel-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Pass %</label>
                    <input type="number" step="0.01" name="pass_percentage" value="{{ old('pass_percentage', $exam->pass_percentage) }}" class="panel-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Max attempts</label>
                    <input type="number" name="max_attempts" value="{{ old('max_attempts', $exam->max_attempts) }}" min="1" class="panel-input">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Scheduled start</label>
                    <input type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start', optional($exam->scheduled_start)->format('Y-m-d\TH:i')) }}" class="panel-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Scheduled end</label>
                    <input type="datetime-local" name="scheduled_end" value="{{ old('scheduled_end', optional($exam->scheduled_end)->format('Y-m-d\TH:i')) }}" class="panel-input">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Negative mark / question</label>
                <input type="number" step="0.0001" name="negative_mark_per_question" value="{{ old('negative_mark_per_question', $exam->negative_mark_per_question) }}" min="0" class="panel-input max-w-xs">
            </div>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $exam->shuffle_questions)) class="rounded border-slate-300 dark:border-slate-600">
                    <span>Shuffle questions</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $exam->shuffle_options)) class="rounded border-slate-300 dark:border-slate-600">
                    <span>Shuffle options</span>
                </label>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Questions</label>
                <select name="question_ids[]" multiple size="12" class="panel-input font-mono">
                    @foreach ($questions as $question)
                        <option value="{{ $question->id }}" @selected(in_array($question->id, $selectedQuestions))>
                            #{{ $question->id }} - {{ \Illuminate\Support\Str::limit(strip_tags($question->body), 80) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                <select name="status" class="panel-input">
                    @foreach (['draft', 'published', 'active', 'inactive', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $exam->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="panel-button-primary">Update</button>
                <a href="{{ route('org-admin.exams.index') }}" class="panel-button-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
@endsection

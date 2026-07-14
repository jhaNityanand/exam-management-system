@extends('backend.layouts.app')

@section('title', 'Question Details')
@section('page-title', 'Question Details')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Questions', 'url' => route('admin.questions.index')],
        ['label' => 'Question #' . $question->id],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <!-- Top Action Banner -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">ID #{{ $question->id }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $question->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ ucfirst($question->status) }}
                    </span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">Question Profile</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.questions.edit', $question) }}" class="panel-button-primary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit Question
            </a>
            <a href="{{ route('admin.questions.index') }}" class="panel-button-secondary">
                Back to List
            </a>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Side: Content & Options (8 Columns) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Question Content Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                <div>
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-555 uppercase tracking-wider mb-3">Question Content</h2>
                    <div class="prose dark:prose-invert max-w-none text-slate-800 dark:text-slate-100 text-lg leading-relaxed font-medium">
                        {!! $question->body !!}
                    </div>
                </div>

                <!-- Answer options -->
                <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                    @if ($question->type === 'mcq' && is_array($question->options))
                        <h3 class="text-sm font-semibold text-slate-400 dark:text-slate-555 uppercase tracking-wider mb-4">Options &amp; Correct Answer</h3>
                        <div class="grid grid-cols-1 gap-3">
                            @foreach ($question->options as $opt)
                                @php
                                    $optText = $opt['text'] ?? '';
                                    $isCorrect = false;
                                    if ($question->allows_multiple) {
                                        $isCorrect = is_array($question->correct_answers) && in_array($optText, $question->correct_answers);
                                    } else {
                                        $isCorrect = $question->correct_answer === $optText;
                                    }
                                @endphp
                                <div class="flex items-start gap-4 p-4 rounded-2xl border transition duration-200
                                    {{ $isCorrect 
                                        ? 'bg-emerald-50/70 border-emerald-250 dark:bg-emerald-500/10 dark:border-emerald-500/30' 
                                        : 'bg-slate-50 dark:bg-slate-950/20 border-slate-200/80 dark:border-slate-800' }}"
                                >
                                    <div class="mt-0.5 flex-shrink-0">
                                        @if($question->allows_multiple)
                                            <div class="w-5 h-5 rounded border flex items-center justify-center 
                                                {{ $isCorrect 
                                                    ? 'bg-emerald-500 border-emerald-500 text-white' 
                                                    : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900' }}"
                                            >
                                                @if($isCorrect)
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                @endif
                                            </div>
                                        @else
                                            <div class="w-5 h-5 rounded-full border flex items-center justify-center 
                                                {{ $isCorrect 
                                                    ? 'bg-emerald-500 border-emerald-500 text-white' 
                                                    : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900' }}"
                                            >
                                                @if($isCorrect)
                                                    <div class="w-2 h-2 rounded-full bg-white"></div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-relaxed">
                                        {!! $optText !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    @elseif ($question->type === 'true_false')
                        <h3 class="text-sm font-semibold text-slate-400 dark:text-slate-555 uppercase tracking-wider mb-4">Correct Answer</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 rounded-2xl border flex items-center justify-between transition duration-200
                                {{ $question->correct_answer === 'True' 
                                    ? 'bg-emerald-50/70 border-emerald-250 dark:bg-emerald-500/10 dark:border-emerald-500/30' 
                                    : 'bg-slate-50 dark:bg-slate-950/20 border-slate-200/80 dark:border-slate-800' }}"
                            >
                                <span class="font-semibold text-slate-800 dark:text-slate-200">True</span>
                                @if($question->correct_answer === 'True')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs bg-emerald-100/50 dark:bg-emerald-500/20 px-2.5 py-1 rounded-lg">Correct</span>
                                @endif
                            </div>
                            <div class="p-4 rounded-2xl border flex items-center justify-between transition duration-200
                                {{ $question->correct_answer === 'False' 
                                    ? 'bg-emerald-50/70 border-emerald-250 dark:bg-emerald-500/10 dark:border-emerald-500/30' 
                                    : 'bg-slate-50 dark:bg-slate-950/20 border-slate-200/80 dark:border-slate-800' }}"
                            >
                                <span class="font-semibold text-slate-800 dark:text-slate-200">False</span>
                                @if($question->correct_answer === 'False')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs bg-emerald-100/50 dark:bg-emerald-500/20 px-2.5 py-1 rounded-lg">Correct</span>
                                @endif
                            </div>
                        </div>

                    @elseif (in_array($question->type, ['short_answer', 'long_answer', 'fill_blank'], true))
                        <h3 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">
                            @if($question->type === 'fill_blank')
                                Expected Blank Answer
                            @elseif($question->type === 'long_answer')
                                Model / Reference Answer
                            @else
                                Reference Answer
                            @endif
                        </h3>
                        <div class="p-5 bg-emerald-50/30 dark:bg-emerald-500/5 border border-emerald-100 dark:border-emerald-500/20 rounded-2xl">
                            <div class="prose prose-sm dark:prose-invert max-w-none text-slate-800 dark:text-slate-200 leading-relaxed font-semibold">
                                {!! $question->correct_answer !!}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Answer Explanation -->
                @if ($question->explanation)
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                        <h3 class="text-sm font-semibold text-slate-400 dark:text-slate-555 uppercase tracking-wider mb-3">Answer Explanation</h3>
                        <div class="p-5 bg-amber-50/30 dark:bg-amber-500/5 border border-amber-100 dark:border-amber-500/20 rounded-2xl">
                            <div class="prose prose-sm dark:prose-invert max-w-none text-slate-800 dark:text-slate-200 leading-relaxed">
                                {!! $question->explanation !!}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- SEO / Metadata Details -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-555 uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-800">SEO &amp; Metadata details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <span class="text-slate-450 dark:text-slate-400 block font-medium">Meta Title</span>
                        <span class="font-bold text-slate-850 dark:text-slate-200 mt-1 block">{{ $question->meta_title ?: 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-450 dark:text-slate-400 block font-medium">Slug</span>
                        <span class="font-bold text-slate-850 dark:text-slate-200 mt-1 block">{{ $question->slug ?: 'N/A' }}</span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-slate-450 dark:text-slate-400 block font-medium">Meta Description</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 block mt-1 leading-relaxed">{{ $question->meta_description ?: 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-450 dark:text-slate-400 block font-medium">Meta Keywords</span>
                        <span class="font-bold text-slate-850 dark:text-slate-200 mt-1 block">{{ $question->meta_keywords ?: 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-450 dark:text-slate-400 block font-medium">Canonical URL</span>
                        @if($question->canonical_url)
                            <a href="{{ $question->canonical_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-700 underline font-semibold mt-1 block">{{ $question->canonical_url }}</a>
                        @else
                            <span class="font-bold text-slate-850 dark:text-slate-200 mt-1 block">N/A</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-slate-450 dark:text-slate-400 block font-medium">OG Title</span>
                        <span class="font-bold text-slate-850 dark:text-slate-200 mt-1 block">{{ $question->og_title ?: 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-450 dark:text-slate-400 block font-medium">OG Description</span>
                        <span class="font-bold text-slate-850 dark:text-slate-200 mt-1 block">{{ $question->og_description ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Classification & Status (4 Columns) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Classification Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-450 dark:text-slate-500 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">Classification</h3>
                <div class="space-y-4">
                    <!-- Category -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-450 dark:text-slate-400 font-semibold">Category</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-950/20 px-3 py-1 rounded-xl border border-slate-200/40 dark:border-slate-800">
                            {{ $question->category ? $question->category->name : 'Uncategorized' }}
                        </span>
                    </div>

                    <!-- Type -->
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Question Type</span>
                        @php
                            $typeLabels = \App\Support\ExamFormats::questionTypeLabels();
                            $typeClasses = \App\Support\ExamFormats::questionTypeBadgeClasses();
                        @endphp
                        <span class="question-type-badge {{ $typeClasses[$question->type] ?? '' }}">
                            {{ $typeLabels[$question->type] ?? ucfirst(str_replace('_', ' ', $question->type)) }}
                        </span>
                    </div>

                    <!-- Difficulty -->
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Difficulty</span>
                        @php
                            $diffLabels = [
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                                'very_hard' => 'Very Hard',
                            ];
                            $diffClasses = [
                                'easy' => 'question-diff-easy',
                                'medium' => 'question-diff-medium',
                                'hard' => 'question-diff-hard',
                                'very_hard' => 'question-diff-very-hard',
                            ];
                        @endphp
                        <span class="question-diff-badge {{ $diffClasses[$question->difficulty] ?? '' }}">
                            {{ $diffLabels[$question->difficulty] ?? $question->difficulty }}
                        </span>
                    </div>

                    <!-- Marks -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-450 dark:text-slate-400 font-semibold">Marks / Points</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-950/20 px-3 py-1 rounded-xl border border-slate-200/40 dark:border-slate-800">
                            @if($question->marks_type === 'multiple' && is_array($question->marks_list))
                                {{ implode(', ', $question->marks_list) }} pts
                            @else
                                {{ $question->marks }} pts
                            @endif
                        </span>
                    </div>

                    <!-- Reference -->
                    @if($question->reference)
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-sm text-slate-450 dark:text-slate-400 font-semibold">Reference</span>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-350">
                                {{ $question->reference }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- AI Integration Details -->
            @if($question->ai_generated || $question->ai_improve)
                <div class="bg-indigo-50/70 dark:bg-indigo-950/20 border border-indigo-150 dark:border-indigo-500/30 rounded-2xl p-6 space-y-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h4 class="font-bold text-lg text-indigo-950 dark:text-white">AI Co-pilot active</h4>
                    </div>
                    <p class="text-xs text-indigo-900/80 dark:text-indigo-200/90 leading-relaxed font-medium">
                        This item leverages automated indexing pipelines. AI details assist search queries and index SEO classifications.
                    </p>
                    <div class="flex flex-wrap gap-2 pt-2">
                        @if($question->ai_generated)
                            <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:border-indigo-500/20">AI Generated</span>
                        @endif
                        @if($question->ai_improve)
                            <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/20">AI Improve</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

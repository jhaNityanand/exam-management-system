@extends('backend.layouts.app')

@section('title', $exam->title)
@section('page-title', 'Exam Details')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exams', 'url' => route('admin.exams.index')],
        ['label' => 'Exam #' . $exam->id],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <!-- Top Action Banner -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">ID #{{ $exam->id }}</span>
                    @php
                        $statusColors = [
                            'published' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                            'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
                            'active' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400',
                            'inactive' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                            'suspended' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400'
                        ];
                        $badgeCls = $statusColors[$exam->status] ?? 'bg-slate-100 text-slate-700';
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeCls }}">
                        {{ ucfirst($exam->status) }}
                    </span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ $exam->title }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($exam->status !== 'published')
                <form action="{{ route('admin.exams.publish', $exam) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="panel-button-primary bg-emerald-600 hover:bg-emerald-700 text-white border-none">
                        Publish Exam
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.exams.edit', $exam) }}" class="panel-button-primary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit Exam
            </a>
            <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">
                Back to List
            </a>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Side: Content & Linked Questions (8 Columns) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Description Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Description</h2>
                <x-rich-text-content :content="$exam->description ?: '<p class=\"text-slate-400 italic\">No description added yet.</p>'" class="text-sm leading-relaxed text-slate-800 dark:text-slate-100" />
            </div>

            <!-- Instructions -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Instructions</h2>
                <x-rich-text-content :content="$exam->instructions ?: '<p class=\"text-slate-400 italic\">No instructions added yet.</p>'" class="text-sm leading-relaxed text-slate-800 dark:text-slate-100" />
            </div>

            <!-- Distributions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Difficulty</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($difficultyDistribution as $label => $count)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-slate-600 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $label)) }}</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-slate-400 italic text-sm">No data</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Question Types</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($typeDistribution as $label => $count)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-slate-600 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $label)) }}</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-slate-400 italic text-sm">No data</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Marks</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($marksDistribution as $label => $count)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-slate-600 dark:text-slate-300">{{ $label }} pts</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-slate-400 italic text-sm">No data</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Linked Questions Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-200/60 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-550 uppercase tracking-wider">Linked Questions ({{ $exam->questions->count() }})</h2>
                    <span class="text-xs bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 font-bold px-2.5 py-1 rounded-full border border-indigo-100 dark:border-indigo-500/20">
                        {{ $exam->questions->sum('pivot.marks_override') ?: $exam->questions->sum('marks') }} Total Marks
                    </span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[480px] overflow-y-auto">
                    @forelse ($exam->questions as $q)
                        <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50/50 dark:hover:bg-slate-950/20 transition">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-950 px-2 py-1 rounded border border-slate-200 dark:border-slate-850 shrink-0">
                                #{{ $q->id }}
                            </span>
                            <div class="flex-1 text-sm min-w-0">
                                <div class="text-slate-800 dark:text-slate-200 line-clamp-3 leading-relaxed font-medium">
                                    {!! strip_tags($q->body) !!}
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                    <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 text-slate-500">
                                        {{ ucfirst($q->type) }}
                                    </span>
                                    <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 text-slate-500">
                                        {{ ucfirst($q->difficulty) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                    {{ $q->pivot->marks_override ?? $q->marks }} pts
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-slate-500 dark:text-slate-500 italic text-sm">
                            No questions linked to this exam workspace.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Side: Metadata / Configuration (4 Columns) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Settings Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-550 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    Configurations
                </h2>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Category</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ $exam->category ? $exam->category->name : 'Uncategorized' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Duration</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ $exam->duration }} minutes
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Pass Criteria</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ $exam->pass_percentage }}% Score
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Max Attempts</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ $exam->max_attempts == 0 ? 'Unlimited' : $exam->max_attempts }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Exam Mode</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wide text-xs">
                            {{ $exam->exam_mode }}
                        </span>
                    </div>

                    <div class="flex items-start justify-between text-sm gap-3">
                        <span class="text-slate-500 dark:text-slate-400 shrink-0">Format</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 text-right">
                            @if (count($formats))
                                <span class="inline-flex flex-wrap justify-end gap-1">
                                    @foreach ($formats as $format)
                                        <span class="inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:text-indigo-300">
                                            {{ $formatLabels[$format] ?? ucfirst(str_replace('_', ' ', $format)) }}
                                        </span>
                                    @endforeach
                                </span>
                            @else
                                —
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Visibility</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wide text-xs">
                            {{ $exam->visibility }}
                        </span>
                    </div>

                    @if($exam->enable_negative_marking)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Negative Penalty</span>
                            <span class="font-semibold text-rose-650 dark:text-rose-400">
                                @if(filled($exam->negative_marking_type))
                                    -{{ rtrim(rtrim(number_format((float) $exam->negative_marking_type, 2, '.', ''), '0'), '.') }}% of question marks
                                @else
                                    -{{ $exam->negative_mark_per_question }} pts
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Schedule Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-550 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    Schedule Bounds
                </h2>

                <div class="space-y-3">
                    <div class="text-sm">
                        <span class="block text-slate-500 dark:text-slate-400 mb-1">Start Access Window</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ $exam->scheduled_start ? $exam->scheduled_start->format('M d, Y h:i A') : 'Any Time (Flexible)' }}
                        </span>
                    </div>

                    <div class="text-sm">
                        <span class="block text-slate-500 dark:text-slate-400 mb-1">End Access Window</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                            {{ $exam->scheduled_end ? $exam->scheduled_end->format('M d, Y h:i A') : 'No Expiry (Flexible)' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attempt Statistics Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-550 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    Attempt Statistics
                </h2>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-950/20 p-3 rounded-xl border border-slate-100 dark:border-slate-850">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">Total Attempts</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-slate-250">{{ $stats['total'] }}</span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950/20 p-3 rounded-xl border border-slate-100 dark:border-slate-850">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">Passed Rate</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-slate-250">
                            {{ $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100) : 0 }}%
                        </span>
                    </div>

                    <div class="col-span-2 bg-slate-50 dark:bg-slate-950/20 p-3 rounded-xl border border-slate-100 dark:border-slate-850">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">Average score</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-slate-250">
                            {{ number_format($stats['avg_score'], 1) }} pts
                        </span>
                    </div>
                </div>
            </div>

            <!-- Audit Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-3">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    Metadata
                </h2>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Created by</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $exam->createdBy?->name ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Created</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $exam->created_at?->format('M d, Y h:i A') ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Updated</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $exam->updated_at?->format('M d, Y h:i A') ?? '—' }}</span>
                </div>
                @if ($exam->difficulty_level)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Difficulty</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ ucfirst($exam->difficulty_level) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

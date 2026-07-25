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

@php
    $hasHtml = static function (?string $html): bool {
        return filled(trim(strip_tags((string) $html)));
    };

    $flagClass = static function (bool $on): string {
        return $on
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
            : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400';
    };

    $statusColors = [
        'published' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
        'active' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400',
        'inactive' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'suspended' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
    ];
    $badgeCls = $statusColors[$exam->status] ?? 'bg-slate-100 text-slate-700';

    $linkedMarks = $questions->sum(fn ($q) => (float) ($q->pivot->marks_override ?? $q->marks));
    $hasAnyFixedQuestions = $exam->parts->contains(fn ($part) => (bool) $part->fixed_questions);
    $importedCandidates = is_array($exam->imported_candidates) ? $exam->imported_candidates : [];
    $manualEmails = is_array($exam->manual_candidate_emails) ? $exam->manual_candidate_emails : [];
    $freeImported = is_array($exam->free_imported_candidates) ? $exam->free_imported_candidates : [];
    $freeManual = is_array($exam->free_manual_candidate_emails) ? $exam->free_manual_candidate_emails : [];
    $tags = is_array($exam->tags) ? $exam->tags : [];
    $selectedDiscounts = is_array($exam->selected_discounts) ? $exam->selected_discounts : [];
    $customDiscounts = is_array($exam->custom_discounts) ? $exam->custom_discounts : [];
    $instructionRules = is_array($exam->predefined_instruction_rules) ? $exam->predefined_instruction_rules : [];
@endphp

@section('content')
<div class="exam-show space-y-6">
    {{-- Header --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div class="flex items-start gap-4 min-w-0">
            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">ID #{{ $exam->id }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeCls }}">
                        {{ $labels['status'][$exam->status] ?? ucfirst((string) $exam->status) }}
                    </span>
                    @if ($exam->visibility)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $labels['visibility'][$exam->visibility] ?? ucfirst(str_replace('_', ' ', (string) $exam->visibility)) }}
                        </span>
                    @endif
                    @if ($exam->exam_mode)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                            {{ $labels['mode'][$exam->exam_mode] ?? ucfirst((string) $exam->exam_mode) }}
                        </span>
                    @endif
                </div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-1 break-words">{{ $exam->title }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    {{ $exam->category?->name ?? 'Uncategorized' }}
                    @if ($exam->difficulty_level)
                        · {{ $labels['difficulty'][$exam->difficulty_level] ?? ucfirst((string) $exam->difficulty_level) }}
                    @endif
                    · {{ (int) $exam->total_questions }} questions · {{ (int) $exam->total_marks }} marks
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @if ($exam->status !== 'published')
                <form action="{{ route('admin.exams.publish', $exam) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="panel-button-primary bg-emerald-600 hover:bg-emerald-700 text-white border-none">
                        Publish Exam
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.exams.edit', $exam) }}" class="panel-button-primary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit Exam
            </a>
            <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">Back to List</a>
        </div>
    </div>

    {{-- Snapshot metrics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
        @foreach ([
            ['label' => 'Parts', 'value' => $exam->parts->count()],
            ['label' => 'Duration', 'value' => $exam->enable_exam_timer ? ((int) $exam->duration).' min' : 'No timer'],
            ['label' => 'Pass Marks', 'value' => (int) $exam->passing_marks.' / '.(int) $exam->total_marks],
            ['label' => 'Pass %', 'value' => rtrim(rtrim(number_format((float) $exam->pass_percentage, 2, '.', ''), '0'), '.').'%'],
            ['label' => 'Attempts', 'value' => ($exam->attempt_limit_type === 'unlimited' || (int) $exam->max_attempts === 0) ? 'Unlimited' : ((int) $exam->max_attempts)],
            ['label' => 'Linked Qs', 'value' => $questions->count()],
        ] as $metric)
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $metric['label'] }}</p>
                <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ $metric['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        {{-- Main column --}}
        <div class="xl:col-span-8 space-y-6">
            {{-- 1. Basic Information --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                <header class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">1. Basic Information</h2>
                </header>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Title</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $exam->title }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Exam Category</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $exam->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Difficulty Level</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $labels['difficulty'][$exam->difficulty_level] ?? ($exam->difficulty_level ? ucfirst($exam->difficulty_level) : '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $labels['status'][$exam->status] ?? ucfirst((string) $exam->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Exam Mode</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $labels['mode'][$exam->exam_mode] ?? ($exam->exam_mode ? ucfirst($exam->exam_mode) : '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Visibility</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $labels['visibility'][$exam->visibility] ?? ($exam->visibility ? ucfirst(str_replace('_', ' ', $exam->visibility)) : '—') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500 dark:text-slate-400">Tags</dt>
                        <dd class="mt-2 flex flex-wrap gap-1.5">
                            @forelse ($tags as $tag)
                                <span class="inline-flex items-center rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-300">{{ $tag }}</span>
                            @empty
                                <span class="text-slate-400 italic">No tags</span>
                            @endforelse
                        </dd>
                    </div>
                </dl>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Description</h3>
                    @if ($hasHtml($exam->description))
                        <x-rich-text-content :content="$exam->description" class="text-sm leading-relaxed text-slate-800 dark:text-slate-100" />
                    @else
                        <p class="text-sm text-slate-400 italic">No description added yet.</p>
                    @endif
                </div>
            </section>

            {{-- 2. Candidate Access --}}
            @if (in_array($exam->visibility, ['private', 'invite_only'], true) || count($importedCandidates) || count($manualEmails))
                <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <header class="border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">2. Candidate Access</h2>
                    </header>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/30 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Imported Candidates</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ count($importedCandidates) }}</p>
                            @if (count($importedCandidates))
                                <ul class="mt-3 space-y-1 max-h-40 overflow-y-auto text-slate-600 dark:text-slate-300">
                                    @foreach (array_slice($importedCandidates, 0, 20) as $candidate)
                                        <li>{{ is_array($candidate) ? ($candidate['email'] ?? $candidate['name'] ?? json_encode($candidate)) : $candidate }}</li>
                                    @endforeach
                                    @if (count($importedCandidates) > 20)
                                        <li class="text-slate-400 italic">+{{ count($importedCandidates) - 20 }} more</li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                        <div class="rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/30 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Manual Emails</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ count($manualEmails) }}</p>
                            @if (count($manualEmails))
                                <ul class="mt-3 space-y-1 max-h-40 overflow-y-auto text-slate-600 dark:text-slate-300">
                                    @foreach (array_slice($manualEmails, 0, 20) as $email)
                                        <li>{{ $email }}</li>
                                    @endforeach
                                    @if (count($manualEmails) > 20)
                                        <li class="text-slate-400 italic">+{{ count($manualEmails) - 20 }} more</li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            {{-- 3. Timer + 4. Schedule --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                <header class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">3. Timer &amp; Schedule</h2>
                </header>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                    <div class="space-y-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Timer</h3>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">Enable Exam Timer</span>
                            <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass((bool) $exam->enable_exam_timer) }}">{{ $exam->enable_exam_timer ? 'On' : 'Off' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">Duration</span>
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $exam->enable_exam_timer ? ((int) $exam->duration).' minutes' : '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">Auto-submit on timer end</span>
                            <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass((bool) $exam->auto_submit_on_timer_end) }}">{{ $exam->auto_submit_on_timer_end ? 'On' : 'Off' }}</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Schedule &amp; Attempts</h3>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">Availability</span>
                            <span class="font-semibold text-slate-900 dark:text-white text-right">{{ $labels['schedule'][$exam->schedule_type] ?? ($exam->schedule_type ? ucfirst(str_replace('_', ' ', $exam->schedule_type)) : 'Any Time') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">Start Window</span>
                            <span class="font-semibold text-slate-900 dark:text-white text-right">{{ $exam->scheduled_start?->format('M d, Y h:i A') ?? 'Any Time' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">End Window</span>
                            <span class="font-semibold text-slate-900 dark:text-white text-right">{{ $exam->scheduled_end?->format('M d, Y h:i A') ?? 'No Expiry' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">Attempt Limit</span>
                            <span class="font-semibold text-slate-900 dark:text-white text-right">
                                {{ $labels['attemptLimit'][$exam->attempt_limit_type] ?? ucfirst((string) ($exam->attempt_limit_type ?: 'once')) }}
                                @if ($exam->attempt_limit_type === 'fixed')
                                    ({{ (int) $exam->max_attempts }})
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 5. Format --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <header class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">4. Exam Format</h2>
                </header>
                <div class="flex flex-wrap gap-2">
                    @forelse ($formats as $format)
                        <span class="inline-flex items-center rounded-lg bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1.5 text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                            {{ $formatLabels[$format] ?? ucfirst(str_replace('_', ' ', $format)) }}
                        </span>
                    @empty
                        <span class="text-sm text-slate-400 italic">No formats selected</span>
                    @endforelse
                </div>
            </section>

            {{-- 5. Configuration --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                <header class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">5. Exam Configuration</h2>
                </header>

                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Total Questions</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ (int) $exam->total_questions }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Total Marks</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ (int) $exam->total_marks }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Passing Marks</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ (int) $exam->passing_marks }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Total Duration</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $exam->enable_exam_timer ? ((int) $exam->duration).' min' : '—' }}</dd>
                    </div>
                </dl>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Exam Parts ({{ $exam->parts->count() }})</h3>
                    <div class="space-y-4">
                        @forelse ($exam->parts as $part)
                            @php
                                $partCategoryNames = $part->selectedQuestionCategories->pluck('name')->filter()->values();
                                $partQuestionAllocations = is_array($part->extra_questions_allocations) ? $part->extra_questions_allocations : [];
                                $partMarksAllocations = is_array($part->extra_marks_allocations) ? $part->extra_marks_allocations : [];
                            @endphp
                            <div class="rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/30 p-4 space-y-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $part->name ?: 'Part #'.$part->sort_order }}</h4>
                                        @if ($part->is_default)
                                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">Default</span>
                                        @endif
                                    </div>
                                    <dl class="grid grid-cols-2 gap-3 text-xs text-right">
                                        <div>
                                            <dt class="text-slate-400">Questions</dt>
                                            <dd class="font-semibold text-slate-900 dark:text-white">{{ (int) $part->total_questions }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-400">Marks</dt>
                                            <dd class="font-semibold text-slate-900 dark:text-white">{{ (int) $part->total_marks }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                                    <div>
                                        <dt class="text-slate-500 dark:text-slate-400">Question Pool</dt>
                                        <dd class="mt-1"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass((bool) $part->use_question_pool) }}">{{ $part->use_question_pool ? 'On' : 'Off' }}</span></dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500 dark:text-slate-400">Maximum Questions in Pool</dt>
                                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $part->use_question_pool ? ((int) $part->maximum_questions ?: '—') : '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500 dark:text-slate-400">Paper Sets</dt>
                                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $part->fixed_paper_set ? ((int) $part->paper_sets ?: 1) : '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500 dark:text-slate-400">Distribution Type</dt>
                                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $labels['distribution'][$part->distribution_type] ?? ($part->distribution_type ? ucfirst(str_replace('_', ' ', $part->distribution_type)) : '—') }}</dd>
                                    </div>
                                </dl>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ([
                                        ['Fixed Questions', (bool) $part->fixed_questions],
                                        ['Fixed Paper Set', (bool) $part->fixed_paper_set],
                                        ['Fix Category Questions', (bool) $part->fix_category_questions],
                                        ['Fix Category Marks', (bool) $part->fix_category_marks],
                                        ['Shuffle Questions', (bool) $part->shuffle_questions],
                                        ['Shuffle Categories', (bool) $part->shuffle_categories],
                                        ['Shuffle Options', (bool) $part->shuffle_options],
                                    ] as [$label, $on])
                                        <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-white/70 dark:bg-slate-900/40 px-3 py-2 text-sm">
                                            <span class="text-slate-600 dark:text-slate-300">{{ $label }}</span>
                                            <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass($on) }}">{{ $on ? 'On' : 'Off' }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Selected Categories</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($partCategoryNames as $name)
                                            <span class="inline-flex items-center rounded-md bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-300">{{ $name }}</span>
                                        @empty
                                            <span class="text-sm text-slate-400 italic">No categories selected</span>
                                        @endforelse
                                    </div>
                                </div>

                                @if (count($partQuestionAllocations))
                                    @php
                                        $categoryNameLookup = $part->selectedQuestionCategories->pluck('name', 'id');
                                    @endphp
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Category Question Allocations</p>
                                        <ul class="space-y-1.5 text-sm">
                                            @foreach ($partQuestionAllocations as $key => $count)
                                                <li class="flex justify-between gap-3 rounded-lg bg-white/70 dark:bg-slate-900/40 px-3 py-2">
                                                    <span class="text-slate-600 dark:text-slate-300">{{ $categoryNameLookup[(int) $key] ?? (is_numeric($key) ? 'Category #'.$key : $key) }}</span>
                                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $count }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (count($partMarksAllocations))
                                    @php
                                        $categoryNameLookup = $part->selectedQuestionCategories->pluck('name', 'id');
                                    @endphp
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Category Marks Allocations</p>
                                        <ul class="space-y-1.5 text-sm">
                                            @foreach ($partMarksAllocations as $key => $count)
                                                <li class="flex justify-between gap-3 rounded-lg bg-white/70 dark:bg-slate-900/40 px-3 py-2">
                                                    <span class="text-slate-600 dark:text-slate-300">{{ $categoryNameLookup[(int) $key] ?? (is_numeric($key) ? 'Category #'.$key : $key) }}</span>
                                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $count }} pts</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-400 italic">No exam parts configured.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- 7. Question Rules --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <header class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">6. Question Rules &amp; Scoring</h2>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 dark:border-slate-800 px-4 py-3">
                        <span class="text-slate-600 dark:text-slate-300">Negative Marking</span>
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass((bool) $exam->enable_negative_marking) }}">{{ $exam->enable_negative_marking ? 'On' : 'Off' }}</span>
                    </div>
                    <div class="rounded-xl border border-slate-100 dark:border-slate-800 px-4 py-3">
                        <p class="text-slate-500 dark:text-slate-400 mb-1">Negative Penalty</p>
                        @if ($exam->enable_negative_marking)
                            <p class="font-semibold text-rose-600 dark:text-rose-400">
                                @if (filled($exam->negative_marking_type))
                                    -{{ rtrim(rtrim(number_format((float) $exam->negative_marking_type, 2, '.', ''), '0'), '.') }}% of question marks
                                @else
                                    -{{ $exam->negative_mark_per_question }} pts per question
                                @endif
                            </p>
                        @else
                            <p class="text-slate-400 italic">Disabled</p>
                        @endif
                    </div>
                </div>

                @if ($exam->parts->isNotEmpty())
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Per-Part Marking Rules</h3>
                        <div class="space-y-3">
                            @foreach ($exam->parts as $part)
                                @php
                                    $partMarksFilter = is_array($part->question_marks_filter) ? $part->question_marks_filter : [];
                                @endphp
                                <div class="rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/30 p-4 space-y-3 text-sm">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $part->name ?: 'Part #'.$part->sort_order }}</p>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-600 dark:text-slate-300">Fix Marks Each Question</span>
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass((bool) $part->fix_marks_each_question) }}">{{ $part->fix_marks_each_question ? 'On' : 'Off' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-slate-500 dark:text-slate-400 mb-2">Question Marks Filter</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse ($partMarksFilter as $mark)
                                                <span class="inline-flex rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $mark }} pts</span>
                                            @empty
                                                <span class="text-slate-400 italic">None</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- 8. Pricing --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <header class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">7. Pricing</h2>
                </header>

                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Pricing Option</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $labels['pricing'][$exam->pricing_option] ?? ($exam->pricing_option ? ucfirst(str_replace('_', ' ', $exam->pricing_option)) : '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Currency</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $exam->exam_currency ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Amount</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">
                            @if ($exam->pricing_option === 'paid' || filled($exam->exam_amount))
                                {{ $exam->exam_currency ?: '' }} {{ number_format((float) $exam->exam_amount, 2) }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>

                @if (count($selectedDiscounts) || count($customDiscounts))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Selected Discounts</p>
                            <ul class="space-y-1">
                                @forelse ($selectedDiscounts as $discount)
                                    <li class="rounded-lg bg-slate-50 dark:bg-slate-950/30 px-3 py-2 text-slate-700 dark:text-slate-300">
                                        {{ $labels['discount'][$discount] ?? (is_string($discount) ? ucfirst(str_replace('_', ' ', $discount)) : json_encode($discount)) }}
                                    </li>
                                @empty
                                    <li class="text-slate-400 italic">None</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Custom Discount Offers</p>
                            <ul class="space-y-1">
                                @forelse ($customDiscounts as $offer)
                                    <li class="rounded-lg bg-slate-50 dark:bg-slate-950/30 px-3 py-2 text-slate-700 dark:text-slate-300">
                                        {{ is_array($offer) ? (($offer['name'] ?? 'Offer').(isset($offer['percentage']) ? ' ('.$offer['percentage'].'%)' : '')) : $offer }}
                                    </li>
                                @empty
                                    <li class="text-slate-400 italic">None</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                @endif

                @if (count($freeImported) || count($freeManual))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="rounded-xl border border-slate-100 dark:border-slate-800 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Free Imported Candidates</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white">{{ count($freeImported) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 dark:border-slate-800 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Free Manual Emails</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white">{{ count($freeManual) }}</p>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Instructions --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <header class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">8. Candidate Instructions</h2>
                </header>

                @if (count($instructionRules))
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Predefined Rules</h3>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($instructionRules as $ruleId)
                                <span class="inline-flex rounded-md bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-800 dark:text-amber-300">
                                    {{ $labels['instructionRules'][$ruleId] ?? $ruleId }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="rounded-xl border border-amber-200 bg-amber-50/80 dark:border-amber-500/30 dark:bg-amber-500/10 px-4 py-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300 mb-1">Warnings allowed</h3>
                    <p class="text-sm text-amber-900 dark:text-amber-100">
                        <span class="font-semibold text-lg">{{ (int) ($exam->proctoringPolicy?->focus_violation_limit ?? 3) }}</span>
                        monitored warning{{ (int) ($exam->proctoringPolicy?->focus_violation_limit ?? 3) === 1 ? '' : 's' }} before auto-submit
                        @if ((int) ($exam->proctoringPolicy?->focus_violation_limit ?? 3) === 0)
                            <span class="block text-xs mt-1 opacity-90">0 means the first counting violation auto-submits the exam.</span>
                        @endif
                    </p>
                </div>

                @if ($hasHtml($exam->instructions))
                    <x-rich-text-content :content="$exam->instructions" class="text-sm leading-relaxed text-slate-800 dark:text-slate-100" />
                @else
                    <p class="text-sm text-slate-400 italic">No instructions added yet.</p>
                @endif
            </section>

            {{-- Linked question distribution --}}
            <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Difficulty Mix</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($difficultyDistribution as $label => $count)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-slate-600 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $label)) }}</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-slate-400 italic text-sm">No linked questions</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Question Types</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($typeDistribution as $label => $count)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-slate-600 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $label)) }}</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-slate-400 italic text-sm">No linked questions</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Marks Mix</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($marksDistribution as $label => $count)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-slate-600 dark:text-slate-300">{{ $label }} pts</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-slate-400 italic text-sm">No linked questions</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            {{-- Linked Questions --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-200/60 dark:border-slate-800 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        9. Question Bank ({{ $questions->count() }})
                    </h2>
                    <span class="text-xs bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 font-bold px-2.5 py-1 rounded-full border border-indigo-100 dark:border-indigo-500/20">
                        {{ rtrim(rtrim(number_format($linkedMarks, 2, '.', ''), '0'), '.') }} Total Marks
                    </span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[520px] overflow-y-auto">
                    @forelse ($questions as $q)
                        <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50/50 dark:hover:bg-slate-950/20 transition">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-950 px-2 py-1 rounded border border-slate-200 dark:border-slate-800 shrink-0">
                                #{{ $q->id }}
                            </span>
                            <div class="flex-1 text-sm min-w-0">
                                <div class="text-slate-800 dark:text-slate-200 line-clamp-3 leading-relaxed font-medium">
                                    {{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $q->body)), 180) }}
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                    <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 text-slate-500">
                                        {{ ucfirst(str_replace('_', ' ', (string) $q->type)) }}
                                    </span>
                                    <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 text-slate-500">
                                        {{ ucfirst((string) $q->difficulty) }}
                                    </span>
                                    @if ($q->category?->name)
                                        <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 text-slate-500">
                                            {{ $q->category->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                    {{ $q->pivot->marks_override ?? $q->marks }} pts
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-slate-500 dark:text-slate-500 italic text-sm">
                            No questions linked to this exam workspace.
                            @if (! $hasAnyFixedQuestions)
                                <span class="block mt-1 not-italic text-slate-400">Dynamic selection may assign questions per candidate at attempt time.</span>
                            @endif
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Sidebar --}}
        <aside class="xl:col-span-4 space-y-6">
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    Overall Exam Summary
                </h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Parts</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $exam->parts->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Questions</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ (int) $exam->total_questions }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Marks</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ (int) $exam->total_marks }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Passing</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ (int) $exam->passing_marks }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Duration</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $exam->enable_exam_timer ? ((int) $exam->duration).' min' : 'Off' }}</dd>
                    </div>
                </dl>
                @if ($exam->parts->isNotEmpty())
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-2">
                        @foreach ($exam->parts as $summaryPart)
                            <div class="rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/30 px-3 py-2.5">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $summaryPart->name ?: 'Part #'.($summaryPart->sort_order + 1) }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ (int) $summaryPart->total_questions }} Q · {{ (int) $summaryPart->total_marks }} marks
                                    · {{ $summaryPart->selectedQuestionCategories->count() }} categor{{ $summaryPart->selectedQuestionCategories->count() === 1 ? 'y' : 'ies' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    Attempt Statistics
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 dark:bg-slate-950/20 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">Total Attempts</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ $stats['total'] }}</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-950/20 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">Passed Rate</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-slate-100">
                            {{ $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100) : 0 }}%
                        </span>
                    </div>
                    <div class="col-span-2 bg-slate-50 dark:bg-slate-950/20 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">Average Score</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ number_format($stats['avg_score'], 1) }} pts</span>
                    </div>
                </div>
            </section>

            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-3">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    SEO &amp; Metadata
                </h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Slug</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200 break-all">{{ $exam->slug ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Meta Title</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">{{ $exam->meta_title ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Meta Description</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">{{ $exam->meta_description ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Meta Keywords</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">{{ $exam->meta_keywords ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Canonical URL</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200 break-all">{{ $exam->canonical_url ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">OG Title</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">{{ $exam->og_title ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">OG Description</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">{{ $exam->og_description ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Robots</dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">{{ $exam->robots ?: 'index,follow' }}</dd>
                    </div>
                    @if ($exam->ogImage)
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400 mb-1">OG Image</dt>
                            <dd>
                                <img src="{{ $exam->ogImage->file_url }}" alt="" class="w-full rounded-lg border border-slate-200 dark:border-slate-700">
                            </dd>
                        </div>
                    @endif
                    @if (filled($exam->schema_markup))
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400 mb-1">Schema Markup</dt>
                            <dd>
                                <pre class="text-xs overflow-x-auto rounded-lg bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800 p-3 text-slate-700 dark:text-slate-300">{{ $exam->schema_markup }}</pre>
                            </dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <span class="text-slate-500 dark:text-slate-400">Create with AI</span>
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass((bool) $exam->ai_generated) }}">{{ $exam->ai_generated ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500 dark:text-slate-400">Improve with AI</span>
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $flagClass((bool) $exam->ai_improve) }}">{{ $exam->ai_improve ? 'Yes' : 'No' }}</span>
                    </div>
                </dl>
            </section>

            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-3">
                <h2 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider pb-1.5 border-b border-slate-100 dark:border-slate-800">
                    Audit
                </h2>
                <div class="flex items-center justify-between text-sm gap-3">
                    <span class="text-slate-500 dark:text-slate-400">Created by</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $exam->createdBy?->name ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm gap-3">
                    <span class="text-slate-500 dark:text-slate-400">Created</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $exam->created_at?->format('M d, Y h:i A') ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm gap-3">
                    <span class="text-slate-500 dark:text-slate-400">Updated</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $exam->updated_at?->format('M d, Y h:i A') ?? '—' }}</span>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
@endpush

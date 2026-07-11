@extends('backend.layouts.app')

@section('title', 'Edit Exam — ' . $exam->title)
@section('page-title', 'Edit Exam')
@section('content-container-class', 'max-w-none')

@php
    $selectedQuestions = old('question_ids', $exam->questions->pluck('id')->all());
@endphp

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Exams', 'url' => route('admin.exams.index')],
        ['label' => $exam->title],
        ['label' => 'Edit'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Exam #{{ $exam->id }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                        Editing
                    </span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ $exam->title }}</h1>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.exams.show', $exam) }}" class="panel-button-secondary">View Exam</a>
            <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">Back to List</a>
        </div>
    </div>

    {{-- Form --}}
    <x-page-card class="overflow-hidden">
        <form action="{{ route('admin.exams.update', $exam) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            {{-- Server Validation Errors --}}
            @if ($errors->any())
                <div class="bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-rose-700 dark:text-rose-400 mb-2">Please correct the following errors:</h3>
                    <ul class="list-disc list-inside text-sm text-rose-600 dark:text-rose-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Section 1: Basic Information ──────────────────────────────── --}}
            <section>
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2 mb-5">
                    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-200">1. Basic Information</h2>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">Update the exam identity and visibility settings.</p>
                </div>
                <div class="space-y-5">
                    <div>
                        <label for="edit_title" class="exam-label">Title <span class="form-required">*</span></label>
                        <input id="edit_title" name="title" type="text" class="panel-input"
                               value="{{ old('title', $exam->title) }}"
                               placeholder="e.g. Senior Laravel Assessment – June 2026">
                    </div>
                    <div>
                        <label for="edit_description" class="exam-label">Description</label>
                        <textarea id="edit_description" name="description" rows="4" class="panel-input"
                                  placeholder="Summarize scope, audience, and expected outcomes.">{{ old('description', $exam->description) }}</textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {{-- Category --}}
                        <div>
                            <label for="edit_category_id" class="exam-label">Exam Category</label>
                            <select id="edit_category_id" name="category_id" class="panel-input">
                                <option value="">— None —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $exam->category_id) == $cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                    @foreach($cat->children as $child)
                                        <option value="{{ $child->id }}" @selected(old('category_id', $exam->category_id) == $child->id)>
                                            &nbsp;&nbsp;— {{ $child->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label for="edit_status" class="exam-label">Status <span class="form-required">*</span></label>
                            <select id="edit_status" name="status" class="panel-input">
                                @foreach(['draft', 'published', 'active', 'inactive', 'suspended'] as $s)
                                    <option value="{{ $s }}" @selected(old('status', $exam->status) === $s)>
                                        {{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Exam Mode --}}
                        <div>
                            <label for="edit_exam_mode" class="exam-label">Exam Mode <span class="form-required">*</span></label>
                            <select id="edit_exam_mode" name="exam_mode" class="panel-input">
                                @foreach(['standard', 'practice', 'proctored'] as $m)
                                    <option value="{{ $m }}" @selected(old('exam_mode', $exam->exam_mode) === $m)>
                                        {{ ucfirst($m) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Exam Format --}}
                        <div>
                            <label for="edit_exam_format" class="exam-label">Exam Format <span class="form-required">*</span></label>
                            <select id="edit_exam_format" name="exam_format" class="panel-input">
                                @foreach(['mcq' => 'MCQ', 'written' => 'Written', 'multi_select' => 'Multi Select', 'mixed' => 'Mixed'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('exam_format', $exam->exam_format) === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Visibility --}}
                        <div>
                            <label for="edit_visibility" class="exam-label">Visibility <span class="form-required">*</span></label>
                            <select id="edit_visibility" name="visibility" class="panel-input">
                                @foreach(['public' => 'Public', 'private' => 'Private', 'invite_only' => 'Invite Only'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('visibility', $exam->visibility) === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Difficulty --}}
                        <div>
                            <label for="edit_difficulty" class="exam-label">Difficulty Level</label>
                            <select id="edit_difficulty" name="difficulty_level" class="panel-input">
                                <option value="">— Any —</option>
                                @foreach(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('difficulty_level', $exam->difficulty_level) === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Section 2: Timer & Duration ──────────────────────────────── --}}
            <section>
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2 mb-5">
                    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-200">2. Timer & Duration</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="edit_duration" class="exam-label">Duration (Minutes) <span class="form-required">*</span></label>
                        <input id="edit_duration" name="duration" type="number" class="panel-input"
                               min="1" value="{{ old('duration', $exam->duration) }}">
                    </div>
                    <div class="flex flex-col gap-2 justify-center">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="hidden" name="enable_exam_timer" value="0">
                            <input type="checkbox" name="enable_exam_timer" value="1"
                                   @checked(old('enable_exam_timer', $exam->enable_exam_timer))
                                   class="rounded border-slate-300 dark:border-slate-600">
                            <span>Enable Exam Timer</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="hidden" name="auto_submit_on_timer_end" value="0">
                            <input type="checkbox" name="auto_submit_on_timer_end" value="1"
                                   @checked(old('auto_submit_on_timer_end', $exam->auto_submit_on_timer_end))
                                   class="rounded border-slate-300 dark:border-slate-600">
                            <span>Auto-submit when timer ends</span>
                        </label>
                    </div>
                </div>
            </section>

            {{-- ── Section 3: Schedule & Attempts ───────────────────────────── --}}
            <section>
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2 mb-5">
                    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-200">3. Schedule & Attempt Limits</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="edit_scheduled_start" class="exam-label">Scheduled Start</label>
                        <input id="edit_scheduled_start" name="scheduled_start" type="datetime-local" class="panel-input"
                               value="{{ old('scheduled_start', optional($exam->scheduled_start)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div>
                        <label for="edit_scheduled_end" class="exam-label">Scheduled End</label>
                        <input id="edit_scheduled_end" name="scheduled_end" type="datetime-local" class="panel-input"
                               value="{{ old('scheduled_end', optional($exam->scheduled_end)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div>
                        <label for="edit_max_attempts" class="exam-label">Max Attempts</label>
                        <input id="edit_max_attempts" name="max_attempts" type="number" class="panel-input"
                               min="0" value="{{ old('max_attempts', $exam->max_attempts) }}"
                               placeholder="0 = Unlimited">
                    </div>
                    <div>
                        <label for="edit_pass_percentage" class="exam-label">Pass Percentage</label>
                        <input id="edit_pass_percentage" name="pass_percentage" type="number" class="panel-input"
                               min="0" max="100" step="0.01" value="{{ old('pass_percentage', $exam->pass_percentage) }}">
                    </div>
                </div>
            </section>

            {{-- ── Section 4: Scoring ────────────────────────────────────────── --}}
            <section>
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2 mb-5">
                    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-200">4. Scoring & Negative Marking</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="edit_total_marks" class="exam-label">Total Marks</label>
                        <input id="edit_total_marks" name="total_marks" type="number" class="panel-input"
                               min="1" value="{{ old('total_marks', $exam->total_marks) }}">
                    </div>
                    <div>
                        <label for="edit_passing_marks" class="exam-label">Passing Marks</label>
                        <input id="edit_passing_marks" name="passing_marks" type="number" class="panel-input"
                               min="0" value="{{ old('passing_marks', $exam->passing_marks) }}">
                    </div>
                    <div>
                        <label for="edit_negative_mark" class="exam-label">Negative Mark / Q</label>
                        <input id="edit_negative_mark" name="negative_mark_per_question" type="number" class="panel-input"
                               step="0.0001" min="0" value="{{ old('negative_mark_per_question', $exam->negative_mark_per_question) }}">
                    </div>
                    <div class="flex flex-col gap-2 justify-center">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="hidden" name="enable_negative_marking" value="0">
                            <input type="checkbox" name="enable_negative_marking" value="1"
                                   @checked(old('enable_negative_marking', $exam->enable_negative_marking))
                                   class="rounded border-slate-300 dark:border-slate-600">
                            <span>Enable Negative Marking</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="hidden" name="fix_marks_each_question" value="0">
                            <input type="checkbox" name="fix_marks_each_question" value="1"
                                   @checked(old('fix_marks_each_question', $exam->fix_marks_each_question))
                                   class="rounded border-slate-300 dark:border-slate-600">
                            <span>Fix Marks Each Question</span>
                        </label>
                    </div>
                </div>
            </section>

            {{-- ── Section 5: Shuffle Settings ──────────────────────────────── --}}
            <section>
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2 mb-5">
                    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-200">5. Shuffle Settings</h2>
                </div>
                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="hidden" name="shuffle_questions" value="0">
                        <input type="checkbox" name="shuffle_questions" value="1"
                               @checked(old('shuffle_questions', $exam->shuffle_questions))
                               class="rounded border-slate-300 dark:border-slate-600">
                        <span>Shuffle Questions</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="hidden" name="shuffle_options" value="0">
                        <input type="checkbox" name="shuffle_options" value="1"
                               @checked(old('shuffle_options', $exam->shuffle_options))
                               class="rounded border-slate-300 dark:border-slate-600">
                        <span>Shuffle Options</span>
                    </label>
                </div>
            </section>

            {{-- ── Section 6: Linked Questions ──────────────────────────────── --}}
            <section>
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2 mb-5">
                    <h2 class="text-base font-semibold text-slate-800 dark:text-slate-200">6. Linked Questions</h2>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">Hold Ctrl / ⌘ to select multiple questions.</p>
                </div>
                <select name="question_ids[]" multiple size="14" class="panel-input font-mono text-xs w-full">
                    @foreach ($questions as $question)
                        <option value="{{ $question->id }}" @selected(in_array($question->id, $selectedQuestions))>
                            #{{ $question->id }} — {{ \Illuminate\Support\Str::limit(strip_tags($question->body), 100) }}
                        </option>
                    @endforeach
                </select>
            </section>

            {{-- ── Footer ───────────────────────────────────────────────────── --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('admin.exams.show', $exam) }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary">Update Exam</button>
            </div>
        </form>
    </x-page-card>
</div>
@endsection

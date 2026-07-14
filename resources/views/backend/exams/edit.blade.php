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
                        <x-rich-text-editor
                            label="Description"
                            input-id="edit_description"
                            name="description"
                            :value="old('description', $exam->description)"
                            placeholder="Summarize scope, audience, and expected outcomes."
                            :height="240"
                            preset="full"
                        />
                    </div>
                    <div class="space-y-3">
                        <label class="exam-label">Candidate Instructions</label>
                        @php
                            $instructionTemplates = $formOptions['instructionTemplates'] ?? [];
                            $instructionRules = $formOptions['instructionRules'] ?? [];
                            $selectedRules = old('predefined_instruction_rules');
                            if ($selectedRules === null) {
                                $selectedRules = is_array($exam->predefined_instruction_rules)
                                    ? $exam->predefined_instruction_rules
                                    : collect($instructionRules)
                                        ->filter(fn ($rule) => ! empty($rule['is_default']) || ! empty($rule['is_required']))
                                        ->pluck('id')
                                        ->all();
                            }
                        @endphp
                        @if(count($instructionTemplates))
                            <div class="flex flex-wrap items-end gap-2">
                                <div class="flex-1 min-w-[14rem]">
                                    <label for="edit_instruction_template" class="exam-label">Instruction Template</label>
                                    <select id="edit_instruction_template" class="panel-input">
                                        <option value="">Choose template</option>
                                        @foreach($instructionTemplates as $template)
                                            <option value="{{ $template['id'] }}">
                                                {{ $template['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" id="edit-apply-instruction-template" class="panel-button-secondary">Apply Template</button>
                            </div>
                        @endif
                        <x-rich-text-editor
                            input-id="edit_instructions"
                            name="instructions"
                            :value="old('instructions', $exam->instructions)"
                            placeholder="Provide instructions to candidates before they start."
                            :height="320"
                            preset="full"
                            help="Select a template to load structured candidate instructions into the editor."
                        />
                    </div>

                    @if(count($instructionRules))
                        <div class="space-y-2">
                            <label class="exam-label">Exam Instructions &amp; Rules</label>
                            <p class="exam-help">Enable the rules that apply to this exam session.</p>
                            <div class="grid gap-2 md:grid-cols-2">
                                @foreach($instructionRules as $rule)
                                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 dark:border-slate-700 p-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="predefined_instruction_rules[]"
                                            value="{{ $rule['id'] }}"
                                            class="mt-1"
                                            @checked(in_array($rule['id'], $selectedRules, true))
                                            @disabled(!empty($rule['is_required']))
                                        >
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $rule['label'] }}</span>
                                            <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $rule['description'] }}</span>
                                        </span>
                                        @if(!empty($rule['is_required']))
                                            <input type="hidden" name="predefined_instruction_rules[]" value="{{ $rule['id'] }}">
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {{-- Category --}}
                        <div>
                            <label for="edit_category_id" class="exam-label">Exam Category</label>
                            <select id="edit_category_id" name="category_id" class="mt-1 block w-full">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        data-level="{{ $cat->depth }}"
                                        data-category-name="{{ $cat->name }}"
                                        class="{{ $cat->depth === 0 ? 'font-semibold text-slate-900' : '' }}"
                                        @selected(old('category_id', $exam->category_id) == $cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label for="edit_status" class="exam-label">Status <span class="form-required">*</span></label>
                            <select id="edit_status" name="status" class="panel-input">
                                @foreach(\App\Support\ExamFormOptions::statusLabels() as $val => $label)
                                    <option value="{{ $val }}" @selected(old('status', $exam->status) === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Exam Mode --}}
                        <div>
                            <label for="edit_exam_mode" class="exam-label">Exam Mode <span class="form-required">*</span></label>
                            <select id="edit_exam_mode" name="exam_mode" class="panel-input">
                                @foreach(\App\Support\ExamFormOptions::modeLabels() as $val => $label)
                                    <option value="{{ $val }}" @selected(old('exam_mode', $exam->exam_mode) === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Exam Format --}}
                        <div>
                            <label for="edit_exam_format" class="exam-label">Exam Format <span class="form-required">*</span></label>
                            <select id="edit_exam_format" name="exam_format[]" class="panel-input" multiple data-placeholder="Select format(s)">
                                @php
                                    $currentFormat = old('exam_format', is_array($exam->exam_format) ? $exam->exam_format : (json_decode($exam->exam_format, true) ?: [$exam->exam_format]));
                                @endphp
                                @foreach(\App\Support\ExamFormOptions::formatLabels() as $val => $label)
                                    <option value="{{ $val }}" @selected(in_array($val, $currentFormat))>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Visibility --}}
                        <div>
                            <label for="edit_visibility" class="exam-label">Visibility <span class="form-required">*</span></label>
                            <select id="edit_visibility" name="visibility" class="panel-input">
                                @foreach(\App\Support\ExamFormOptions::visibilityLabels() as $val => $label)
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
                                @foreach(\App\Support\ExamFormOptions::difficultyLabels() as $val => $label)
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

            {{-- ═══════════════════════════════════════════════════════════════
            SEO & METADATA SECTION (Identical across all Create & Edit views)
            ════════════════════════════════════════════════════════════════ --}}
            @php
                $seoItem = $exam ?? null;
            @endphp
            <div id="metadata-section" class="category-builder__metadata" style="margin-top: 2rem; margin-bottom: 2rem;">
                <div class="qcat-meta-header" id="meta-accordion-toggle" role="button" aria-expanded="false" tabindex="0">
                    <div class="qcat-meta-header-left">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="qcat-meta-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="qcat-meta-title">SEO &amp; Metadata</span>
                        <span class="qcat-meta-badge">Optional</span>
                    </div>
                    <svg class="qcat-meta-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <div id="meta-accordion-body" class="qcat-meta-body hidden pt-4 border-t border-slate-200/80 dark:border-slate-800">
                    <p class="qcat-meta-hint mb-4">Add SEO keywords, meta details, and titles to index this content properly.</p>

                    <!-- Row 1: AI Toggles -->
                    <div class="qcat-seo-row qcat-seo-row--toggles">
                        {{-- Create with AI --}}
                        <div class="qcat-seo-col col-lg-4">
                            <label class="qcat-ai-toggle-label" for="toggle-ai-create">
                                <input type="hidden" name="ai_generated" value="0">
                                <input type="checkbox" name="ai_generated" id="toggle-ai-create" value="1"
                                    class="qcat-ai-checkbox" @checked(old('ai_generated', $seoItem?->ai_generated ?? false))>
                                <span class="qcat-ai-toggle-wrap">
                                    <span class="qcat-ai-thumb"></span>
                                </span>
                                <span class="qcat-ai-text">
                                    <span class="qcat-ai-title">Create with AI</span>
                                    <span class="qcat-ai-hint">Let AI generate details automatically</span>
                                </span>
                            </label>
                        </div>

                        {{-- Improve with AI --}}
                        <div class="qcat-seo-col col-lg-4" id="improve-with-ai-wrapper">
                            <label class="qcat-ai-toggle-label" for="toggle-ai-improve">
                                <input type="hidden" name="ai_improve" value="0">
                                <input type="checkbox" name="ai_improve" id="toggle-ai-improve" value="1"
                                    class="qcat-ai-checkbox" @checked(old('ai_improve', $seoItem?->ai_improve ?? false))>
                                <span class="qcat-ai-toggle-wrap">
                                    <span class="qcat-ai-thumb"></span>
                                </span>
                                <span class="qcat-ai-text">
                                    <span class="qcat-ai-title">Improve with AI</span>
                                    <span class="qcat-ai-hint">Queue for AI improvement</span>
                                </span>
                            </label>
                        </div>

                        {{-- Empty column for layout balance --}}
                        <div class="qcat-seo-col col-lg-4"></div>
                    </div>

                    <!-- Manual SEO Fields Wrapper -->
                    <div id="manual-seo-fields-wrapper" class="space-y-4">
                        <!-- Row 2: Meta Title, Slug, OG Title (col-lg-4 each) -->
                        <div class="qcat-seo-row qcat-seo-row--three-cols">
                            {{-- Meta Title --}}
                            <div class="qcat-meta-field col-lg-4">
                                <label class="qcat-meta-label" for="meta-title">Meta Title</label>
                                <input type="text" id="meta-title" name="meta_title" value="{{ old('meta_title', $seoItem?->meta_title ?? '') }}" placeholder="e.g. Meta Title" class="panel-input qcat-meta-input">
                                <span class="qcat-meta-count" data-max="255">0 / 255</span>
                                @error('meta_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Slug --}}
                            <div class="qcat-meta-field col-lg-4">
                                <label class="qcat-meta-label" for="meta-slug">Slug</label>
                                <input type="text" id="meta-slug" name="slug" value="{{ old('slug', $seoItem?->slug ?? '') }}" placeholder="e.g. slug-value" class="panel-input qcat-meta-input">
                                @error('slug')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- OG Title --}}
                            <div class="qcat-meta-field col-lg-4">
                                <label class="qcat-meta-label" for="meta-og-title">OG Title</label>
                                <input type="text" id="meta-og-title" name="og_title" value="{{ old('og_title', $seoItem?->og_title ?? '') }}" placeholder="e.g. Open Graph Title" class="panel-input qcat-meta-input">
                                @error('og_title')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Row 3: Meta Description, OG Description (col-lg-6 each) -->
                        <div class="qcat-seo-row qcat-seo-row--two-cols">
                            {{-- Meta Description --}}
                            <div class="qcat-meta-field col-lg-6">
                                <label class="qcat-meta-label" for="meta-desc">Meta Description</label>
                                <textarea id="meta-desc" name="meta_description" rows="2" placeholder="Brief description for search engines (up to 500 characters)" class="panel-input qcat-meta-textarea">{{ old('meta_description', $seoItem?->meta_description ?? '') }}</textarea>
                                <span class="qcat-meta-count" data-max="500">0 / 500</span>
                                @error('meta_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- OG Description --}}
                            <div class="qcat-meta-field col-lg-6">
                                <label class="qcat-meta-label" for="meta-og-desc">OG Description</label>
                                <textarea id="meta-og-desc" name="og_description" rows="2" placeholder="Open Graph Description" class="panel-input qcat-meta-textarea">{{ old('og_description', $seoItem?->og_description ?? '') }}</textarea>
                                @error('og_description')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Row 4: Meta Keywords, Canonical URL (col-lg-6 each) -->
                        <div class="qcat-seo-row qcat-seo-row--two-cols">
                            {{-- Meta Keywords --}}
                            <div class="qcat-meta-field col-lg-6">
                                <label class="qcat-meta-label" for="meta-keywords">Meta Keywords</label>
                                <input type="text" id="meta-keywords" name="meta_keywords" value="{{ old('meta_keywords', $seoItem?->meta_keywords ?? '') }}" placeholder="keywords, comma, separated" class="panel-input qcat-meta-input">
                                @error('meta_keywords')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>

                            {{-- Canonical URL --}}
                            <div class="qcat-meta-field col-lg-6">
                                <label class="qcat-meta-label" for="meta-canonical">Canonical URL</label>
                                <input type="url" id="meta-canonical" name="canonical_url" value="{{ old('canonical_url', $seoItem?->canonical_url ?? '') }}" placeholder="https://example.com/canonical" class="panel-input qcat-meta-input">
                                @error('canonical_url')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Footer ───────────────────────────────────────────────────── --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('admin.exams.show', $exam) }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary">Update Exam</button>
            </div>
        </form>
    </x-page-card>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <style>
        .ts-wrapper.panel-input {
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
        }

        #edit_category_id + .ts-wrapper .ts-control {
            border-radius: var(--field-radius, 0.82rem) !important;
            min-height: var(--field-height, 2.75rem) !important;
            display: flex;
            align-items: center;
        }

        .dark #edit_category_id + .ts-wrapper .ts-control .item,
        .dark #edit_category_id + .ts-wrapper .ts-control input {
            color: #f8fafc !important;
        }

        .info-tip {
            position: relative;
        }
        .info-tip:hover {
            z-index: 99999 !important;
        }
        .info-tip::before {
            left: 0.5rem !important;
            transform: translateY(2px) !important;
        }
        .info-tip::after {
            left: 0 !important;
            transform: translateY(2px) !important;
        }
        .info-tip:hover::before,
        .info-tip:focus-visible::before {
            transform: translateY(0) !important;
        }
        .info-tip:hover::after,
        .info-tip:focus-visible::after {
            transform: translateY(0) !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/components/select.js') }}"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}"></script>
    <script>
        window.examEditInstructionTemplates = @json(
            collect($formOptions['instructionTemplates'] ?? [])
                ->mapWithKeys(fn ($template) => [$template['id'] => $template['content'] ?? ''])
        );

        document.addEventListener('DOMContentLoaded', async function() {
            if (window.EmsRichTextEditor?.initAll) {
                await window.EmsRichTextEditor.initAll(document);
            }

            if (window.EmsSelect) {
                window.EmsSelect.initAll(
                    document,
                    'select.panel-input:not(#edit_category_id)'
                );
            }

            const categorySelect = window.EmsTomSelectHierarchy?.create('#edit_category_id', {
                placeholder: "Search for a category...",
            }) || new TomSelect('#edit_category_id', {
                create: false,
                placeholder: "Search for a category...",
                closeAfterSelect: true,
            });
            window.EmsTomSelectBlur?.attach(categorySelect);
            window.EmsTomSelectBlur?.blurNativeSelects(document.querySelector('form') || document);

            const applyBtn = document.getElementById('edit-apply-instruction-template');
            const templateSelect = document.getElementById('edit_instruction_template');
            applyBtn?.addEventListener('click', () => {
                if (!templateSelect) return;
                const templateId = templateSelect.value;
                const content = window.examEditInstructionTemplates?.[templateId] || '';
                if (!content) return;
                const adapter = window.EmsRichTextEditor?.get('edit_instructions');
                if (adapter) {
                    adapter.setData(content);
                } else {
                    const field = document.getElementById('edit_instructions');
                    if (field) {
                        field.value = content;
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });

            document.querySelector('form')?.addEventListener('submit', () => {
                window.EmsRichTextEditor?.syncAll();
            });
        });
    </script>
@endpush

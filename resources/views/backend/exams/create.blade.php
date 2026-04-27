@extends('backend.layouts.app')

@section('title', 'Create Exam')
@section('page-title', 'Create Exam')
@section('content-container-class', 'max-w-none')

@php
    $realCategories = $categories->values();
    $realCategoryNames = $realCategories->pluck('name', 'id');

    $realQuestions = $questions->map(function ($question) use ($realCategoryNames) {
        return [
            'id'            => (int) $question->id,
            'body'          => (string) $question->body,
            'category_id'   => $question->category_id ? (int) $question->category_id : null,
            'category_name' => $realCategoryNames[$question->category_id] ?? 'Uncategorized',
            'marks'         => (int) ($question->marks ?? 1),
            'difficulty'    => (string) ($question->difficulty ?? 'medium'),
            'type'          => (string) ($question->type ?? 'mcq'),
        ];
    })->values();

    $demoQuestions = collect([
        ['id'=>9001,'body'=>'Explain the difference between RESTful and RPC-style API design in production systems.','category_id'=>301,'category_name'=>'Web Development','marks'=>5,'difficulty'=>'medium','type'=>'short_answer'],
        ['id'=>9002,'body'=>'In a queue implemented with arrays, what is the time complexity of dequeue in the naive approach?','category_id'=>302,'category_name'=>'Data Structures','marks'=>2,'difficulty'=>'easy','type'=>'mcq'],
        ['id'=>9003,'body'=>'State whether this claim is true or false: TLS only secures data at rest.','category_id'=>303,'category_name'=>'Networking','marks'=>1,'difficulty'=>'easy','type'=>'true_false'],
        ['id'=>9004,'body'=>'A payment service handles 1,200 requests/sec. Which scaling strategy best reduces single-region failure impact?','category_id'=>304,'category_name'=>'System Design','marks'=>4,'difficulty'=>'hard','type'=>'mcq'],
        ['id'=>9005,'body'=>'Write a short explanation of eventual consistency and where it is acceptable in enterprise products.','category_id'=>304,'category_name'=>'System Design','marks'=>6,'difficulty'=>'medium','type'=>'short_answer'],
        ['id'=>9006,'body'=>'Which HTTP status code is most appropriate when a user is authenticated but lacks permission to access a resource?','category_id'=>301,'category_name'=>'Web Development','marks'=>2,'difficulty'=>'easy','type'=>'mcq'],
    ]);

    $questionBank             = $realQuestions->isNotEmpty() ? $realQuestions : $demoQuestions;
    $isDemoQuestionBank       = $realQuestions->isEmpty();
    $selectedQuestionIds      = collect(old('question_ids', []))->map(fn ($v) => (string) $v)->all();
    $questionFilterCategories = $questionBank
        ->map(fn ($q) => ['id' => (string) ($q['category_id'] ?? 'none'), 'name' => (string) ($q['category_name'] ?? 'Uncategorized')])
        ->unique('id')
        ->values();
@endphp

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin',  'url' => route('admin.dashboard')],
        ['label' => 'Exams',  'url' => route('admin.exams.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
<div class="w-full">
    <x-page-card class="exam-builder-card overflow-hidden">
        <form action="{{ route('admin.exams.store') }}" method="POST" id="exam-create-form" class="exam-builder">
            @csrf

            {{-- ── Header ──────────────────────────────────────── --}}
            <div class="exam-builder__header">
                <div>
                    <h1 class="exam-builder__title">Create exam configuration</h1>
                    <p class="exam-builder__subtitle">
                        Configure exam rules, schedule windows, and question selection from one workspace.
                    </p>
                </div>
            </div>

            {{-- ── Body (main + aside) ──────────────────────────── --}}
            <div class="exam-builder__body">
                <div class="exam-builder__main space-y-6">

                    {{-- ─ Section 1 · Identity ─ --}}
                    <section class="exam-block">
                        <div class="exam-block__head">
                            <h2>Basic details</h2>
                            <p>Name, classify, and describe this exam for filtering and reporting.</p>
                        </div>

                        <div class="exam-block__content space-y-4">

                            {{-- 4-column meta strip (mirrors question form) --}}
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                                {{-- Category --}}
                                <div>
                                    <label for="category_id" class="exam-label">
                                        Category <span class="form-required">*</span>
                                    </label>
                                    <select id="category_id" name="category_id" class="panel-input mt-1 block w-full" data-summary-field="category">
                                        <option value="">Search or select…</option>
                                        <option value="1" class="font-semibold">Science</option>
                                        <option value="2">&nbsp;&nbsp;&nbsp;&nbsp;Physics</option>
                                        <option value="3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Classical Mechanics</option>
                                        <option value="4">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Quantum Physics</option>
                                        <option value="6">&nbsp;&nbsp;&nbsp;&nbsp;Biology</option>
                                        <option value="8">&nbsp;&nbsp;&nbsp;&nbsp;Chemistry</option>
                                        <option value="9" class="font-semibold">Mathematics</option>
                                        <option value="10">&nbsp;&nbsp;&nbsp;&nbsp;Algebra</option>
                                        <option value="11">&nbsp;&nbsp;&nbsp;&nbsp;Geometry</option>
                                        <option value="12">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Trigonometry</option>
                                        <option value="14" class="font-semibold">Computer Science</option>
                                        <option value="15">&nbsp;&nbsp;&nbsp;&nbsp;Programming</option>
                                        <option value="16">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Web Development</option>
                                        <option value="17">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Data Structures</option>
                                        @foreach ($realCategories as $cat)
                                            <option value="{{ $cat->id }}" @selected((string) old('category_id') === (string) $cat->id)>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="form-field-error @error('category_id') is-visible @enderror" data-error-for="category_id">@error('category_id'){{ $message }}@enderror</p>
                                </div>

                                {{-- Exam mode --}}
                                <div>
                                    <label for="exam_mode" class="exam-label">
                                        Exam mode <span class="form-required">*</span>
                                    </label>
                                    <select id="exam_mode" name="exam_mode" class="panel-input mt-1 block w-full" data-summary-field="mode">
                                        @foreach (['standard', 'practice', 'proctored'] as $mode)
                                            <option value="{{ $mode }}" @selected(old('exam_mode', 'standard') === $mode)>{{ ucfirst($mode) }}</option>
                                        @endforeach
                                    </select>
                                    <p class="form-field-error @error('exam_mode') is-visible @enderror" data-error-for="exam_mode">@error('exam_mode'){{ $message }}@enderror</p>
                                </div>

                                {{-- Difficulty level --}}
                                <div>
                                    <label for="difficulty_level" class="exam-label">Difficulty level</label>
                                    <select id="difficulty_level" name="difficulty_level" class="panel-input mt-1 block w-full" data-summary-field="difficulty">
                                        @foreach (['beginner', 'intermediate', 'advanced'] as $dl)
                                            <option value="{{ $dl }}" @selected(old('difficulty_level', 'intermediate') === $dl)>{{ ucfirst($dl) }}</option>
                                        @endforeach
                                    </select>
                                    <p class="form-field-error @error('difficulty_level') is-visible @enderror" data-error-for="difficulty_level">@error('difficulty_level'){{ $message }}@enderror</p>
                                </div>

                                {{-- Status --}}
                                <div>
                                    <label for="status" class="exam-label">
                                        Status <span class="form-required">*</span>
                                    </label>
                                    <select id="status" name="status" class="panel-input mt-1 block w-full" data-summary-field="status">
                                        @foreach (['draft', 'published', 'active', 'inactive', 'suspended'] as $st)
                                            <option value="{{ $st }}" @selected(old('status', 'draft') === $st)>{{ ucfirst($st) }}</option>
                                        @endforeach
                                    </select>
                                    <p class="form-field-error @error('status') is-visible @enderror" data-error-for="status">@error('status'){{ $message }}@enderror</p>
                                </div>
                            </div>

                            {{-- Visibility + Tags --}}
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="visibility" class="exam-label">Visibility</label>
                                    <select id="visibility" name="visibility" class="panel-input mt-1 block w-full" data-summary-field="visibility">
                                        <option value="public"  @selected(old('visibility', 'public')  === 'public') >Public  — anyone can attempt</option>
                                        <option value="private" @selected(old('visibility', 'public')  === 'private')>Private — invite only</option>
                                        <option value="invite"  @selected(old('visibility', 'public')  === 'invite') >Link only — via direct URL</option>
                                    </select>
                                    <p class="form-field-error @error('visibility') is-visible @enderror" data-error-for="visibility">@error('visibility'){{ $message }}@enderror</p>
                                </div>

                                <div>
                                    <label for="tags" class="exam-label">Tags</label>
                                    <input
                                        id="tags"
                                        type="text"
                                        name="tags"
                                        value="{{ old('tags') }}"
                                        class="panel-input mt-1 block w-full"
                                        placeholder="e.g. backend, certification, batch-a"
                                    >
                                    <p class="exam-help">Comma-separated — used for search and grouping.</p>
                                </div>
                            </div>

                            {{-- Title --}}
                            <div>
                                <label for="title" class="exam-label">
                                    Exam title <span class="form-required">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    name="title"
                                    value="{{ old('title') }}"
                                    class="panel-input"
                                    placeholder="e.g. Backend Engineering Assessment — April Batch"
                                    data-summary-field="title"
                                >
                                <p class="form-field-error @error('title') is-visible @enderror" data-error-for="title">@error('title'){{ $message }}@enderror</p>
                            </div>

                            {{-- Description (rich editor) --}}
                            <div>
                                <label for="description" class="exam-label">Description</label>
                                <textarea id="description" name="description" rows="4" class="hidden"
                                    placeholder="Share scope, target audience, and key sections.">{{ old('description') }}</textarea>
                                <div id="editor-description" class="exam-editor-shell"></div>
                                <p class="form-field-error @error('description') is-visible @enderror" data-error-for="description">@error('description'){{ $message }}@enderror</p>
                            </div>

                            {{-- Instructions (rich editor) --}}
                            <div>
                                <label for="instructions" class="exam-label">Instructions for candidates</label>
                                <textarea id="instructions" name="instructions" rows="3" class="hidden"
                                    placeholder="Guidelines shown to the candidate before the exam begins.">{{ old('instructions') }}</textarea>
                                <div id="editor-instructions" class="exam-editor-shell"></div>
                                <p class="form-field-error @error('instructions') is-visible @enderror" data-error-for="instructions">@error('instructions'){{ $message }}@enderror</p>
                            </div>
                        </div>
                    </section>

                    {{-- ─ Section 2 · Scoring & rules ─ --}}
                    <section class="exam-block">
                        <div class="exam-block__head">
                            <h2>Scoring and access rules</h2>
                            <p>Set limits, pass criteria, timing, and randomization controls.</p>
                        </div>

                        <div class="exam-block__content space-y-4">
                            {{-- Numeric row --}}
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div>
                                    <label for="duration" class="exam-label">Duration (min) <span class="form-required">*</span></label>
                                    <input id="duration" type="number" name="duration" min="1" max="480" class="panel-input" value="{{ old('duration', 60) }}" data-summary-field="duration">
                                    <p class="form-field-error @error('duration') is-visible @enderror" data-error-for="duration">@error('duration'){{ $message }}@enderror</p>
                                </div>
                                <div>
                                    <label for="pass_percentage" class="exam-label">Pass % <span class="form-required">*</span></label>
                                    <input id="pass_percentage" type="number" step="0.01" min="0" max="100" name="pass_percentage" class="panel-input" value="{{ old('pass_percentage', 50) }}" data-summary-field="pass_percentage">
                                    <p class="form-field-error @error('pass_percentage') is-visible @enderror" data-error-for="pass_percentage">@error('pass_percentage'){{ $message }}@enderror</p>
                                </div>
                                <div>
                                    <label for="max_attempts" class="exam-label">Max attempts <span class="form-required">*</span></label>
                                    <input id="max_attempts" type="number" min="1" max="50" name="max_attempts" class="panel-input" value="{{ old('max_attempts', 1) }}" data-summary-field="max_attempts">
                                    <p class="form-field-error @error('max_attempts') is-visible @enderror" data-error-for="max_attempts">@error('max_attempts'){{ $message }}@enderror</p>
                                </div>
                                <div>
                                    <label for="negative_mark_per_question" class="exam-label">Negative mark / Q</label>
                                    <input id="negative_mark_per_question" type="number" step="0.0001" min="0" max="100" name="negative_mark_per_question" class="panel-input" value="{{ old('negative_mark_per_question', 0) }}">
                                    <p class="form-field-error @error('negative_mark_per_question') is-visible @enderror" data-error-for="negative_mark_per_question">@error('negative_mark_per_question'){{ $message }}@enderror</p>
                                </div>
                            </div>

                            {{-- Schedule --}}
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="scheduled_start" class="exam-label">Start date &amp; time</label>
                                    <input id="scheduled_start" type="text" name="scheduled_start" class="panel-input js-datetime"
                                        placeholder="Select start date and time"
                                        value="{{ old('scheduled_start') ? str_replace('T', ' ', old('scheduled_start')) : '' }}"
                                        data-summary-field="scheduled_start">
                                    <p class="form-field-error @error('scheduled_start') is-visible @enderror" data-error-for="scheduled_start">@error('scheduled_start'){{ $message }}@enderror</p>
                                </div>
                                <div>
                                    <label for="scheduled_end" class="exam-label">End date &amp; time</label>
                                    <input id="scheduled_end" type="text" name="scheduled_end" class="panel-input js-datetime is-readonly"
                                        placeholder="Auto calculated from start + duration"
                                        value="{{ old('scheduled_end') ? str_replace('T', ' ', old('scheduled_end')) : '' }}"
                                        data-summary-field="scheduled_end"
                                        readonly>
                                    <p class="exam-help">Auto-filled once start date and duration are set.</p>
                                    <p class="form-field-error @error('scheduled_end') is-visible @enderror" data-error-for="scheduled_end">@error('scheduled_end'){{ $message }}@enderror</p>
                                </div>
                            </div>

                            {{-- Toggles row 1 --}}
                            <div class="exam-toggle-grid">
                                <label class="exam-toggle">
                                    <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions'))>
                                    <span>
                                        <strong>Shuffle questions</strong>
                                        <small>Rotate question order per attempt.</small>
                                    </span>
                                </label>
                                <label class="exam-toggle">
                                    <input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options'))>
                                    <span>
                                        <strong>Shuffle options</strong>
                                        <small>Randomize MCQ options per attempt.</small>
                                    </span>
                                </label>
                                <label class="exam-toggle">
                                    <input type="checkbox" name="show_result_immediately" value="1" @checked(old('show_result_immediately', true))>
                                    <span>
                                        <strong>Show result immediately</strong>
                                        <small>Candidate sees pass/fail right after submission.</small>
                                    </span>
                                </label>
                                <label class="exam-toggle">
                                    <input type="checkbox" name="allow_review" value="1" @checked(old('allow_review'))>
                                    <span>
                                        <strong>Allow answer review</strong>
                                        <small>Candidate can revisit all answers before submitting.</small>
                                    </span>
                                </label>
                                <label class="exam-toggle">
                                    <input type="checkbox" name="certificate_enabled" value="1" @checked(old('certificate_enabled'))>
                                    <span>
                                        <strong>Issue certificate on pass</strong>
                                        <small>Auto-generate a certificate for passing candidates.</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </section>

                    {{-- ─ Section 3 · Question bank ─ --}}
                    <section class="exam-block">
                        <div class="exam-block__head">
                            <h2>Question bank</h2>
                            <p>Search and pick questions that will be included in this exam.</p>
                        </div>

                        <div class="exam-block__content">
                            <div class="exam-question-toolbar">
                                <label class="relative w-full lg:max-w-sm">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </span>
                                    <input id="question-bank-search" type="search" class="panel-input pl-9" placeholder="Search question body…">
                                </label>

                                <select id="question-bank-category" class="panel-input lg:max-w-xs">
                                    <option value="">All categories</option>
                                    @foreach ($questionFilterCategories as $cf)
                                        <option value="{{ $cf['id'] }}">{{ $cf['name'] }}</option>
                                    @endforeach
                                </select>

                                <div class="exam-selected-pill">
                                    <span id="question-selected-count">0</span>&nbsp;selected
                                </div>
                            </div>

                            <div id="question-bank-list" class="exam-question-list">
                                @foreach ($questionBank as $question)
                                    @php
                                        $qId   = (string) $question['id'];
                                        $qCat  = (string) ($question['category_id'] ?? 'none');
                                        $qText = strip_tags((string) $question['body']);
                                    @endphp
                                    <label
                                        class="exam-question-item"
                                        data-question-item
                                        data-question-category="{{ $qCat }}"
                                        data-question-text="{{ str($qText)->lower() }}"
                                        data-question-marks="{{ (int) $question['marks'] }}"
                                    >
                                        <input
                                            type="checkbox"
                                            class="exam-question-item__check"
                                            value="{{ $qId }}"
                                            @unless ($isDemoQuestionBank) name="question_ids[]" @endunless
                                            @checked(in_array($qId, $selectedQuestionIds, true))
                                        >
                                        <div class="exam-question-item__content">
                                            <div class="exam-question-item__head">
                                                <span class="exam-question-item__id">#{{ $qId }}</span>
                                                <span class="exam-question-item__tag">{{ $question['category_name'] }}</span>
                                                <span class="exam-question-item__tag">{{ ucfirst($question['difficulty']) }}</span>
                                                <span class="exam-question-item__tag">{{ strtoupper(str_replace('_', ' ', $question['type'])) }}</span>
                                            </div>
                                            <p class="exam-question-item__body">{{ \Illuminate\Support\Str::limit($qText, 150) }}</p>
                                            <p class="exam-question-item__meta">{{ (int) $question['marks'] }} marks</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <div id="question-bank-empty" class="exam-question-empty hidden">
                                No questions match the current filters.
                            </div>

                            <p class="form-field-error @error('question_ids') is-visible @enderror" data-error-for="question_ids">@error('question_ids'){{ $message }}@enderror</p>
                        </div>
                    </section>
                </div>{{-- /.exam-builder__main --}}

                {{-- ── Aside ────────────────────────────────────── --}}
                <aside class="exam-builder__aside">

                    {{-- Live summary --}}
                    <section class="exam-summary-card">
                        <h3>Live summary</h3>
                        <dl>
                            <div><dt>Title</dt>       <dd data-summary-title>Untitled exam</dd></div>
                            <div><dt>Status</dt>      <dd data-summary-status>{{ ucfirst(old('status', 'draft')) }}</dd></div>
                            <div><dt>Category</dt>    <dd data-summary-category>No category</dd></div>
                            <div><dt>Mode</dt>        <dd data-summary-mode>{{ ucfirst(old('exam_mode', 'standard')) }}</dd></div>
                            <div><dt>Difficulty</dt>  <dd data-summary-difficulty>{{ ucfirst(old('difficulty_level', 'intermediate')) }}</dd></div>
                            <div><dt>Visibility</dt>  <dd data-summary-visibility>{{ ucfirst(old('visibility', 'public')) }}</dd></div>
                            <div><dt>Duration</dt>    <dd data-summary-duration>{{ old('duration', 60) }} min</dd></div>
                            <div><dt>Pass rule</dt>   <dd data-summary-pass>{{ old('pass_percentage', 50) }}%</dd></div>
                            <div><dt>Max attempts</dt><dd data-summary-attempts>{{ old('max_attempts', 1) }}</dd></div>
                            <div><dt>Questions</dt>   <dd><span data-summary-selected>0</span> selected</dd></div>
                            <div><dt>Total marks</dt> <dd><span data-summary-total-marks>0</span></dd></div>
                            <div><dt>Schedule</dt>    <dd data-summary-schedule>Not scheduled</dd></div>
                        </dl>
                    </section>

                    {{-- Quick presets --}}
                    <section class="exam-summary-card">
                        <h3>Quick presets</h3>
                        <p class="exam-summary-help">Apply a baseline config, then fine-tune as needed.</p>
                        <div class="exam-preset-grid">
                            <button type="button" class="exam-preset-btn" data-exam-preset="practice">Practice</button>
                            <button type="button" class="exam-preset-btn" data-exam-preset="screening">Screening</button>
                            <button type="button" class="exam-preset-btn" data-exam-preset="certification">Certification</button>
                        </div>
                    </section>

                    {{-- Behavioural flags summary --}}
                    <section class="exam-summary-card">
                        <h3>Behaviour flags</h3>
                        <div class="exam-flag-list mt-3 space-y-2" id="flag-summary">
                            {{-- Filled by JS --}}
                        </div>
                    </section>
                </aside>
            </div>{{-- /.exam-builder__body --}}

            {{-- ── Footer ──────────────────────────────────────── --}}
            <div class="exam-builder__footer">
                <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary">Save Exam</button>
            </div>
        </form>
    </x-page-card>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/modules/form-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/exam-create.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/core/form-utils.js') }}"></script>
    <script>
        window.examCreateConfig = {
            demoQuestionBank: @json($isDemoQuestionBank),
        };
    </script>
    <script src="{{ asset('js/backend/exam-create.js') }}"></script>
@endpush

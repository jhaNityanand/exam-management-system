@extends('backend.layouts.app')

@section('title', 'Create Exam')
@section('page-title', 'Create Exam')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin',  'url' => route('admin.dashboard')],
        ['label' => 'Exams',  'url' => route('admin.exams.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
<div id="exam-create-page" class="exam-create-page" data-page-ready="false">
    <div id="exam-page-loader" class="exam-page-loader" aria-live="polite" aria-busy="true">
        <div class="exam-page-loader__inner">
            <span class="exam-page-loader__spinner" aria-hidden="true"></span>
            <p>Preparing exam creation workspace...</p>
        </div>
    </div>

    <x-page-card class="exam-shell-card overflow-hidden">
        <form action="{{ route('admin.exams.store') }}" method="POST" id="exam-create-form" class="exam-create-form exam-form" novalidate>
            @csrf

            <header class="exam-page-header">
                <div>
                    <h1>Create Exam</h1>
                    <p>Build a structured exam flow with clear rules, candidate access control, and question availability checks.</p>
                </div>
                <span class="exam-header-badge">Draft Workspace</span>
            </header>

            <div class="exam-create-layout">
                <main class="exam-create-main">
                    <section class="exam-section" id="exam-basic-information">
                        <div class="exam-section__head">
                            <h2>1. Exam Basic Information</h2>
                            <p>Define the exam identity first so downstream sections can adapt correctly.</p>
                        </div>
                        <div class="exam-section__body space-y-5">
                            <div class="exam-basic-stack space-y-4">
                                <div>
                                    <label for="exam_title" class="exam-label">Title <span class="form-required">*</span></label>
                                    <input id="exam_title" name="title" type="text" class="panel-input" value="{{ old('title') }}" placeholder="e.g. Senior Laravel Assessment - June 2026">
                                </div>

                                <x-rich-text-editor
                                    label="Description"
                                    input-id="exam_description"
                                    name="description"
                                    :value="old('description')"
                                    placeholder="Summarize scope, audience, and expected outcomes."
                                    :height="240"
                                    preset="full"
                                            module="exam"
                                />
                            </div>

                            <div class="exam-grid exam-grid--3" id="basic-meta-grid">
                                <div>
                                    <label for="exam_category_id" class="exam-label">Exam Category <span class="form-required">*</span></label>
                                    <select id="exam_category_id" name="exam_category_id" class="mt-1 block w-full">
                                        <option value="">Search or select...</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}"
                                                data-level="{{ $cat->depth }}"
                                                data-category-name="{{ $cat->name }}"
                                                class="{{ $cat->depth === 0 ? 'font-semibold text-slate-900' : '' }}"
                                                {{ old('exam_category_id') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="difficulty_level" class="exam-label">Difficulty Level <span class="info-tip" tabindex="0" role="button" aria-label="Difficulty level info" data-tooltip="Used for filtering and recommendation in reports.">i</span></label>
                                    <select id="difficulty_level" name="difficulty_level" class="panel-input"></select>
                                </div>
                                <div>
                                    <label for="exam_status" class="exam-label">Status <span class="form-required">*</span></label>
                                    <select id="exam_status" name="status" class="panel-input"></select>
                                </div>
                                <div>
                                    <label for="exam_mode" class="exam-label">Exam Mode <span class="form-required">*</span></label>
                                    <select id="exam_mode" name="exam_mode" class="panel-input"></select>
                                </div>
                                <div>
                                    <label for="exam_visibility" class="exam-label">Visibility <span class="form-required">*</span></label>
                                    <select id="exam_visibility" name="visibility" class="panel-input"></select>
                                </div>
                                <div class="exam-grid-span-3">
                                    <label for="exam-tags-input" class="exam-label">Tags <span class="info-tip" tabindex="0" role="button" aria-label="Tags input info" data-tooltip="Press Enter to add each tag. Commas are ignored.">i</span></label>
                                    <div class="chip-input" data-chip-input="tags">
                                        <input id="exam-tags-input" type="text" placeholder="Type a tag and press Enter">
                                    </div>
                                    <input type="hidden" name="tags" id="exam_tags" value="[]">
                                    <p class="exam-help">Tags help in searching, segmentation, and reporting.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="exam-section" id="timer-section">
                        <div class="exam-section__head">
                            <h2>2. Timer &amp; Duration Management</h2>
                            <p>Configure exam timing behavior and auto-submit rules.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div class="config-preview-card">
                                <label class="switch-control">
                                    <input
                                        id="enable_exam_timer"
                                        name="enable_exam_timer"
                                        type="checkbox"
                                        value="1"
                                        {{ old('enable_exam_timer', 1) ? 'checked' : '' }}
                                    >
                                    <span class="switch-control__track"></span>
                                    <span class="switch-control__label">Enable Exam Timer</span>
                                </label>
                                <p class="exam-help mt-1">If enabled, the exam will be bound to the configured duration.</p>
                            </div>

                            <div class="exam-grid exam-grid--2">
                                <div id="timer-duration-wrap">
                                    <label for="exam_duration_minutes" class="exam-label">Exam Duration (Minutes) <span class="form-required">*</span></label>
                                    <input
                                        id="exam_duration_minutes"
                                        name="exam_duration_minutes"
                                        type="number"
                                        class="panel-input"
                                        min="1"
                                        step="1"
                                        value="{{ old('exam_duration_minutes', 60) }}"
                                        placeholder="e.g. 60"
                                    >
                                    <p class="exam-help">Set total allowed time. Example: 60 minutes.</p>
                                </div>
                                <div id="timer-autosubmit-wrap">
                                    <label class="exam-label">Timer End Behavior</label>
                                    <label class="switch-control">
                                        <input
                                            id="auto_submit_on_timer_end"
                                            name="auto_submit_on_timer_end"
                                            type="checkbox"
                                            value="1"
                                            {{ old('auto_submit_on_timer_end', 1) ? 'checked' : '' }}
                                        >
                                        <span class="switch-control__track"></span>
                                        <span class="switch-control__label">Auto-submit exam when timer ends</span>
                                    </label>
                                    <p class="exam-help">Recommended to avoid incomplete attempts.</p>
                                </div>
                            </div>

                            <p id="timer-config-summary" class="exam-help"></p>
                        </div>
                    </section>

                    <section class="exam-section" id="exam-format-section">
                        <div class="exam-section__head">
                            <h2>3. Exam Format Management</h2>
                            <p>Choose the format style for this exam session.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div>
                                <label class="exam-label">Exam Format <span class="form-required">*</span></label>
                                <div id="exam-format-options" class="option-card-grid"></div>
                                <input type="hidden" name="exam_format" id="exam_format" value="{{ old('exam_format', '["mcq"]') }}">
                                <p class="exam-help">Select one or more formats: MCQ, Multi Select, True/False, Written, Fill in the Blanks.</p>
                            </div>
                        </div>
                    </section>

                    <section class="exam-section" id="schedule-section">
                        <div class="exam-section__head">
                            <h2>4. Schedule &amp; Attempt Management</h2>
                            <p>Control when the exam is available and how many attempts each candidate can make.</p>
                        </div>
                        <div class="exam-section__body space-y-5">
                            <div>
                                <label class="exam-label">Schedule Availability</label>
                                <div id="schedule-type-options" class="option-card-grid option-card-grid--compact"></div>
                                <input type="hidden" name="schedule_type" id="schedule_type" value="{{ old('schedule_type', 'any_time') }}">
                                <p class="exam-help">Choose whether candidates can start anytime or only within a fixed date-time window.</p>
                            </div>

                            <div id="fixed-schedule-window" class="exam-grid exam-grid--2" hidden>
                                <div>
                                    <label for="schedule_start_at" class="exam-label">Start Date &amp; Time <span class="form-required">*</span></label>
                                    <input
                                        id="schedule_start_at"
                                        name="schedule_start_at"
                                        type="text"
                                        class="panel-input"
                                        value="{{ old('schedule_start_at') }}"
                                        placeholder="Select start date and time"
                                        autocomplete="off"
                                    >
                                </div>
                                <div>
                                    <label for="schedule_end_at" class="exam-label">End Date &amp; Time <span class="form-required">*</span></label>
                                    <input
                                        id="schedule_end_at"
                                        name="schedule_end_at"
                                        type="text"
                                        class="panel-input"
                                        value="{{ old('schedule_end_at') }}"
                                        placeholder="Select end date and time"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="exam-label">Attempt Limit Per Candidate</label>
                                <div id="attempt-limit-options" class="option-card-grid option-card-grid--compact"></div>
                                <input type="hidden" name="attempt_limit_type" id="attempt_limit_type" value="{{ old('attempt_limit_type', 'once') }}">
                            </div>

                            <div id="fixed-attempt-limit-wrap" hidden>
                                <label for="attempt_limit_count" class="exam-label">Maximum Attempts <span class="form-required">*</span></label>
                                <div class="attempt-limit-field">
                                    <input
                                        id="attempt_limit_count"
                                        name="attempt_limit_count"
                                        type="number"
                                        min="2"
                                        step="1"
                                        class="panel-input"
                                        value="{{ old('attempt_limit_count', 2) }}"
                                        placeholder="e.g. 2 or 3"
                                    >
                                </div>
                                <p class="exam-help">Use this only when “Fixed Attempts” is selected.</p>
                            </div>

                            <p id="schedule-config-summary" class="exam-help"></p>
                        </div>
                    </section>

                    <section class="exam-section" id="candidate-access-section">
                        <div class="exam-section__head">
                            <h2>5. Candidate Access Management</h2>
                            <p>Import or manually add candidate emails for controlled access.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div class="segmented-control" role="tablist" aria-label="Candidate access method">
                                <button type="button" class="segmented-control__button is-active" data-candidate-tab="import" role="tab" aria-selected="true">Import Excel</button>
                                <button type="button" class="segmented-control__button" data-candidate-tab="manual" role="tab" aria-selected="false">Manual Entry</button>
                            </div>

                            <div class="candidate-panel is-active" data-candidate-panel="import">
                                <div class="candidate-panel__row">
                                    <p class="exam-help">Use a spreadsheet with <strong>Name</strong> and <strong>Email</strong> columns.</p>
                                    <a class="panel-button-secondary" href="{{ asset('data/exam-create/sample-candidates.csv') }}" download>Download Sample Excel Format</a>
                                </div>

                                <label class="drop-zone" id="candidate-drop-zone">
                                    <input id="candidate_excel_file" type="file" name="candidate_excel_file" class="sr-only" accept=".csv,.xls,.xlsx">
                                    <strong>Drag and drop Excel/CSV file</strong>
                                    <span>or click to choose file</span>
                                </label>

                                <input type="hidden" name="imported_candidates" id="imported_candidates" value="[]">
                                <div id="imported-candidate-preview" class="candidate-preview" hidden></div>
                            </div>

                            <div class="candidate-panel" data-candidate-panel="manual" hidden>
                                <label for="manual-email-input" class="exam-label">Add candidate emails <span class="info-tip" tabindex="0" role="button" aria-label="Add candidate emails info" data-tooltip="Type an email address and press Enter to add it. You can add multiple emails one by one.">i</span></label>
                                <div class="chip-input" data-chip-input="emails">
                                    <input id="manual-email-input" type="text" placeholder="Type email and press Enter">
                                </div>
                                <input type="hidden" name="manual_candidate_emails" id="manual_candidate_emails" value="[]">
                                <p id="manual-email-feedback" class="exam-help"></p>
                            </div>
                        </div>
                    </section>

                    <section class="exam-section" id="exam-configuration-section">
                        <div class="exam-section__head">
                            <h2>6. Exam Configuration</h2>
                            <p>Configure question volume, category requirements, marks, and set generation rules.</p>
                        </div>
                        <div class="exam-section__body space-y-5">
                            <div class="exam-grid exam-grid--3">
                                <div>
                                    <label for="total_questions" class="exam-label">Total Questions Ask <span class="form-required">*</span></label>
                                    <input id="total_questions" name="total_questions" type="number" class="panel-input" min="1" step="1" value="50" placeholder="Enter total questions">
                                </div>
                                <div>
                                    <label for="total_categories" class="exam-label">Total Categories Used <span class="form-required">*</span></label>
                                    <input id="total_categories" name="total_categories" type="number" class="panel-input" min="1" step="1" value="3" placeholder="Enter category count">
                                    <p id="category-target-helper" class="exam-help"></p>
                                </div>
                                <div>
                                    <label for="total_marks" class="exam-label">Total Marks <span class="form-required">*</span></label>
                                    <input id="total_marks" name="total_marks" type="number" class="panel-input" min="1" step="1" value="100" placeholder="Enter total marks">
                                </div>
                                <div>
                                    <label for="passing_marks" class="exam-label">Passing Marks <span class="form-required">*</span></label>
                                    <input id="passing_marks" name="passing_marks" type="number" class="panel-input" min="0" step="1" value="40" placeholder="Enter passing marks">
                                </div>
                                <div>
                                    <label for="paper_sets" class="exam-label">Number of Paper Sets Generated <span class="form-required">*</span></label>
                                    <input id="paper_sets" name="paper_sets" type="number" class="panel-input" min="1" step="1" value="1" placeholder="Minimum 1">
                                    <p id="paper-sets-helper" class="exam-help"></p>
                                </div>
                                <div>
                                    <label class="exam-label">Fix Each Category Question Count</label>
                                    <label class="switch-control">
                                        <input id="fix_category_questions" name="fix_category_questions" type="checkbox" value="1">
                                        <span class="switch-control__track"></span>
                                        <span class="switch-control__label">Enable exact per-category question allocation</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="exam-label">Question Distribution Type</label>
                                <div id="distribution-type-group" class="pill-group"></div>
                                <input type="hidden" name="distribution_type" id="distribution_type" value="">
                            </div>

                            <div id="category-selector-wrap">
                                <div class="exam-section__mini-head">
                                    <h3>Select Categories</h3>
                                    <p>Select exactly the number of categories defined above.</p>
                                </div>
                                <select
                                    id="selected_categories_select"
                                    class="panel-input"
                                    multiple
                                    data-select-mode="multiple"
                                    data-option-style="hierarchy"
                                    data-placeholder="Select categories"
                                    data-max-items="3"
                                ></select>
                                <input type="hidden" name="selected_categories" id="selected_categories" value="[]">
                                <p id="category-selection-feedback" class="exam-help"></p>
                            </div>

                            <div id="category-selection-complete" class="config-preview-card" hidden>
                                <p class="exam-help"><strong id="category-selection-complete-text"></strong></p>
                            </div>

                            <div id="fixed-category-distribution" class="config-preview-card" hidden>
                                <h4>Fixed Category Question Allocation</h4>
                                <p class="exam-help" id="fixed-distribution-helper"></p>

                                <div id="extra-questions-wrap" class="mt-2" hidden>
                                    <label for="extra_questions_category" id="extra-questions-label" class="exam-label">Category for Extra Questions</label>
                                    <select
                                        id="extra_questions_category"
                                        class="panel-input"
                                        multiple
                                        data-select-mode="multiple"
                                        data-option-style="hierarchy"
                                        data-placeholder="Select categories for extra questions"
                                        data-max-items="1"
                                    ></select>
                                    <input type="hidden" name="extra_questions_categories" id="extra_questions_categories" value="[]">
                                    <p id="extra-questions-help" class="exam-help">Remainder questions will be assigned to the selected category.</p>

                                    <div id="extra-questions-allocations-wrap" class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded" hidden>
                                        <p class="exam-label mb-2">Distribute Extra Questions <span class="text-sm font-normal text-gray-500">(Allocated: <span id="allocated-count">0</span> / <span id="remaining-count">0</span>)</span></p>
                                        <div id="extra-questions-allocation-list" class="exam-grid exam-grid--3"></div>
                                        <input type="hidden" name="extra_questions_allocations" id="extra_questions_allocations" value="{}">
                                    </div>
                                </div>

                                <ul id="fixed-category-distribution-list" class="preview-list mt-2"></ul>
                            </div>

                            <div class="config-preview-grid">
                                <article class="config-preview-card">
                                    <h4>Live Calculation Preview</h4>
                                    <ul id="config-preview-list" class="preview-list"></ul>
                                </article>
                                <article class="config-preview-card">
                                    <h4>Validation Summary</h4>
                                    <ul id="config-validation-list" class="preview-list"></ul>
                                </article>
                            </div>
                        </div>
                    </section>

                    <section class="exam-section" id="question-rules-section">
                        <div class="exam-section__head">
                            <h2>7. Question Rules and Filters</h2>
                            <p>Limit question import to selected marks only.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div>
                                <label class="exam-label">Question Marks Type</label>
                                <label class="switch-control">
                                    <input id="fix_marks_each_question" name="fix_marks_each_question" type="checkbox" value="1">
                                    <span class="switch-control__track"></span>
                                    <span class="switch-control__label">Fix Marks Each Question (All questions will have the same marks)</span>
                                </label>
                            </div>

                            <div>
                                <label class="exam-label">Negative Marking</label>
                                <label class="switch-control">
                                    <input id="enable_negative_marking" name="enable_negative_marking" type="checkbox" value="1">
                                    <span class="switch-control__track"></span>
                                    <span class="switch-control__label">Enable Negative Marking</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-9">
                                    <label class="exam-label">Question Marks Filter</label>
                                    <div id="question-marks-filter" class="pill-group"></div>
                                    <input type="hidden" name="question_marks_filter" id="question_marks_filter" value="[]">
                                    <p class="exam-help">Only questions that match selected marks are available in the question bank.</p>
                                    <p class="exam-help"><strong id="selected-marks-count">0</strong> marks filters selected.</p>
                                </div>

                                <div class="md:col-span-3" id="negative-marking-config" hidden>
                                    <label for="negative_marking_type" class="exam-label">Penalty per wrong answer</label>
                                    <select id="negative_marking_type" name="negative_marking_type" class="panel-input">
                                        <option value="25">25% (1/4th of question marks)</option>
                                        <option value="33.33">33.33% (1/3rd of question marks)</option>
                                        <option value="50">50% (1/2 of question marks)</option>
                                        <option value="100">100% (Full question marks)</option>
                                    </select>
                                </div>
                            </div>

                            <div id="marks-calculation-management" class="marks-management-card" hidden>
                                <h4 class="marks-management-card__title">Marks Calculation Management</h4>
                                <p class="exam-help marks-management-card__help">
                                    When fixed marks are enabled, Total Marks must equal Total Questions x Selected Marks Per Question.
                                </p>

                                <div id="marks-calculation-summary" class="marks-management-card__summary" aria-live="polite"></div>

                                <p id="marks-calculation-warning" class="marks-management-card__warning" role="status" aria-live="polite"></p>
                                <p id="marks-calculation-suggestion" class="marks-management-card__suggestion" aria-live="polite"></p>

                                <div id="marks-calculation-actions" class="marks-management-card__actions" hidden>
                                    <button type="button" id="marks-fix-total-marks" class="panel-button-secondary">
                                        Update Total Marks
                                    </button>
                                    <button type="button" id="marks-fix-total-questions" class="panel-button-secondary">
                                        Update Total Questions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="exam-section" id="pricing-section">
                        <div class="exam-section__head">
                            <h2>8. Pricing and Discount Rules</h2>
                            <p>Choose pricing strategy and attach predefined discount rules.</p>
                        </div>
                        <div class="exam-section__body space-y-5">
                            <div>
                                <label class="exam-label">Pricing Option</label>
                                <div id="pricing-options" class="option-card-grid"></div>
                                <input type="hidden" name="pricing_option" id="pricing_option" value="">
                                <p id="pricing-imported-note" class="exam-help" hidden>Imported candidates will get free access. Candidate import tools are now available.</p>
                            </div>

                            <div id="pricing-details-wrap" hidden>
                                <label class="exam-label">Exam Fee</label>
                                <div class="pricing-amount-group">
                                    <select id="exam_currency" name="exam_currency" class="panel-input pricing-amount-group__currency"></select>
                                    <input type="number" id="exam_amount" name="exam_amount" class="pricing-amount-group__input" min="0" step="0.01" placeholder="Enter amount">
                                </div>
                                <p class="exam-help exam-help-warning mt-1"><strong>&#9888;</strong> A 5% platform charge applies to all paid exams.</p>
                            </div>

                            <div id="free-candidates-wrap" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700" hidden>
                                <div class="mb-3">
                                    <h4 class="exam-label exam-subsection-title">
                                        <span>Free Candidate List</span>
                                        <span class="info-tip" tabindex="0" role="button" aria-label="Free Candidate List info" data-tooltip="Candidates added to this list will not be charged any fee to attempt the exam, while other candidates pay the default fee.">i</span>
                                    </h4>
                                    <p class="exam-help">Import or manually add candidate emails who will receive free access to this paid exam.</p>
                                </div>

                                <div class="segmented-control" role="tablist" aria-label="Free candidate access method">
                                    <button type="button" class="segmented-control__button is-active" data-free-candidate-tab="import" role="tab" aria-selected="true">Import Excel</button>
                                    <button type="button" class="segmented-control__button" data-free-candidate-tab="manual" role="tab" aria-selected="false">Manual Entry</button>
                                </div>

                                <div class="candidate-panel is-active" data-free-candidate-panel="import">
                                    <div class="candidate-panel__row mb-2">
                                        <p class="exam-help">Use a spreadsheet with <strong>Name</strong> and <strong>Email</strong> columns.</p>
                                        <a class="panel-button-secondary exam-sample-download" href="{{ asset('data/exam-create/sample-candidates.csv') }}" download>Download Sample Excel Format</a>
                                    </div>

                                    <label class="drop-zone" id="free-candidate-drop-zone">
                                        <input id="free_candidate_excel_file" type="file" name="free_candidate_excel_file" class="sr-only" accept=".csv,.xls,.xlsx">
                                        <strong>Drag and drop Excel/CSV file</strong>
                                        <span>or click to choose file</span>
                                    </label>

                                    <input type="hidden" name="free_imported_candidates" id="free_imported_candidates" value="[]">
                                    <div id="free-imported-candidate-preview" class="candidate-preview mt-2" hidden></div>
                                </div>

                                <div class="candidate-panel" data-free-candidate-panel="manual" hidden>
                                    <label for="free-manual-email-input" class="exam-label">Add free candidate emails <span class="info-tip" tabindex="0" role="button" aria-label="Add free candidate emails info" data-tooltip="Type an email address and press Enter to add it. You can add multiple emails one by one.">i</span></label>
                                    <div class="chip-input" data-chip-input="free-emails">
                                        <input id="free-manual-email-input" type="text" placeholder="Type email and press Enter">
                                    </div>
                                    <input type="hidden" name="free_manual_candidate_emails" id="free_manual_candidate_emails" value="[]">
                                    <p id="free-manual-email-feedback" class="exam-help"></p>
                                </div>
                            </div>

                            <div id="discount-rules-wrap" hidden>
                                <label class="exam-label">Discount Rules</label>
                                <div id="discount-rules" class="option-card-grid option-card-grid--compact"></div>
                                <input type="hidden" name="selected_discounts" id="selected_discounts" value="[]">

                                <!-- Custom Discount Section -->
                                <div class="custom-discounts-section mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <div class="flex justify-between items-center mb-3 gap-3 flex-wrap">
                                        <div>
                                            <h4 class="exam-label exam-subsection-title">Custom Discount Offers</h4>
                                            <p class="exam-help">Create custom discount incentives for specific events or campaigns.</p>
                                        </div>
                                        <button type="button" id="add-custom-discount-btn" class="panel-button-secondary exam-sample-download flex items-center gap-1.5">
                                            <span>+ Add Custom Offer</span>
                                        </button>
                                    </div>

                                    <!-- Guide Info Box -->
                                    <div class="custom-discount-guide mb-4 flex items-start gap-2">
                                        <span aria-hidden="true">💡</span>
                                        <div>
                                            <strong>Guide:</strong> Provide an offer name (e.g., "Early Bird Offer"), a brief description (e.g., "Save 15% before June 1st"), and a percentage value (between 0% and 100%). You can add multiple offers. Click the trash icon to remove any offer.
                                        </div>
                                    </div>

                                    <!-- List Container -->
                                    <div id="custom-discounts-container" class="space-y-3">
                                        <!-- Custom discount rows will be injected here -->
                                    </div>
                                    <input type="hidden" name="custom_discounts" id="custom_discounts" value="[]">
                                </div>
                            </div>

                            <div class="summary-box" id="discount-summary-wrap" hidden>
                                <h4>Discount Summary Preview</h4>
                                <ul id="discount-summary" class="preview-list"></ul>
                            </div>
                        </div>
                    </section>

                    <section class="exam-section" id="question-bank-section">
                        <div class="exam-section__head exam-section__head--with-action">
                            <div class="exam-section__head-copy">
                                <h2>9. Question Bank Management</h2>
                                <p>Track availability by category, fill shortfalls, and keep question creation always accessible.</p>
                            </div>
                            <button
                                type="button"
                                id="refresh-question-bank"
                                class="question-bank-refresh-btn"
                                title="Refresh question bank"
                                aria-label="Refresh question bank"
                            >
                                <span class="question-bank-refresh-btn__icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 12a9 9 0 1 1-2.64-6.36"/>
                                        <polyline points="21 3 21 9 15 9"/>
                                    </svg>
                                </span>
                                <span class="question-bank-refresh-btn__label">Refresh</span>
                            </button>
                        </div>
                        <div class="exam-section__body space-y-4" id="question-bank-container" data-question-bank>
                            <div class="question-bank-toolbar">
                                <label class="question-search">
                                    <span>Search Questions</span>
                                    <input id="question-search" type="search" class="panel-input" placeholder="Search by keyword" data-question-search-input>
                                </label>
                                <div class="question-bank-global-actions">
                                    <div class="global-selection-stats">
                                        Total Selected: <span id="global-selected-count">0</span> / <span id="global-allowed-count">0</span>
                                    </div>
                                    <div class="global-action-buttons">
                                        <button type="button" id="global-random-select" class="panel-button-secondary">Random Select</button>
                                        <button type="button" id="open-add-question-modal" class="panel-button-secondary">Add Question</button>
                                        <input type="hidden" name="question_ids" id="question_ids" value="[]">
                                    </div>
                                </div>
                            </div>

                            <div id="question-bank-feedback" class="exam-help" data-question-bank-feedback></div>
                            <div id="question-category-cards" class="question-category-cards" data-question-category-cards></div>
                        </div>
                    </section>

                    <section class="exam-section" id="instructions-rules-section">
                        <div class="exam-section__head">
                            <h2>Exam Instructions &amp; Rules Management</h2>
                            <p>Enable or disable predefined rules that candidates must follow during the exam.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div class="instruction-rules-header">
                                <p class="exam-help">Turn on the rules you want to enforce for this exam session. Disabling a rule removes it from the selection.</p>
                                <p class="exam-help"><strong id="selected-instruction-rules-count">0</strong> predefined rules enabled.</p>
                            </div>
                            <div id="instruction-rules-list" class="instruction-rules-grid"></div>
                            <input
                                type="hidden"
                                name="predefined_instruction_rules"
                                id="predefined_instruction_rules"
                                value="{{ json_encode(old('predefined_instruction_rules', collect($formOptions['instructionRules'] ?? [])->where(fn ($r) => !empty($r['is_default']) || !empty($r['is_required']))->pluck('id')->values()->all())) }}"
                            >
                        </div>
                    </section>

                    <section class="exam-section" id="instructions-section">
                        <div class="exam-section__head">
                            <h2>Instructions for Candidates</h2>
                            <p>Provide clear, structured guidance shown before exam start.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div class="instruction-tools">
                                <div class="instruction-tools__select">
                                    <label for="instruction_template" class="exam-label">Default Instruction Templates</label>
                                    <select id="instruction_template" class="panel-input"></select>
                                </div>
                                <button type="button" id="apply-instruction-template" class="panel-button-secondary">Apply Template</button>
                            </div>

                            <div class="instruction-editor-wrap">
                                <x-rich-text-editor
                                    label="Candidate Instructions"
                                    input-id="candidate_instructions"
                                    name="instructions"
                                    :value="old('instructions')"
                                    placeholder="Add clear instructions for candidates before they start the exam."
                                    :height="320"
                                    preset="full"
                                    module="exam"
                                    help="Supports rich formatting, media, tables, attachments, and HTML source view."
                                />
                            </div>
                            <div class="instruction-footer">
                                <p class="instruction-counter"><span id="instructions-char-count">0</span> characters</p>
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="exam-create-aside">
                    <section class="summary-box">
                        <h3>Workflow Progress</h3>
                        <ul id="workflow-status-list" class="preview-list"></ul>
                    </section>

                    <section class="summary-box">
                        <h3>Live Snapshot</h3>
                        <dl class="summary-stats" id="live-snapshot">
                            <div><dt>Visibility</dt><dd id="snapshot-visibility">-</dd></div>
                            <div><dt>Exam Mode</dt><dd id="snapshot-mode">-</dd></div>
                            <div><dt>Categories</dt><dd id="snapshot-categories">0</dd></div>
                            <div><dt>Marks Filters</dt><dd id="snapshot-marks">0</dd></div>
                            <div><dt>Timer</dt><dd id="snapshot-timer">-</dd></div>
                            <div><dt>Exam Format</dt><dd id="snapshot-exam-format">-</dd></div>
                            <div><dt>Schedule</dt><dd id="snapshot-schedule">-</dd></div>
                            <div><dt>Attempts</dt><dd id="snapshot-attempts">-</dd></div>
                            <div><dt>Candidate Emails</dt><dd id="snapshot-candidates">0</dd></div>
                            <div><dt>Discount Rules</dt><dd id="snapshot-discounts">0</dd></div>
                            <div><dt>Enabled Rules</dt><dd id="snapshot-instruction-rules">0</dd></div>
                        </dl>
                    </section>
                </aside>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
            SEO & METADATA SECTION (Identical across all Create & Edit views)
            ════════════════════════════════════════════════════════════════ --}}
            @php
                $seoItem = $exam ?? null;
            @endphp
            <div id="metadata-section" class="category-builder__metadata mt-8">
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

            <div id="form-error-banner" class="form-error-banner" hidden></div>
            <footer class="exam-page-footer">
                <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary">Save Exam</button>
            </footer>
        </form>
    </x-page-card>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backend/tom-select-theme.css') }}?v={{ filemtime(public_path('css/backend/tom-select-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modules/form-utils.css') }}?v={{ filemtime(public_path('css/modules/form-utils.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/exam-create.css') }}?v={{ filemtime(public_path('css/backend/exam-create.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/question-bank-accordion.css') }}?v={{ filemtime(public_path('css/question-bank-accordion.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}?v={{ filemtime(public_path('css/backend/question-category-form.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ filemtime(public_path('js/components/editor.js')) }}"></script>
    <script src="{{ asset('js/components/select.js') }}?v={{ filemtime(public_path('js/components/select.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-blur.js') }}?v={{ filemtime(public_path('js/components/tom-select-blur.js')) }}"></script>
    <script src="{{ asset('js/components/tom-select-hierarchy.js') }}?v={{ filemtime(public_path('js/components/tom-select-hierarchy.js')) }}"></script>
    <script src="{{ asset('js/components/question-bank-accordion.js') }}?v={{ filemtime(public_path('js/components/question-bank-accordion.js')) }}"></script>
    <script src="{{ asset('js/backend/question-bank-init.js') }}?v={{ filemtime(public_path('js/backend/question-bank-init.js')) }}"></script>
    <script src="{{ asset('js/backend/seo-manager.js') }}?v={{ filemtime(public_path('js/backend/seo-manager.js')) }}"></script>
    <script src="{{ asset('js/core/form-utils.js') }}?v={{ filemtime(public_path('js/core/form-utils.js')) }}"></script>
    <script>
        window.examCreateConfig = {
            options: @json($formOptions),
            endpoints: {
                // Categories only on first load. Question bank loads on demand (filters) to avoid timeouts.
                categories: @json(route('admin.api.question-bank.categories')),
                questionBank: @json(route('admin.api.question-bank.questions')),
            },
            bootstrapEndpoints: {
                categories: @json(route('admin.api.question-bank.categories')),
            },
        };
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = window.EmsTomSelectHierarchy?.create('#exam_category_id', {
                placeholder: "Search for a category...",
            }) || new TomSelect('#exam_category_id', {
                create: false,
                placeholder: "Search for a category...",
                closeAfterSelect: true,
            });
            if (!window.EmsTomSelectHierarchy) {
                window.EmsTomSelectBlur?.attach(categorySelect);
            }
            window.EmsTomSelectBlur?.blurNativeSelects(document.querySelector('form') || document);
        });
    </script>
    <script src="{{ asset('js/backend/exam-create.js') }}?v={{ filemtime(public_path('js/backend/exam-create.js')) }}"></script>
@endpush

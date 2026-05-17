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
        <form action="{{ route('admin.exams.store') }}" method="POST" id="exam-create-form" class="exam-create-form" novalidate>
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
                                    :height="210"
                                    :toolbar="['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo']"
                                />
                            </div>

                            <div class="exam-grid exam-grid--3" id="basic-meta-grid">
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
                                <div class="exam-grid-span-2">
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

                    <section class="exam-section" id="candidate-access-section">
                        <div class="exam-section__head">
                            <h2>2. Candidate Access Management</h2>
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
                            <h2>3. Exam Configuration</h2>
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
                            <h2>4. Question Rules and Filters</h2>
                            <p>Limit question import to selected marks only.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div>
                                <label class="exam-label">Question Marks Type</label>
                                <label class="switch-control" style="cursor: pointer;">
                                    <input id="mixed_marks_questions" name="mixed_marks_questions" type="checkbox" value="1">
                                    <span class="switch-control__track"></span>
                                    <span class="switch-control__label">Enable mixed marks questions (allows selecting multiple marks)</span>
                                </label>
                            </div>

                            <div>
                                <label class="exam-label">Question Marks Filter</label>
                                <div id="question-marks-filter" class="pill-group"></div>
                                <input type="hidden" name="question_marks_filter" id="question_marks_filter" value="[]">
                                <p class="exam-help">Only questions that match selected marks are available in the question bank.</p>
                                <p class="exam-help"><strong id="selected-marks-count">0</strong> marks filters selected.</p>
                            </div>
                        </div>
                    </section>

                    <section class="exam-section" id="pricing-section">
                        <div class="exam-section__head">
                            <h2>5. Pricing and Discount Rules</h2>
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
                                <p class="exam-help mt-1" style="color: #d97706;"><strong>&#9888;</strong> A 5% platform charge applies to all paid exams.</p>
                            </div>

                            <div id="free-candidates-wrap" class="mt-4 pt-4 border-t border-slate-200" hidden>
                                <div class="mb-3">
                                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                        <span>Free Candidate List</span> &nbsp;
                                        <span class="info-tip" tabindex="0" role="button" aria-label="Free Candidate List info" data-tooltip="Candidates added to this list will not be charged any fee to attempt the exam, while other candidates pay the default fee.">i</span>
                                    </h4>
                                    <p class="text-xs text-slate-500">Import or manually add candidate emails who will receive free access to this paid exam.</p>
                                </div>

                                <div class="segmented-control" role="tablist" aria-label="Free candidate access method">
                                    <button type="button" class="segmented-control__button is-active" data-free-candidate-tab="import" role="tab" aria-selected="true">Import Excel</button>
                                    <button type="button" class="segmented-control__button" data-free-candidate-tab="manual" role="tab" aria-selected="false">Manual Entry</button>
                                </div>

                                <div class="candidate-panel is-active" data-free-candidate-panel="import">
                                    <div class="candidate-panel__row mb-2">
                                        <p class="exam-help">Use a spreadsheet with <strong>Name</strong> and <strong>Email</strong> columns.</p>
                                        <a class="panel-button-secondary" href="{{ asset('data/exam-create/sample-candidates.csv') }}" download style="padding: 0.5rem 0.75rem; font-size: 0.75rem; height: auto;">Download Sample Excel Format</a>
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
                                <div class="custom-discounts-section mt-4 pt-4 border-t border-slate-200">
                                    <div class="flex justify-between items-center mb-3">
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">Custom Discount Offers</h4>
                                            <p class="text-xs text-slate-500">Create custom discount incentives for specific events or campaigns.</p>
                                        </div>
                                        <button type="button" id="add-custom-discount-btn" class="panel-button-secondary py-1.5 px-3 text-xs flex items-center gap-1.5" style="border-radius: var(--field-radius); padding: 0.5rem 0.75rem; height: auto;">
                                            <span>+ Add Custom Offer</span>
                                        </button>
                                    </div>

                                    <!-- Guide Info Box -->
                                    <div class="custom-discount-guide bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-800 mb-4 flex items-start gap-2">
                                        <span style="font-size: 1.1rem; line-height: 1;">💡</span>
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
                        <div class="exam-section__head">
                            <h2>6. Question Bank Management</h2>
                            <p>Track availability by category, fill shortfalls, and keep question creation always accessible.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div class="question-bank-toolbar">
                                <label class="question-search">
                                    <span>Search Questions</span>
                                    <input id="question-search" type="search" class="panel-input" placeholder="Search by keyword">
                                </label>
                                <button type="button" id="open-add-question-modal" class="panel-button-secondary">Add Question</button>
                            </div>

                            <div id="question-bank-feedback" class="exam-help"></div>
                            <div id="question-category-cards" class="question-category-cards"></div>
                        </div>
                    </section>

                    <section class="exam-section" id="instructions-section">
                        <div class="exam-section__head">
                            <h2>7. Instructions for Candidates</h2>
                            <p>Provide clear, structured guidance shown before exam start.</p>
                        </div>
                        <div class="exam-section__body space-y-4">
                            <div class="instruction-tools">
                                <div>
                                    <label for="instruction_template" class="exam-label">Default Instruction Templates</label>
                                    <select id="instruction_template" class="panel-input"></select>
                                </div>
                                <button type="button" id="apply-instruction-template" class="panel-button-secondary">Apply Template</button>
                            </div>

                            <x-rich-text-editor
                                label="Candidate Instructions"
                                input-id="candidate_instructions"
                                name="instructions"
                                :value="old('instructions')"
                                placeholder="Add concise instructions for candidates before they start the exam."
                                :height="200"
                                :toolbar="['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo']"
                                help="Supports bullet points, numbered lists, and short emphasis formatting."
                            />
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
                            <div><dt>Candidate Emails</dt><dd id="snapshot-candidates">0</dd></div>
                            <div><dt>Discount Rules</dt><dd id="snapshot-discounts">0</dd></div>
                        </dl>
                    </section>
                </aside>
            </div>

            <div id="form-error-banner" class="form-error-banner" hidden></div>
            <footer class="exam-page-footer">
                <a href="{{ route('admin.exams.index') }}" class="panel-button-secondary">Cancel</a>
                <button type="submit" class="panel-button-primary">Save Exam</button>
            </footer>
        </form>
    </x-page-card>

    <div id="add-question-modal" class="exam-modal" hidden>
        <div class="exam-modal__backdrop" data-modal-close></div>
        <div class="exam-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="add-question-modal-title">
            <div class="exam-modal__head">
                <h3 id="add-question-modal-title">Add Question</h3>
                <button type="button" class="exam-modal__close" data-modal-close aria-label="Close">x</button>
            </div>
            <form id="add-question-form" class="space-y-3">
                <div>
                    <label for="new_question_category" class="exam-label">Category</label>
                    <select id="new_question_category" class="panel-input"></select>
                </div>
                <div>
                    <label for="new_question_text" class="exam-label">Question Text</label>
                    <textarea id="new_question_text" rows="4" class="panel-input" placeholder="Write a concise question statement."></textarea>
                </div>
                <div class="exam-grid exam-grid--2">
                    <div>
                        <label for="new_question_marks" class="exam-label">Marks</label>
                        <select id="new_question_marks" class="panel-input"></select>
                    </div>
                    <div>
                        <label for="new_question_difficulty" class="exam-label">Difficulty</label>
                        <select id="new_question_difficulty" class="panel-input"></select>
                    </div>
                </div>
                <div class="exam-modal__footer">
                    <button type="button" class="panel-button-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="panel-button-primary">Add Question</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/modules/form-utils.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/backend/exam-create.css') }}?v={{ time() }}">
@endpush

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/components/editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/components/select.js') }}?v={{ time() }}"></script>
    <script>
        window.examCreateConfig = {
            endpoints: {
                difficultyLevels: "{{ asset('data/exam-create/difficulty-levels.json') }}",
                examStatus: "{{ asset('data/exam-create/exam-status.json') }}",
                examModes: "{{ asset('data/exam-create/exam-modes.json') }}",
                visibilityOptions: "{{ asset('data/exam-create/visibility-options.json') }}",
                categories: "{{ asset('data/exam-create/categories.json') }}",
                discountRules: "{{ asset('data/exam-create/discount-rules.json') }}",
                questionMarks: "{{ asset('data/exam-create/question-marks.json') }}",
                questionBank: "{{ asset('data/exam-create/question-bank.json') }}",
                pricingOptions: "{{ asset('data/exam-create/pricing-options.json') }}",
                distributionTypes: "{{ asset('data/exam-create/distribution-types.json') }}",
                instructionTemplates: "{{ asset('data/exam-create/instruction-templates.json') }}",
                currencies: "{{ asset('data/exam-create/currencies.json') }}"
            }
        };
    </script>
    <script src="{{ asset('js/backend/exam-create.js') }}?v={{ time() }}"></script>
@endpush

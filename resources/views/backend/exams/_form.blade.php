@php
    // ── Shared Create/Edit form partial ─────────────────────────────────────
    // $exam is null on create and an Exam model instance on edit. Field values
    // resolve in this priority: old(request input) -> exam's stored value -> default.
    // Complex JS-driven state (category selection, question bank, pill groups,
    // pricing, discounts, etc.) is hydrated client-side via `window.examFormConfig`
    // (see edit.blade.php) — this partial only seeds the raw HTML defaults that the
    // existing bootstrap JS already reads from hidden inputs on load.
    $exam = $exam ?? null;
    $httpMethod = $httpMethod ?? 'POST';
    $pageHeading = $pageHeading ?? 'Create Exam';
    $pageSubheading = $pageSubheading ?? 'Build a structured exam flow with clear rules, candidate access control, and question availability checks.';
    $headerBadge = $headerBadge ?? 'Draft Workspace';
    $submitLabel = $submitLabel ?? 'Save Exam';
    $cancelUrl = $cancelUrl ?? route('admin.exams.index');

    /** Scalar field: old() input wins, falls back to the exam's stored attribute, then $default. */
    $val = function (string $formKey, $default = null, ?string $examAttr = null) use ($exam) {
        $examAttr = $examAttr ?? $formKey;
        $examValue = $exam?->{$examAttr};
        return old($formKey, $examValue ?? $default);
    };

    /** Checkbox field: same resolution order, used inside @checked(). */
    $checkedVal = function (string $formKey, bool $default = false, ?string $examAttr = null) use ($exam) {
        $examAttr = $examAttr ?? $formKey;
        $examValue = $exam?->{$examAttr};
        return old($formKey, $examValue ?? $default);
    };

    /** Datetime field: formats the exam's Carbon attribute for datetime-picker inputs. */
    $dateVal = function (string $formKey, ?string $examAttr = null) use ($exam) {
        $examAttr = $examAttr ?? $formKey;
        $raw = $exam?->{$examAttr};
        $formatted = $raw instanceof \Illuminate\Support\Carbon ? $raw->format('Y-m-d H:i') : $raw;
        return old($formKey, $formatted);
    };

    /**
     * JSON hidden-field value: encodes the exam's stored array/object as the
     * fallback default. Several bootstrap-time JS routines read these hidden
     * values directly (tags, exam_format, schedule/attempt type, allocations,
     * custom discounts, instruction rules) so setting the right default here
     * is enough to hydrate them without extra JS.
     */
    $jsonVal = function (string $formKey, array $default = [], ?string $examAttr = null) use ($exam) {
        $examAttr = $examAttr ?? $formKey;
        $examValue = $exam?->{$examAttr};
        return old($formKey, json_encode(is_array($examValue) ? $examValue : $default));
    };
@endphp

<form action="{{ $formAction }}" method="POST" id="exam-create-form" class="exam-create-form exam-form" novalidate>
    @csrf
    @if(strtoupper($httpMethod) !== 'POST')
        @method($httpMethod)
    @endif

    <header class="exam-page-header">
        <div>
            <h1>{{ $pageHeading }}</h1>
            <p>{{ $pageSubheading }}</p>
        </div>
        <span class="exam-header-badge">{{ $headerBadge }}</span>
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
                            <input id="exam_title" name="title" type="text" class="panel-input" value="{{ $val('title') }}" placeholder="e.g. Senior Laravel Assessment - June 2026">
                        </div>

                        <x-rich-text-editor
                            label="Description"
                            input-id="exam_description"
                            name="description"
                            :value="$val('description')"
                            placeholder="Summarize scope, audience, and expected outcomes."
                            :height="240"
                            preset="full"
                                    module="exam"
                        />
                    </div>

                    <div class="exam-grid exam-grid--3" id="basic-meta-grid">
                        <div>
                            <label for="exam_category_id" class="exam-label">Exam Category <span class="form-required">*</span></label>
                            <select id="exam_category_id" name="exam_category_id" class="panel-input mt-1 block w-full" data-option-style="hierarchy" data-ems-select="manual" data-placeholder="Select category" data-no-search>
                                <option value="">Select category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        data-level="{{ $cat->depth }}"
                                        data-category-name="{{ $cat->name }}"
                                        class="{{ $cat->depth === 0 ? 'font-semibold text-slate-900' : '' }}"
                                        {{ $val('exam_category_id', null, 'category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="difficulty_level" class="exam-label">Difficulty Level <span class="info-tip" tabindex="0" role="button" aria-label="Difficulty level info" data-tooltip="Used for filtering and recommendation in reports.">i</span></label>
                            <select id="difficulty_level" name="difficulty_level" class="panel-input" data-placeholder="Select difficulty"></select>
                        </div>
                        <div>
                            <label for="exam_status" class="exam-label">Status <span class="form-required">*</span></label>
                            <select id="exam_status" name="status" class="panel-input" data-placeholder="Select status"></select>
                        </div>
                        <div>
                            <label for="exam_mode" class="exam-label">Exam Mode <span class="form-required">*</span></label>
                            <select id="exam_mode" name="exam_mode" class="panel-input" data-placeholder="Select mode"></select>
                        </div>
                        <div>
                            <label for="exam_visibility" class="exam-label">Visibility <span class="form-required">*</span></label>
                            <select id="exam_visibility" name="visibility" class="panel-input" data-placeholder="Select visibility"></select>
                        </div>
                        <div class="exam-grid-span-3">
                            <label for="exam-tags-input" class="exam-label">Tags <span class="info-tip" tabindex="0" role="button" aria-label="Tags input info" data-tooltip="Press Enter to add each tag. Commas are ignored.">i</span></label>
                            <div class="chip-input" data-chip-input="tags">
                                <input id="exam-tags-input" type="text" placeholder="Type a tag and press Enter">
                            </div>
                            <input type="hidden" name="tags" id="exam_tags" value="{{ $jsonVal('tags', [], 'tags') }}">
                            <p class="exam-help">Tags help in searching, segmentation, and reporting.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="exam-section" id="candidate-access-section" hidden>
                <div class="exam-section__head">
                    <h2>2. Candidate Access Management</h2>
                    <p>Import or manually add the candidates who may access this exam.</p>
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

                        <input type="hidden" name="imported_candidates" id="imported_candidates" value="{{ $jsonVal('imported_candidates', [], 'imported_candidates') }}">
                        <div id="imported-candidate-preview" class="candidate-preview" hidden></div>
                    </div>

                    <div class="candidate-panel" data-candidate-panel="manual" hidden>
                        <label for="manual-email-input" class="exam-label">Add candidate emails <span class="info-tip" tabindex="0" role="button" aria-label="Add candidate emails info" data-tooltip="Type an email address and press Enter to add it. You can add multiple emails one by one.">i</span></label>
                        <div class="chip-input" data-chip-input="emails">
                            <input id="manual-email-input" type="text" placeholder="Type email and press Enter">
                        </div>
                        <input type="hidden" name="manual_candidate_emails" id="manual_candidate_emails" value="{{ $jsonVal('manual_candidate_emails', [], 'manual_candidate_emails') }}">
                        <p id="manual-email-feedback" class="exam-help"></p>
                    </div>
                </div>
            </section>

            <section class="exam-section" id="timer-section">
                <div class="exam-section__head">
                    <h2>3. Timer &amp; Duration Management</h2>
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
                                @checked($checkedVal('enable_exam_timer', true))
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
                                value="{{ $val('exam_duration_minutes', 60, 'duration') }}"
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
                                    @checked($checkedVal('auto_submit_on_timer_end', true))
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

            <section class="exam-section" id="schedule-section" data-collapsed-default="true">
                <div class="exam-section__head">
                    <h2>4. Schedule &amp; Attempt Management</h2>
                    <p>Control when the exam is available and how many attempts each candidate can make.</p>
                </div>
                <div class="exam-section__body space-y-5">
                    <div>
                        <label class="exam-label">Schedule Availability</label>
                        <div id="schedule-type-options" class="option-card-grid option-card-grid--compact"></div>
                        <input type="hidden" name="schedule_type" id="schedule_type" value="{{ $val('schedule_type', 'any_time', 'schedule_type') }}">
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
                                value="{{ $dateVal('schedule_start_at', 'scheduled_start') }}"
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
                                value="{{ $dateVal('schedule_end_at', 'scheduled_end') }}"
                                placeholder="Select end date and time"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="exam-label">Attempt Limit Per Candidate</label>
                        <div id="attempt-limit-options" class="option-card-grid option-card-grid--compact"></div>
                        <input type="hidden" name="attempt_limit_type" id="attempt_limit_type" value="{{ $val('attempt_limit_type', 'unlimited', 'attempt_limit_type') }}">
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
                                value="{{ $val('attempt_limit_count', 2, 'max_attempts') }}"
                                placeholder="e.g. 2 or 3"
                            >
                        </div>
                        <p class="exam-help">Use this only when “Fixed Attempts” is selected.</p>
                    </div>

                    <p id="schedule-config-summary" class="exam-help"></p>
                </div>
            </section>

            <section class="exam-section" id="exam-format-section">
                <div class="exam-section__head">
                    <h2>5. Exam Format Management</h2>
                    <p>Choose the format style for this exam session.</p>
                </div>
                <div class="exam-section__body space-y-4">
                    <div>
                        <label class="exam-label">Exam Format <span class="form-required">*</span></label>
                        <div id="exam-format-options" class="option-card-grid"></div>
                        <input type="hidden" name="exam_format" id="exam_format" value="{{ $jsonVal('exam_format', ['mcq'], 'exam_format') }}">
                        <p class="exam-help">Select one or more formats: MCQ, Multi Select, True/False, Written, Fill in the Blanks.</p>
                    </div>
                </div>
            </section>

            <section class="exam-section" id="exam-parts-section">
                <div class="exam-section__head exam-section__head--with-action">
                    <div class="exam-section__head-copy">
                        <h2>6. Exam Parts</h2>
                        <p>Each part has its own configuration, filters, categories, and question bank. Totals roll up into the exam summary.</p>
                    </div>
                    <button type="button" id="add-exam-part" class="panel-button-secondary">+ Add Part</button>
                </div>
                <div class="exam-section__body space-y-4">
                    <div id="exam-parts-list" class="exam-parts-list" aria-live="polite"></div>
                    @include('backend.exams._part')
                </div>
            </section>

            <section class="exam-section" id="exam-scoring-section">
                <div class="exam-section__head">
                    <h2>7. Passing Marks &amp; Negative Marking</h2>
                    <p>Exam-level scoring gateways applied across all parts.</p>
                </div>
                <div class="exam-section__body space-y-4">
                    <div class="exam-grid exam-grid--3">
                        <div>
                            <label for="passing_marks" class="exam-label">Passing Marks <span class="form-required">*</span></label>
                            <input id="passing_marks" name="passing_marks" type="number" class="panel-input" min="0" step="1" value="{{ $val('passing_marks', 30, 'passing_marks') }}" placeholder="Enter passing marks">
                            <p class="exam-help">Must be less than or equal to total marks across all parts (<span id="passing-marks-ceiling">0</span>).</p>
                        </div>
                        <div>
                            <label class="exam-label">Negative Marking</label>
                            <label class="switch-control">
                                <input id="enable_negative_marking" name="enable_negative_marking" type="checkbox" value="1" @checked($checkedVal('enable_negative_marking', true))>
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Enable Negative Marking</span>
                            </label>
                        </div>
                        <div id="negative-marking-config" @if(! $checkedVal('enable_negative_marking', true)) hidden @endif>
                            <label for="negative_marking_type" class="exam-label">Penalty Marks</label>
                            @php $negativeMarkingType = (string) $val('negative_marking_type', '25', 'negative_marking_type'); @endphp
                            <select id="negative_marking_type" name="negative_marking_type" class="panel-input">
                                <option value="25" @selected($negativeMarkingType === '25')>25% (1/4th of question marks)</option>
                                <option value="33.33" @selected($negativeMarkingType === '33.33')>33.33% (1/3rd of question marks)</option>
                                <option value="50" @selected($negativeMarkingType === '50')>50% (1/2 of question marks)</option>
                                <option value="100" @selected($negativeMarkingType === '100')>100% (Full question marks)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>
            <section class="exam-section" id="pricing-section" data-collapsed-default="true">
                <div class="exam-section__head">
                    <h2>8. Pricing and Discount Rules</h2>
                    <p>Choose pricing strategy and attach predefined discount rules.</p>
                </div>
                <div class="exam-section__body space-y-5">
                    <div>
                        <label class="exam-label">Pricing Option</label>
                        <div id="pricing-options" class="option-card-grid"></div>
                        <input type="hidden" name="pricing_option" id="pricing_option" value="{{ $val('pricing_option', 'free', 'pricing_option') }}">
                        <p id="pricing-imported-note" class="exam-help" hidden>Imported candidates will get free access. Candidate import tools are now available.</p>
                    </div>

                    <div id="pricing-details-wrap" hidden>
                        <label class="exam-label">Exam Fee</label>
                        <div class="pricing-amount-group">
                            <select id="exam_currency" name="exam_currency" class="panel-input pricing-amount-group__currency" data-placeholder="Select currency"></select>
                            <input type="number" id="exam_amount" name="exam_amount" class="pricing-amount-group__input" min="0" step="0.01" value="{{ $val('exam_amount', '', 'exam_amount') }}" placeholder="Enter amount">
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

                            <input type="hidden" name="free_imported_candidates" id="free_imported_candidates" value="{{ $jsonVal('free_imported_candidates', [], 'free_imported_candidates') }}">
                            <div id="free-imported-candidate-preview" class="candidate-preview mt-2" hidden></div>
                        </div>

                        <div class="candidate-panel" data-free-candidate-panel="manual" hidden>
                            <label for="free-manual-email-input" class="exam-label">Add free candidate emails <span class="info-tip" tabindex="0" role="button" aria-label="Add free candidate emails info" data-tooltip="Type an email address and press Enter to add it. You can add multiple emails one by one.">i</span></label>
                            <div class="chip-input" data-chip-input="free-emails">
                                <input id="free-manual-email-input" type="text" placeholder="Type email and press Enter">
                            </div>
                            <input type="hidden" name="free_manual_candidate_emails" id="free_manual_candidate_emails" value="{{ $jsonVal('free_manual_candidate_emails', [], 'free_manual_candidate_emails') }}">
                            <p id="free-manual-email-feedback" class="exam-help"></p>
                        </div>
                    </div>

                    <div id="discount-rules-wrap" hidden>
                        <label class="exam-label">Discount Rules</label>
                        <div id="discount-rules" class="option-card-grid option-card-grid--compact"></div>
                        <input type="hidden" name="selected_discounts" id="selected_discounts" value="{{ $jsonVal('selected_discounts', [], 'selected_discounts') }}">

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
                            <input type="hidden" name="custom_discounts" id="custom_discounts" value="{{ $jsonVal('custom_discounts', [], 'custom_discounts') }}">
                        </div>
                    </div>

                    <div class="summary-box" id="discount-summary-wrap" hidden>
                        <h4>Discount Summary Preview</h4>
                        <ul id="discount-summary" class="preview-list"></ul>
                    </div>
                </div>
            </section>

            <section class="exam-section" id="instructions-rules-section" data-collapsed-default="true">
                <div class="exam-section__head">
                    <h2>9. Exam Instructions &amp; Rules Management</h2>
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
                        @php
                            if (old('predefined_instruction_rules') !== null) {
                                $instructionRulesSeed = old('predefined_instruction_rules');
                            } elseif ($exam !== null) {
                                $instructionRulesSeed = $exam->predefined_instruction_rules ?? [];
                            } else {
                                $instructionRulesSeed = collect($formOptions['instructionRules'] ?? [])
                                    ->where(fn ($r) => ! empty($r['is_default']) || ! empty($r['is_required']))
                                    ->pluck('id')
                                    ->values()
                                    ->all();
                            }
                            if (! is_array($instructionRulesSeed)) {
                                $instructionRulesSeed = [];
                            }
                        @endphp
                        value="{{ json_encode(array_values($instructionRulesSeed)) }}"
                    >

                    @php
                        $warningLimitSeed = old('focus_violation_limit', $exam?->proctoringPolicy?->focus_violation_limit ?? 3);
                    @endphp
                    <div class="instruction-warning-limit">
                        <label for="focus_violation_limit" class="exam-label">Warnings allowed before auto-submit</label>
                        <p class="exam-help">How many monitored warnings (tab switch, focus loss, etc.) a candidate may receive before the exam is auto-submitted. Use <strong>0</strong> for no warnings (first violation submits).</p>
                        <div class="instruction-warning-limit__row">
                            <input
                                type="number"
                                name="focus_violation_limit"
                                id="focus_violation_limit"
                                class="panel-input"
                                min="0"
                                max="99"
                                step="1"
                                required
                                value="{{ (int) $warningLimitSeed }}"
                            >
                            <div class="instruction-warning-limit__presets" role="group" aria-label="Quick presets">
                                @foreach ([0, 1, 3, 5, 10] as $preset)
                                    <button type="button" class="panel-button-secondary js-warning-limit-preset" data-value="{{ $preset }}">{{ $preset }}</button>
                                @endforeach
                            </div>
                        </div>
                        @error('focus_violation_limit')
                            <p class="exam-help exam-help-warning">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="exam-section" id="instructions-section">
                <div class="exam-section__head">
                    <h2>10. Instructions for Candidates</h2>
                    <p>Provide clear, structured guidance shown before exam start.</p>
                </div>
                <div class="exam-section__body space-y-4">
                    <div class="instruction-tools">
                        <div class="instruction-tools__select">
                            <label for="instruction_template" class="exam-label">Default Instruction Templates</label>
                            <select id="instruction_template" class="panel-input" data-placeholder="Select template"></select>
                        </div>
                        <button type="button" id="apply-instruction-template" class="panel-button-secondary">Apply Template</button>
                    </div>

                    <div class="instruction-editor-wrap">
                        <x-rich-text-editor
                            label="Candidate Instructions"
                            input-id="candidate_instructions"
                            name="instructions"
                            :value="$val('instructions')"
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

        <aside class="exam-create-aside" id="exam-create-aside" aria-label="Exam create progress and summary">
            <section class="summary-box exam-aside-readiness" id="exam-aside-readiness">
                <div class="exam-aside-readiness__head">
                    <h3>Create Progress</h3>
                    <span class="exam-aside-readiness__badge" id="sidebar-readiness-badge">Checking…</span>
                </div>
                <div class="exam-aside-progress" aria-hidden="true">
                    <div class="exam-aside-progress__track">
                        <div class="exam-aside-progress__fill" id="sidebar-progress-fill"></div>
                    </div>
                    <p class="exam-aside-progress__label" id="sidebar-progress-label">0 / 0 checks ready</p>
                </div>
                <ul class="exam-aside-legend" aria-label="Status legend">
                    <li><span class="exam-aside-dot exam-aside-dot--ok"></span> Ready</li>
                    <li><span class="exam-aside-dot exam-aside-dot--warn"></span> Optional</li>
                    <li><span class="exam-aside-dot exam-aside-dot--error"></span> Required</li>
                </ul>
            </section>

            <section class="summary-box exam-aside-validation">
                <h3>Validation Checklist</h3>
                <p class="exam-help exam-aside-summary__help">Required fields turn green when valid. Missing required items stay red.</p>
                <ul id="sidebar-validation-list" class="exam-aside-check-list"></ul>
            </section>

            <section class="summary-box exam-aside-summary" id="exam-final-summary-section">
                <h3>Exam Summary</h3>
                <p class="exam-help exam-aside-summary__help">Live roll-up of every part. Updates as you edit.</p>
                <dl class="exam-final-summary-stats" id="exam-final-summary-stats">
                    <div><dt>Total Parts</dt><dd id="summary-total-parts">0</dd></div>
                    <div><dt>Total Questions</dt><dd id="summary-total-questions">0</dd></div>
                    <div><dt>Total Marks</dt><dd id="summary-total-marks">0</dd></div>
                    <div><dt>Total Duration</dt><dd id="summary-total-duration">0 min</dd></div>
                    <div><dt>Passing Marks</dt><dd id="summary-passing-marks">0</dd></div>
                    <div><dt>Negative Marking</dt><dd id="summary-negative-marks">Off</dd></div>
                </dl>

                <article class="config-preview-card exam-aside-summary-card">
                    <h4>Question Distribution</h4>
                    <ul id="summary-question-distribution" class="preview-list"></ul>
                </article>
                <article class="config-preview-card exam-aside-summary-card">
                    <h4>Category Summary</h4>
                    <ul id="summary-category-list" class="preview-list"></ul>
                </article>
                <article class="config-preview-card exam-aside-summary-card">
                    <h4>Overall Exam Overview</h4>
                    <ul id="summary-overview-list" class="preview-list"></ul>
                </article>
            </section>
        </aside>
    </div>

    {{-- SEO & METADATA (shared partial — keep constant across modules) --}}
    @include('backend.partials.seo-metadata-section', [
        'seoItem' => $exam ?? null,
        'showSlug' => true,
        'slugPlaceholder' => 'auto-generated-from-title',
        'sectionClass' => 'mt-8',
        'showPublishingExtras' => true,
        'previewBaseUrl' => url('/exams'),
        'previewClassPrefix' => 'exam',
    ])

    <div id="form-error-banner" class="form-error-banner" hidden></div>
    <footer class="exam-page-footer">
        <a href="{{ $cancelUrl }}" class="panel-button-secondary">Cancel</a>
        <button type="submit" class="panel-button-primary">{{ $submitLabel }}</button>
    </footer>
</form>

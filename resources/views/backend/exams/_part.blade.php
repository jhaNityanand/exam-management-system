{{--
  Exam Part card template (cloned by exam-create.js).
  Placeholders: __INDEX__, __PART_KEY__, __PART_NAME__, __IS_DEFAULT__
  All interactive fields use data-field so JS can scope lookups inside [data-exam-part].
--}}
<template id="exam-part-template">
    <article class="exam-part-card is-expanded" data-exam-part data-part-key="__PART_KEY__" data-part-index="__INDEX__" data-is-default="__IS_DEFAULT__">
        <header class="exam-part-card__header">
            <button type="button" class="exam-part-card__toggle" data-part-action="toggle" aria-expanded="true">
                <span class="exam-part-card__chevron" aria-hidden="true"></span>
                <span class="exam-part-card__title-wrap">
                    <span class="exam-part-card__badge" data-part-badge>Part</span>
                    <input
                        type="text"
                        class="exam-part-card__name-input"
                        data-field="name"
                        name="parts[__INDEX__][name]"
                        value="__PART_NAME__"
                        aria-label="Part name"
                        maxlength="120"
                    >
                </span>
            </button>
            <div class="exam-part-card__actions">
                <button type="button" class="exam-part-card__action" data-part-action="duplicate" title="Duplicate part">Duplicate</button>
                <button type="button" class="exam-part-card__action exam-part-card__action--danger" data-part-action="delete" title="Delete part" data-part-delete hidden>Delete</button>
            </div>
            <input type="hidden" data-field="id" name="parts[__INDEX__][id]" value="">
            <input type="hidden" data-field="is_default" name="parts[__INDEX__][is_default]" value="__IS_DEFAULT__">
            <input type="hidden" data-field="temp_key" name="parts[__INDEX__][temp_key]" value="__PART_KEY__">
        </header>

        <div class="exam-part-card__body" data-part-body>
            {{-- Configuration --}}
            <section class="exam-part-section">
                <div class="exam-part-section__head">
                    <h3>Part Configuration</h3>
                    <p>Questions, marks, and selection rules for this part only.</p>
                </div>
                <div class="exam-part-section__body space-y-5">
                    <div class="exam-grid exam-grid--3">
                        <div>
                            <label class="exam-label">Total Questions Ask <span class="form-required">*</span></label>
                            <input data-field="total_questions" name="parts[__INDEX__][total_questions]" type="number" class="panel-input" min="1" step="1" value="30" placeholder="Enter total questions">
                        </div>
                        <div>
                            <label class="exam-label">Total Marks <span class="form-required">*</span></label>
                            <input data-field="total_marks" name="parts[__INDEX__][total_marks]" type="number" class="panel-input" min="1" step="1" value="50" placeholder="Enter total marks">
                        </div>

                        <div class="config-toggle-card">
                            <label class="exam-label">Question Pool</label>
                            <label class="switch-control">
                                <input data-field="use_question_pool" name="parts[__INDEX__][use_question_pool]" type="checkbox" value="1" checked>
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Select each candidate's questions from a larger pool</span>
                            </label>
                        </div>
                        <div data-field-wrap="maximum_questions" hidden>
                            <label class="exam-label">Maximum Questions in Pool <span class="form-required">*</span></label>
                            <input data-field="maximum_questions" name="parts[__INDEX__][maximum_questions]" type="number" class="panel-input" min="2" step="1" value="50" placeholder="Must exceed total questions">
                            <p class="exam-help" data-field-help="maximum_questions"></p>
                        </div>
                        <div class="config-toggle-card" data-field-wrap="fixed_questions">
                            <label class="exam-label">Fixed Questions</label>
                            <label class="switch-control">
                                <input data-field="fixed_questions" name="parts[__INDEX__][fixed_questions]" type="checkbox" value="1">
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Use the same selected questions for every candidate</span>
                            </label>
                        </div>
                        <div class="config-toggle-card">
                            <label class="exam-label">Fixed Paper Set</label>
                            <label class="switch-control">
                                <input data-field="fixed_paper_set" name="parts[__INDEX__][fixed_paper_set]" type="checkbox" value="1">
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Generate multiple fixed paper sets</span>
                            </label>
                        </div>
                        <div data-field-wrap="paper_sets" hidden>
                            <label class="exam-label">Paper Set <span class="form-required">*</span></label>
                            <input data-field="paper_sets" name="parts[__INDEX__][paper_sets]" type="number" class="panel-input" min="1" step="1" value="1">
                        </div>
                        <div class="config-toggle-card">
                            <label class="exam-label">Fix Each Category Question Count</label>
                            <label class="switch-control">
                                <input data-field="fix_category_questions" name="parts[__INDEX__][fix_category_questions]" type="checkbox" value="1">
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Enable exact per-category question allocation</span>
                            </label>
                        </div>
                        <div class="config-toggle-card">
                            <label class="exam-label">Fix Each Category Marks</label>
                            <label class="switch-control">
                                <input data-field="fix_category_marks" name="parts[__INDEX__][fix_category_marks]" type="checkbox" value="1">
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Distribute total marks across selected categories</span>
                            </label>
                        </div>
                        <div class="config-toggle-card">
                            <label class="exam-label">Shuffle Questions</label>
                            <label class="switch-control">
                                <input data-field="shuffle_questions" name="parts[__INDEX__][shuffle_questions]" type="checkbox" value="1" checked>
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Randomize question order for each candidate</span>
                            </label>
                        </div>
                        <div class="config-toggle-card">
                            <label class="exam-label">Shuffle Categories</label>
                            <label class="switch-control">
                                <input data-field="shuffle_categories" name="parts[__INDEX__][shuffle_categories]" type="checkbox" value="1" checked>
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Randomize category order for each candidate</span>
                            </label>
                        </div>
                        <div class="config-toggle-card" data-field-wrap="shuffle_options" hidden>
                            <label class="exam-label">Shuffle Options</label>
                            <label class="switch-control">
                                <input data-field="shuffle_options" name="parts[__INDEX__][shuffle_options]" type="checkbox" value="1" checked>
                                <span class="switch-control__track"></span>
                                <span class="switch-control__label">Randomize answer option order for each candidate</span>
                            </label>
                        </div>
                        <div>
                            <label class="exam-label">Question Distribution Type</label>
                            <div class="pill-group" data-field-ui="distribution_type"></div>
                            <input type="hidden" data-field="distribution_type" name="parts[__INDEX__][distribution_type]" value="">
                        </div>
                    </div>

                    <div data-field-wrap="category_selector">
                        <div class="exam-section__mini-head">
                            <h4>Select Categories</h4>
                            <p>Select one or more categories for this part.</p>
                        </div>
                        <select
                            data-field="selected_categories_select"
                            class="panel-input"
                            multiple
                            data-select-mode="multiple"
                            data-option-style="hierarchy"
                            data-placeholder="Select categories"
                            data-max-items="100"
                        ></select>
                        <input type="hidden" data-field="selected_categories" name="parts[__INDEX__][selected_categories]" value="[]">
                        <p class="exam-help" data-field-help="category_selection"></p>
                    </div>

                    <div class="config-preview-card" data-field-wrap="fixed_category_distribution" hidden>
                        <h4>Fixed Category Question Allocation</h4>
                        <p class="exam-help" data-field-help="fixed_distribution"></p>
                        <div class="category-allocation-panel mt-3" data-field-wrap="extra_questions_allocations" hidden>
                            <p class="exam-label mb-2">
                                Questions per Category
                                <span class="category-allocation-panel__meta">(Allocated: <span data-field-ui="allocated_count">0</span> / <span data-field-ui="remaining_count">0</span>)</span>
                            </p>
                            <div class="exam-grid exam-grid--3" data-field-ui="extra_questions_allocation_list"></div>
                            <input type="hidden" data-field="extra_questions_categories" name="parts[__INDEX__][extra_questions_categories]" value="[]">
                            <input type="hidden" data-field="extra_questions_allocations" name="parts[__INDEX__][extra_questions_allocations]" value="{}">
                        </div>
                    </div>

                    <div class="config-preview-card" data-field-wrap="fixed_category_marks_distribution" hidden>
                        <h4>Fixed Category Marks Allocation</h4>
                        <p class="exam-help" data-field-help="fixed_category_marks"></p>
                        <div class="category-allocation-panel mt-3" data-field-wrap="extra_marks_allocations">
                            <p class="exam-label mb-2">
                                Marks per Category
                                <span class="category-allocation-panel__meta">(Allocated: <span data-field-ui="marks_allocated_count">0</span> / <span data-field-ui="marks_remaining_count">0</span>)</span>
                            </p>
                            <div class="exam-grid exam-grid--3" data-field-ui="extra_marks_allocation_list"></div>
                        </div>
                        <input type="hidden" data-field="extra_marks_allocations" name="parts[__INDEX__][extra_marks_allocations]" value="{}">
                    </div>
                </div>
            </section>

            {{-- Question Rules & Filters (negative marking stays exam-level) --}}
            <section class="exam-part-section">
                <div class="exam-part-section__head">
                    <h3>Question Rules &amp; Filters</h3>
                    <p>Limit this part’s bank to selected marks.</p>
                </div>
                <div class="exam-part-section__body space-y-4">
                    <div>
                        <label class="exam-label">Question Marks Type</label>
                        <label class="switch-control">
                            <input data-field="fix_marks_each_question" name="parts[__INDEX__][fix_marks_each_question]" type="checkbox" value="1">
                            <span class="switch-control__track"></span>
                            <span class="switch-control__label">Fix Marks Each Question (All questions will have the same marks)</span>
                        </label>
                    </div>
                    <div>
                        <label class="exam-label">Question Marks Filter</label>
                        <div class="pill-group" data-field-ui="question_marks_filter"></div>
                        <input type="hidden" data-field="question_marks_filter" name="parts[__INDEX__][question_marks_filter]" value="[]">
                        <p class="exam-help">Only questions that match selected marks are available in this part’s question bank.</p>
                        <p class="exam-help"><strong data-field-ui="selected_marks_count">0</strong> marks filters selected.</p>
                    </div>
                    <div class="marks-management-card" data-field-wrap="marks_calculation_management" hidden>
                        <h4 class="marks-management-card__title">Marks Calculation Management</h4>
                        <p class="exam-help marks-management-card__help">
                            When fixed marks are enabled, Total Marks must equal Total Questions × Selected Marks Per Question.
                        </p>
                        <div class="marks-management-card__summary" data-field-ui="marks_calculation_summary" aria-live="polite"></div>
                        <p class="marks-management-card__warning" data-field-ui="marks_calculation_warning" role="status" aria-live="polite"></p>
                        <p class="marks-management-card__suggestion" data-field-ui="marks_calculation_suggestion" aria-live="polite"></p>
                        <div class="marks-management-card__actions" data-field-wrap="marks_calculation_actions" hidden>
                            <button type="button" class="panel-button-secondary" data-part-action="fix_total_marks">Update Total Marks</button>
                            <button type="button" class="panel-button-secondary" data-part-action="fix_total_questions">Update Total Questions</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Question Bank --}}
            <section class="exam-part-section">
                <div class="exam-part-section__head exam-section__head--with-action">
                    <div class="exam-section__head-copy">
                        <h3>Question Bank Management</h3>
                        <p>Track availability by category and select questions for this part.</p>
                    </div>
                    <button type="button" class="question-bank-refresh-btn" data-part-action="refresh_bank" title="Refresh question bank">
                        <span class="question-bank-refresh-btn__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 1 1-2.64-6.36"/>
                                <polyline points="21 3 21 9 15 9"/>
                            </svg>
                        </span>
                        <span class="question-bank-refresh-btn__label">Refresh</span>
                    </button>
                </div>
                <div class="exam-part-section__body space-y-4" data-question-bank>
                    <div class="question-bank-toolbar">
                        <div class="question-bank-toolbar__left">
                            <label class="question-search-label">Search Questions</label>
                            <input type="search" class="panel-input question-bank-toolbar__search-input" placeholder="Search by keyword" data-field="question_search" data-question-search-input>
                        </div>
                        <div class="question-bank-toolbar__right">
                            <div class="global-selection-stats">
                                Total Selected: <span data-field-ui="global_selected_count">0</span> / <span data-field-ui="global_allowed_count">0</span>
                                <span class="global-selection-range" data-field-ui="global_selection_range" hidden></span>
                            </div>
                            <div class="global-action-buttons">
                                <button type="button" class="panel-button-secondary" data-part-action="random_select">Random Select</button>
                                <button type="button" class="panel-button-secondary" data-part-action="add_question">Add Question</button>
                                <input type="hidden" data-field="question_ids" name="parts[__INDEX__][question_ids]" value="[]">
                            </div>
                        </div>
                    </div>
                    <div class="exam-help" data-field-ui="question_bank_load_meta" data-question-bank-load-meta></div>
                    <div class="question-bank-load-more-wrap" data-field-wrap="question_bank_load_more" hidden>
                        <button type="button" class="panel-button-secondary" data-part-action="load_more">Load more questions</button>
                    </div>
                    <div class="question-bank-shortages" data-field-ui="question_bank_shortages" hidden></div>
                    <div class="exam-help" data-field-ui="question_bank_feedback" data-question-bank-feedback></div>
                    <div class="question-category-cards" data-field-ui="question_category_cards" data-question-category-cards></div>
                </div>
            </section>

            <div class="exam-part-card__meta" data-field-ui="part_meta_summary"></div>
        </div>
    </article>
</template>

class ChipInput {
    constructor(container, options = {}) {
        this.container = container;
        this.input = container ? container.querySelector('input') : null;
        this.values = [];
        this.options = Object.assign(
            {
                validate: () => true,
                normalize: (value) => value.trim(),
                duplicateKey: (value) => value.toLowerCase(),
                chipClass: '',
                onChange: () => {},
                onInvalid: () => {},
            },
            options
        );

        if (this.container && this.input) {
            this.bindEvents();
        }
    }

    bindEvents() {
        this.input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.addValue(this.input.value);
            }
        });

        this.container.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-chip-remove]');
            if (!removeButton) {
                this.input.focus();
                return;
            }

            const key = removeButton.getAttribute('data-chip-remove');
            this.removeByKey(key);
        });
    }

    setValues(values) {
        this.values = Array.isArray(values) ? values.slice() : [];
        this.render();
        this.options.onChange(this.values.slice());
    }

    addValue(rawValue) {
        const normalized = this.options.normalize(String(rawValue || ''));
        if (!normalized) {
            this.input.value = '';
            return;
        }

        if (!this.options.validate(normalized)) {
            this.options.onInvalid(normalized);
            return;
        }

        const duplicate = this.values.some(
            (item) => this.options.duplicateKey(item) === this.options.duplicateKey(normalized)
        );

        if (!duplicate) {
            this.values.push(normalized);
            this.render();
            this.options.onChange(this.values.slice());
        }

        this.input.value = '';
    }

    removeByKey(key) {
        this.values = this.values.filter((value) => this.options.duplicateKey(value) !== key);
        this.render();
        this.options.onChange(this.values.slice());
    }

    render() {
        this.container.querySelectorAll('.chip').forEach((chip) => chip.remove());

        const fragment = document.createDocumentFragment();
        this.values.forEach((value) => {
            const key = this.options.duplicateKey(value);
            const chip = document.createElement('span');
            chip.className = ['chip', this.options.chipClass].filter(Boolean).join(' ');
            chip.innerHTML = `${escapeHtml(value)} <button type="button" data-chip-remove="${escapeHtml(key)}" aria-label="Remove">x</button>`;
            fragment.appendChild(chip);
        });

        this.container.insertBefore(fragment, this.input);
    }
}

function toInt(value, fallback = 0) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function cleanText(value) {
    return String(value || '').trim();
}

function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function jsonSafeParse(value) {
    try {
        return JSON.parse(value);
    } catch {
        return [];
    }
}

async function loadJsonMap(endpoints) {
    const entries = await Promise.all(
        Object.entries(endpoints).map(async ([key, endpoint]) => {
            const controller = new AbortController();
            const timeoutId = window.setTimeout(() => controller.abort(), 12000);

            try {
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                if (!response.ok) {
                    console.warn(`Failed to load ${key}: HTTP ${response.status}`);
                    return [key, key === 'categories' ? [] : (key === 'questionBank' ? [] : null)];
                }
                return [key, await response.json()];
            } catch (error) {
                console.warn(`Failed to load ${key}:`, error);
                return [key, key === 'categories' ? [] : (key === 'questionBank' ? [] : null)];
            } finally {
                window.clearTimeout(timeoutId);
            }
        })
    );

    return Object.fromEntries(entries);
}

async function loadJsonMapWithTimeout(endpoints, timeoutMs = 15000) {
    try {
        return await Promise.race([
            loadJsonMap(endpoints),
            new Promise((_, reject) => {
                window.setTimeout(() => reject(new Error('Exam configuration load timed out')), timeoutMs);
            }),
        ]);
    } catch (error) {
        console.warn(error);
        // Never block the whole create form — continue with empty remote data.
        return {};
    }
}

const EXAM_FORMAT_OPTIONS = []; // populated from ExamFormOptions via examCreateConfig

const SCHEDULE_TYPE_OPTIONS = [
    {
        id: 'any_time',
        label: 'Any Time Allowed',
        description: 'Candidates can start the exam at any time.',
    },
    {
        id: 'fixed_window',
        label: 'Fixed Date & Time Window',
        description: 'Candidates can start only between a configured start and end date-time.',
    },
];

const ATTEMPT_LIMIT_OPTIONS = [
    {
        id: 'once',
        label: 'One Time Only',
        description: 'Each candidate can attempt this exam once.',
    },
    {
        id: 'fixed_count',
        label: 'Fixed Attempts',
        description: 'Allow a fixed number of attempts per candidate (e.g., 2 or 3).',
    },
    {
        id: 'unlimited',
        label: 'Unlimited Attempts',
        description: 'Candidates can reattempt without an attempt cap.',
    },
];

const SCHEDULE_DATE_TIME_FORMAT = 'Y-m-d H:i';
const SCHEDULE_ALT_DATE_TIME_FORMAT = 'M j, Y h:i K';

function flattenCategoryTree(nodes, level = 0, parentId = null, path = []) {
    const source = Array.isArray(nodes) ? nodes : [];
    const flattened = [];

    source.forEach((node) => {
        const currentName = cleanText(node?.name);
        const currentPath = path.concat(currentName);
        const children = Array.isArray(node?.children) ? node.children : [];

        flattened.push({
            id: String(node?.id || ''),
            name: currentName,
            availableQuestions: toInt(node?.availableQuestions, 0),
            parentId: parentId ? String(parentId) : null,
            level,
            path: currentPath,
            isLeaf: children.length === 0,
        });

        flattened.push(...flattenCategoryTree(children, level + 1, node?.id, currentPath));
    });

    return flattened.filter((item) => item.id && item.name);
}

function getCategoryParent(category, categories) {
    if (!category?.parentId) {
        return null;
    }
    return categories.find((item) => item.id === category.parentId) || null;
}

function buildCategoryHierarchyIndex(categories) {
    const childrenByParent = new Map();

    categories.forEach((category) => {
        if (!category.parentId) {
            return;
        }
        if (!childrenByParent.has(category.parentId)) {
            childrenByParent.set(category.parentId, []);
        }
        childrenByParent.get(category.parentId).push(category.id);
    });

    return { childrenByParent };
}

function getAllDescendantIds(categoryId, hierarchyIndex) {
    const descendants = [];
    const queue = [...(hierarchyIndex.childrenByParent.get(categoryId) || [])];
    const seen = new Set(queue);

    while (queue.length) {
        const id = queue.shift();
        descendants.push(id);
        const children = hierarchyIndex.childrenByParent.get(id) || [];
        for (const childId of children) {
            if (!seen.has(childId)) {
                seen.add(childId);
                queue.push(childId);
            }
        }
    }

    return descendants;
}

function pruneDescendantSelections(selectedIds, hierarchyIndex) {
    const pruned = new Set(selectedIds);

    [...pruned].forEach((id) => {
        getAllDescendantIds(id, hierarchyIndex).forEach((descendantId) => {
            pruned.delete(descendantId);
        });
    });

    return pruned;
}

function isCategoryVisibleInDropdown(categoryId, selectedIds, categories) {
    const selectedSet = selectedIds instanceof Set ? selectedIds : new Set(selectedIds);
    let current = categories.find((category) => category.id === categoryId);
    const seen = new Set();

    while (current?.parentId) {
        if (seen.has(current.parentId)) break;
        seen.add(current.parentId);

        if (selectedSet.has(current.parentId)) {
            return false;
        }
        current = categories.find((category) => category.id === current.parentId);
    }

    return true;
}

function buildCategoryOptionMarkup(category, categories, selected = false) {
    const availability = toInt(category.availableQuestions, 0);
    const selectedAttr = selected ? 'selected' : '';
    const categoryName = escapeHtml(category.name);
    const level = category.level;
    const plainLabel = `${category.name} (${availability})`;

    return `
        <option
            value="${escapeHtml(category.id)}"
            ${selectedAttr}
            data-category-name="${categoryName}"
            data-availability="${availability}"
            data-level="${level}"
        >${escapeHtml(plainLabel)}</option>
    `;
}

document.addEventListener('DOMContentLoaded', () => {
    const refs = {
        page: document.getElementById('exam-create-page'),
        loader: document.getElementById('exam-page-loader'),
        form: document.getElementById('exam-create-form'),
        errorBanner: document.getElementById('form-error-banner'),

        title: document.getElementById('exam_title'),
        description: document.getElementById('exam_description'),
        difficulty: document.getElementById('difficulty_level'),
        status: document.getElementById('exam_status'),
        mode: document.getElementById('exam_mode'),
        visibility: document.getElementById('exam_visibility'),
        enableExamTimer: document.getElementById('enable_exam_timer'),
        examDurationMinutes: document.getElementById('exam_duration_minutes'),
        autoSubmitOnTimerEnd: document.getElementById('auto_submit_on_timer_end'),
        timerDurationWrap: document.getElementById('timer-duration-wrap'),
        timerAutoSubmitWrap: document.getElementById('timer-autosubmit-wrap'),
        timerConfigSummary: document.getElementById('timer-config-summary'),
        examFormatOptions: document.getElementById('exam-format-options'),
        examFormatHidden: document.getElementById('exam_format'),
        scheduleTypeOptions: document.getElementById('schedule-type-options'),
        scheduleTypeHidden: document.getElementById('schedule_type'),
        fixedScheduleWindow: document.getElementById('fixed-schedule-window'),
        scheduleStartAt: document.getElementById('schedule_start_at'),
        scheduleEndAt: document.getElementById('schedule_end_at'),
        attemptLimitOptions: document.getElementById('attempt-limit-options'),
        attemptLimitTypeHidden: document.getElementById('attempt_limit_type'),
        fixedAttemptLimitWrap: document.getElementById('fixed-attempt-limit-wrap'),
        attemptLimitCount: document.getElementById('attempt_limit_count'),
        scheduleConfigSummary: document.getElementById('schedule-config-summary'),

        tagsHidden: document.getElementById('exam_tags'),
        tagsChip: document.querySelector('[data-chip-input="tags"]'),

        candidateSection: document.getElementById('candidate-access-section'),
        candidateTabButtons: [...document.querySelectorAll('[data-candidate-tab]')],
        candidatePanels: [...document.querySelectorAll('[data-candidate-panel]')],
        manualEmailChip: document.querySelector('[data-chip-input="emails"]'),
        manualEmailsHidden: document.getElementById('manual_candidate_emails'),
        manualEmailFeedback: document.getElementById('manual-email-feedback'),
        dropZone: document.getElementById('candidate-drop-zone'),
        candidateFile: document.getElementById('candidate_excel_file'),
        importedCandidatesHidden: document.getElementById('imported_candidates'),
        importedCandidatePreview: document.getElementById('imported-candidate-preview'),

        freeCandidatesWrap: document.getElementById('free-candidates-wrap'),
        freeCandidateTabButtons: [...document.querySelectorAll('[data-free-candidate-tab]')],
        freeCandidatePanels: [...document.querySelectorAll('[data-free-candidate-panel]')],
        freeManualEmailChip: document.querySelector('[data-chip-input="free-emails"]'),
        freeManualEmailsHidden: document.getElementById('free_manual_candidate_emails'),
        freeManualEmailFeedback: document.getElementById('free-manual-email-feedback'),
        freeDropZone: document.getElementById('free-candidate-drop-zone'),
        freeCandidateFile: document.getElementById('free_candidate_excel_file'),
        freeImportedCandidatesHidden: document.getElementById('free_imported_candidates'),
        freeImportedCandidatePreview: document.getElementById('free-imported-candidate-preview'),

        totalQuestions: document.getElementById('total_questions'),
        totalMarks: document.getElementById('total_marks'),
        passingMarks: document.getElementById('passing_marks'),
        useQuestionPool: document.getElementById('use_question_pool'),
        maximumQuestionsWrap: document.getElementById('maximum-questions-wrap'),
        maximumQuestions: document.getElementById('maximum_questions'),
        maximumQuestionsHelper: document.getElementById('maximum-questions-helper'),
        fixedQuestionsWrap: document.getElementById('fixed-questions-wrap'),
        fixedQuestions: document.getElementById('fixed_questions'),
        fixedPaperSet: document.getElementById('fixed_paper_set'),
        paperSetsWrap: document.getElementById('paper-sets-wrap'),
        paperSets: document.getElementById('paper_sets'),
        fixCategoryQuestions: document.getElementById('fix_category_questions'),
        fixCategoryMarks: document.getElementById('fix_category_marks'),
        shuffleQuestions: document.getElementById('shuffle_questions'),
        shuffleCategories: document.getElementById('shuffle_categories'),
        shuffleOptionsWrap: document.getElementById('shuffle-options-wrap'),
        shuffleOptions: document.getElementById('shuffle_options'),
        paperSetsHelper: document.getElementById('paper-sets-helper'),

        distributionTypeGroup: document.getElementById('distribution-type-group'),
        distributionTypeHidden: document.getElementById('distribution_type'),
        categorySelectorWrap: document.getElementById('category-selector-wrap'),
        categorySelectionComplete: document.getElementById('category-selection-complete'),
        categorySelectionCompleteText: document.getElementById('category-selection-complete-text'),
        selectedCategoriesSelect: document.getElementById('selected_categories_select'),
        selectedCategoriesHidden: document.getElementById('selected_categories'),
        categoryFeedback: document.getElementById('category-selection-feedback'),
        fixedDistributionCard: document.getElementById('fixed-category-distribution'),
        fixedDistributionHelper: document.getElementById('fixed-distribution-helper'),
        fixedDistributionList: document.getElementById('fixed-category-distribution-list'),
        extraQuestionsWrap: document.getElementById('extra-questions-wrap'),
        extraQuestionsLabel: document.getElementById('extra-questions-label'),
        extraQuestionsHelp: document.getElementById('extra-questions-help'),
        extraQuestionsCategory: document.getElementById('extra_questions_category'),
        extraQuestionsCategoriesHidden: document.getElementById('extra_questions_categories'),
        extraQuestionsAllocationsWrap: document.getElementById('extra-questions-allocations-wrap'),
        extraQuestionsAllocationList: document.getElementById('extra-questions-allocation-list'),
        extraQuestionsAllocationsHidden: document.getElementById('extra_questions_allocations'),
        allocatedCount: document.getElementById('allocated-count'),
        remainingCount: document.getElementById('remaining-count'),
        fixedCategoryMarksCard: document.getElementById('fixed-category-marks-distribution'),
        fixedCategoryMarksHelper: document.getElementById('fixed-category-marks-helper'),
        fixedCategoryMarksList: document.getElementById('fixed-category-marks-list'),
        extraMarksAllocationsWrap: document.getElementById('extra-marks-allocations-wrap'),
        extraMarksAllocationList: document.getElementById('extra-marks-allocation-list'),
        extraMarksAllocationsHidden: document.getElementById('extra_marks_allocations'),
        marksAllocatedCount: document.getElementById('marks-allocated-count'),
        marksRemainingCount: document.getElementById('marks-remaining-count'),

        configPreviewList: document.getElementById('config-preview-list'),
        configValidationList: document.getElementById('config-validation-list'),

        marksFilter: document.getElementById('question-marks-filter'),
        marksHidden: document.getElementById('question_marks_filter'),
        marksCount: document.getElementById('selected-marks-count'),
        fixMarksEachQuestion: document.getElementById('fix_marks_each_question'),
        marksCalculationManagement: document.getElementById('marks-calculation-management'),
        marksCalculationSummary: document.getElementById('marks-calculation-summary'),
        marksCalculationWarning: document.getElementById('marks-calculation-warning'),
        marksCalculationSuggestion: document.getElementById('marks-calculation-suggestion'),
        marksCalculationActions: document.getElementById('marks-calculation-actions'),
        marksFixTotalMarksBtn: document.getElementById('marks-fix-total-marks'),
        marksFixTotalQuestionsBtn: document.getElementById('marks-fix-total-questions'),
        enableNegativeMarking: document.getElementById('enable_negative_marking'),
        negativeMarkingConfig: document.getElementById('negative-marking-config'),
        negativeMarkingType: document.getElementById('negative_marking_type'),

        pricingSection: document.getElementById('pricing-section'),
        pricingOptions: document.getElementById('pricing-options'),
        pricingOptionHidden: document.getElementById('pricing_option'),
        pricingImportedNote: document.getElementById('pricing-imported-note'),
        pricingDetailsWrap: document.getElementById('pricing-details-wrap'),
        examCurrency: document.getElementById('exam_currency'),
        discountRulesWrap: document.getElementById('discount-rules-wrap'),
        discountRules: document.getElementById('discount-rules'),
        discountHidden: document.getElementById('selected_discounts'),
        discountSummaryWrap: document.getElementById('discount-summary-wrap'),
        discountSummary: document.getElementById('discount-summary'),
        customDiscountsBtn: document.getElementById('add-custom-discount-btn'),
        customDiscountsContainer: document.getElementById('custom-discounts-container'),
        customDiscountsHidden: document.getElementById('custom_discounts'),

        questionSearch: document.getElementById('question-search'),
        questionBankFeedback: document.getElementById('question-bank-feedback'),
        questionBankLoadMeta: document.getElementById('question-bank-load-meta'),
        questionBankLoadMoreWrap: document.getElementById('question-bank-load-more-wrap'),
        questionBankLoadMoreBtn: document.getElementById('question-bank-load-more'),
        questionBankShortages: document.getElementById('question-bank-shortages'),
        questionCategoryCards: document.getElementById('question-category-cards'),
        openAddQuestionModal: document.getElementById('open-add-question-modal'),
        globalSelectionStats: document.getElementById('global-selection-stats'),
        globalSelectedCount: document.getElementById('global-selected-count'),
        globalAllowedCount: document.getElementById('global-allowed-count'),
        globalSelectionRange: document.getElementById('global-selection-range'),
        globalRandomSelectBtn: document.getElementById('global-random-select'),
        questionIdsHidden: document.getElementById('question_ids'),

        instructionTemplate: document.getElementById('instruction_template'),
        applyInstructionTemplate: document.getElementById('apply-instruction-template'),
        instructions: document.getElementById('candidate_instructions'),
        instructionsCount: document.getElementById('instructions-char-count'),
        instructionRulesList: document.getElementById('instruction-rules-list'),
        instructionRulesHidden: document.getElementById('predefined_instruction_rules'),
        instructionRulesCount: document.getElementById('selected-instruction-rules-count'),

        workflowStatusList: document.getElementById('workflow-status-list'),

        snapshotVisibility: document.getElementById('snapshot-visibility'),
        snapshotMode: document.getElementById('snapshot-mode'),
        snapshotCategories: document.getElementById('snapshot-categories'),
        snapshotMarks: document.getElementById('snapshot-marks'),
        snapshotTimer: document.getElementById('snapshot-timer'),
        snapshotExamFormat: document.getElementById('snapshot-exam-format'),
        snapshotSchedule: document.getElementById('snapshot-schedule'),
        snapshotAttempts: document.getElementById('snapshot-attempts'),
        snapshotCandidates: document.getElementById('snapshot-candidates'),
        snapshotDiscounts: document.getElementById('snapshot-discounts'),
        snapshotInstructionRules: document.getElementById('snapshot-instruction-rules'),

        modal: document.getElementById('add-question-modal'),
        modalCloseButtons: [...document.querySelectorAll('[data-modal-close]')],
        addQuestionForm: document.getElementById('add-question-form'),
        newQuestionCategory: document.getElementById('new_question_category'),
        newQuestionText: document.getElementById('new_question_text'),
        newQuestionMarks: document.getElementById('new_question_marks'),
        newQuestionDifficulty: document.getElementById('new_question_difficulty'),
    };

    if (!refs.page || !refs.form) {
        return;
    }

    const state = {
        config: {
            difficultyLevels: [],
            examStatus: [],
            examModes: [],
            visibilityOptions: [],
            categories: [],
            discountRules: [],
            questionMarks: [],
            questionBank: [],
            pricingOptions: [],
            distributionTypes: [],
            instructionTemplates: [],
            instructionRules: [],
            currencies: [],
        },
        questionBank: [],
        questionBankMeta: { total: 0, next_cursor: null, has_more: false, per_page: 50 },
        categoryCounts: {},
        categoryLoadState: {},
        selectedQuestionCache: {},
        questionBankRequestSeq: 0,
        questionBankAbortController: null,
        countsAbortController: null,
        categoryAvailability: {},
        selectedCategories: new Set(),
        selectedMarks: new Set(),
        selectedDiscounts: new Set(),
        discountPercentages: {},
        customDiscounts: [],
        selectedPricing: 'free',
        selectedDistributionType: '',
        selectedVisibility: '',
        selectedMode: '',
        selectedExamFormat: new Set(['mcq']),
        selectedScheduleType: 'any_time',
        selectedAttemptLimitType: 'once',
        activeCandidateTab: 'import',
        importedCandidates: [],
        manualEmails: [],
        activeFreeCandidateTab: 'import',
        freeImportedCandidates: [],
        freeManualEmails: [],
        tags: [],
        expandedCards: new Set(),
        categoryTree: [],
        lastFetchedCategories: '',
        lastFetchedMarks: '',
        lastFetchedFormats: '',
        extraQuestionsCategoryIds: [],
        extraQuestionsAllocations: {},
        categoryQuestionCountsKey: '',
        extraMarksAllocations: {},
        categoryMarksCountsKey: '',
        extraQuestionsOptionsKey: '',
        extraQuestionsSelectBound: false,
        mainCategorySelectBound: false,
        categoryHierarchyIndex: { childrenByParent: new Map() },
        isSyncingCategories: false,
        isSyncingExtraQuestions: false,
        suppressCategorySelectEvents: false,
        suppressExtraSelectEvents: false,
        richEditors: new Map(),
        richEditorsInitializing: false,
        richEditorsReady: false,
        eventsBound: false,
        schedulePickers: {
            start: null,
            end: null,
        },
        selectedQuestions: new Set(),
        selectedInstructionRules: new Set(),

        // ── Edit-mode hydration state ──────────────────────────────────────
        isEditMode: false,
        examConfig: null,
        hydratedSelectedCategories: null,
        hydratedQuestionIds: null,
        hasHydratedSelectedQuestions: false,
    };

    const tagInput = new ChipInput(refs.tagsChip, {
        normalize: (value) => cleanText(value.replace(/,/g, ' ')).replace(/\s+/g, ' '),
        onChange: (values) => {
            state.tags = values;
            refs.tagsHidden.value = JSON.stringify(values);
        },
    });

    const emailInput = new ChipInput(refs.manualEmailChip, {
        chipClass: 'is-email',
        validate: isValidEmail,
        normalize: (value) => cleanText(value.toLowerCase()),
        onInvalid: (value) => {
            refs.manualEmailFeedback.textContent = `${value} is not a valid email format.`;
            refs.manualEmailFeedback.classList.add('is-invalid');
        },
        onChange: (values) => {
            refs.manualEmailFeedback.classList.remove('is-invalid');
            refs.manualEmailFeedback.textContent = values.length
                ? `${values.length} manual candidate email(s) added.`
                : 'Type email and press Enter to add.';
            state.manualEmails = values;
            refs.manualEmailsHidden.value = JSON.stringify(values);
            updateWorkflowAndSnapshot();
        },
    });

    const freeEmailInput = new ChipInput(refs.freeManualEmailChip, {
        chipClass: 'is-email',
        validate: isValidEmail,
        normalize: (value) => cleanText(value.toLowerCase()),
        onInvalid: (value) => {
            refs.freeManualEmailFeedback.textContent = `${value} is not a valid email format.`;
            refs.freeManualEmailFeedback.classList.add('is-invalid');
        },
        onChange: (values) => {
            refs.freeManualEmailFeedback.classList.remove('is-invalid');
            refs.freeManualEmailFeedback.textContent = values.length
                ? `${values.length} manual free candidate email(s) added.`
                : 'Type email and press Enter to add.';
            state.freeManualEmails = values;
            refs.freeManualEmailsHidden.value = JSON.stringify(values);
            updateWorkflowAndSnapshot();
        },
    });

    initialize().catch((error) => {
        console.error(error);
        // Keep the form usable even if bootstrap partially fails.
        hideLoader();
        try {
            renderInitialControls();
            bindEvents();
            initRichTextEditors().catch(() => {});
            safeUpdateAll();
        } catch (innerError) {
            console.error(innerError);
            showFormErrors(['Unable to load exam configuration. Please refresh the page and try again.']);
        }
    });

    async function initialize() {
        let emergencyHide = window.setTimeout(() => {
            console.warn('Exam create page init exceeded time limit; revealing form.');
            hideLoader();
        }, 5000);

        showLoader();

        // Mount description / instructions editors immediately so fields are never blank.
        const editorsReady = initRichTextEditors().catch((error) => {
            console.warn(error);
        });

        try {
            const endpoints = window.examCreateConfig?.bootstrapEndpoints
                || { categories: window.examCreateConfig?.endpoints?.categories };
            const staticOptions = window.examCreateConfig?.options || {};
            // Only fetch live category tree on bootstrap. Question bank loads on demand.
            const remoteData = Object.keys(endpoints).length
                ? await loadJsonMapWithTimeout(endpoints, 15000)
                : {};
            const configData = { ...staticOptions, ...remoteData };
            const categoryTree = Array.isArray(configData.categories) ? configData.categories : [];
            const flatCategories = flattenCategoryTree(categoryTree);

            state.config = {
                difficultyLevels: Array.isArray(configData.difficultyLevels) ? configData.difficultyLevels : [],
                examStatus: Array.isArray(configData.examStatus) ? configData.examStatus : [],
                examModes: Array.isArray(configData.examModes) ? configData.examModes : [],
                visibilityOptions: Array.isArray(configData.visibilityOptions) ? configData.visibilityOptions : [],
                examFormats: Array.isArray(configData.examFormats) ? configData.examFormats : [],
                categories: flatCategories,
                discountRules: Array.isArray(configData.discountRules) ? configData.discountRules : [],
                questionMarks: Array.isArray(configData.questionMarks) ? configData.questionMarks : [],
                questionBank: Array.isArray(configData.questionBank) ? configData.questionBank : [],
                pricingOptions: Array.isArray(configData.pricingOptions) ? configData.pricingOptions : [],
                distributionTypes: Array.isArray(configData.distributionTypes) ? configData.distributionTypes : [],
                instructionTemplates: Array.isArray(configData.instructionTemplates) ? configData.instructionTemplates : [],
                instructionRules: Array.isArray(configData.instructionRules) ? configData.instructionRules : [],
                currencies: Array.isArray(configData.currencies) ? configData.currencies : [],
            };
            state.categoryTree = categoryTree;
            state.categoryHierarchyIndex = buildCategoryHierarchyIndex(state.config.categories);

            state.config.discountRules.forEach(rule => {
                state.discountPercentages[rule.id] = rule.default_percentage || 0;
            });

            state.questionBank = state.config.questionBank.slice();
            state.categoryAvailability = state.config.categories.reduce((carry, category) => {
                carry[category.id] = toInt(category.availableQuestions, 0);
                return carry;
            }, {});

            hydrateFromExamConfig();
            renderInitialControls();
            applyExamConfigToSelects();
            initEnhancedSelects();
            initScheduleDateTimePickers();
            renderCategorySelector();
            bindEvents();
            bindMainCategorySelect();
            bindExtraQuestionsCategorySelect();

            window.clearTimeout(emergencyHide);
            emergencyHide = null;
            hideLoader();

            await editorsReady;
            safeUpdateAll();
        } catch (error) {
            console.error(error);
            hideLoader();
            // Soft-fail: keep static options usable and still mount editors.
            try {
                if (!state.config.difficultyLevels?.length) {
                    const staticOptions = window.examCreateConfig?.options || {};
                    state.config = {
                        ...state.config,
                        difficultyLevels: staticOptions.difficultyLevels || [],
                        examStatus: staticOptions.examStatus || [],
                        examModes: staticOptions.examModes || [],
                        visibilityOptions: staticOptions.visibilityOptions || [],
                        examFormats: staticOptions.examFormats || [],
                        discountRules: staticOptions.discountRules || [],
                        questionMarks: staticOptions.questionMarks || [],
                        pricingOptions: staticOptions.pricingOptions || [],
                        distributionTypes: staticOptions.distributionTypes || [],
                        instructionTemplates: staticOptions.instructionTemplates || [],
                        instructionRules: staticOptions.instructionRules || [],
                        currencies: staticOptions.currencies || [],
                        categories: state.config.categories || [],
                        questionBank: [],
                    };
                    hydrateFromExamConfig();
                    renderInitialControls();
                    applyExamConfigToSelects();
                    bindEvents();
                }
                await editorsReady;
                safeUpdateAll();
            } catch (innerError) {
                console.error(innerError);
                showFormErrors(['Unable to load exam configuration. Please refresh the page and try again.']);
            }
        } finally {
            if (emergencyHide) {
                window.clearTimeout(emergencyHide);
            }
            hideLoader();
            await editorsReady.catch(() => {});
        }
    }

    function safeUpdateAll() {
        try {
            updateAll();
        } catch (error) {
            console.error(error);
            showFormErrors(['Something went wrong while updating the exam form. Please refresh the page.']);
        }
    }

    /**
     * Reads window.examFormConfig (edit mode) and seeds `state` with the
     * exam's saved values *before* renderInitialControls() runs, so the
     * normal "only default when empty" render logic picks up the hydrated
     * values instead of the create-wizard defaults. Must run once per page
     * load, before any other render/init call.
     */
    function hydrateFromExamConfig() {
        const cfg = window.examFormConfig || window.examCreateConfig?.exam || null;
        if (!cfg || typeof cfg !== 'object') {
            state.isEditMode = false;
            state.examConfig = null;
            return;
        }

        state.isEditMode = true;
        state.examConfig = cfg;

        if (Array.isArray(cfg.selected_categories) && cfg.selected_categories.length) {
            state.hydratedSelectedCategories = cfg.selected_categories.map(String);
        }

        if (Array.isArray(cfg.question_ids)) {
            state.hydratedQuestionIds = cfg.question_ids
                .map((id) => toInt(id, 0))
                .filter((id) => id > 0);
            state.selectedQuestions = new Set(state.hydratedQuestionIds);
        }

        if (Array.isArray(cfg.question_marks_filter) && cfg.question_marks_filter.length) {
            state.selectedMarks = new Set(cfg.question_marks_filter.map((mark) => Number(mark)));
        }

        if (cfg.distribution_type) {
            state.selectedDistributionType = String(cfg.distribution_type);
        }

        if (cfg.pricing_option) {
            state.selectedPricing = String(cfg.pricing_option);
        }

        if (Array.isArray(cfg.selected_discounts) && cfg.selected_discounts.length) {
            cfg.selected_discounts.forEach((discount) => {
                const id = typeof discount === 'object' && discount ? discount.id : discount;
                const percentage = typeof discount === 'object' && discount ? discount.percentage : undefined;
                if (!id) return;
                state.selectedDiscounts.add(id);
                if (percentage !== undefined && percentage !== null) {
                    state.discountPercentages[id] = Number(percentage);
                }
            });
        }

        if (Array.isArray(cfg.custom_discounts) && cfg.custom_discounts.length && !state.customDiscounts.length) {
            state.customDiscounts = cfg.custom_discounts;
        }

        if (Array.isArray(cfg.imported_candidates) && cfg.imported_candidates.length) {
            state.importedCandidates = cfg.imported_candidates;
        }

        if (Array.isArray(cfg.free_imported_candidates) && cfg.free_imported_candidates.length) {
            state.freeImportedCandidates = cfg.free_imported_candidates;
        }

        if (Array.isArray(cfg.extra_questions_categories) && cfg.extra_questions_categories.length) {
            state.extraQuestionsCategoryIds = cfg.extra_questions_categories.map(String);
        }

        if (cfg.extra_questions_allocations && typeof cfg.extra_questions_allocations === 'object') {
            Object.entries(cfg.extra_questions_allocations).forEach(([categoryId, count]) => {
                state.extraQuestionsAllocations[String(categoryId)] = Math.max(0, toInt(count, 0));
            });
        }

        if (cfg.extra_marks_allocations && typeof cfg.extra_marks_allocations === 'object') {
            Object.entries(cfg.extra_marks_allocations).forEach(([categoryId, marks]) => {
                state.extraMarksAllocations[String(categoryId)] = Math.max(0, toInt(marks, 0));
            });
        }

        if (Array.isArray(cfg.predefined_instruction_rules) && cfg.predefined_instruction_rules.length) {
            state.selectedInstructionRules = new Set(cfg.predefined_instruction_rules);
        }
    }

    /**
     * Applies hydrated select values that the create-wizard would otherwise
     * force to its own defaults (difficulty/status/mode/visibility/currency
     * are populated dynamically via JS and have no server-rendered <option
     * selected> markup to fall back on).
     */
    function applyExamConfigToSelects() {
        if (!state.isEditMode || !state.examConfig) {
            return;
        }

        const cfg = state.examConfig;

        setSelectValueIfAvailable(refs.difficulty, cfg.difficulty_level);
        setSelectValueIfAvailable(refs.status, cfg.status);
        setSelectValueIfAvailable(refs.mode, cfg.exam_mode);
        setSelectValueIfAvailable(refs.visibility, cfg.visibility);
        setSelectValueIfAvailable(refs.examCurrency, cfg.exam_currency);

        state.selectedMode = refs.mode ? refs.mode.value : state.selectedMode;
        state.selectedVisibility = refs.visibility ? refs.visibility.value : state.selectedVisibility;

        if (Array.isArray(cfg.imported_candidates) && cfg.imported_candidates.length) {
            renderImportedCandidatePreview('previously imported file');
        }
        if (Array.isArray(cfg.free_imported_candidates) && cfg.free_imported_candidates.length) {
            renderFreeImportedCandidatePreview('previously imported file');
        }
    }

    function setSelectValueIfAvailable(select, value) {
        if (!select || value === undefined || value === null || value === '') {
            return false;
        }
        const stringValue = String(value);
        const hasOption = [...select.options].some((option) => option.value === stringValue);
        if (hasOption) {
            select.value = stringValue;
            return true;
        }
        return false;
    }

    /**
     * Called once, after the first server-side question bank sync completes
     * in edit mode, so previously linked questions that weren't part of the
     * freshly-fetched page still show up as selected/cached for display.
     */
    function hydrateSelectedQuestions() {
        state.hasHydratedSelectedQuestions = true;
        if (!Array.isArray(state.hydratedQuestionIds) || !state.hydratedQuestionIds.length) {
            return;
        }

        state.hydratedQuestionIds.forEach((questionId) => {
            state.selectedQuestions.add(questionId);
            const question = getQuestionById(questionId);
            if (question) {
                rememberSelectedQuestion(question);
            }
        });

        if (refs.questionIdsHidden) {
            refs.questionIdsHidden.value = JSON.stringify([...state.selectedQuestions]);
        }

        safeUpdateAll();
    }

    function initEnhancedSelects() {
        if (!window.EmsSelect || typeof window.EmsSelect.initAll !== 'function') {
            return;
        }

        // Category selects are mounted via replaceOptions after options HTML is ready.
        window.EmsSelect.initAll(
            document,
            'select.panel-input:not(#selected_categories_select):not(#extra_questions_category)'
        );
    }

    function normalizeScheduleDateTimeValue(rawValue) {
        const cleaned = cleanText(rawValue);
        if (!cleaned) {
            return '';
        }

        if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(cleaned)) {
            return cleaned.replace('T', ' ').slice(0, 16);
        }

        if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(cleaned)) {
            return cleaned.replace(/\s+/, ' ').slice(0, 16);
        }

        return cleaned;
    }

    function parseDateTimeObject(value) {
        const cleaned = cleanText(String(value || ''));
        if (!cleaned) {
            return null;
        }

        const parser = window.EmsFormUtils && typeof window.EmsFormUtils.parseDateTime === 'function'
            ? window.EmsFormUtils.parseDateTime
            : null;

        if (parser) {
            const parsed = parser(cleaned, SCHEDULE_DATE_TIME_FORMAT)
                || parser(cleaned.replace('T', ' '), SCHEDULE_DATE_TIME_FORMAT);
            if (parsed instanceof Date && !Number.isNaN(parsed.getTime())) {
                return parsed;
            }
        }

        const normalizedIso = cleaned.includes('T') ? cleaned : cleaned.replace(' ', 'T');
        const fallback = new Date(normalizedIso);
        return Number.isNaN(fallback.getTime()) ? null : fallback;
    }

    function formatScheduleDateTimeForDisplay(value) {
        const cleaned = cleanText(String(value || ''));
        if (!cleaned) {
            return '';
        }

        const parsed = parseDateTimeObject(cleaned);
        if (!parsed) {
            return normalizeScheduleDateTimeValue(cleaned);
        }

        if (window.EmsFormUtils && typeof window.EmsFormUtils.formatHumanDateTime === 'function') {
            const readable = window.EmsFormUtils.formatHumanDateTime(parsed);
            if (cleanText(readable)) {
                return readable;
            }
        }

        return normalizeScheduleDateTimeValue(cleaned);
    }

    function syncScheduleEndPickerMinDate() {
        const endPicker = state.schedulePickers.end;
        if (!endPicker || typeof endPicker.set !== 'function') {
            return;
        }

        const startDate = parseDateTimeObject(refs.scheduleStartAt ? refs.scheduleStartAt.value : '');
        endPicker.set('minDate', startDate || null);

        const endDate = parseDateTimeObject(refs.scheduleEndAt ? refs.scheduleEndAt.value : '');
        if (startDate && endDate && endDate.getTime() <= startDate.getTime()) {
            endPicker.clear();
        }
    }

    function initScheduleDateTimePickers() {
        [refs.scheduleStartAt, refs.scheduleEndAt].forEach((field) => {
            if (!field) {
                return;
            }
            field.value = normalizeScheduleDateTimeValue(field.value);
        });

        if (!window.EmsFormUtils || typeof window.EmsFormUtils.initDateTimePicker !== 'function') {
            return;
        }

        const pickerOptions = {
            dateFormat: SCHEDULE_DATE_TIME_FORMAT,
            altInput: true,
            altFormat: SCHEDULE_ALT_DATE_TIME_FORMAT,
            altInputClass: 'panel-input',
            minuteIncrement: 5,
            disableMobile: true,
            onReady: (_, __, instance) => {
                if (instance?.altInput) {
                    instance.altInput.placeholder = instance.input.placeholder || 'Select date and time';
                }
            },
        };

        state.schedulePickers.start = window.EmsFormUtils.initDateTimePicker(refs.scheduleStartAt, {
            ...pickerOptions,
            onChange: () => {
                syncScheduleEndPickerMinDate();
                safeUpdateAll();
            },
            onClose: () => {
                syncScheduleEndPickerMinDate();
                safeUpdateAll();
            },
        });

        state.schedulePickers.end = window.EmsFormUtils.initDateTimePicker(refs.scheduleEndAt, {
            ...pickerOptions,
            onChange: () => {
                safeUpdateAll();
            },
            onClose: () => {
                safeUpdateAll();
            },
        });

        syncScheduleEndPickerMinDate();
    }

    function renderInitialControls() {
        populateSelect(refs.difficulty, state.config.difficultyLevels, 'Select difficulty');
        populateSelect(refs.status, state.config.examStatus, 'Select status');
        populateSelect(refs.mode, state.config.examModes, 'Select mode');
        populateSelect(refs.visibility, state.config.visibilityOptions, 'Select visibility');

        if (!state.isEditMode) {
            setSelectDefault(refs.difficulty, 'medium');
            setSelectDefault(refs.status, 'draft');
            setSelectDefault(refs.mode, 'standard');
            setSelectDefault(refs.visibility, 'public');
        }
        // In edit mode, applyExamConfigToSelects() (called right after this
        // function) sets these selects from the hydrated exam values instead.

        state.selectedMode = refs.mode.value;
        state.selectedVisibility = refs.visibility.value;

        initializeCategorySelection();
        renderDistributionTypes();
        populateCategorySelectElement();
        renderQuestionMarks();
        renderPricingOptions();
        renderDiscountRules();

        const defaultCustomDiscounts = jsonSafeParse(refs.customDiscountsHidden.value);
        if (Array.isArray(defaultCustomDiscounts) && defaultCustomDiscounts.length) {
            state.customDiscounts = defaultCustomDiscounts;
        }
        renderCustomDiscounts();

        let initialFormats = ['mcq'];
        if (refs.examFormatHidden && refs.examFormatHidden.value) {
            try {
                const parsed = JSON.parse(refs.examFormatHidden.value);
                if (Array.isArray(parsed)) {
                    initialFormats = parsed;
                } else if (typeof parsed === 'string') {
                    initialFormats = [parsed];
                }
            } catch (e) {
                initialFormats = refs.examFormatHidden.value.split(',').map(s => s.trim()).filter(Boolean);
            }
        }
        state.selectedExamFormat = new Set(initialFormats.map(f => normalizeExamFormat(f)));
        renderExamFormatOptions();
        state.selectedScheduleType = normalizeScheduleType(refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time');
        state.selectedAttemptLimitType = normalizeAttemptLimitType(refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once');
        renderScheduleTypeOptions();
        renderAttemptLimitOptions();
        updateScheduleConfigState();
        updateTimerConfigState();

        renderInstructionTemplates();
        const parsedRules = jsonSafeParse(refs.instructionRulesHidden ? refs.instructionRulesHidden.value : '[]');
        const seedRules = Array.isArray(parsedRules) && parsedRules.length
            ? parsedRules
            : defaultInstructionRuleIds();
        const defaultInstructionRules = normalizeInstructionRuleSelection(seedRules);
        state.selectedInstructionRules = new Set(defaultInstructionRules);
        renderInstructionRules();
        if (refs.modal || refs.newQuestionCategory) {
            renderModalSelects();
        }
        refs.manualEmailFeedback.textContent = 'Type email and press Enter to add.';

        const defaultTags = jsonSafeParse(refs.tagsHidden.value);
        if (Array.isArray(defaultTags) && defaultTags.length) {
            tagInput.setValues(defaultTags);
        }

        const defaultExtraMarks = jsonSafeParse(refs.extraMarksAllocationsHidden?.value || '{}');
        if (defaultExtraMarks && typeof defaultExtraMarks === 'object' && !Array.isArray(defaultExtraMarks)) {
            Object.entries(defaultExtraMarks).forEach(([categoryId, marks]) => {
                state.extraMarksAllocations[String(categoryId)] = Math.max(0, toInt(marks, 0));
            });
        }

        const defaultExtraQuestions = jsonSafeParse(refs.extraQuestionsAllocationsHidden?.value || '{}');
        if (defaultExtraQuestions && typeof defaultExtraQuestions === 'object' && !Array.isArray(defaultExtraQuestions)) {
            Object.entries(defaultExtraQuestions).forEach(([categoryId, count]) => {
                state.extraQuestionsAllocations[String(categoryId)] = Math.max(0, toInt(count, 0));
            });
        }

        const defaultEmails = jsonSafeParse(refs.manualEmailsHidden.value);
        if (Array.isArray(defaultEmails) && defaultEmails.length) {
            emailInput.setValues(defaultEmails);
        }

        refs.freeManualEmailFeedback.textContent = 'Type email and press Enter to add.';
        const defaultFreeEmails = jsonSafeParse(refs.freeManualEmailsHidden.value);
        if (Array.isArray(defaultFreeEmails) && defaultFreeEmails.length) {
            freeEmailInput.setValues(defaultFreeEmails);
        }
    }

    function populateSelect(select, items, placeholder) {
        if (!select) return;

        const html = [`<option value="">${escapeHtml(placeholder)}</option>`]
            .concat(items.map((item) => `<option value="${escapeHtml(item.id)}">${escapeHtml(item.label)}</option>`))
            .join('');

        select.innerHTML = html;
    }

    function setSelectDefault(select, expectedValue) {
        if (!select) return;
        const hasExpected = [...select.options].some((option) => option.value === expectedValue);
        if (hasExpected) {
            select.value = expectedValue;
            return;
        }
        if (select.options.length > 1) {
            select.value = select.options[1].value;
        }
    }

    function getAssignableCategories() {
        return state.config.categories;
    }

    function initializeCategorySelection() {
        const categories = getAssignableCategories();
        const validIds = new Set(categories.map((category) => category.id));

        if (state.isEditMode && Array.isArray(state.hydratedSelectedCategories) && state.hydratedSelectedCategories.length) {
            const hydratedValid = state.hydratedSelectedCategories.filter((id) => validIds.has(id));
            if (hydratedValid.length) {
                state.selectedCategories = new Set(hydratedValid);
                refs.selectedCategoriesHidden.value = JSON.stringify([...state.selectedCategories]);
                state.extraQuestionsCategoryIds = state.extraQuestionsCategoryIds.length
                    ? state.extraQuestionsCategoryIds
                    : [hydratedValid[0]];
                return;
            }
        }

        const preferred = Math.min(3, categories.length);

        state.selectedCategories = new Set(categories.slice(0, preferred).map((category) => category.id));
        refs.selectedCategoriesHidden.value = JSON.stringify([...state.selectedCategories]);

        state.extraQuestionsCategoryIds = categories[0]?.id ? [categories[0].id] : [];
    }

    function renderDistributionTypes() {
        if (!refs.distributionTypeGroup) {
            return;
        }

        if (!state.selectedDistributionType && state.config.distributionTypes.length) {
            state.selectedDistributionType = state.config.distributionTypes[0].id;
        }

        refs.distributionTypeGroup.innerHTML = state.config.distributionTypes
            .map((type) => {
                const active = type.id === state.selectedDistributionType ? 'is-active' : '';
                return `<button type="button" class="pill ${active}" data-distribution-id="${escapeHtml(type.id)}">${escapeHtml(type.label)}</button>`;
            })
            .join('');

        if (refs.distributionTypeHidden) {
            refs.distributionTypeHidden.value = state.selectedDistributionType || '';
        }
    }

    function applyMainCategorySelectionRules(rawIds) {
        const validIds = new Set(getAssignableCategories().map((category) => category.id));
        const filtered = [...rawIds].filter((id) => validIds.has(id));
        return [...pruneDescendantSelections(filtered, state.categoryHierarchyIndex)];
    }

    function buildCategorySelectOptionsHtml(selectedSet) {
        const categories = getAssignableCategories();
        const visibleCategories = categories.filter((category) => isCategoryVisibleInDropdown(
            category.id,
            selectedSet,
            state.config.categories
        ));

        return visibleCategories
            .map((category) => buildCategoryOptionMarkup(
                category,
                state.config.categories,
                selectedSet.has(category.id)
            ))
            .join('');
    }

    function updateCategorySelectorFeedback() {
        const selectedCount = state.selectedCategories.size;

        refs.selectedCategoriesHidden.value = JSON.stringify([...state.selectedCategories]);
        refs.categoryFeedback.textContent = `${selectedCount} categor${selectedCount === 1 ? 'y' : 'ies'} selected.`;

        const isSelectionComplete = selectedCount > 0;
        if (refs.categorySelectorWrap) {
            refs.categorySelectorWrap.hidden = false;
        }
        if (refs.categorySelectionComplete) {
            refs.categorySelectionComplete.hidden = !isSelectionComplete;
        }
        if (refs.categorySelectionCompleteText) {
            refs.categorySelectionCompleteText.textContent = isSelectionComplete
                ? `${selectedCount} categories selected. Category selection is complete.`
                : '';
        }
    }

    function populateCategorySelectElement() {
        const normalized = applyMainCategorySelectionRules([...state.selectedCategories]);
        state.selectedCategories = new Set(normalized);
        refs.selectedCategoriesSelect.dataset.maxItems = String(Math.max(1, getAssignableCategories().length));
        refs.selectedCategoriesSelect.innerHTML = buildCategorySelectOptionsHtml(state.selectedCategories);
        updateCategorySelectorFeedback();
    }

    function bindMainCategorySelect() {
        if (state.mainCategorySelectBound || !window.EmsSelect || typeof window.EmsSelect.onChange !== 'function') {
            return;
        }

        window.EmsSelect.onChange('selected_categories_select', () => {
            if (state.isSyncingCategories || state.suppressCategorySelectEvents) {
                return;
            }

            const rawValue = window.EmsSelect.getValue('selected_categories_select');
            const selectedValues = Array.isArray(rawValue) ? rawValue : (rawValue ? [rawValue] : []);
            const normalized = applyMainCategorySelectionRules(selectedValues);

            state.selectedCategories = new Set(normalized);
            state.extraQuestionsOptionsKey = '';
            state.categoryQuestionCountsKey = '';
            state.categoryMarksCountsKey = '';
            renderCategorySelector();
            safeUpdateAll();
        });

        state.mainCategorySelectBound = true;
    }

    function renderCategorySelector() {
        if (state.isSyncingCategories) {
            return;
        }

        state.isSyncingCategories = true;
        state.suppressCategorySelectEvents = true;

        try {
            const normalized = applyMainCategorySelectionRules([...state.selectedCategories]);
            state.selectedCategories = new Set(normalized);
            const html = buildCategorySelectOptionsHtml(state.selectedCategories);
            const values = [...state.selectedCategories];
            const maxItems = Math.max(1, getAssignableCategories().length);

            const isSelectionComplete = state.selectedCategories.size > 0;

            refs.selectedCategoriesSelect.dataset.maxItems = String(maxItems);

            if (window.EmsSelect && typeof window.EmsSelect.replaceOptions === 'function') {
                const prevInstance = window.EmsSelect.get('selected_categories_select');
                const wasOpen = prevInstance ? prevInstance.isOpen : false;

                window.EmsSelect.replaceOptions('selected_categories_select', html, values, maxItems);

                const newInstance = window.EmsSelect.get('selected_categories_select');
                if (newInstance && wasOpen && !isSelectionComplete) {
                    newInstance.open();
                }
            } else {
                refs.selectedCategoriesSelect.innerHTML = html;
            }

            updateCategorySelectorFeedback();
        } finally {
            state.suppressCategorySelectEvents = false;
            state.isSyncingCategories = false;
        }
    }

    function renderQuestionMarks() {
        if (!state.selectedMarks.size) {
            [1].forEach((mark) => state.selectedMarks.add(mark));
        }

        if (refs.fixMarksEachQuestion && refs.fixMarksEachQuestion.checked && state.selectedMarks.size > 1) {
            const firstMark = Array.from(state.selectedMarks)[0];
            state.selectedMarks.clear();
            if (firstMark) {
                state.selectedMarks.add(firstMark);
            }
        }

        refs.marksFilter.innerHTML = state.config.questionMarks
            .map((item) => {
                const mark = Number(item.value);
                const active = state.selectedMarks.has(mark) ? 'is-active' : '';
                return `<button type="button" class="pill ${active}" data-mark-value="${mark}">${escapeHtml(item.label)}</button>`;
            })
            .join('');

        refs.marksHidden.value = JSON.stringify([...state.selectedMarks]);
        refs.marksCount.textContent = String(state.selectedMarks.size);
    }

    function computeMarksCalculationState() {
        const fixEnabled = Boolean(refs.fixMarksEachQuestion && refs.fixMarksEachQuestion.checked);
        const totalQuestions = Math.max(0, toInt(refs.totalQuestions.value, 0));
        const totalMarks = Math.max(0, toInt(refs.totalMarks.value, 0));
        const selectedMark = state.selectedMarks.size === 1 ? Number(Array.from(state.selectedMarks)[0]) : 0;
        const hasSelectedMark = Number.isFinite(selectedMark) && selectedMark > 0;
        const expectedTotalMarks = hasSelectedMark ? totalQuestions * selectedMark : 0;
        const rawQuestionCountFromMarks = hasSelectedMark ? (totalMarks / selectedMark) : 0;
        const hasExactQuestionCount = hasSelectedMark
            && rawQuestionCountFromMarks > 0
            && Number.isInteger(rawQuestionCountFromMarks);
        const suggestedQuestionCount = hasSelectedMark
            ? Math.max(1, hasExactQuestionCount ? rawQuestionCountFromMarks : Math.round(rawQuestionCountFromMarks || 1))
            : 0;
        const suggestedTotalMarks = hasSelectedMark ? suggestedQuestionCount * selectedMark : 0;
        const isValid = !fixEnabled || (
            hasSelectedMark
            && totalQuestions > 0
            && totalMarks > 0
            && totalMarks === expectedTotalMarks
        );

        return {
            fixEnabled,
            totalQuestions,
            totalMarks,
            selectedMark,
            hasSelectedMark,
            expectedTotalMarks,
            hasExactQuestionCount,
            suggestedQuestionCount,
            suggestedTotalMarks,
            isValid,
        };
    }

    function renderMarksCalculationManagement() {
        if (
            !refs.marksCalculationManagement
            || !refs.marksCalculationSummary
            || !refs.marksCalculationWarning
            || !refs.marksCalculationSuggestion
            || !refs.marksCalculationActions
        ) {
            return;
        }

        const calculation = computeMarksCalculationState();
        if (!calculation.fixEnabled) {
            refs.marksCalculationManagement.hidden = true;
            refs.marksCalculationManagement.classList.remove('is-valid', 'is-warning');
            refs.marksCalculationSummary.textContent = '';
            refs.marksCalculationWarning.textContent = '';
            refs.marksCalculationWarning.hidden = true;
            refs.marksCalculationSuggestion.textContent = '';
            refs.marksCalculationSuggestion.hidden = true;
            refs.marksCalculationActions.hidden = true;
            return;
        }

        refs.marksCalculationManagement.hidden = false;
        refs.marksCalculationManagement.classList.remove('is-valid', 'is-warning');

        if (!calculation.hasSelectedMark) {
            refs.marksCalculationSummary.textContent = 'Select one question mark value to validate fixed marks calculation.';
            refs.marksCalculationWarning.textContent = 'Fixed marks mode requires one selected mark value.';
            refs.marksCalculationWarning.hidden = false;
            refs.marksCalculationSuggestion.textContent = 'Choose a mark from Question Marks Filter, then use suggested auto-fix actions if needed.';
            refs.marksCalculationSuggestion.hidden = false;
            refs.marksCalculationActions.hidden = true;
            refs.marksCalculationManagement.classList.add('is-warning');
            return;
        }

        refs.marksCalculationSummary.textContent = `Current formula: ${calculation.totalQuestions} questions x ${calculation.selectedMark} mark(s) = ${calculation.expectedTotalMarks} expected total marks. Current Total Marks: ${calculation.totalMarks}.`;

        if (calculation.isValid) {
            refs.marksCalculationWarning.textContent = '';
            refs.marksCalculationWarning.hidden = true;
            refs.marksCalculationSuggestion.textContent = 'Marks configuration is valid and ready.';
            refs.marksCalculationSuggestion.hidden = false;
            refs.marksCalculationActions.hidden = true;
            refs.marksCalculationManagement.classList.add('is-valid');
            return;
        }

        refs.marksCalculationWarning.textContent = 'The selected marks configuration does not match the total questions and total marks. Please adjust the values.';
        refs.marksCalculationWarning.hidden = false;

        if (calculation.hasExactQuestionCount) {
            refs.marksCalculationSuggestion.textContent = `Suggested fix: set Total Marks to ${calculation.expectedTotalMarks}, or set Total Questions to ${calculation.suggestedQuestionCount}.`;
        } else {
            refs.marksCalculationSuggestion.textContent = `Suggested fix: set Total Marks to ${calculation.expectedTotalMarks}, or set Total Questions to ${calculation.suggestedQuestionCount} (nearest whole number, then Total Marks will sync to ${calculation.suggestedTotalMarks}).`;
        }
        refs.marksCalculationSuggestion.hidden = false;
        refs.marksCalculationActions.hidden = false;
        refs.marksCalculationManagement.classList.add('is-warning');

        if (refs.marksFixTotalMarksBtn) {
            refs.marksFixTotalMarksBtn.textContent = `Update Total Marks (${calculation.expectedTotalMarks})`;
            refs.marksFixTotalMarksBtn.disabled = false;
        }
        if (refs.marksFixTotalQuestionsBtn) {
            refs.marksFixTotalQuestionsBtn.textContent = `Update Total Questions (${calculation.suggestedQuestionCount})`;
            refs.marksFixTotalQuestionsBtn.disabled = false;
        }
    }

    function applyMarksCalculationFix(fixType) {
        const calculation = computeMarksCalculationState();
        if (!calculation.fixEnabled || !calculation.hasSelectedMark) {
            return;
        }

        if (fixType === 'total_marks') {
            refs.totalMarks.value = String(calculation.expectedTotalMarks);
        }

        if (fixType === 'total_questions') {
            refs.totalQuestions.value = String(calculation.suggestedQuestionCount);
            if (!calculation.hasExactQuestionCount) {
                refs.totalMarks.value = String(calculation.suggestedTotalMarks);
            }
        }

        updateAll();
    }

    function renderPricingOptions() {
        refs.pricingOptions.innerHTML = state.config.pricingOptions
            .map((option) => {
                const selected = state.selectedPricing === option.id ? 'is-selected' : '';
                return `
                    <article class="option-card ${selected}" data-pricing-option="${escapeHtml(option.id)}">
                        <h4>${escapeHtml(option.label)}</h4>
                        <p>${escapeHtml(option.description)}</p>
                    </article>
                `;
            })
            .join('');

        const allOptions = state.config.pricingOptions.map((option) => option.id);

        if (!allOptions.includes(state.selectedPricing)) {
            state.selectedPricing = allOptions.includes('free') ? 'free' : (allOptions[0] || '');
        }

        refs.pricingOptionHidden.value = state.selectedPricing;
        highlightPricingOptions();

        if (refs.examCurrency && refs.examCurrency.options.length === 0) {
            populateSelect(refs.examCurrency, state.config.currencies, 'Select currency');
            setSelectDefault(refs.examCurrency, 'USD');
        }
    }

    function highlightPricingOptions() {
        refs.pricingOptions.querySelectorAll('[data-pricing-option]').forEach((card) => {
            card.classList.toggle('is-selected', card.dataset.pricingOption === state.selectedPricing);
        });
        refs.pricingOptionHidden.value = state.selectedPricing;

        const showPricingDetails = state.selectedPricing === 'paid' || state.selectedPricing === 'free_for_imported';
        if (refs.pricingDetailsWrap) refs.pricingDetailsWrap.hidden = !showPricingDetails;
        if (refs.discountRulesWrap) refs.discountRulesWrap.hidden = !showPricingDetails;
        if (refs.discountSummaryWrap) refs.discountSummaryWrap.hidden = !showPricingDetails;
    }

    function renderDiscountRules() {
        refs.discountRules.innerHTML = state.config.discountRules
            .map((rule) => {
                const selected = state.selectedDiscounts.has(rule.id) ? 'is-selected' : '';
                const percentage = state.discountPercentages[rule.id] || rule.default_percentage || 0;

                return `
                    <article class="option-card ${selected}" data-discount-id="${escapeHtml(rule.id)}">
                        <h4>${escapeHtml(rule.label)}</h4>
                        <p>${escapeHtml(rule.summary)}</p>
                        <div class="mt-2 discount-pct-wrap" ${selected ? '' : 'hidden'}>
                            <label class="exam-label discount-pct-label">Discount Percentage (%)</label>
                            <input type="number" class="panel-input discount-percentage-input" data-rule-id="${escapeHtml(rule.id)}" value="${percentage}" min="0" max="100">
                            <p class="exam-help is-invalid mt-1 text-xs" id="err-predefined-${rule.id}" hidden></p>
                        </div>
                    </article>
                `;
            })
            .join('');

        refs.discountHidden.value = JSON.stringify(
            [...state.selectedDiscounts].map(id => ({ id, percentage: state.discountPercentages[id] }))
        );
        bindDiscountInputs();
    }

    function bindDiscountInputs() {
        refs.discountRules.querySelectorAll('.discount-percentage-input').forEach(input => {
            input.addEventListener('input', (e) => {
                const ruleId = e.target.dataset.ruleId;
                const errEl = document.getElementById(`err-predefined-${ruleId}`);
                const rawVal = e.target.value;
                const val = rawVal === '' ? NaN : parseInt(rawVal, 10);

                let isInvalid = false;
                let errMsg = '';

                if (isNaN(val)) {
                    isInvalid = true;
                    errMsg = 'Percentage value is required.';
                } else if (val < 0) {
                    isInvalid = true;
                    errMsg = 'Discount percentage cannot be less than 0%.';
                } else if (val > 100) {
                    isInvalid = true;
                    errMsg = 'Discount percentage cannot exceed 100%.';
                }

                state.discountPercentages[ruleId] = isNaN(val) ? 0 : val;

                if (errEl) {
                    if (isInvalid) {
                        errEl.textContent = errMsg;
                        errEl.hidden = false;
                    } else {
                        errEl.hidden = true;
                    }
                }

                refs.discountHidden.value = JSON.stringify(
                    [...state.selectedDiscounts].map(id => ({ id, percentage: state.discountPercentages[id] }))
                );
                renderDiscountSummary();
                updateWorkflowAndSnapshot();
            });
        });
    }

    /* ── Custom Discount Management ── */
    function renderCustomDiscounts() {
        if (!refs.customDiscountsContainer) return;

        if (state.customDiscounts.length === 0) {
            refs.customDiscountsContainer.innerHTML = `
                <div class="custom-discount-empty">
                    No custom discount offers added yet. Click "+ Add Custom Offer" to create one.
                </div>
            `;
            refs.customDiscountsHidden.value = '[]';
            return;
        }

        refs.customDiscountsContainer.innerHTML = state.customDiscounts
            .map((item, index) => {
                const name = escapeHtml(item.name || '');
                const desc = escapeHtml(item.description || '');
                const pct = isNaN(item.percentage) || item.percentage === null ? '' : item.percentage;

                return `
                    <div class="custom-discount-row" data-row-index="${index}">
                        <button type="button" class="remove-custom-discount-btn" data-row-index="${index}" title="Remove Offer">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 1.15rem; height: 1.15rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>

                        <div class="custom-discount-grid">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1">Offer Name <span class="text-red-500">*</span></label>
                                <input type="text" class="panel-input custom-discount-name" data-row-index="${index}" placeholder="e.g. Summer Sale" value="${name}">
                                <p class="exam-help is-invalid mt-1 text-xs" id="err-custom-name-${index}" hidden></p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1">Description</label>
                                <input type="text" class="panel-input custom-discount-desc" data-row-index="${index}" placeholder="e.g. Valid for standard exams" value="${desc}">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1">Discount Percentage (%) <span class="text-red-500">*</span></label>
                                <input type="number" class="panel-input custom-discount-pct" data-row-index="${index}" placeholder="e.g. 15" value="${pct}" min="0" max="100">
                                <p class="exam-help is-invalid mt-1 text-xs" id="err-custom-pct-${index}" hidden></p>
                            </div>
                        </div>
                    </div>
                `;
            })
            .join('');

        refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts);
        bindCustomDiscountRowEvents();
        updateWorkflowAndSnapshot();
    }

    function bindCustomDiscountRowEvents() {
        if (!refs.customDiscountsContainer) return;

        // Trash buttons
        refs.customDiscountsContainer.querySelectorAll('.remove-custom-discount-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const btnActual = e.currentTarget;
                const index = parseInt(btnActual.dataset.rowIndex, 10);
                state.customDiscounts.splice(index, 1);
                renderCustomDiscounts();
            });
        });

        // Name inputs
        refs.customDiscountsContainer.querySelectorAll('.custom-discount-name').forEach(input => {
            input.addEventListener('input', (e) => {
                const index = parseInt(e.target.dataset.rowIndex, 10);
                const val = e.target.value;
                state.customDiscounts[index].name = val;

                // Validate name
                const errEl = document.getElementById(`err-custom-name-${index}`);
                if (errEl) {
                    if (!val.trim()) {
                        errEl.textContent = 'Offer name is required.';
                        errEl.hidden = false;
                    } else {
                        errEl.hidden = true;
                    }
                }
                refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts);
                updateWorkflowAndSnapshot();
            });
        });

        // Desc inputs
        refs.customDiscountsContainer.querySelectorAll('.custom-discount-desc').forEach(input => {
            input.addEventListener('input', (e) => {
                const index = parseInt(e.target.dataset.rowIndex, 10);
                state.customDiscounts[index].description = e.target.value;
                refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts);
            });
        });

        // Percentage inputs
        refs.customDiscountsContainer.querySelectorAll('.custom-discount-pct').forEach(input => {
            input.addEventListener('input', (e) => {
                const index = parseInt(e.target.dataset.rowIndex, 10);
                const rawVal = e.target.value;
                const val = rawVal === '' ? NaN : parseInt(rawVal, 10);
                state.customDiscounts[index].percentage = val;

                // Validate percentage
                const errEl = document.getElementById(`err-custom-pct-${index}`);
                if (errEl) {
                    if (isNaN(val)) {
                        errEl.textContent = 'Discount percentage is required.';
                        errEl.hidden = false;
                    } else if (val < 0) {
                        errEl.textContent = 'Discount percentage cannot be less than 0%.';
                        errEl.hidden = false;
                    } else if (val > 100) {
                        errEl.textContent = 'Discount percentage cannot exceed 100%.';
                        errEl.hidden = false;
                    } else {
                        errEl.hidden = true;
                    }
                }
                refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts);
                updateWorkflowAndSnapshot();
            });
        });
    }

    function normalizeExamFormat(rawValue) {
        const normalized = cleanText(String(rawValue || '')).toLowerCase();
        return getExamFormatById(normalized) ? normalized : 'mcq';
    }

    function getScheduleTypeById(scheduleTypeId) {
        return SCHEDULE_TYPE_OPTIONS.find((option) => option.id === scheduleTypeId) || null;
    }

    function normalizeScheduleType(rawValue) {
        const normalized = cleanText(String(rawValue || '')).toLowerCase();
        return getScheduleTypeById(normalized) ? normalized : 'any_time';
    }

    function getAttemptLimitTypeById(attemptTypeId) {
        return ATTEMPT_LIMIT_OPTIONS.find((option) => option.id === attemptTypeId) || null;
    }

    function normalizeAttemptLimitType(rawValue) {
        const normalized = cleanText(String(rawValue || '')).toLowerCase();
        if (normalized === 'fixed') {
            return 'fixed_count';
        }
        return getAttemptLimitTypeById(normalized) ? normalized : 'once';
    }

    function getExamFormatOptions() {
        const fromConfig = Array.isArray(state.config.examFormats) ? state.config.examFormats : [];
        return fromConfig.length ? fromConfig : EXAM_FORMAT_OPTIONS;
    }

    function getExamFormatById(formatId) {
        return getExamFormatOptions().find((option) => option.id === formatId) || null;
    }

    function getSelectedExamFormatLabels() {
        return [...state.selectedExamFormat]
            .map((id) => getExamFormatById(id)?.label || id)
            .filter(Boolean);
    }

    function renderExamFormatOptions() {
        if (!refs.examFormatOptions || !refs.examFormatHidden) {
            return;
        }

        const activeOptions = getExamFormatOptions();

        refs.examFormatOptions.innerHTML = activeOptions
            .map((option) => {
                const selected = state.selectedExamFormat.has(option.id) ? 'is-selected' : '';
                const description = option.description || '';
                return `
                    <article class="option-card ${selected}" data-format-id="${escapeHtml(option.id)}">
                        <h4>${escapeHtml(option.label)}</h4>
                        <p>${escapeHtml(description)}</p>
                    </article>
                `;
            })
            .join('');

        refs.examFormatHidden.value = JSON.stringify([...state.selectedExamFormat]);
    }

    function renderScheduleTypeOptions() {
        if (!refs.scheduleTypeOptions || !refs.scheduleTypeHidden) {
            return;
        }

        refs.scheduleTypeOptions.innerHTML = SCHEDULE_TYPE_OPTIONS
            .map((option) => {
                const selected = state.selectedScheduleType === option.id ? 'is-selected' : '';
                return `
                    <article class="option-card ${selected}" data-schedule-type-id="${escapeHtml(option.id)}">
                        <h4>${escapeHtml(option.label)}</h4>
                        <p>${escapeHtml(option.description)}</p>
                    </article>
                `;
            })
            .join('');

        refs.scheduleTypeHidden.value = state.selectedScheduleType;
    }

    function renderAttemptLimitOptions() {
        if (!refs.attemptLimitOptions || !refs.attemptLimitTypeHidden) {
            return;
        }

        refs.attemptLimitOptions.innerHTML = ATTEMPT_LIMIT_OPTIONS
            .map((option) => {
                const selected = state.selectedAttemptLimitType === option.id ? 'is-selected' : '';
                return `
                    <article class="option-card ${selected}" data-attempt-type-id="${escapeHtml(option.id)}">
                        <h4>${escapeHtml(option.label)}</h4>
                        <p>${escapeHtml(option.description)}</p>
                    </article>
                `;
            })
            .join('');

        refs.attemptLimitTypeHidden.value = state.selectedAttemptLimitType;
    }

    function updateScheduleConfigState() {
        const scheduleType = normalizeScheduleType(state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time'));
        state.selectedScheduleType = scheduleType;
        const attemptType = normalizeAttemptLimitType(state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once'));
        state.selectedAttemptLimitType = attemptType;

        if (refs.fixedScheduleWindow) {
            refs.fixedScheduleWindow.hidden = scheduleType !== 'fixed_window';
        }
        if (refs.fixedAttemptLimitWrap) {
            refs.fixedAttemptLimitWrap.hidden = attemptType !== 'fixed_count';
        }

        const startAt = cleanText(refs.scheduleStartAt ? refs.scheduleStartAt.value : '');
        const endAt = cleanText(refs.scheduleEndAt ? refs.scheduleEndAt.value : '');
        const startAtLabel = formatScheduleDateTimeForDisplay(startAt);
        const endAtLabel = formatScheduleDateTimeForDisplay(endAt);
        const fixedCount = Math.max(0, toInt(refs.attemptLimitCount ? refs.attemptLimitCount.value : 0, 0));

        let scheduleSummary = 'Schedule: candidates can start anytime.';
        if (scheduleType === 'fixed_window') {
            scheduleSummary = startAt && endAt
                ? `Schedule: exam is allowed between ${startAtLabel} and ${endAtLabel}.`
                : 'Schedule: fixed date-time window selected. Please set both start and end.';
        }

        let attemptSummary = 'Attempt policy: one time only.';
        if (attemptType === 'fixed_count') {
            attemptSummary = `Attempt policy: maximum ${fixedCount || 0} attempt(s) per candidate.`;
        } else if (attemptType === 'unlimited') {
            attemptSummary = 'Attempt policy: unlimited attempts are allowed.';
        }

        if (refs.scheduleConfigSummary) {
            refs.scheduleConfigSummary.textContent = `${scheduleSummary} ${attemptSummary}`;
        }

        if (refs.scheduleTypeHidden) {
            refs.scheduleTypeHidden.value = state.selectedScheduleType;
        }
        if (refs.attemptLimitTypeHidden) {
            refs.attemptLimitTypeHidden.value = state.selectedAttemptLimitType;
        }
    }

    function updateTimerConfigState() {
        if (!refs.enableExamTimer) {
            return;
        }

        const timerEnabled = refs.enableExamTimer.checked;
        const duration = Math.max(0, toInt(refs.examDurationMinutes ? refs.examDurationMinutes.value : 0, 0));
        const autoSubmitEnabled = Boolean(refs.autoSubmitOnTimerEnd && refs.autoSubmitOnTimerEnd.checked);

        if (refs.timerDurationWrap) {
            refs.timerDurationWrap.hidden = !timerEnabled;
        }
        if (refs.timerAutoSubmitWrap) {
            refs.timerAutoSubmitWrap.hidden = !timerEnabled;
        }

        if (refs.timerConfigSummary) {
            if (!timerEnabled) {
                refs.timerConfigSummary.textContent = 'Timer is disabled. Candidates can continue without a countdown limit.';
            } else {
                refs.timerConfigSummary.textContent = autoSubmitEnabled
                    ? `Timer is enabled for ${duration || 0} minute(s). Exam will auto-submit when time ends.`
                    : `Timer is enabled for ${duration || 0} minute(s). Auto-submit on timer end is currently disabled.`;
            }
        }
    }

    function parseDateTimeValue(value) {
        const parsed = parseDateTimeObject(value);
        return parsed ? parsed.getTime() : null;
    }

    function renderInstructionTemplates() {
        const templates = Array.isArray(state.config.instructionTemplates)
            ? state.config.instructionTemplates
            : [];

        refs.instructionTemplate.innerHTML = [`<option value="">Choose template</option>`]
            .concat(
                templates.map(
                    (template) => `<option value="${escapeHtml(template.id)}">${escapeHtml(template.label)}</option>`
                )
            )
            .join('');

        const defaultTemplate = templates.find((template) => template.is_default);
        if (defaultTemplate) {
            refs.instructionTemplate.value = defaultTemplate.id;
        }
    }

    function getInstructionRulesConfig() {
        return Array.isArray(state.config.instructionRules) ? state.config.instructionRules : [];
    }

    function normalizeInstructionRuleSelection(rawValues) {
        const validIds = new Set(getInstructionRulesConfig().map((rule) => rule.id));
        const source = Array.isArray(rawValues) ? rawValues : [];
        return [...new Set(source
            .map((value) => cleanText(String(value || '')))
            .filter((value) => validIds.has(value)))];
    }

    function defaultInstructionRuleIds() {
        return getInstructionRulesConfig()
            .filter((rule) => rule.is_default || rule.is_required)
            .map((rule) => rule.id);
    }

    function syncInstructionRulesHidden() {
        if (!refs.instructionRulesHidden) {
            return;
        }
        refs.instructionRulesHidden.value = JSON.stringify([...state.selectedInstructionRules]);
    }

    function renderInstructionRules() {
        if (!refs.instructionRulesList) {
            return;
        }

        const rules = getInstructionRulesConfig();
        if (!rules.length) {
            refs.instructionRulesList.innerHTML = `
                <p class="exam-help">No instruction rules are configured for this organization yet.</p>
            `;
            if (refs.instructionRulesCount) {
                refs.instructionRulesCount.textContent = '0';
            }
            syncInstructionRulesHidden();
            return;
        }

        refs.instructionRulesList.innerHTML = rules
            .map((rule) => {
                const checked = state.selectedInstructionRules.has(rule.id);
                const cardState = checked ? 'is-active' : '';
                const requiredAttr = rule.is_required ? 'data-required="1"' : '';
                return `
                    <article class="instruction-rule-card ${cardState}">
                        <label class="switch-control" style="cursor: pointer;">
                            <input type="checkbox" data-rule-id="${escapeHtml(rule.id)}" ${requiredAttr} ${checked ? 'checked' : ''}>
                            <span class="switch-control__track"></span>
                            <span class="switch-control__label">${escapeHtml(rule.label)}</span>
                        </label>
                        <p class="instruction-rule-card__description">${escapeHtml(rule.description || '')}</p>
                    </article>
                `;
            })
            .join('');

        if (refs.instructionRulesCount) {
            refs.instructionRulesCount.textContent = String(state.selectedInstructionRules.size);
        }
        syncInstructionRulesHidden();
    }

    function renderModalSelects() {
        if (!refs.modal && !refs.newQuestionCategory && !refs.newQuestionMarks && !refs.newQuestionDifficulty) {
            return;
        }

        const categories = getAssignableCategories();

        if (refs.newQuestionCategory) {
            refs.newQuestionCategory.innerHTML = categories
                .map((category) => buildCategoryOptionMarkup(category, state.config.categories))
                .join('');
        }

        if (refs.newQuestionMarks) {
            refs.newQuestionMarks.innerHTML = state.config.questionMarks
                .map((mark) => `<option value="${Number(mark.value)}">${escapeHtml(mark.label)}</option>`)
                .join('');
        }

        if (refs.newQuestionDifficulty) {
            refs.newQuestionDifficulty.innerHTML = state.config.difficultyLevels
                .map((level) => `<option value="${escapeHtml(level.id)}">${escapeHtml(level.label)}</option>`)
                .join('');
        }
    }

    function bindEvents() {
        if (state.eventsBound) {
            return;
        }

        try {
            bindEventsInternal();
            state.eventsBound = true;
        } catch (error) {
            state.eventsBound = false;
            throw error;
        }
    }

    function bindEventsInternal() {
        refs.mode.addEventListener('change', () => {
            state.selectedMode = refs.mode.value;
            updateAll();
        });

        refs.visibility.addEventListener('change', () => {
            state.selectedVisibility = refs.visibility.value;
            renderPricingOptions();
            updateAll();
        });

        if (refs.enableExamTimer) {
            refs.enableExamTimer.addEventListener('change', () => {
                updateTimerConfigState();
                updateAll();
            });
        }

        if (refs.examDurationMinutes) {
            refs.examDurationMinutes.addEventListener('input', () => {
                updateTimerConfigState();
                updateAll();
            });
            refs.examDurationMinutes.addEventListener('change', () => {
                updateTimerConfigState();
                updateAll();
            });
        }

        if (refs.autoSubmitOnTimerEnd) {
            refs.autoSubmitOnTimerEnd.addEventListener('change', () => {
                updateTimerConfigState();
                updateAll();
            });
        }

        if (refs.examFormatOptions) {
            refs.examFormatOptions.addEventListener('click', (event) => {
                const card = event.target.closest('[data-format-id]');
                if (!card) return;
                const formatId = card.dataset.formatId;
                if (state.selectedExamFormat.has(formatId)) {
                    if (state.selectedExamFormat.size > 1) {
                        state.selectedExamFormat.delete(formatId);
                    }
                } else {
                    state.selectedExamFormat.add(formatId);
                }
                renderExamFormatOptions();
                updateAll();
            });
        }

        if (refs.scheduleTypeOptions) {
            refs.scheduleTypeOptions.addEventListener('click', (event) => {
                const card = event.target.closest('[data-schedule-type-id]');
                if (!card) return;
                state.selectedScheduleType = normalizeScheduleType(card.dataset.scheduleTypeId);
                renderScheduleTypeOptions();
                updateAll();
            });
        }

        if (refs.attemptLimitOptions) {
            refs.attemptLimitOptions.addEventListener('click', (event) => {
                const card = event.target.closest('[data-attempt-type-id]');
                if (!card) return;
                state.selectedAttemptLimitType = normalizeAttemptLimitType(card.dataset.attemptTypeId);
                renderAttemptLimitOptions();
                updateAll();
            });
        }

        [refs.scheduleStartAt, refs.scheduleEndAt, refs.attemptLimitCount].forEach((field) => {
            if (!field) return;
            field.addEventListener('input', updateAll);
            field.addEventListener('change', updateAll);
        });

        bindExtraQuestionsCategorySelect();

        [
            refs.totalQuestions,
            refs.totalMarks,
            refs.passingMarks,
            refs.useQuestionPool,
            refs.maximumQuestions,
            refs.fixedQuestions,
            refs.fixedPaperSet,
            refs.paperSets,
            refs.fixCategoryQuestions,
            refs.fixCategoryMarks,
            refs.shuffleQuestions,
            refs.shuffleCategories,
            refs.shuffleOptions,
        ].forEach((field) => {
            if (!field) return;
            field.addEventListener('input', updateAll);
            field.addEventListener('change', updateAll);
        });

        if (refs.extraMarksAllocationList) {
            refs.extraMarksAllocationList.addEventListener('input', (event) => {
                const input = event.target.closest('.category-marks-count-input');
                if (!input) {
                    return;
                }
                const cid = String(input.dataset.categoryId || '');
                const fieldMin = Math.max(0, toInt(input.getAttribute('min'), 0));
                const fieldMax = Math.max(
                    fieldMin,
                    toInt(input.getAttribute('max'), toInt(refs.totalMarks?.value, 0))
                );
                let nextValue = toInt(input.value, fieldMin);
                if (Number.isNaN(nextValue) || nextValue < fieldMin) {
                    nextValue = fieldMin;
                }
                if (nextValue > fieldMax) {
                    nextValue = fieldMax;
                }
                input.value = String(nextValue);
                state.extraMarksAllocations[cid] = nextValue;
                syncExtraMarksAllocations();
                updateAll();
            });
        }

        if (refs.distributionTypeGroup) {
            refs.distributionTypeGroup.addEventListener('click', (event) => {
                const button = event.target.closest('[data-distribution-id]');
                if (!button) return;
                state.selectedDistributionType = button.dataset.distributionId;
                renderDistributionTypes();
                updateAll();
            });
        }

        bindMainCategorySelect();

        refs.marksFilter.addEventListener('click', (event) => {
            const button = event.target.closest('[data-mark-value]');
            if (!button) return;

            const mark = Number(button.dataset.markValue);

            if (refs.fixMarksEachQuestion && !refs.fixMarksEachQuestion.checked) {
                // Not fixed, allow multiple marks
                if (state.selectedMarks.has(mark)) {
                    state.selectedMarks.delete(mark);
                } else {
                    state.selectedMarks.add(mark);
                }
            } else {
                // Fixed, allow only one mark
                state.selectedMarks.clear();
                state.selectedMarks.add(mark);
            }

            renderQuestionMarks();
            updateAll();
        });

        if (refs.fixMarksEachQuestion) {
            refs.fixMarksEachQuestion.addEventListener('change', () => {
                if (refs.fixMarksEachQuestion.checked && state.selectedMarks.size > 1) {
                    const firstMark = Array.from(state.selectedMarks)[0];
                    state.selectedMarks.clear();
                    if (firstMark) state.selectedMarks.add(firstMark);
                }
                renderQuestionMarks();
                updateAll();
            });
        }

        if (refs.marksFixTotalMarksBtn) {
            refs.marksFixTotalMarksBtn.addEventListener('click', () => {
                applyMarksCalculationFix('total_marks');
            });
        }

        if (refs.marksFixTotalQuestionsBtn) {
            refs.marksFixTotalQuestionsBtn.addEventListener('click', () => {
                applyMarksCalculationFix('total_questions');
            });
        }

        if (refs.enableNegativeMarking) {
            refs.enableNegativeMarking.addEventListener('change', () => {
                refs.negativeMarkingConfig.hidden = !refs.enableNegativeMarking.checked;
            });
        }

        refs.pricingOptions.addEventListener('click', (event) => {
            const card = event.target.closest('[data-pricing-option]');
            if (!card || card.classList.contains('is-hidden')) return;
            state.selectedPricing = card.dataset.pricingOption;
            highlightPricingOptions();
            updateAll();
        });

        refs.discountRules.addEventListener('click', (event) => {
            if (event.target.closest('input') || event.target.closest('.discount-pct-wrap')) return;

            const card = event.target.closest('[data-discount-id]');
            if (!card) return;
            const id = card.dataset.discountId;
            if (state.selectedDiscounts.has(id)) {
                state.selectedDiscounts.delete(id);
            } else {
                state.selectedDiscounts.add(id);
            }
            renderDiscountRules();
            updateAll();
        });

        refs.questionSearch.addEventListener('input', () => {
            scheduleQuestionBankSync(400);
            updateQuestionBankCards();
        });
        refs.questionBankLoadMoreBtn?.addEventListener('click', () => {
            // Per-category load-more buttons handle pagination.
        });

        refs.questionCategoryCards.addEventListener('click', (event) => {
            const expandButton = event.target.closest('[data-action="toggle-expand"]');
            const addButton = event.target.closest('[data-action="add-question"]');
            const addMissingButton = event.target.closest('[data-action="add-missing-question"]');
            const loadCategoryBtn = event.target.closest('[data-action="load-category-questions"]');
            const loadMoreCategoryBtn = event.target.closest('[data-action="load-more-category-questions"]');

            if (loadCategoryBtn) {
                const categoryId = String(loadCategoryBtn.dataset.categoryId || '');
                if (!categoryId) return;
                state.expandedCards.add(categoryId);
                loadCategoryQuestions(categoryId, { append: false });
                return;
            }

            if (loadMoreCategoryBtn) {
                const categoryId = String(loadMoreCategoryBtn.dataset.categoryId || '');
                if (!categoryId) return;
                loadCategoryQuestions(categoryId, { append: true });
                return;
            }

            if (expandButton) {
                const categoryId = String(expandButton.dataset.categoryId || '');
                if (state.expandedCards.has(categoryId)) {
                    state.expandedCards.delete(categoryId);
                    updateQuestionBankCards();
                } else {
                    state.expandedCards.add(categoryId);
                    updateQuestionBankCards();
                    const loadState = state.categoryLoadState[categoryId];
                    if (!loadState || loadState.status === 'idle') {
                        loadCategoryQuestions(categoryId, { append: false });
                    }
                }
                return;
            }

            if (addMissingButton) {
                openAddQuestionModal(addMissingButton.dataset.categoryId || '', {
                    marks: addMissingButton.dataset.marks || '',
                });
                return;
            }

            if (addButton) {
                openAddQuestionModal(addButton.dataset.categoryId || '');
                return;
            }

            const randomSelectBtn = event.target.closest('[data-action="random-select-category"]');
            if (randomSelectBtn) {
                const categoryId = randomSelectBtn.dataset.categoryId;
                randomSelectCategory(categoryId);
                return;
            }
        });

        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) {
                return;
            }
            const payload = event.data;
            if (!payload || payload.type !== 'exam-create-question-created') {
                return;
            }

            const questionId = Number(payload.question?.id || 0);
            syncQuestionBankFromServer().then(() => {
                if (questionId > 0 && isManualQuestionSelectionEnabled()) {
                    const limits = getQuestionSelectionLimits();
                    if (state.selectedQuestions.size < limits.max) {
                        state.selectedQuestions.add(questionId);
                    }
                    updateQuestionBankCards();
                    updateWorkflowAndSnapshot();
                }
                window.EmsToast?.success('Question created and question bank refreshed.');
            });
        });

        if (refs.globalRandomSelectBtn) {
            refs.globalRandomSelectBtn.addEventListener('click', () => {
                randomSelectGlobal();
            });
        }

        refs.questionCategoryCards.addEventListener('change', (event) => {
            const questionCheckbox = event.target.closest('.question-checkbox');
            if (questionCheckbox) {
                const questionId = Number(questionCheckbox.dataset.questionId);
                if (questionCheckbox.checked) {
                    state.selectedQuestions.add(questionId);
                    const question = getQuestionById(questionId);
                    if (question) {
                        rememberSelectedQuestion(question);
                    }
                } else {
                    state.selectedQuestions.delete(questionId);
                    delete state.selectedQuestionCache[String(questionId)];
                }
                updateQuestionBankCards();
                updateWorkflowAndSnapshot();
                return;
            }

            const checkbox = event.target.closest('[data-role="category-toggle"]');
            if (!checkbox) return;

            const categoryId = checkbox.dataset.categoryId;

            if (checkbox.checked) {
                const nextSelection = applyMainCategorySelectionRules([...state.selectedCategories, categoryId]);
                state.selectedCategories = new Set(nextSelection);
            } else {
                state.selectedCategories.delete(categoryId);
            }

            state.extraQuestionsOptionsKey = '';
            state.categoryQuestionCountsKey = '';
            state.categoryMarksCountsKey = '';
            renderCategorySelector();
            updateAll();
        });

        refs.candidateTabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                state.activeCandidateTab = button.dataset.candidateTab;
                renderCandidateTabs();
            });
        });

        refs.dropZone.addEventListener('dragover', (event) => {
            event.preventDefault();
            refs.dropZone.classList.add('is-active');
        });

        refs.dropZone.addEventListener('dragleave', () => {
            refs.dropZone.classList.remove('is-active');
        });

        refs.dropZone.addEventListener('drop', async (event) => {
            event.preventDefault();
            refs.dropZone.classList.remove('is-active');
            const file = event.dataTransfer?.files?.[0];
            if (file) {
                refs.candidateFile.files = event.dataTransfer.files;
                await handleCandidateFile(file);
            }
        });

        refs.candidateFile.addEventListener('change', async (event) => {
            const file = event.target.files?.[0];
            if (!file) return;
            await handleCandidateFile(file);
        });

        refs.freeCandidateTabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                state.activeFreeCandidateTab = button.dataset.freeCandidateTab;
                renderFreeCandidateTabs();
            });
        });

        if (refs.freeDropZone) {
            refs.freeDropZone.addEventListener('dragover', (event) => {
                event.preventDefault();
                refs.freeDropZone.classList.add('is-active');
            });

            refs.freeDropZone.addEventListener('dragleave', () => {
                refs.freeDropZone.classList.remove('is-active');
            });

            refs.freeDropZone.addEventListener('drop', async (event) => {
                event.preventDefault();
                refs.freeDropZone.classList.remove('is-active');
                const file = event.dataTransfer?.files?.[0];
                if (file) {
                    refs.freeCandidateFile.files = event.dataTransfer.files;
                    await handleFreeCandidateFile(file);
                }
            });
        }

        if (refs.freeCandidateFile) {
            refs.freeCandidateFile.addEventListener('change', async (event) => {
                const file = event.target.files?.[0];
                if (!file) return;
                await handleFreeCandidateFile(file);
            });
        }

        if (refs.openAddQuestionModal) {
            refs.openAddQuestionModal.addEventListener('click', () => openAddQuestionModal(''));
        }

        refs.modalCloseButtons.forEach((button) => button.addEventListener('click', closeAddQuestionModal));

        if (refs.addQuestionForm) {
            refs.addQuestionForm.addEventListener('submit', (event) => {
                event.preventDefault();
                addQuestionFromModal();
            });
        }

        if (refs.customDiscountsBtn) {
            refs.customDiscountsBtn.addEventListener('click', () => {
                state.customDiscounts.push({
                    name: '',
                    description: '',
                    percentage: 10
                });
                renderCustomDiscounts();
            });
        }

        refs.applyInstructionTemplate.addEventListener('click', applyInstructionTemplate);

        if (refs.instructionRulesList) {
            refs.instructionRulesList.addEventListener('change', (event) => {
                const checkbox = event.target.closest('input[data-rule-id]');
                if (!checkbox) {
                    return;
                }

                const ruleId = cleanText(checkbox.dataset.ruleId || '');
                if (!ruleId) {
                    return;
                }

                const card = checkbox.closest('.instruction-rule-card');
                const isRequired = checkbox.dataset.required === '1';

                if (checkbox.checked) {
                    state.selectedInstructionRules.add(ruleId);
                    card?.classList.add('is-active');
                } else if (isRequired) {
                    checkbox.checked = true;
                    state.selectedInstructionRules.add(ruleId);
                    card?.classList.add('is-active');
                } else {
                    state.selectedInstructionRules.delete(ruleId);
                    card?.classList.remove('is-active');
                }

                if (refs.instructionRulesCount) {
                    refs.instructionRulesCount.textContent = String(state.selectedInstructionRules.size);
                }
                syncInstructionRulesHidden();
                updateWorkflowAndSnapshot();
            });
        }

        refs.form.addEventListener('submit', (event) => {
            syncRichTextFields();
            const errors = collectSubmissionErrors();
            if (errors.length) {
                event.preventDefault();
                showFormErrors(errors);
                refs.errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            clearFormErrors();
        });

        [
            refs.title,
            refs.description,
            refs.difficulty,
            refs.status,
            refs.mode,
            refs.visibility,
            refs.enableExamTimer,
            refs.examDurationMinutes,
            refs.autoSubmitOnTimerEnd,
            refs.scheduleStartAt,
            refs.scheduleEndAt,
            refs.attemptLimitCount,
            refs.totalQuestions,
            refs.totalMarks,
            refs.passingMarks,
            refs.paperSets,
        ].forEach((field) => {
            if (!field) return;
            field.addEventListener('input', updateWorkflowAndSnapshot);
            field.addEventListener('change', updateWorkflowAndSnapshot);
        });
    }

    function renderCandidateTabs() {
        refs.candidateTabButtons.forEach((button) => {
            const active = button.dataset.candidateTab === state.activeCandidateTab;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', String(active));
        });

        refs.candidatePanels.forEach((panel) => {
            const active = panel.dataset.candidatePanel === state.activeCandidateTab;
            panel.hidden = !active;
            panel.classList.toggle('is-active', active);
        });
    }

    function renderFreeCandidateTabs() {
        refs.freeCandidateTabButtons.forEach((button) => {
            const active = button.dataset.freeCandidateTab === state.activeFreeCandidateTab;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', String(active));
        });

        refs.freeCandidatePanels.forEach((panel) => {
            const active = panel.dataset.freeCandidatePanel === state.activeFreeCandidateTab;
            panel.hidden = !active;
            panel.classList.toggle('is-active', active);
        });
    }

    async function handleCandidateFile(file) {
        try {
            const parsed = await parseCandidateFile(file);
            state.importedCandidates = parsed.filter((candidate) => isValidEmail(candidate.email));
            refs.importedCandidatesHidden.value = JSON.stringify(state.importedCandidates);
            renderImportedCandidatePreview(file.name);
            updateWorkflowAndSnapshot();
        } catch (error) {
            state.importedCandidates = [];
            refs.importedCandidatesHidden.value = '[]';
            refs.importedCandidatePreview.hidden = false;
            refs.importedCandidatePreview.textContent = error.message || 'Unable to import this candidate file.';
            window.EmsToast?.error(error.message || 'Unable to import candidate file.');
        }
    }

    async function handleFreeCandidateFile(file) {
        try {
            const parsed = await parseCandidateFile(file);
            state.freeImportedCandidates = parsed.filter((candidate) => isValidEmail(candidate.email));
            refs.freeImportedCandidatesHidden.value = JSON.stringify(state.freeImportedCandidates);
            renderFreeImportedCandidatePreview(file.name);
            updateWorkflowAndSnapshot();
        } catch (error) {
            state.freeImportedCandidates = [];
            refs.freeImportedCandidatesHidden.value = '[]';
            refs.freeImportedCandidatePreview.hidden = false;
            refs.freeImportedCandidatePreview.textContent = error.message || 'Unable to import this candidate file.';
            window.EmsToast?.error(error.message || 'Unable to import candidate file.');
        }
    }

    async function parseCandidateFile(file) {
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        if (extension === 'csv') {
            return parseCandidateCsv(await file.text());
        }

        if (!['xls', 'xlsx'].includes(extension)) {
            throw new Error('Choose a CSV, XLS, or XLSX candidate file.');
        }
        if (!window.XLSX) {
            throw new Error('Excel import is unavailable. Refresh the page and try again.');
        }

        const workbook = window.XLSX.read(await file.arrayBuffer(), { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        if (!firstSheet) {
            return [];
        }

        const rows = window.XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });
        if (rows.length <= 1) {
            return [];
        }

        const headers = rows[0].map((value) => cleanText(value).toLowerCase());
        const nameIndex = headers.indexOf('name');
        const emailIndex = headers.indexOf('email');
        if (emailIndex < 0) {
            throw new Error('The candidate file must contain an Email column.');
        }

        return rows.slice(1)
            .map((row) => ({
                name: cleanText(nameIndex >= 0 ? row[nameIndex] : 'Candidate') || 'Candidate',
                email: cleanText(row[emailIndex]).toLowerCase(),
            }))
            .filter((candidate) => candidate.email);
    }

    function parseCandidateCsv(content) {
        const lines = content.split(/\r?\n/).filter((line) => cleanText(line));
        if (lines.length <= 1) return [];

        const items = lines.slice(1).map((line) => {
            const [namePart, emailPart] = line.split(',');
            return {
                name: cleanText(namePart || 'Candidate'),
                email: cleanText(emailPart || '').toLowerCase(),
            };
        });

        const deduped = [];
        const seen = new Set();

        items.forEach((item) => {
            if (!item.email || seen.has(item.email)) return;
            seen.add(item.email);
            deduped.push(item);
        });

        return deduped;
    }

    function renderImportedCandidatePreview(fileName) {
        if (!state.importedCandidates.length) {
            refs.importedCandidatePreview.hidden = true;
            refs.importedCandidatePreview.textContent = '';
            return;
        }

        const topItems = state.importedCandidates
            .slice(0, 5)
            .map((candidate) => `${escapeHtml(candidate.name)} (${escapeHtml(candidate.email)})`)
            .join('<br>');

        refs.importedCandidatePreview.hidden = false;
        refs.importedCandidatePreview.innerHTML = `
            <strong>${state.importedCandidates.length}</strong> candidates loaded from <strong>${escapeHtml(fileName)}</strong>.<br>
            ${topItems}
        `;
    }

    function renderFreeImportedCandidatePreview(fileName) {
        if (!state.freeImportedCandidates.length) {
            refs.freeImportedCandidatePreview.hidden = true;
            refs.freeImportedCandidatePreview.textContent = '';
            return;
        }

        const topItems = state.freeImportedCandidates
            .slice(0, 5)
            .map((candidate) => `${escapeHtml(candidate.name)} (${escapeHtml(candidate.email)})`)
            .join('<br>');

        refs.freeImportedCandidatePreview.hidden = false;
        refs.freeImportedCandidatePreview.innerHTML = `
            <strong>${state.freeImportedCandidates.length}</strong> candidates loaded from <strong>${escapeHtml(fileName)}</strong>.<br>
            ${topItems}
        `;
    }

    function updateSectionNumbers() {
        const sections = [
            { id: 'exam-basic-information', defaultTitle: 'Exam Basic Information' },
            { id: 'candidate-access-section', defaultTitle: 'Candidate Access Management' },
            { id: 'timer-section', defaultTitle: 'Timer & Duration Management' },
            { id: 'exam-format-section', defaultTitle: 'Exam Format Management' },
            { id: 'schedule-section', defaultTitle: 'Schedule & Attempt Management' },
            { id: 'exam-configuration-section', defaultTitle: 'Exam Configuration' },
            { id: 'question-rules-section', defaultTitle: 'Question Rules and Filters' },
            { id: 'pricing-section', defaultTitle: 'Pricing and Discount Rules' },
            { id: 'question-bank-section', defaultTitle: 'Question Bank Management' },
            { id: 'instructions-rules-section', defaultTitle: 'Exam Instructions & Rules Management' },
            { id: 'instructions-section', defaultTitle: 'Instructions for Candidates' }
        ];

        let visibleCount = 0;
        sections.forEach(sec => {
            const el = document.getElementById(sec.id);
            if (el) {
                if (!el.hidden) {
                    visibleCount++;
                    const h2 = el.querySelector('.exam-section__head h2');
                    if (h2) {
                        h2.textContent = `${visibleCount}. ${sec.defaultTitle}`;
                    }
                }
            }
        });
    }

    function updateConditionalSections() {
        const hasRestrictedAccess = ['private', 'invite_only'].includes(state.selectedVisibility);
        const importedFree = state.selectedPricing === 'free_for_imported';

        refs.candidateSection.hidden = !hasRestrictedAccess;

        const usesQuestionPool = Boolean(refs.useQuestionPool?.checked);
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions?.value, 1));
        refs.maximumQuestionsWrap.hidden = !usesQuestionPool;
        refs.maximumQuestions.disabled = !usesQuestionPool;
        refs.fixedQuestionsWrap.hidden = usesQuestionPool;
        refs.fixedQuestions.disabled = usesQuestionPool;
        refs.maximumQuestions.min = String(totalQuestions + 1);
        refs.maximumQuestionsHelper.textContent = `Enter at least ${totalQuestions + 1}. Each candidate will receive ${totalQuestions} question(s).`;
        if (usesQuestionPool) {
            refs.fixedQuestions.checked = false;
        } else {
            refs.maximumQuestions.value = '';
        }

        if (!isManualQuestionSelectionEnabled()) {
            state.selectedQuestions.clear();
        }
        updateQuestionSelectionControlsVisibility();

        const hasFixedPaperSets = Boolean(refs.fixedPaperSet?.checked);
        refs.paperSetsWrap.hidden = !hasFixedPaperSets;
        refs.paperSets.disabled = !hasFixedPaperSets;
        if (!hasFixedPaperSets) {
            refs.paperSets.value = '1';
        }

        const supportsOptionShuffle = state.selectedExamFormat instanceof Set
            && (state.selectedExamFormat.has('mcq') || state.selectedExamFormat.has('multi_select'));
        refs.shuffleOptionsWrap.hidden = !supportsOptionShuffle;
        refs.shuffleOptions.disabled = !supportsOptionShuffle;
        if (!supportsOptionShuffle) {
            refs.shuffleOptions.checked = false;
        }

        refs.negativeMarkingConfig.hidden = !refs.enableNegativeMarking.checked;

        // Show/hide Free Candidate List section depending on selected pricing option
        refs.freeCandidatesWrap.hidden = !importedFree;

        refs.pricingImportedNote.hidden = !importedFree;
        renderCandidateTabs();
        renderFreeCandidateTabs();

        refs.pricingSection.hidden = state.selectedMode === 'practice';

        // Update the visible sections dynamic numbering
        updateSectionNumbers();
    }

    function isQuestionPoolEnabled() {
        return Boolean(refs.useQuestionPool?.checked);
    }

    function isManualQuestionSelectionEnabled() {
        return isQuestionPoolEnabled() || Boolean(refs.fixedQuestions?.checked);
    }

    function getQuestionSelectionLimits() {
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions?.value, 1));

        if (isQuestionPoolEnabled()) {
            const maximumQuestions = Math.max(
                totalQuestions + 1,
                toInt(refs.maximumQuestions?.value, totalQuestions + 1)
            );
            return {
                viewOnly: false,
                min: totalQuestions,
                max: maximumQuestions,
                exact: null,
                target: maximumQuestions,
            };
        }

        if (Boolean(refs.fixedQuestions?.checked)) {
            return {
                viewOnly: false,
                min: totalQuestions,
                max: totalQuestions,
                exact: totalQuestions,
                target: totalQuestions,
            };
        }

        return {
            viewOnly: true,
            min: 0,
            max: 0,
            exact: null,
            target: 0,
        };
    }

    function getQuestionSelectionTarget() {
        return getQuestionSelectionLimits().target;
    }

    function questionMatchesSelectedCategory(question, categoryId) {
        const questionCategoryId = String(question?.categoryId || '');
        const selectedCategoryId = String(categoryId || '');
        if (!questionCategoryId || !selectedCategoryId) {
            return false;
        }
        if (questionCategoryId === selectedCategoryId) {
            return true;
        }
        const descendants = getAllDescendantIds(selectedCategoryId, state.categoryHierarchyIndex);
        return descendants.includes(questionCategoryId);
    }

    function resolveQuestionDisplayCategory(question, selectedCategoryIds) {
        const selectedCategorySet = new Set(selectedCategoryIds.map(String));
        const questionCategoryId = String(question?.categoryId || '');
        if (selectedCategorySet.has(questionCategoryId)) {
            return questionCategoryId;
        }
        for (const selectedId of selectedCategoryIds) {
            if (questionMatchesSelectedCategory(question, selectedId)) {
                return String(selectedId);
            }
        }
        return null;
    }

    function computeQuestionShortages() {
        const shortages = [];
        const selectedMarks = [...state.selectedMarks].map(Number).filter((mark) => mark > 0);
        const selectedCategories = [...state.selectedCategories];
        const limits = getQuestionSelectionLimits();
        const requiredTotal = limits.viewOnly
            ? Math.max(1, toInt(refs.totalQuestions?.value, 1))
            : limits.max;
        const countedTotal = Object.values(state.categoryCounts || {}).reduce(
            (sum, value) => sum + Number(value || 0),
            0
        );
        const availableTotal = countedTotal > 0
            ? countedTotal
            : (Number(state.questionBankMeta?.total ?? 0) > 0
                ? Number(state.questionBankMeta.total)
                : state.questionBank.filter((question) => {
                    return selectedCategories.some((categoryId) => questionMatchesSelectedCategory(question, categoryId))
                        && (!selectedMarks.length || selectedMarks.includes(Number(question.marks)));
                }).length);

        if (availableTotal < requiredTotal) {
            shortages.push({
                categoryId: null,
                marks: null,
                required: requiredTotal,
                available: availableTotal,
                missing: requiredTotal - availableTotal,
            });
        }

        return shortages;
    }

    function renderQuestionShortages(shortages) {
        if (!refs.questionBankShortages) {
            return;
        }

        if (!shortages.length) {
            refs.questionBankShortages.hidden = true;
            refs.questionBankShortages.innerHTML = '';
            return;
        }

        refs.questionBankShortages.hidden = false;
        refs.questionBankShortages.innerHTML = `
            <div class="question-bank-shortages__title">Question shortages detected</div>
            <ul>
                ${shortages.map((item) => {
                    if (!item.categoryId) {
                        return `<li>Need ${item.missing} more matching question(s) overall (available ${item.available} / required ${item.required}).</li>`;
                    }
                    const categoryName = escapeHtml(getCategoryLabelById(item.categoryId));
                    return `<li>Need ${item.missing} more ${item.marks}-mark question(s) in <strong>${categoryName}</strong> (available ${item.available} / required ${item.required}).</li>`;
                }).join('')}
            </ul>
        `;
    }

    function rememberSelectedQuestion(question) {
        if (!question || question.id === undefined || question.id === null) {
            return;
        }
        state.selectedQuestionCache[String(question.id)] = question;
    }

    function getQuestionById(questionId) {
        const key = String(questionId);
        return state.questionBank.find((item) => String(item.id) === key)
            || state.selectedQuestionCache[key]
            || null;
    }

    function pruneSelectedQuestionsToVisibleBank() {
        if (!isManualQuestionSelectionEnabled()) {
            state.selectedQuestions.clear();
            state.selectedQuestionCache = {};
            return;
        }

        // Keep selections even when their rows are not on the currently loaded page.
        // Only enforce the max selection count.
        const limits = getQuestionSelectionLimits();
        if (limits.max > 0 && state.selectedQuestions.size > limits.max) {
            const kept = [...state.selectedQuestions].slice(0, limits.max);
            state.selectedQuestions = new Set(kept);
        }

        Object.keys(state.selectedQuestionCache).forEach((id) => {
            if (!state.selectedQuestions.has(Number(id)) && !state.selectedQuestions.has(id)) {
                delete state.selectedQuestionCache[id];
            }
        });
    }

    function updateQuestionSelectionControlsVisibility() {
        const limits = getQuestionSelectionLimits();
        const selectionEnabled = !limits.viewOnly;

        if (refs.globalSelectionStats) {
            refs.globalSelectionStats.hidden = !selectionEnabled;
        }
        if (refs.globalRandomSelectBtn) {
            refs.globalRandomSelectBtn.hidden = !selectionEnabled;
        }
        if (refs.globalSelectionRange) {
            if (selectionEnabled && isQuestionPoolEnabled()) {
                refs.globalSelectionRange.hidden = false;
                refs.globalSelectionRange.textContent = `(select ${limits.min}–${limits.max})`;
            } else {
                refs.globalSelectionRange.hidden = true;
                refs.globalSelectionRange.textContent = '';
            }
        }
    }

    function computeCategoryTarget() {
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions.value, 1));
        const totalCategories = Math.max(1, state.selectedCategories.size);
        return Math.ceil(totalQuestions / totalCategories);
    }

    function getCategoryById(categoryId) {
        return state.config.categories.find((category) => category.id === categoryId) || null;
    }

    function getCategoryLabelById(categoryId) {
        const category = getCategoryById(categoryId);
        if (!category) {
            return categoryId;
        }
        const parent = getCategoryParent(category, state.config.categories);
        if (!parent) {
            return category.name;
        }
        return `${parent.name} / ${category.name}`;
    }

    function getCategoryDisplayHtml(categoryId) {
        const category = getCategoryById(categoryId);
        if (!category) {
            return escapeHtml(categoryId);
        }
        return `<span class="category-display-name">${escapeHtml(category.name)}</span>`;
    }

    function syncExtraQuestionsHidden() {
        if (refs.extraQuestionsCategoriesHidden) {
            refs.extraQuestionsCategoriesHidden.value = JSON.stringify(state.extraQuestionsCategoryIds);
        }
    }

    function syncExtraQuestionsAllocations() {
        if (refs.extraQuestionsAllocationsHidden) {
            refs.extraQuestionsAllocationsHidden.value = JSON.stringify(state.extraQuestionsAllocations);
        }
    }

    function getCategoryAllocationBounds(total, selectedCount) {
        const safeCount = Math.max(0, selectedCount);
        const safeTotal = Math.max(0, total);
        if (!safeCount) {
            return {
                base: 0,
                remainder: 0,
                minPerCategory: 0,
                maxPerCategory: safeTotal,
            };
        }

        const base = Math.floor(safeTotal / safeCount);
        const remainder = safeTotal % safeCount;
        return {
            base,
            remainder,
            minPerCategory: base,
            // Leave enough for every other category to keep the minimum.
            maxPerCategory: safeTotal - (base * (safeCount - 1)),
        };
    }

    function allocationsMeetMinimum(allocations, categoryIds, minimum) {
        return categoryIds.every((categoryId) => (
            Math.max(0, toInt(allocations[String(categoryId)], 0)) >= minimum
        ));
    }

    function buildEvenCategoryQuestionCounts(selectedIds, totalQuestions) {
        const counts = {};
        const selectedCount = selectedIds.length;
        if (!selectedCount) {
            return counts;
        }

        const { base, remainder } = getCategoryAllocationBounds(totalQuestions, selectedCount);
        selectedIds.forEach((categoryId, index) => {
            counts[String(categoryId)] = base + (index < remainder ? 1 : 0);
        });
        return counts;
    }

    function ensureCategoryQuestionCounts(selectedIds, totalQuestions) {
        const normalizedIds = selectedIds.map(String);
        const { minPerCategory } = getCategoryAllocationBounds(totalQuestions, normalizedIds.length);
        const structureKey = `${totalQuestions}|${[...normalizedIds].sort().join(',')}`;
        if (state.categoryQuestionCountsKey === structureKey) {
            let needsRebuild = false;
            normalizedIds.forEach((categoryId) => {
                if (typeof state.extraQuestionsAllocations[categoryId] === 'undefined') {
                    needsRebuild = true;
                    return;
                }
                if (toInt(state.extraQuestionsAllocations[categoryId], 0) < minPerCategory) {
                    needsRebuild = true;
                }
            });
            Object.keys(state.extraQuestionsAllocations).forEach((categoryId) => {
                if (!normalizedIds.includes(categoryId)) {
                    delete state.extraQuestionsAllocations[categoryId];
                    needsRebuild = true;
                }
            });
            if (needsRebuild) {
                state.extraQuestionsAllocations = buildEvenCategoryQuestionCounts(normalizedIds, totalQuestions);
                syncExtraQuestionsHidden();
                syncExtraQuestionsAllocations();
            }
            return;
        }

        const existingIds = Object.keys(state.extraQuestionsAllocations);
        const sameCategorySet = normalizedIds.length > 0
            && normalizedIds.length === existingIds.length
            && normalizedIds.every((categoryId) => Object.prototype.hasOwnProperty.call(
                state.extraQuestionsAllocations,
                categoryId
            ));
        const existingSum = existingIds.reduce(
            (sum, categoryId) => sum + Math.max(0, toInt(state.extraQuestionsAllocations[categoryId], 0)),
            0
        );

        // Keep hydrated/edited totals when they match total and respect the per-category minimum.
        if (
            sameCategorySet
            && existingSum === totalQuestions
            && allocationsMeetMinimum(state.extraQuestionsAllocations, normalizedIds, minPerCategory)
        ) {
            state.categoryQuestionCountsKey = structureKey;
            state.extraQuestionsCategoryIds = normalizedIds.slice();
            syncExtraQuestionsHidden();
            syncExtraQuestionsAllocations();
            return;
        }

        state.extraQuestionsAllocations = buildEvenCategoryQuestionCounts(normalizedIds, totalQuestions);
        state.categoryQuestionCountsKey = structureKey;
        state.extraQuestionsCategoryIds = normalizedIds.slice();
        syncExtraQuestionsHidden();
        syncExtraQuestionsAllocations();
    }

    function computeFixedCategoryDistribution() {
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions.value, 1));
        const selectedIds = [...state.selectedCategories];
        const selectedCount = selectedIds.length;

        if (!selectedCount) {
            state.extraQuestionsAllocations = {};
            state.extraQuestionsCategoryIds = [];
            state.categoryQuestionCountsKey = '';
            syncExtraQuestionsHidden();
            syncExtraQuestionsAllocations();
            return {
                totalQuestions,
                selectedCount: 0,
                base: 0,
                remainder: 0,
                minPerCategory: 0,
                maxPerCategory: 0,
                extraCategoryIds: [],
                rows: [],
                totalAllocated: 0,
                isComplete: false,
            };
        }

        ensureCategoryQuestionCounts(selectedIds, totalQuestions);

        const bounds = getCategoryAllocationBounds(totalQuestions, selectedCount);
        const rows = selectedIds.map((categoryId) => ({
            categoryId,
            count: Math.max(
                bounds.minPerCategory,
                toInt(state.extraQuestionsAllocations[String(categoryId)], bounds.minPerCategory)
            ),
        }));
        const totalAllocated = rows.reduce((sum, row) => sum + row.count, 0);
        const extraCategoryIds = rows
            .filter((row) => row.count > bounds.base)
            .map((row) => row.categoryId);
        const meetsMinimum = rows.every((row) => row.count >= bounds.minPerCategory);

        state.extraQuestionsCategoryIds = selectedIds.map(String);
        syncExtraQuestionsHidden();
        syncExtraQuestionsAllocations();

        return {
            totalQuestions,
            selectedCount,
            base: bounds.base,
            remainder: bounds.remainder,
            minPerCategory: bounds.minPerCategory,
            maxPerCategory: bounds.maxPerCategory,
            extraCategoryIds,
            rows,
            totalAllocated,
            isComplete: totalAllocated === totalQuestions && meetsMinimum,
        };
    }

    function syncExtraMarksAllocations() {
        if (refs.extraMarksAllocationsHidden) {
            refs.extraMarksAllocationsHidden.value = JSON.stringify(state.extraMarksAllocations);
        }
    }

    function buildEvenCategoryMarksCounts(selectedIds, totalMarks) {
        const counts = {};
        const selectedCount = selectedIds.length;
        if (!selectedCount) {
            return counts;
        }

        const { base, remainder } = getCategoryAllocationBounds(totalMarks, selectedCount);
        selectedIds.forEach((categoryId, index) => {
            counts[String(categoryId)] = base + (index < remainder ? 1 : 0);
        });
        return counts;
    }

    function ensureCategoryMarksCounts(selectedIds, totalMarks) {
        const normalizedIds = selectedIds.map(String);
        const { base, remainder, minPerCategory } = getCategoryAllocationBounds(totalMarks, normalizedIds.length);
        const structureKey = `${totalMarks}|${[...normalizedIds].sort().join(',')}`;
        if (state.categoryMarksCountsKey === structureKey) {
            let needsRebuild = false;
            normalizedIds.forEach((categoryId) => {
                if (typeof state.extraMarksAllocations[categoryId] === 'undefined') {
                    needsRebuild = true;
                    return;
                }
                if (toInt(state.extraMarksAllocations[categoryId], 0) < minPerCategory) {
                    needsRebuild = true;
                }
            });
            Object.keys(state.extraMarksAllocations).forEach((categoryId) => {
                if (!normalizedIds.includes(categoryId)) {
                    delete state.extraMarksAllocations[categoryId];
                    needsRebuild = true;
                }
            });
            if (needsRebuild) {
                state.extraMarksAllocations = buildEvenCategoryMarksCounts(normalizedIds, totalMarks);
                syncExtraMarksAllocations();
            }
            return;
        }

        const existingIds = Object.keys(state.extraMarksAllocations);
        const sameCategorySet = normalizedIds.length > 0
            && normalizedIds.length === existingIds.length
            && normalizedIds.every((categoryId) => Object.prototype.hasOwnProperty.call(
                state.extraMarksAllocations,
                categoryId
            ));
        const existingSum = existingIds.reduce(
            (sum, categoryId) => sum + Math.max(0, toInt(state.extraMarksAllocations[categoryId], 0)),
            0
        );

        // Keep hydrated/edited totals when they match total and respect the per-category minimum.
        if (
            sameCategorySet
            && existingSum === totalMarks
            && allocationsMeetMinimum(state.extraMarksAllocations, normalizedIds, minPerCategory)
        ) {
            state.categoryMarksCountsKey = structureKey;
            syncExtraMarksAllocations();
            return;
        }

        // Migrate legacy "extra only" payloads (sum === remainder) into full totals.
        if (sameCategorySet && remainder > 0 && existingSum === remainder) {
            const totals = {};
            normalizedIds.forEach((categoryId) => {
                totals[categoryId] = base + Math.max(0, toInt(state.extraMarksAllocations[categoryId], 0));
            });
            state.extraMarksAllocations = totals;
            state.categoryMarksCountsKey = structureKey;
            syncExtraMarksAllocations();
            return;
        }

        state.extraMarksAllocations = buildEvenCategoryMarksCounts(normalizedIds, totalMarks);
        state.categoryMarksCountsKey = structureKey;
        syncExtraMarksAllocations();
    }

    function computeFixedCategoryMarksDistribution() {
        const totalMarks = Math.max(1, toInt(refs.totalMarks?.value, 1));
        const selectedIds = [...state.selectedCategories];
        const selectedCount = selectedIds.length;

        if (!selectedCount) {
            state.extraMarksAllocations = {};
            state.categoryMarksCountsKey = '';
            syncExtraMarksAllocations();
            return {
                totalMarks,
                selectedCount: 0,
                base: 0,
                remainder: 0,
                minPerCategory: 0,
                maxPerCategory: 0,
                totalAllocated: 0,
                rows: [],
                isComplete: false,
            };
        }

        ensureCategoryMarksCounts(selectedIds, totalMarks);

        const bounds = getCategoryAllocationBounds(totalMarks, selectedCount);
        const rows = selectedIds.map((categoryId) => {
            const marks = Math.max(
                bounds.minPerCategory,
                toInt(state.extraMarksAllocations[String(categoryId)], bounds.minPerCategory)
            );
            return {
                categoryId,
                marks,
                extraMarks: Math.max(0, marks - bounds.base),
            };
        });
        const totalAllocated = rows.reduce((sum, row) => sum + row.marks, 0);
        const meetsMinimum = rows.every((row) => row.marks >= bounds.minPerCategory);

        syncExtraMarksAllocations();

        return {
            totalMarks,
            selectedCount,
            base: bounds.base,
            remainder: bounds.remainder,
            minPerCategory: bounds.minPerCategory,
            maxPerCategory: bounds.maxPerCategory,
            totalAllocated,
            rows,
            isComplete: totalAllocated === totalMarks && meetsMinimum,
        };
    }

    function renderFixedCategoryMarksDistribution() {
        if (!refs.fixedCategoryMarksCard) {
            return;
        }

        const enabled = Boolean(refs.fixCategoryMarks?.checked);
        const distribution = computeFixedCategoryMarksDistribution();

        if (!enabled || distribution.selectedCount === 0) {
            refs.fixedCategoryMarksCard.hidden = true;
            if (refs.extraMarksAllocationList) {
                refs.extraMarksAllocationList.innerHTML = '';
                refs.extraMarksAllocationList.dataset.structureKey = '';
            }
            state.categoryMarksCountsKey = '';
            return;
        }

        refs.fixedCategoryMarksCard.hidden = false;

        let helperText = `Set how many marks each category contributes. Totals must equal ${distribution.totalMarks}.`;
        helperText += ` Equally distributed with a minimum of ${distribution.minPerCategory} mark(s) per category`;
        if (distribution.remainder > 0) {
            helperText += ` (leftover +1 from the first category onward)`;
        }
        helperText += '.';
        if (refs.fixedCategoryMarksHelper) {
            refs.fixedCategoryMarksHelper.textContent = helperText;
        }

        if (refs.extraMarksAllocationsWrap) {
            refs.extraMarksAllocationsWrap.classList.toggle(
                'is-invalid',
                !distribution.isComplete
            );
        }
        if (refs.marksAllocatedCount) {
            refs.marksAllocatedCount.textContent = String(distribution.totalAllocated);
        }
        if (refs.marksRemainingCount) {
            refs.marksRemainingCount.textContent = String(distribution.totalMarks);
        }

        if (!refs.extraMarksAllocationList) {
            return;
        }

        const minAllowed = distribution.minPerCategory;
        const maxAllowed = distribution.maxPerCategory;
        const structureKey = `${distribution.rows.map((row) => String(row.categoryId)).join(',')}|${minAllowed}|${maxAllowed}`;
        const allocationsHtml = distribution.rows.map((row) => {
            const categoryName = escapeHtml(getCategoryLabelById(row.categoryId));
            const marks = Math.max(
                minAllowed,
                toInt(state.extraMarksAllocations[String(row.categoryId)], minAllowed)
            );
            return `
                <div>
                    <label class="exam-label">${categoryName}</label>
                    <input
                        type="number"
                        class="panel-input category-marks-count-input"
                        data-category-id="${escapeHtml(String(row.categoryId))}"
                        value="${marks}"
                        min="${minAllowed}"
                        max="${maxAllowed}"
                        step="1"
                    >
                </div>
            `;
        }).join('');

        if (refs.extraMarksAllocationList.dataset.structureKey !== structureKey) {
            refs.extraMarksAllocationList.innerHTML = allocationsHtml;
            refs.extraMarksAllocationList.dataset.structureKey = structureKey;
        } else {
            refs.extraMarksAllocationList.querySelectorAll('.category-marks-count-input').forEach((input) => {
                input.setAttribute('min', String(minAllowed));
                input.setAttribute('max', String(maxAllowed));
                if (document.activeElement === input) {
                    return;
                }
                const cid = String(input.dataset.categoryId || '');
                const val = Math.max(
                    minAllowed,
                    toInt(state.extraMarksAllocations[cid], minAllowed)
                );
                if (String(input.value) !== String(val)) {
                    input.value = String(val);
                }
            });
        }

        syncExtraMarksAllocations();
    }

    function getExtraQuestionsOptionsKey(selectedIds, remainder) {
        return `${remainder}|${selectedIds.slice().sort().join(',')}`;
    }

    function applyExtraCategorySelectionRules(rawIds, maxExtra, allowedIds) {
        const allowed = new Set(allowedIds);
        const filtered = [...rawIds].filter((id) => allowed.has(id));
        const pruned = [...pruneDescendantSelections(filtered, state.categoryHierarchyIndex)];
        return pruned.slice(0, Math.max(1, maxExtra));
    }

    function bindExtraQuestionsCategorySelect() {
        if (state.extraQuestionsSelectBound || !window.EmsSelect || typeof window.EmsSelect.onChange !== 'function') {
            return;
        }

        window.EmsSelect.onChange('extra_questions_category', () => {
            if (state.isSyncingExtraQuestions || state.suppressExtraSelectEvents) {
                return;
            }

            const selectedIds = [...state.selectedCategories];
            const remainder = selectedIds.length
                ? Math.max(0, toInt(refs.totalQuestions.value, 1) % selectedIds.length)
                : 0;
            const maxExtra = Math.max(1, remainder);
            const rawValue = window.EmsSelect.getValue('extra_questions_category');
            const selectedValues = Array.isArray(rawValue) ? rawValue : (rawValue ? [rawValue] : []);
            const normalized = applyExtraCategorySelectionRules(selectedValues, maxExtra, selectedIds);

            state.extraQuestionsCategoryIds = normalized;
            syncExtraQuestionsHidden();

            state.isSyncingExtraQuestions = true;
            try {
                refreshExtraQuestionsDropdownOptions(maxExtra);
            } finally {
                state.isSyncingExtraQuestions = false;
            }

            const distribution = computeFixedCategoryDistribution();
            renderExtraQuestionsAllocations(distribution);
            renderFixedDistributionListOnly();
            updateConfigPreview();
            updateWorkflowAndSnapshot();
            updateQuestionBankCards();
        });

        state.extraQuestionsSelectBound = true;
    }

    function buildExtraQuestionsOptionsHtml() {
        const selectedIds = [...state.selectedCategories];
        const visibleIds = selectedIds.filter((categoryId) => isCategoryVisibleInDropdown(
            categoryId,
            state.extraQuestionsCategoryIds,
            state.config.categories
        ));

        return visibleIds
            .map((categoryId) => {
                const category = getCategoryById(categoryId);
                return category
                    ? buildCategoryOptionMarkup(
                        category,
                        state.config.categories,
                        state.extraQuestionsCategoryIds.includes(categoryId)
                    )
                    : '';
            })
            .join('');
    }

    function refreshExtraQuestionsDropdownOptions(maxExtra) {
        const html = buildExtraQuestionsOptionsHtml();
        const values = state.extraQuestionsCategoryIds.slice();
        const limit = Math.max(1, maxExtra);

        refs.extraQuestionsCategory.dataset.maxItems = String(limit);

        if (window.EmsSelect && typeof window.EmsSelect.replaceOptions === 'function') {
            const prevInstance = window.EmsSelect.get('extra_questions_category');
            const wasOpen = prevInstance ? prevInstance.isOpen : false;

            window.EmsSelect.replaceOptions('extra_questions_category', html, values, limit);

            const newInstance = window.EmsSelect.get('extra_questions_category');
            const isComplete = limit > 0 && values.length >= limit;
            if (newInstance && wasOpen && !isComplete) {
                newInstance.open();
            }
        } else {
            refs.extraQuestionsCategory.innerHTML = html;
        }
    }

    function renderExtraQuestionsCategorySelect(distribution, options = {}) {
        const selectedIds = [...state.selectedCategories];
        const optionsKey = getExtraQuestionsOptionsKey(selectedIds, distribution.remainder);
        const maxExtra = Math.max(1, distribution.remainder);
        const forceRefresh = options.forceOptionsRefresh === true;

        if (refs.extraQuestionsLabel) {
            refs.extraQuestionsLabel.textContent = distribution.remainder > 1
                ? `Categories for Extra Questions (${distribution.remainder} max)`
                : 'Category for Extra Questions';
        }
        if (refs.extraQuestionsHelp) {
            refs.extraQuestionsHelp.textContent = distribution.remainder > 1
                ? `Select up to ${distribution.remainder} categories. Allocate the extra questions manually if fewer than ${distribution.remainder} categories are chosen.`
                : 'The remainder question will be assigned to this category.';
        }

        state.extraQuestionsCategoryIds = applyExtraCategorySelectionRules(
            state.extraQuestionsCategoryIds,
            maxExtra,
            selectedIds
        );

        state.isSyncingExtraQuestions = true;

        try {
            if (forceRefresh || state.extraQuestionsOptionsKey !== optionsKey) {
                state.extraQuestionsOptionsKey = optionsKey;
                normalizeExtraQuestionCategoryIds(selectedIds, distribution.remainder);
            }

            state.extraQuestionsCategoryIds = applyExtraCategorySelectionRules(
                state.extraQuestionsCategoryIds,
                maxExtra,
                selectedIds
            );

            refreshExtraQuestionsDropdownOptions(maxExtra);
            syncExtraQuestionsHidden();
        } finally {
            state.isSyncingExtraQuestions = false;
        }
    }

    function renderFixedDistributionListOnly() {
        // Summary list removed — inputs are the source of truth.
    }

    function renderExtraQuestionsAllocations(distribution) {
        if (!refs.extraQuestionsAllocationsWrap || !refs.extraQuestionsAllocationList) {
            return;
        }

        if (!refs.fixCategoryQuestions?.checked || distribution.selectedCount === 0) {
            refs.extraQuestionsAllocationsWrap.hidden = true;
            refs.extraQuestionsAllocationList.innerHTML = '';
            refs.extraQuestionsAllocationList.dataset.structureKey = '';
            return;
        }

        refs.extraQuestionsAllocationsWrap.hidden = false;
        refs.extraQuestionsAllocationsWrap.classList.toggle(
            'is-invalid',
            !distribution.isComplete
        );

        if (refs.allocatedCount) {
            refs.allocatedCount.textContent = String(distribution.totalAllocated);
        }
        if (refs.remainingCount) {
            refs.remainingCount.textContent = String(distribution.totalQuestions);
        }

        const minAllowed = distribution.minPerCategory;
        const maxAllowed = distribution.maxPerCategory;
        const structureKey = `${distribution.rows.map((row) => row.categoryId).join(',')}|${minAllowed}|${maxAllowed}`;
        const allocationsHtml = distribution.rows.map((row) => {
            const categoryName = escapeHtml(getCategoryLabelById(row.categoryId));
            const count = Math.max(
                minAllowed,
                toInt(state.extraQuestionsAllocations[String(row.categoryId)], minAllowed)
            );
            return `
                <div>
                    <label class="exam-label">${categoryName}</label>
                    <input
                        type="number"
                        class="panel-input category-question-count-input"
                        data-category-id="${escapeHtml(String(row.categoryId))}"
                        value="${count}"
                        min="${minAllowed}"
                        max="${maxAllowed}"
                        step="1"
                    >
                </div>
            `;
        }).join('');

        if (refs.extraQuestionsAllocationList.dataset.structureKey !== structureKey) {
            refs.extraQuestionsAllocationList.innerHTML = allocationsHtml;
            refs.extraQuestionsAllocationList.dataset.structureKey = structureKey;

            refs.extraQuestionsAllocationList.querySelectorAll('.category-question-count-input').forEach((input) => {
                input.addEventListener('input', (event) => {
                    const cid = String(event.target.dataset.categoryId || '');
                    const fieldMin = Math.max(0, toInt(event.target.getAttribute('min'), minAllowed));
                    const fieldMax = Math.max(fieldMin, toInt(event.target.getAttribute('max'), maxAllowed));
                    let nextValue = toInt(event.target.value, fieldMin);
                    if (Number.isNaN(nextValue) || nextValue < fieldMin) {
                        nextValue = fieldMin;
                    }
                    if (nextValue > fieldMax) {
                        nextValue = fieldMax;
                    }
                    event.target.value = String(nextValue);
                    state.extraQuestionsAllocations[cid] = nextValue;
                    syncExtraQuestionsAllocations();
                    updateAll();
                });
            });
        } else {
            refs.extraQuestionsAllocationList.querySelectorAll('.category-question-count-input').forEach((input) => {
                input.setAttribute('min', String(minAllowed));
                input.setAttribute('max', String(maxAllowed));
                if (document.activeElement === input) {
                    return;
                }
                const cid = String(input.dataset.categoryId || '');
                const val = Math.max(
                    minAllowed,
                    toInt(state.extraQuestionsAllocations[cid], minAllowed)
                );
                if (String(input.value) !== String(val)) {
                    input.value = String(val);
                }
            });
        }

        syncExtraQuestionsAllocations();
    }

    function renderFixedCategoryDistribution() {
        if (!refs.fixCategoryQuestions.checked || state.selectedCategories.size === 0) {
            refs.fixedDistributionCard.hidden = true;
            if (refs.fixedDistributionList) {
                refs.fixedDistributionList.innerHTML = '';
            }
            if (refs.extraQuestionsWrap) {
                refs.extraQuestionsWrap.hidden = true;
            }
            if (refs.extraQuestionsAllocationsWrap) {
                refs.extraQuestionsAllocationsWrap.hidden = true;
            }
            state.extraQuestionsOptionsKey = '';
            state.categoryQuestionCountsKey = '';
            return;
        }

        const distribution = computeFixedCategoryDistribution();
        refs.fixedDistributionCard.hidden = false;

        let helperText = `Set how many questions each category contributes. Totals must equal ${distribution.totalQuestions}.`;
        helperText += ` Equally distributed with a minimum of ${distribution.minPerCategory} question(s) per category`;
        if (distribution.remainder > 0) {
            helperText += ` (leftover +1 from the first category onward)`;
        }
        helperText += '.';
        refs.fixedDistributionHelper.textContent = helperText;

        if (refs.extraQuestionsWrap) {
            refs.extraQuestionsWrap.hidden = true;
        }

        renderExtraQuestionsAllocations(distribution);
    }

    function updateConfigPreview() {
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions.value, 1));
        const usesQuestionPool = isQuestionPoolEnabled();
        const maximumQuestions = toInt(refs.maximumQuestions?.value, 0);
        const selectedCount = state.selectedCategories.size;
        const paperSets = Math.max(1, toInt(refs.paperSets.value, 1));
        const hasFixedPaperSets = Boolean(refs.fixedPaperSet?.checked);
        const totalMarks = Math.max(0, toInt(refs.totalMarks.value, 0));
        const passingMarks = Math.max(0, toInt(refs.passingMarks.value, 0));
        const fixedPerCategory = refs.fixCategoryQuestions.checked;
        const fixedMarksPerCategory = Boolean(refs.fixCategoryMarks?.checked);
        const perCategoryTarget = computeCategoryTarget();
        const distribution = state.config.distributionTypes.find((item) => item.id === state.selectedDistributionType);
        const marksCalculation = computeMarksCalculationState();
        const timerEnabled = Boolean(refs.enableExamTimer && refs.enableExamTimer.checked);
        const durationMinutes = Math.max(0, toInt(refs.examDurationMinutes ? refs.examDurationMinutes.value : 0, 0));
        const formatLabel = getSelectedExamFormatLabels().join(', ') || '-';
        const scheduleType = normalizeScheduleType(state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time'));
        const attemptLimitType = normalizeAttemptLimitType(state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once'));
        const scheduleTypeLabel = getScheduleTypeById(scheduleType)?.label || '-';
        const attemptLimitLabel = getAttemptLimitTypeById(attemptLimitType)?.label || '-';
        const scheduleStartAt = cleanText(refs.scheduleStartAt ? refs.scheduleStartAt.value : '');
        const scheduleEndAt = cleanText(refs.scheduleEndAt ? refs.scheduleEndAt.value : '');
        const scheduleStartTs = parseDateTimeValue(scheduleStartAt);
        const scheduleEndTs = parseDateTimeValue(scheduleEndAt);
        const fixedAttemptCount = Math.max(0, toInt(refs.attemptLimitCount ? refs.attemptLimitCount.value : 0, 0));

        refs.paperSets.min = '1';
        refs.paperSets.max = String(totalQuestions);
        refs.paperSetsHelper.textContent = `Allowed range: 1 to ${totalQuestions} set(s).`;

        const fixedDistribution = computeFixedCategoryDistribution();
        const fixedInfo = fixedPerCategory
            ? (
                fixedDistribution.selectedCount > 0
                    ? `Fixed allocation: ${fixedDistribution.base} each${fixedDistribution.remainder ? ` + ${fixedDistribution.remainder} extra distributed among ${fixedDistribution.extraCategoryIds.map((id) => getCategoryLabelById(id)).join(', ')}` : ''}`
                    : 'Fixed allocation enabled. Select categories to calculate distribution.'
            )
            : 'Fixed allocation disabled';
        const categoryMarksDistribution = computeFixedCategoryMarksDistribution();
        const categoryMarksInfo = fixedMarksPerCategory
            ? (
                categoryMarksDistribution.selectedCount > 0
                    ? `Category marks: ${categoryMarksDistribution.totalAllocated} / ${categoryMarksDistribution.totalMarks} allocated`
                    : 'Category marks enabled. Select categories to calculate distribution.'
            )
            : 'Fixed category marks disabled';
        const marksInfo = !marksCalculation.fixEnabled
            ? 'Flexible marks per question'
            : (
                !marksCalculation.hasSelectedMark
                    ? 'Fixed marks enabled. Select exactly one mark value.'
                    : (
                        marksCalculation.isValid
                            ? `Fixed marks valid (${marksCalculation.totalQuestions} x ${marksCalculation.selectedMark} = ${marksCalculation.totalMarks}).`
                            : `Fixed marks mismatch (${marksCalculation.totalQuestions} x ${marksCalculation.selectedMark} should be ${marksCalculation.expectedTotalMarks}).`
                    )
            );

        refs.configPreviewList.innerHTML = [
            `<li>Total questions planned: <strong>${totalQuestions}</strong></li>`,
            `<li>Question pool: <strong>${usesQuestionPool ? `${maximumQuestions || '-'} maximum` : 'Disabled'}</strong></li>`,
            `<li>Distribution mode: <strong>${escapeHtml(distribution?.label || '-')}</strong></li>`,
            `<li>Exam format: <strong>${escapeHtml(formatLabel)}</strong></li>`,
            `<li>Timer: <strong>${timerEnabled ? `${durationMinutes} minute(s)` : 'Disabled'}</strong></li>`,
            `<li>Schedule type: <strong>${escapeHtml(scheduleTypeLabel)}</strong></li>`,
            `<li>Attempt limit: <strong>${escapeHtml(attemptLimitType === 'fixed_count' ? `${attemptLimitLabel} (${fixedAttemptCount})` : attemptLimitLabel)}</strong></li>`,
            `<li>Paper sets: <strong>${hasFixedPaperSets ? paperSets : 'Disabled'}</strong></li>`,
            `<li>Categories selected: <strong>${selectedCount}</strong></li>`,
            `<li>Passing threshold: <strong>${passingMarks}</strong> of ${totalMarks} marks</li>`,
            `<li>${escapeHtml(marksInfo)}</li>`,
            `<li>${escapeHtml(fixedInfo)}</li>`,
            `<li>${escapeHtml(categoryMarksInfo)}</li>`,
        ].join('');

        const validations = [];
        validations.push(
            !usesQuestionPool || maximumQuestions > totalQuestions
                ? '<li class="status-ok">Question-pool size is valid.</li>'
                : '<li class="status-error">Maximum questions must exceed total questions.</li>'
        );
        validations.push(
            selectedCount > 0
                ? '<li class="status-ok">At least one question category is selected.</li>'
                : '<li class="status-warning">Select at least one question category.</li>'
        );
        validations.push(
            !hasFixedPaperSets || (paperSets >= 1 && paperSets <= totalQuestions)
                ? '<li class="status-ok">Paper sets are within allowed limits.</li>'
                : '<li class="status-error">Paper sets must be between 1 and total questions.</li>'
        );
        validations.push(
            passingMarks <= totalMarks
                ? '<li class="status-ok">Passing marks do not exceed total marks.</li>'
                : '<li class="status-error">Passing marks cannot exceed total marks.</li>'
        );
        validations.push(
            timerEnabled && durationMinutes < 1
                ? '<li class="status-error">Set a valid exam duration when timer is enabled.</li>'
                : '<li class="status-ok">Timer configuration is valid.</li>'
        );
        validations.push(
            state.selectedExamFormat
                ? '<li class="status-ok">Exam format is selected.</li>'
                : '<li class="status-error">Select one exam format.</li>'
        );
        if (scheduleType === 'fixed_window') {
            if (!scheduleStartAt || !scheduleEndAt) {
                validations.push('<li class="status-warning">Set both schedule start and end date-time for fixed window access.</li>');
            } else if (scheduleStartTs === null || scheduleEndTs === null) {
                validations.push('<li class="status-error">Schedule date-time values are invalid.</li>');
            } else if (scheduleEndTs <= scheduleStartTs) {
                validations.push('<li class="status-error">Schedule end date-time must be after start date-time.</li>');
            } else {
                validations.push('<li class="status-ok">Schedule window is valid.</li>');
            }
        } else {
            validations.push('<li class="status-ok">Any-time schedule access is enabled.</li>');
        }
        if (attemptLimitType === 'fixed_count') {
            validations.push(
                fixedAttemptCount >= 2
                    ? '<li class="status-ok">Fixed attempt limit is configured.</li>'
                    : '<li class="status-error">Fixed attempts must be at least 2.</li>'
            );
        } else if (attemptLimitType === 'once') {
            validations.push('<li class="status-ok">Single-attempt policy is enabled.</li>');
        } else {
            validations.push('<li class="status-ok">Unlimited attempts are allowed.</li>');
        }
        if (marksCalculation.fixEnabled) {
            if (!marksCalculation.hasSelectedMark) {
                validations.push('<li class="status-error">Select exactly one mark value for fixed marks mode.</li>');
            } else if (marksCalculation.isValid) {
                validations.push('<li class="status-ok">Fixed marks calculation matches total questions and total marks.</li>');
            } else {
                validations.push(`<li class="status-error">Fixed marks mismatch: ${marksCalculation.totalQuestions} x ${marksCalculation.selectedMark} = ${marksCalculation.expectedTotalMarks}, but Total Marks is ${marksCalculation.totalMarks}.</li>`);
            }
        }
        if (fixedPerCategory) {
            if (fixedDistribution.isComplete) {
                validations.push('<li class="status-ok">Category question counts match the exam total.</li>');
            } else if (fixedDistribution.totalAllocated < fixedDistribution.totalQuestions) {
                validations.push(`<li class="status-warning">Allocate exactly ${fixedDistribution.totalQuestions} questions across categories. Currently allocated: ${fixedDistribution.totalAllocated}.</li>`);
            } else {
                validations.push(`<li class="status-error">Category question counts (${fixedDistribution.totalAllocated}) exceed the exam total (${fixedDistribution.totalQuestions}).</li>`);
            }
        }
        if (fixedMarksPerCategory) {
            if (categoryMarksDistribution.isComplete) {
                validations.push('<li class="status-ok">Category marks match the exam total.</li>');
            } else if (categoryMarksDistribution.totalAllocated < categoryMarksDistribution.totalMarks) {
                validations.push(`<li class="status-warning">Allocate exactly ${categoryMarksDistribution.totalMarks} marks across categories. Currently allocated: ${categoryMarksDistribution.totalAllocated}.</li>`);
            } else {
                validations.push(`<li class="status-error">Category marks (${categoryMarksDistribution.totalAllocated}) exceed the exam total (${categoryMarksDistribution.totalMarks}).</li>`);
            }
        }

        refs.configValidationList.innerHTML = validations.join('');
    }

    function shuffleArray(items) {
        const copy = [...items];
        for (let index = copy.length - 1; index > 0; index -= 1) {
            const swapIndex = Math.floor(Math.random() * (index + 1));
            [copy[index], copy[swapIndex]] = [copy[swapIndex], copy[index]];
        }
        return copy;
    }

    function showQuestionSelectionWarning(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Insufficient Questions',
                text: message,
            });
            return;
        }

        if (window.EmsToast && typeof window.EmsToast.warning === 'function') {
            window.EmsToast.warning(message);
            return;
        }

        if (refs.questionBankFeedback) {
            refs.questionBankFeedback.textContent = message;
            refs.questionBankFeedback.classList.add('is-invalid');
        }
    }

    async function randomSelectCategory(categoryId) {
        if (!isManualQuestionSelectionEnabled()) {
            showQuestionSelectionWarning('Enable Fixed Questions or Question Pool before selecting questions.');
            return;
        }

        const limits = getQuestionSelectionLimits();
        const totalQuestionsAllowed = limits.max;
        const fixedPerCategory = refs.fixCategoryQuestions.checked && !isQuestionPoolEnabled();
        const endpoints = window.examCreateConfig?.endpoints || {};
        const endpoint = endpoints.questionBankRandom || endpoints.questionBank;
        if (!endpoint) {
            showQuestionSelectionWarning('Random selection endpoint is unavailable.');
            return;
        }

        let categoryAllowedLimit = totalQuestionsAllowed;
        if (fixedPerCategory) {
            const fixedDistribution = computeFixedCategoryDistribution();
            const row = fixedDistribution.rows.find((r) => String(r.categoryId) === String(categoryId));
            categoryAllowedLimit = row ? row.count : 0;
        } else {
            const selectedOutsideCategory = [...state.selectedQuestions].filter((questionId) => {
                const question = getQuestionById(questionId);
                return question && !questionMatchesSelectedCategory(question, categoryId);
            }).length;
            categoryAllowedLimit = Math.max(0, totalQuestionsAllowed - selectedOutsideCategory);
        }

        const marks = [...state.selectedMarks].join(',');
        const formats = [...state.selectedExamFormat].join(',');
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('categories', String(categoryId));
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);
        url.searchParams.set('count', String(categoryAllowedLimit));

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            if (rows.length < categoryAllowedLimit) {
                showQuestionSelectionWarning(
                    `Only ${rows.length} matching question(s) are available in ${getCategoryLabelById(categoryId)}; ${categoryAllowedLimit} are required.`
                );
                return;
            }

            [...state.selectedQuestions].forEach((questionId) => {
                const question = getQuestionById(questionId);
                if (question && questionMatchesSelectedCategory(question, categoryId)) {
                    state.selectedQuestions.delete(questionId);
                }
            });

            rows.forEach((question) => {
                rememberSelectedQuestion(question);
                state.selectedQuestions.add(question.id);
            });
            mergeQuestionBankRows(rows, { append: true });
            refs.questionBankFeedback?.classList.remove('is-invalid');
            updateQuestionBankCards();
            updateWorkflowAndSnapshot();
        } catch (err) {
            console.error('Category random select failed', err);
            showQuestionSelectionWarning('Could not complete random selection. Please try again.');
        }
    }

    async function randomSelectGlobal() {
        if (!isManualQuestionSelectionEnabled()) {
            showQuestionSelectionWarning('Enable Fixed Questions or Question Pool before selecting questions.');
            return;
        }

        const limits = getQuestionSelectionLimits();
        const totalQuestionsAllowed = limits.max;
        const fixedPerCategory = refs.fixCategoryQuestions.checked && !isQuestionPoolEnabled();
        const previousSelection = new Set(state.selectedQuestions);
        const endpoints = window.examCreateConfig?.endpoints || {};
        const endpoint = endpoints.questionBankRandom || endpoints.questionBank;
        if (!endpoint) {
            showQuestionSelectionWarning('Random selection endpoint is unavailable.');
            return;
        }

        const categoryIds = [...state.selectedCategories].join(',');
        const marks = [...state.selectedMarks].join(',');
        const formats = [...state.selectedExamFormat].join(',');
        const url = new URL(endpoint, window.location.origin);
        if (categoryIds) url.searchParams.set('categories', categoryIds);
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);

        let categoryQuotas = {};
        if (fixedPerCategory) {
            const fixedDistribution = computeFixedCategoryDistribution();
            categoryQuotas = Object.fromEntries(
                fixedDistribution.rows.map((row) => [String(row.categoryId), row.count])
            );
            url.searchParams.set('count', String(fixedDistribution.rows.reduce((sum, row) => sum + row.count, 0)));
            url.searchParams.set('category_quotas', JSON.stringify(categoryQuotas));
        } else {
            url.searchParams.set('count', String(totalQuestionsAllowed));
        }

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            const required = fixedPerCategory
                ? Object.values(categoryQuotas).reduce((sum, n) => sum + Number(n || 0), 0)
                : totalQuestionsAllowed;
            if (rows.length < required) {
                state.selectedQuestions = previousSelection;
                showQuestionSelectionWarning(
                    `Only ${rows.length} matching question(s) are available; ${required} are required.`
                );
                return;
            }

            state.selectedQuestions.clear();
            rows.forEach((question) => {
                rememberSelectedQuestion(question);
                state.selectedQuestions.add(question.id);
            });
            mergeQuestionBankRows(rows, { append: true });
            refs.questionBankFeedback?.classList.remove('is-invalid');
            updateQuestionBankCards();
            updateWorkflowAndSnapshot();
        } catch (err) {
            state.selectedQuestions = previousSelection;
            console.error('Random select failed', err);
            showQuestionSelectionWarning('Could not complete random selection. Please try again.');
        }
    }

    function updateQuestionBankCards() {
        const selectedCategoryIds = [...state.selectedCategories].map(String);
        const hasMarksFilter = state.selectedMarks.size > 0;
        const limits = getQuestionSelectionLimits();
        const selectionEnabled = !limits.viewOnly;
        const totalQuestionsAllowed = limits.max;
        const fixedPerCategory = refs.fixCategoryQuestions.checked && !isQuestionPoolEnabled();

        pruneSelectedQuestionsToVisibleBank();
        updateQuestionSelectionControlsVisibility();
        const shortages = computeQuestionShortages();
        renderQuestionShortages(shortages);

        let fixedDistribution = { rows: [] };
        if (fixedPerCategory) {
            fixedDistribution = computeFixedCategoryDistribution();
        }

        const bankWithSelected = [...state.questionBank];
        Object.values(state.selectedQuestionCache || {}).forEach((question) => {
            if (!bankWithSelected.some((item) => String(item.id) === String(question.id))) {
                bankWithSelected.push(question);
            }
        });

        const byCategory = new Map();
        for (const q of bankWithSelected) {
            const targetCategoryId = resolveQuestionDisplayCategory(q, selectedCategoryIds);
            if (!targetCategoryId) {
                continue;
            }
            if (!byCategory.has(targetCategoryId)) {
                byCategory.set(targetCategoryId, []);
            }
            byCategory.get(targetCategoryId).push(q);
        }

        const totalSelectedGlobal = state.selectedQuestions.size;
        if (refs.globalSelectedCount) refs.globalSelectedCount.textContent = totalSelectedGlobal;
        if (refs.globalAllowedCount) {
            refs.globalAllowedCount.textContent = selectionEnabled ? String(totalQuestionsAllowed) : '0';
        }

        const globalLimitReached = selectionEnabled && totalSelectedGlobal >= totalQuestionsAllowed;

        if (!selectedCategoryIds.length) {
            refs.questionCategoryCards.innerHTML = '';
            refs.questionBankFeedback.textContent = 'Select categories above to load the question bank.';
            updateQuestionBankLoadMeta();
            return;
        }

        refs.questionCategoryCards.innerHTML = selectedCategoryIds
            .map((categoryId) => {
                const categoryKey = String(categoryId);
                const categoryName = getCategoryLabelById(categoryKey);
                const questions = byCategory.get(categoryKey) || [];
                const expanded = state.expandedCards.has(categoryKey);
                const serverCount = Number(state.categoryCounts[categoryKey] ?? 0);
                const loadState = state.categoryLoadState[categoryKey] || { status: 'idle', has_more: false };
                const loadedCount = questions.length;

                let categoryAllowedLimit = totalQuestionsAllowed;
                if (fixedPerCategory) {
                    const row = fixedDistribution.rows.find((r) => String(r.categoryId) === categoryKey);
                    categoryAllowedLimit = row ? row.count : 0;
                }

                const selectedInCategory = [...state.selectedQuestions].filter((questionId) => {
                    const question = getQuestionById(questionId);
                    return question && questionMatchesSelectedCategory(question, categoryKey);
                }).length;
                const categoryLimitReached = selectionEnabled && fixedPerCategory
                    ? selectedInCategory >= categoryAllowedLimit
                    : false;

                let categorySelectionText = '';
                if (selectionEnabled) {
                    categorySelectionText = fixedPerCategory
                        ? `<span class="question-accordion__selection-count">${selectedInCategory}/${categoryAllowedLimit} selected</span>`
                        : `<span class="question-accordion__selection-count">${selectedInCategory} picked</span>`;
                }

                let questionsList = '';
                if (loadState.status === 'loading') {
                    questionsList = '<li class="question-accordion__empty">Loading questions...</li>';
                } else if (loadState.status === 'idle') {
                    questionsList = `
                        <li class="question-accordion__empty">
                            ${serverCount} matching question(s) available.
                            <div style="margin-top:0.75rem;">
                                <button type="button" class="panel-button-secondary panel-button--small" data-action="load-category-questions" data-category-id="${escapeHtml(categoryKey)}">
                                    Load questions
                                </button>
                            </div>
                        </li>
                    `;
                } else if (loadState.status === 'error') {
                    questionsList = `
                        <li class="question-accordion__empty">
                            Could not load questions.
                            <div style="margin-top:0.75rem;">
                                <button type="button" class="panel-button-secondary panel-button--small" data-action="load-category-questions" data-category-id="${escapeHtml(categoryKey)}">
                                    Retry
                                </button>
                            </div>
                        </li>
                    `;
                } else if (loadedCount === 0) {
                    questionsList = `<li class="question-accordion__empty">No questions found for this category${hasMarksFilter ? ' matching the selected marks filter' : ''}.</li>`;
                } else {
                    questionsList = questions
                        .map((question) => {
                            const isSelected = state.selectedQuestions.has(question.id)
                                || state.selectedQuestions.has(String(question.id))
                                || state.selectedQuestions.has(Number(question.id));
                            const disabled = selectionEnabled && !isSelected && (globalLimitReached || categoryLimitReached);
                            const diffBadge = question.difficulty
                                ? `<span class="question-accordion__badge question-accordion__badge--${escapeHtml(question.difficulty)}">${escapeHtml(question.difficulty)}</span>`
                                : '';
                            const marksBadge = question.marks
                                ? `<span class="question-accordion__marks">${Number(question.marks)} mark${Number(question.marks) !== 1 ? 's' : ''}</span>`
                                : '';
                            const typeBadge = question.type
                                ? `<span class="question-accordion__type">${escapeHtml(String(question.type).replace(/_/g, ' '))}</span>`
                                : '';

                            if (!selectionEnabled) {
                                return `
                                    <li class="question-accordion__item question-accordion__item--readonly">
                                        <div class="question-accordion__body">
                                            <span class="question-accordion__text">${escapeHtml(question.text)}</span>
                                            <div class="question-accordion__meta">${diffBadge}${marksBadge}${typeBadge}</div>
                                        </div>
                                    </li>
                                `;
                            }

                            return `
                                <li class="question-accordion__item ${isSelected ? 'is-selected' : ''}">
                                    <label class="question-checkbox-label ${disabled ? 'is-disabled' : ''}">
                                        <input type="checkbox" class="question-checkbox" data-question-id="${question.id}" data-category-id="${escapeHtml(categoryKey)}" ${isSelected ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
                                        <div class="question-accordion__body">
                                            <span class="question-accordion__text">${escapeHtml(question.text)}</span>
                                            <div class="question-accordion__meta">${diffBadge}${marksBadge}${typeBadge}</div>
                                        </div>
                                    </label>
                                </li>
                            `;
                        })
                        .join('');

                    if (loadState.has_more) {
                        questionsList += `
                            <li class="question-accordion__empty">
                                <button type="button" class="panel-button-secondary panel-button--small" data-action="load-more-category-questions" data-category-id="${escapeHtml(categoryKey)}">
                                    Load more (${loadedCount} of ${serverCount})
                                </button>
                            </li>
                        `;
                    }
                }

                const randomSelectHtml = selectionEnabled && fixedPerCategory
                    ? `<button type="button" class="panel-button-secondary panel-button--small" data-action="random-select-category" data-category-id="${escapeHtml(categoryKey)}">Random Select</button>`
                    : '';

                const loadBtnHtml = loadState.status === 'idle' || loadState.status === 'error'
                    ? `<button type="button" class="panel-button-secondary panel-button--small" data-action="load-category-questions" data-category-id="${escapeHtml(categoryKey)}">Load questions</button>`
                    : '';

                return `
                    <article class="question-accordion" data-category-accordion="${escapeHtml(categoryKey)}" data-expanded="${expanded ? 'true' : 'false'}">
                        <button
                            type="button"
                            class="question-accordion__header"
                            data-action="toggle-expand"
                            data-category-id="${escapeHtml(categoryKey)}"
                            aria-expanded="${expanded ? 'true' : 'false'}"
                        >
                            <span class="question-accordion__chevron" aria-hidden="true">${expanded ? '▾' : '▸'}</span>
                            <span class="question-accordion__title">
                                ${escapeHtml(categoryName)}
                                <span class="question-accordion__count">(${serverCount} question${serverCount !== 1 ? 's' : ''})</span>
                            </span>
                        </button>

                        <div class="question-accordion__panel" data-role="accordion-panel"${expanded ? '' : ' hidden'}>
                            <div class="question-accordion__panel-inner">
                                <div class="question-accordion__toolbar">
                                    <div class="toolbar-left">${randomSelectHtml}${loadBtnHtml}</div>
                                    <div class="toolbar-right">
                                        ${categorySelectionText}
                                        <button type="button" class="panel-button-secondary panel-button--small" data-action="add-question" data-category-id="${escapeHtml(categoryKey)}">+ Add Question</button>
                                    </div>
                                </div>
                                <ul class="question-accordion__list">${questionsList}</ul>
                            </div>
                        </div>
                    </article>
                `;
            })
            .join('');

        const modeHint = limits.viewOnly
            ? ' View-only mode: enable Fixed Questions or Question Pool to select specific questions.'
            : (isQuestionPoolEnabled()
                ? ` Select between ${limits.min} and ${limits.max} questions for the pool.`
                : ` Select exactly ${limits.exact} question(s).`);

        const totalAvailable = Object.values(state.categoryCounts || {}).reduce((sum, n) => sum + Number(n || 0), 0);
        const loadedRows = state.questionBank.length;
        refs.questionBankFeedback.textContent = (
            `${totalAvailable} matching question(s) across ${selectedCategoryIds.length} categor${selectedCategoryIds.length === 1 ? 'y' : 'ies'}. Expand a category or click Load questions to fetch rows (${loadedRows} loaded).`
        ) + modeHint;
        updateQuestionBankLoadMeta();
    }

    let questionBankSyncTimer = null;
    function scheduleQuestionBankSync(delayMs = 350) {
        clearTimeout(questionBankSyncTimer);
        questionBankSyncTimer = setTimeout(() => {
            syncQuestionBankFromServer();
        }, delayMs);
    }

    function buildSharedQuestionBankParams() {
        const marks = [...state.selectedMarks].join(',');
        const formats = [...state.selectedExamFormat].join(',');
        const keyword = cleanText(refs.questionSearch?.value || '');
        return { marks, formats, keyword };
    }

    function mergeQuestionBankRows(rows, { append = false, replaceCategoryId = null } = {}) {
        const incoming = Array.isArray(rows) ? rows : [];
        if (replaceCategoryId) {
            const keep = state.questionBank.filter((q) => !questionMatchesSelectedCategory(q, replaceCategoryId));
            state.questionBank = keep.concat(incoming);
            return;
        }
        if (!append) {
            state.questionBank = incoming.slice();
            return;
        }
        const seen = new Set(state.questionBank.map((q) => String(q.id)));
        incoming.forEach((question) => {
            const key = String(question.id);
            if (seen.has(key)) {
                return;
            }
            seen.add(key);
            state.questionBank.push(question);
        });
    }

    function updateQuestionBankLoadMeta() {
        const loaded = state.questionBank.length;
        const total = Object.values(state.categoryCounts || {}).reduce((sum, n) => sum + Number(n || 0), 0);
        if (refs.questionBankLoadMeta) {
            refs.questionBankLoadMeta.textContent = total > 0
                ? `Counts ready: ${total} matching. Loaded rows: ${loaded}.`
                : (loaded > 0 ? `Loaded ${loaded} question(s).` : '');
        }
        if (refs.questionBankLoadMoreWrap) {
            refs.questionBankLoadMoreWrap.hidden = true;
        }
    }

    function resetCategoryQuestionLoads() {
        state.questionBank = [];
        state.categoryLoadState = {};
        [...state.selectedCategories].forEach((categoryId) => {
            state.categoryLoadState[String(categoryId)] = {
                status: 'idle',
                next_cursor: null,
                has_more: false,
                requestSeq: 0,
            };
        });
    }

    async function fetchSelectedQuestionMetadata(ids) {
        const endpoints = window.examCreateConfig?.endpoints || {};
        if (!endpoints.questionBank || !ids?.length) {
            return;
        }
        const missing = ids.filter((id) => !getQuestionById(id));
        if (!missing.length) {
            return;
        }
        const url = new URL(endpoints.questionBank, window.location.origin);
        url.searchParams.set('ids', missing.join(','));
        url.searchParams.set('per_page', String(Math.min(100, missing.length)));
        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            rows.forEach((question) => rememberSelectedQuestion(question));
            mergeQuestionBankRows(rows, { append: true });
        } catch (err) {
            console.error('Failed to hydrate selected questions', err);
        }
    }

    async function syncQuestionBankCounts() {
        const endpoints = window.examCreateConfig?.endpoints || {};
        const countsUrl = endpoints.questionBankCounts || endpoints.questionBank;
        if (!countsUrl) return;

        const categoryIds = [...state.selectedCategories].map(String);
        if (!categoryIds.length) {
            state.categoryCounts = {};
            state.questionBankMeta = { total: 0, next_cursor: null, has_more: false, per_page: 50 };
            resetCategoryQuestionLoads();
            updateQuestionBankLoadMeta();
            updateQuestionBankCards();
            return;
        }

        if (state.countsAbortController) {
            state.countsAbortController.abort();
        }
        state.countsAbortController = new AbortController();
        const { marks, formats, keyword } = buildSharedQuestionBankParams();
        const url = new URL(countsUrl, window.location.origin);
        url.searchParams.set('categories', categoryIds.join(','));
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);
        if (keyword) url.searchParams.set('q', keyword);

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: state.countsAbortController.signal,
            });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const payload = await response.json();
            const counts = payload?.data && typeof payload.data === 'object' ? payload.data : {};
            state.categoryCounts = {};
            categoryIds.forEach((id) => {
                state.categoryCounts[id] = Number(counts[id] ?? counts[String(id)] ?? 0);
            });
            state.questionBankMeta = {
                total: Number(payload?.meta?.total ?? Object.values(state.categoryCounts).reduce((s, n) => s + Number(n || 0), 0)),
                next_cursor: null,
                has_more: false,
                per_page: 50,
            };
        } catch (err) {
            if (err?.name === 'AbortError') {
                return;
            }
            console.error('Failed to load question bank counts', err);
            window.EmsToast?.error('Failed to load question counts.');
        } finally {
            state.countsAbortController = null;
            updateQuestionBankLoadMeta();
        }
    }

    async function loadCategoryQuestions(categoryId, { append = false } = {}) {
        const endpoints = window.examCreateConfig?.endpoints || {};
        if (!endpoints.questionBank) return;

        const categoryKey = String(categoryId || '');
        if (!categoryKey) return;

        const current = state.categoryLoadState[categoryKey] || {
            status: 'idle',
            next_cursor: null,
            has_more: false,
            requestSeq: 0,
        };
        if (append && !current.has_more) {
            return;
        }

        const requestSeq = (current.requestSeq || 0) + 1;
        state.categoryLoadState[categoryKey] = {
            ...current,
            status: 'loading',
            requestSeq,
        };
        updateQuestionBankCards();

        const { marks, formats, keyword } = buildSharedQuestionBankParams();
        const url = new URL(endpoints.questionBank, window.location.origin);
        url.searchParams.set('categories', categoryKey);
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);
        if (keyword) url.searchParams.set('q', keyword);
        url.searchParams.set('per_page', '50');
        if (append && current.next_cursor) {
            url.searchParams.set('cursor', String(current.next_cursor));
        }

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            if ((state.categoryLoadState[categoryKey]?.requestSeq || 0) !== requestSeq) {
                return;
            }
            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            const meta = payload?.meta && typeof payload.meta === 'object' ? payload.meta : {};

            if (append) {
                mergeQuestionBankRows(rows, { append: true });
            } else {
                mergeQuestionBankRows(rows, { replaceCategoryId: categoryKey });
            }

            rows.forEach((question) => {
                if (
                    state.selectedQuestions.has(question.id)
                    || state.selectedQuestions.has(String(question.id))
                    || state.selectedQuestions.has(Number(question.id))
                ) {
                    rememberSelectedQuestion(question);
                }
            });

            state.categoryLoadState[categoryKey] = {
                status: 'loaded',
                next_cursor: meta.next_cursor ?? null,
                has_more: Boolean(meta.has_more),
                requestSeq,
            };
            if (typeof meta.total === 'number') {
                state.categoryCounts[categoryKey] = Number(meta.total);
            }
            updateQuestionBankCards();
            updateWorkflowAndSnapshot();
        } catch (err) {
            console.error('Failed to load category questions', err);
            state.categoryLoadState[categoryKey] = {
                status: 'error',
                next_cursor: null,
                has_more: false,
                requestSeq,
            };
            updateQuestionBankCards();
            window.EmsToast?.error('Failed to load questions for ' + getCategoryLabelById(categoryKey));
        }
    }

    async function syncQuestionBankFromServer() {
        const refreshBtn = document.getElementById('refresh-question-bank');
        const bankSection = document.getElementById('question-bank-section');
        const managedByButton = Boolean(refreshBtn?.classList.contains('is-loading'));

        if (refs.questionBankFeedback) {
            refs.questionBankFeedback.textContent = 'Loading question counts...';
        }
        if (refreshBtn && !managedByButton) {
            refreshBtn.disabled = true;
            refreshBtn.classList.add('is-loading');
            refreshBtn.setAttribute('aria-busy', 'true');
        }
        bankSection?.classList.add('is-loading');

        try {
            resetCategoryQuestionLoads();
            await syncQuestionBankCounts();
            await fetchSelectedQuestionMetadata([...state.selectedQuestions]);
            if (state.isEditMode && !state.hasHydratedSelectedQuestions) {
                await fetchSelectedQuestionMetadata(state.hydratedQuestionIds || []);
                hydrateSelectedQuestions();
            }
            updateQuestionBankCards();
            updateWorkflowAndSnapshot();
        } finally {
            if (refreshBtn && !managedByButton) {
                refreshBtn.disabled = false;
                refreshBtn.classList.remove('is-loading');
                refreshBtn.removeAttribute('aria-busy');
            }
            bankSection?.classList.remove('is-loading');
            updateQuestionBankLoadMeta();
        }
    }
    window.syncQuestionBankFromServer = syncQuestionBankFromServer;
    window.loadCategoryQuestions = loadCategoryQuestions;


    function renderDiscountSummary() {
        if (!state.selectedDiscounts.size) {
            refs.discountSummary.innerHTML = '<li>No discount rules selected.</li>';
            return;
        }

        const selected = state.config.discountRules.filter((rule) => state.selectedDiscounts.has(rule.id));
        refs.discountSummary.innerHTML = selected
            .map((rule) => {
                const pct = state.discountPercentages[rule.id] || rule.default_percentage || 0;
                return `<li><strong>${pct}%</strong> ${escapeHtml(rule.label)} — ${escapeHtml(rule.summary)}</li>`;
            })
            .join('');
    }

    function updateWorkflowAndSnapshot() {
        const basicComplete = cleanText(refs.title.value).length >= 3
            && refs.difficulty.value && refs.status.value && refs.mode.value && refs.visibility.value;
        const candidateVisible = !refs.candidateSection.hidden;
        const candidateComplete = !candidateVisible || state.importedCandidates.length > 0 || state.manualEmails.length > 0;
        const totalQuestions = toInt(refs.totalQuestions.value, 0);
        const questionPoolComplete = !isQuestionPoolEnabled()
            || toInt(refs.maximumQuestions.value, 0) > totalQuestions;
        const categoryMarksDistribution = computeFixedCategoryMarksDistribution();
        const categoryMarksComplete = !refs.fixCategoryMarks?.checked
            || categoryMarksDistribution.isComplete;
        const configComplete = state.selectedCategories.size > 0 && totalQuestions > 0 && questionPoolComplete &&
            (!refs.fixCategoryQuestions.checked || computeFixedCategoryDistribution().isComplete) &&
            categoryMarksComplete;
        const marksCalculation = computeMarksCalculationState();
        const marksComplete = state.selectedMarks.size > 0 && (!marksCalculation.fixEnabled || marksCalculation.isValid);
        const timerEnabled = Boolean(refs.enableExamTimer && refs.enableExamTimer.checked);
        const durationMinutes = Math.max(0, toInt(refs.examDurationMinutes ? refs.examDurationMinutes.value : 0, 0));
        const timerComplete = !timerEnabled || durationMinutes > 0;
        const examFormatComplete = state.selectedExamFormat instanceof Set
            ? state.selectedExamFormat.size > 0
            : Boolean(state.selectedExamFormat);
        const scheduleType = normalizeScheduleType(state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time'));
        const attemptLimitType = normalizeAttemptLimitType(state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once'));
        const scheduleStartAt = cleanText(refs.scheduleStartAt ? refs.scheduleStartAt.value : '');
        const scheduleEndAt = cleanText(refs.scheduleEndAt ? refs.scheduleEndAt.value : '');
        const scheduleStartTs = parseDateTimeValue(scheduleStartAt);
        const scheduleEndTs = parseDateTimeValue(scheduleEndAt);
        const fixedAttemptCount = Math.max(0, toInt(refs.attemptLimitCount ? refs.attemptLimitCount.value : 0, 0));
        const scheduleComplete = scheduleType !== 'fixed_window'
            || (Boolean(scheduleStartAt) && Boolean(scheduleEndAt) && scheduleStartTs !== null && scheduleEndTs !== null && scheduleEndTs > scheduleStartTs);
        const attemptsComplete = attemptLimitType !== 'fixed_count' || fixedAttemptCount >= 2;
        const scheduleAndAttemptsComplete = scheduleComplete && attemptsComplete;

        const freeCount = state.freeImportedCandidates.length + state.freeManualEmails.length;
        const pricingComplete = refs.pricingSection.hidden || (Boolean(state.selectedPricing) && (state.selectedPricing !== 'free_for_imported' || freeCount > 0));

        const fixedDistribution = computeFixedCategoryDistribution();
        const shortages = computeQuestionShortages();
        const selectionLimits = getQuestionSelectionLimits();
        const selectionCountValid = selectionLimits.viewOnly
            || (
                state.selectedQuestions.size >= selectionLimits.min
                && state.selectedQuestions.size <= selectionLimits.max
            );
        const questionBankComplete = state.selectedCategories.size > 0
            && shortages.length === 0
            && selectionCountValid;
        const instructionRulesComplete = state.selectedInstructionRules.size > 0;
        const instructionsComplete = getInstructionTextLength() > 20;

        const checklist = [
            { label: 'Basic Information', complete: basicComplete, show: true },
            { label: 'Timer Setup', complete: timerComplete, show: true },
            { label: 'Exam Format', complete: examFormatComplete, show: true },
            { label: 'Schedule & Attempts', complete: scheduleAndAttemptsComplete, show: true },
            { label: 'Candidate Access', complete: candidateComplete, show: candidateVisible },
            { label: 'Exam Configuration', complete: configComplete, show: true },
            { label: 'Question Rules', complete: marksComplete, show: true },
            { label: 'Pricing and Discount', complete: pricingComplete, show: !refs.pricingSection.hidden },
            { label: 'Question Bank', complete: questionBankComplete, show: true },
            { label: 'Exam Rules', complete: instructionRulesComplete, show: true },
            { label: 'Instructions', complete: instructionsComplete, show: true }
        ];

        let checklistIndex = 0;
        refs.workflowStatusList.innerHTML = checklist
            .filter(item => item.show)
            .map((item) => {
                checklistIndex++;
                return `<li class="${item.complete ? 'status-ok' : 'status-warning'}">${checklistIndex}. ${escapeHtml(item.label)} - ${item.complete ? 'Complete' : 'Pending'}</li>`;
            })
            .join('');

        refs.snapshotVisibility.textContent = refs.visibility.options[refs.visibility.selectedIndex]?.textContent || '-';
        refs.snapshotMode.textContent = refs.mode.options[refs.mode.selectedIndex]?.textContent || '-';
        refs.snapshotCategories.textContent = String(state.selectedCategories.size);
        refs.snapshotMarks.textContent = String(state.selectedMarks.size);
        if (refs.snapshotTimer) {
            refs.snapshotTimer.textContent = timerEnabled ? `${durationMinutes} min` : 'Disabled';
        }
        if (refs.snapshotExamFormat) {
            refs.snapshotExamFormat.textContent = getSelectedExamFormatLabels().join(', ') || '-';
        }
        if (refs.snapshotSchedule) {
            const scheduleStartLabel = formatScheduleDateTimeForDisplay(scheduleStartAt);
            const scheduleEndLabel = formatScheduleDateTimeForDisplay(scheduleEndAt);
            refs.snapshotSchedule.textContent = scheduleType === 'fixed_window'
                ? (scheduleStartAt && scheduleEndAt ? `${scheduleStartLabel} to ${scheduleEndLabel}` : 'Fixed window (incomplete)')
                : 'Any time';
        }
        if (refs.snapshotAttempts) {
            refs.snapshotAttempts.textContent = attemptLimitType === 'fixed_count'
                ? `${fixedAttemptCount || 0} times`
                : (attemptLimitType === 'once' ? 'Once' : 'Unlimited');
        }

        const privateCount = state.importedCandidates.length + state.manualEmails.length;
        if (state.selectedPricing === 'free_for_imported') {
            refs.snapshotCandidates.textContent = `${privateCount} (Private) / ${freeCount} (Free)`;
        } else {
            refs.snapshotCandidates.textContent = String(privateCount);
        }

        refs.snapshotDiscounts.textContent = String(state.selectedDiscounts.size);
        if (refs.snapshotInstructionRules) {
            refs.snapshotInstructionRules.textContent = String(state.selectedInstructionRules.size);
        }
    }

    async function initRichTextEditors() {
        if (state.richEditorsInitializing || state.richEditorsReady) {
            return state.richEditors;
        }
        state.richEditorsInitializing = true;

        const revealFallback = (input) => {
            if (!input) return;
            input.classList.remove('hidden');
            input.classList.add('panel-input', 'rich-editor-fallback');
            input.removeAttribute('hidden');
            input.style.display = 'block';
            if (!input.style.minHeight) {
                input.style.minHeight = '180px';
            }
            const host = document.querySelector(`[data-editor-input="${input.id}"]`);
            if (host) {
                host.hidden = true;
                host.classList.add('is-fallback');
                host.classList.remove('is-ready');
            }
        };

        if (!window.EmsRichTextEditor || typeof window.EmsRichTextEditor.initAll !== 'function') {
            revealFallback(refs.description);
            revealFallback(refs.instructions);
            if (refs.instructions) {
                refs.instructions.addEventListener('input', updateInstructionCounter);
            }
            updateInstructionCounter();
            state.richEditorsReady = true;
            state.richEditorsInitializing = false;
            return state.richEditors;
        }

        let registry = new Map();
        try {
            registry = await Promise.race([
                window.EmsRichTextEditor.initAll(document),
                new Promise((_, reject) => {
                    window.setTimeout(() => reject(new Error('Rich text editor init timed out')), 12000);
                }),
            ]);
        } catch (error) {
            console.warn(error);
            revealFallback(refs.description);
            revealFallback(refs.instructions);
            if (refs.instructions) {
                refs.instructions.addEventListener('input', updateInstructionCounter);
            }
        }
        state.richEditors = registry instanceof Map ? registry : new Map();

        // Guarantee visible editors even if adapters were skipped
        if (!state.richEditors.has('exam_description')) {
            revealFallback(refs.description);
        }
        if (!state.richEditors.has('candidate_instructions')) {
            revealFallback(refs.instructions);
            if (refs.instructions) {
                refs.instructions.addEventListener('input', updateInstructionCounter);
            }
        }

        const descriptionEditor = getRichEditor('exam_description');
        const instructionEditor = getRichEditor('candidate_instructions');

        if (descriptionEditor) {
            descriptionEditor.onChange(() => {
                updateWorkflowAndSnapshot();
            });
        }

        if (instructionEditor) {
            instructionEditor.onChange(() => {
                updateInstructionCounter();
                updateWorkflowAndSnapshot();
            });
        }

        updateInstructionCounter();
        state.richEditorsReady = true;
        state.richEditorsInitializing = false;
        return state.richEditors;
    }

    function applyInstructionTemplate() {
        const template = state.config.instructionTemplates.find((item) => item.id === refs.instructionTemplate.value);
        if (!template) return;

        const instructionEditor = getRichEditor('candidate_instructions');
        if (instructionEditor) {
            instructionEditor.setData(template.content || '');
        } else {
            refs.instructions.value = template.content || '';
        }

        syncRichTextFields();
        updateInstructionCounter();
        updateWorkflowAndSnapshot();
    }

    function getInstructionTextLength() {
        const html = getRichTextValue('candidate_instructions');
        return cleanText(String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ')).length;
    }

    function getRichEditor(inputId) {
        if (!(state.richEditors instanceof Map)) {
            return null;
        }
        return state.richEditors.get(inputId) || null;
    }

    function getRichTextValue(inputId) {
        const editor = getRichEditor(inputId);
        if (editor) {
            return editor.getData();
        }

        const field = document.getElementById(inputId);
        return field ? field.value : '';
    }

    function syncRichTextFields() {
        if (!(state.richEditors instanceof Map) || state.richEditors.size === 0) {
            return;
        }
        state.richEditors.forEach((editor) => {
            if (editor && typeof editor.sync === 'function') {
                editor.sync();
            }
        });
    }

    function updateInstructionCounter() {
        syncRichTextFields();
        refs.instructionsCount.textContent = String(getInstructionTextLength());
    }

    function openAddQuestionModal(categoryId, options = {}) {
        const endpoints = window.examCreateConfig?.endpoints || {};
        const createUrl = endpoints.questionCreate || '/admin/questions/create';
        const url = new URL(createUrl, window.location.origin);
        url.searchParams.set('source', 'exam-create');

        const resolvedCategoryId = categoryId
            || ([...state.selectedCategories].length === 1 ? [...state.selectedCategories][0] : '');
        if (resolvedCategoryId) {
            url.searchParams.set('category_id', resolvedCategoryId);
        }

        const marksValues = [];
        if (options.marks) {
            marksValues.push(Number(options.marks));
        } else {
            marksValues.push(...[...state.selectedMarks].map(Number));
        }
        [...new Set(marksValues.filter((mark) => mark > 0))].forEach((mark) => {
            url.searchParams.append('marks[]', String(mark));
        });

        [...state.selectedExamFormat].forEach((format) => {
            url.searchParams.append('formats[]', format);
        });

        if (refs.difficulty?.value) {
            url.searchParams.set('difficulty', refs.difficulty.value);
        }

        window.open(url.toString(), '_blank');
    }

    function closeAddQuestionModal() {
        if (refs.modal) {
            refs.modal.hidden = true;
        }
        if (refs.addQuestionForm) {
            refs.addQuestionForm.reset();
        }

        if (window.EmsSelect && typeof window.EmsSelect.setValue === 'function') {
            ['new_question_category', 'new_question_marks', 'new_question_difficulty'].forEach((fieldId) => {
                const field = document.getElementById(fieldId);
                if (field) {
                    window.EmsSelect.setValue(fieldId, field.value);
                }
            });
        }
    }

    function addQuestionFromModal() {
        if (!refs.addQuestionForm || !refs.newQuestionCategory || !refs.newQuestionText) {
            return;
        }

        const categoryId = refs.newQuestionCategory.value;
        const marks = toInt(refs.newQuestionMarks?.value, 1);
        const difficulty = refs.newQuestionDifficulty?.value || 'medium';
        const text = cleanText(refs.newQuestionText.value);

        if (!categoryId || !text) {
            showFormErrors(['Please select category and write question text before adding.']);
            return;
        }

        clearFormErrors();
        const nextId = state.questionBank.length
            ? Math.max(...state.questionBank.map((question) => toInt(question.id, 0))) + 1
            : 2001;

        state.questionBank.push({ id: nextId, categoryId, marks, difficulty, text });
        state.categoryAvailability[categoryId] = toInt(state.categoryAvailability[categoryId], 0) + 1;
        closeAddQuestionModal();
        renderCategorySelector();
        updateQuestionBankCards();
        updateWorkflowAndSnapshot();
    }

    function collectSubmissionErrors() {
        const errors = [];
        const totalQuestions = toInt(refs.totalQuestions.value, 0);
        const usesQuestionPool = isQuestionPoolEnabled();
        const maximumQuestions = toInt(refs.maximumQuestions?.value, 0);
        const totalMarks = toInt(refs.totalMarks.value, 0);
        const passingMarks = toInt(refs.passingMarks.value, 0);
        const paperSets = toInt(refs.paperSets.value, 0);
        const hasFixedPaperSets = Boolean(refs.fixedPaperSet?.checked);
        const timerEnabled = Boolean(refs.enableExamTimer && refs.enableExamTimer.checked);
        const durationMinutes = Math.max(0, toInt(refs.examDurationMinutes ? refs.examDurationMinutes.value : 0, 0));
        const scheduleType = normalizeScheduleType(state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time'));
        const attemptLimitType = normalizeAttemptLimitType(state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once'));
        const scheduleStartAt = cleanText(refs.scheduleStartAt ? refs.scheduleStartAt.value : '');
        const scheduleEndAt = cleanText(refs.scheduleEndAt ? refs.scheduleEndAt.value : '');
        const scheduleStartTs = parseDateTimeValue(scheduleStartAt);
        const scheduleEndTs = parseDateTimeValue(scheduleEndAt);
        const attemptLimitCount = Math.max(0, toInt(refs.attemptLimitCount ? refs.attemptLimitCount.value : 0, 0));

        if (cleanText(refs.title.value).length < 3) errors.push('Exam title must be at least 3 characters long.');
        if (!state.selectedExamFormat || state.selectedExamFormat.size === 0) errors.push('Select at least one exam format.');
        if (timerEnabled && (!Number.isInteger(durationMinutes) || durationMinutes < 1)) {
            errors.push('Exam duration must be a whole number of at least 1 minute when timer is enabled.');
        }
        if (scheduleType === 'fixed_window') {
            if (!scheduleStartAt || !scheduleEndAt) {
                errors.push('Set both schedule start and end date-time when fixed schedule window is selected.');
            } else if (scheduleStartTs === null || scheduleEndTs === null) {
                errors.push('Schedule date-time values are invalid.');
            } else if (scheduleEndTs <= scheduleStartTs) {
                errors.push('Schedule end date-time must be later than start date-time.');
            }
        }
        if (attemptLimitType === 'fixed_count' && attemptLimitCount < 2) {
            errors.push('Fixed attempt limit must be at least 2.');
        }
        if (totalQuestions < 1) errors.push('Total questions must be at least 1.');
        if (usesQuestionPool && maximumQuestions <= totalQuestions) {
            errors.push('Maximum questions in the pool must be greater than Total Questions Ask.');
        }
        if (state.selectedCategories.size < 1) errors.push('Select at least one question category.');
        if (hasFixedPaperSets && (!Number.isInteger(paperSets) || paperSets < 1 || paperSets > totalQuestions)) {
            errors.push('Paper sets must be a whole number between 1 and total questions.');
        }
        if (passingMarks > totalMarks) errors.push('Passing marks cannot exceed total marks.');

        const selectionLimits = getQuestionSelectionLimits();
        const selectedQuestionCount = state.selectedQuestions.size;
        if (!selectionLimits.viewOnly) {
            if (selectionLimits.exact !== null && selectedQuestionCount !== selectionLimits.exact) {
                errors.push(`Select exactly ${selectionLimits.exact} question(s) for this exam.`);
            } else if (selectedQuestionCount < selectionLimits.min || selectedQuestionCount > selectionLimits.max) {
                errors.push(`Select between ${selectionLimits.min} and ${selectionLimits.max} question(s) for the question pool.`);
            }
        } else {
            state.selectedQuestions.clear();
        }

        const shortages = computeQuestionShortages();
        shortages.forEach((item) => {
            if (!item.categoryId) {
                errors.push(`Not enough matching questions available. Need ${item.missing} more (available ${item.available} / required ${item.required}).`);
                return;
            }
            errors.push(
                `Need ${item.missing} more ${item.marks}-mark question(s) in ${getCategoryLabelById(item.categoryId)} (available ${item.available} / required ${item.required}).`
            );
        });

        if (refs.fixCategoryQuestions.checked) {
            const distribution = computeFixedCategoryDistribution();
            if (!distribution.isComplete) {
                errors.push(
                    `Allocate exactly ${distribution.totalQuestions} questions across categories. Currently allocated: ${distribution.totalAllocated}.`
                );
            }
        }
        if (refs.fixCategoryMarks?.checked) {
            const marksDistribution = computeFixedCategoryMarksDistribution();
            if (!marksDistribution.isComplete) {
                errors.push(
                    `Allocate exactly ${marksDistribution.totalMarks} marks across categories. Currently allocated: ${marksDistribution.totalAllocated}.`
                );
            }
        }
        const marksCalculation = computeMarksCalculationState();
        if (marksCalculation.fixEnabled) {
            if (!marksCalculation.hasSelectedMark || state.selectedMarks.size !== 1) {
                errors.push('Select exactly one question marks filter when "Fix Marks Each Question" is enabled.');
            }
            if (marksCalculation.hasSelectedMark && !marksCalculation.isValid) {
                errors.push(`Fixed marks mismatch: ${marksCalculation.totalQuestions} questions with ${marksCalculation.selectedMark} mark(s) requires Total Marks ${marksCalculation.expectedTotalMarks}.`);
            }
        } else if (!state.selectedMarks.size) {
            errors.push('Select at least one question marks filter.');
        }
        if (!refs.candidateSection.hidden && state.importedCandidates.length + state.manualEmails.length === 0) errors.push('Add at least one candidate email for the current access configuration.');
        if (!refs.pricingSection.hidden && !state.selectedPricing) {
            errors.push('Select one pricing option.');
        } else if (!refs.pricingSection.hidden && (state.selectedPricing === 'paid' || state.selectedPricing === 'free_for_imported')) {
            // If Free for Imported Candidates is selected, validate that they added at least one free candidate email
            if (state.selectedPricing === 'free_for_imported' && state.freeImportedCandidates.length + state.freeManualEmails.length === 0) {
                errors.push('Add at least one candidate email to the Free Candidate List for the free access configuration.');
            }

            // Validate Predefined Discounts
            state.selectedDiscounts.forEach(id => {
                const pct = state.discountPercentages[id];
                if (pct === undefined || isNaN(pct) || pct < 0 || pct > 100) {
                    errors.push('All selected predefined discounts must have a percentage value between 0% and 100%.');
                }
            });

            // Validate Custom Discounts
            state.customDiscounts.forEach((item, index) => {
                if (!item.name || !item.name.trim()) {
                    errors.push(`Custom Discount Offer #${index + 1}: Offer name is required.`);
                }
                if (item.percentage === undefined || isNaN(item.percentage) || item.percentage < 0 || item.percentage > 100) {
                    errors.push(`Custom Discount Offer #${index + 1}: Discount percentage must be between 0% and 100%.`);
                }
            });
        }

        refs.selectedCategoriesHidden.value = JSON.stringify([...state.selectedCategories]);
        refs.marksHidden.value = JSON.stringify([...state.selectedMarks]);
        if (refs.discountHidden) {
            refs.discountHidden.value = JSON.stringify(
                [...state.selectedDiscounts].map((id) => ({
                    id,
                    percentage: state.discountPercentages[id],
                }))
            );
        }
        if (refs.customDiscountsHidden) {
            refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts || []);
        }
        if (refs.pricingOptionHidden) {
            refs.pricingOptionHidden.value = state.selectedPricing || '';
        }
        if (refs.distributionTypeHidden) {
            refs.distributionTypeHidden.value = state.selectedDistributionType || '';
        }
        if (refs.questionIdsHidden) {
            refs.questionIdsHidden.value = JSON.stringify(
                selectionLimits.viewOnly
                    ? []
                    : [...state.selectedQuestions].map((id) => Number(id)).filter((id) => id > 0)
            );
        }
        syncExtraMarksAllocations();
        refs.tagsHidden.value = JSON.stringify(state.tags);
        if (refs.examFormatHidden) {
            refs.examFormatHidden.value = JSON.stringify([...state.selectedExamFormat]);
        }
        if (refs.scheduleTypeHidden) {
            refs.scheduleTypeHidden.value = scheduleType;
        }
        if (refs.attemptLimitTypeHidden) {
            refs.attemptLimitTypeHidden.value = attemptLimitType;
        }
        refs.manualEmailsHidden.value = JSON.stringify(state.manualEmails);
        refs.importedCandidatesHidden.value = JSON.stringify(state.importedCandidates);

        refs.freeManualEmailsHidden.value = JSON.stringify(state.freeManualEmails);
        refs.freeImportedCandidatesHidden.value = JSON.stringify(state.freeImportedCandidates);
        state.selectedInstructionRules = new Set(
            normalizeInstructionRuleSelection([...state.selectedInstructionRules])
        );
        syncInstructionRulesHidden();

        syncExtraQuestionsHidden();

        return errors;
    }

    function showFormErrors(errors) {
        refs.errorBanner.hidden = false;
        refs.errorBanner.innerHTML = `
            <strong>Please resolve the following:</strong>
            <ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>
        `;
    }

    function clearFormErrors() {
        refs.errorBanner.hidden = true;
        refs.errorBanner.innerHTML = '';
    }

    function showLoader() {
        refs.loader.classList.remove('is-hidden');
    }

    function hideLoader() {
        if (!refs.loader) {
            return;
        }

        refs.loader.classList.add('is-hidden');
        if (refs.page) {
            refs.page.setAttribute('data-page-ready', 'true');
        }

        window.setTimeout(() => {
            setDefaultFocusOnTitle();
        }, 50);
    }

    function setDefaultFocusOnTitle() {
        if (!refs.title || refs.title.disabled) {
            return;
        }

        window.requestAnimationFrame(() => {
            refs.title.focus({ preventScroll: true });
        });
    }

    function updateAll() {
        if (!state.selectedExamFormat || !(state.selectedExamFormat instanceof Set) || state.selectedExamFormat.size === 0) {
            state.selectedExamFormat = new Set(['mcq']);
        }
        state.selectedScheduleType = normalizeScheduleType(
            state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time')
        );
        state.selectedAttemptLimitType = normalizeAttemptLimitType(
            state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once')
        );

        // Reactive question bank sync check
        const currentCategoriesStr = [...state.selectedCategories].sort().join(',');
        const currentMarksStr = [...state.selectedMarks].sort().join(',');
        const currentFormatsStr = [...state.selectedExamFormat].sort().join(',');

        if (state.lastFetchedCategories !== currentCategoriesStr ||
            state.lastFetchedMarks !== currentMarksStr ||
            state.lastFetchedFormats !== currentFormatsStr) {

            state.lastFetchedCategories = currentCategoriesStr;
            state.lastFetchedMarks = currentMarksStr;
            state.lastFetchedFormats = currentFormatsStr;

            scheduleQuestionBankSync(200);
        }

        if (refs.examFormatOptions && refs.examFormatHidden) {
            renderExamFormatOptions();
        }
        renderScheduleTypeOptions();
        renderAttemptLimitOptions();
        updateScheduleConfigState();
        updateTimerConfigState();
        updateConditionalSections();
        renderMarksCalculationManagement();
        updateConfigPreview();
        renderFixedCategoryDistribution();
        renderFixedCategoryMarksDistribution();
        updateQuestionBankCards();
        renderDiscountSummary();
        updateWorkflowAndSnapshot();
    }

    window.examCreateState = state;
    window.examCreateUpdateAll = updateAll;
});

/**
 * Exam Create/Edit wizard — multi-part architecture.
 *
 * Exam-level concerns (basic info, candidates, timer toggles, schedule,
 * format, pricing, instructions, SEO, passing marks, negative marking) stay
 * global. Configuration / rules / question bank live inside per-part cards
 * cloned from `#exam-part-template`. All part DOM lookups are scoped inside
 * the part's root element (`[data-exam-part]`) so multiple parts never
 * cross-contaminate each other's fields.
 *
 * Public surface kept for `question-bank-init.js` and other legacy callers:
 *   window.examCreateState        — live state object
 *   window.examCreateUpdateAll    — re-run every exam-level + part render
 *   window.syncQuestionBankFromServer(categoryId?) — refresh expanded parts
 *   window.loadCategoryQuestions(categoryId, opts) — refresh one category
 */

// ────────────────────────────────────────────────────────────────────────
// Generic utilities
// ────────────────────────────────────────────────────────────────────────

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
const PART_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const PART_REACTIVE_FIELDS = new Set([
    'total_questions', 'total_marks',
    'use_question_pool', 'maximum_questions',
    'fixed_questions', 'fixed_paper_set', 'paper_sets',
    'fix_category_questions', 'fix_category_marks',
    'shuffle_questions', 'shuffle_categories', 'shuffle_options',
    'fix_marks_each_question',
]);

// ── Category hierarchy helpers (shared across all parts) ─────────────────

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

// ── Allocation math helpers (pure — shared by question + marks allocation) ─

function getCategoryAllocationBounds(total, selectedCount) {
    const safeCount = Math.max(0, selectedCount);
    const safeTotal = Math.max(0, total);
    if (!safeCount) {
        return { base: 0, remainder: 0, minPerCategory: 0, maxPerCategory: safeTotal };
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

function buildEvenCategoryCounts(selectedIds, total) {
    const counts = {};
    const selectedCount = selectedIds.length;
    if (!selectedCount) {
        return counts;
    }

    const { base, remainder } = getCategoryAllocationBounds(total, selectedCount);
    selectedIds.forEach((categoryId, index) => {
        counts[String(categoryId)] = base + (index < remainder ? 1 : 0);
    });
    return counts;
}

function shuffleArray(items) {
    const copy = [...items];
    for (let index = copy.length - 1; index > 0; index -= 1) {
        const swapIndex = Math.floor(Math.random() * (index + 1));
        [copy[index], copy[swapIndex]] = [copy[swapIndex], copy[index]];
    }
    return copy;
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
        timerDurationWrap: document.getElementById('timer-duration-wrap'),
        autoSubmitOnTimerEnd: document.getElementById('auto_submit_on_timer_end'),
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

        passingMarks: document.getElementById('passing_marks'),
        enableNegativeMarking: document.getElementById('enable_negative_marking'),
        negativeMarkingConfig: document.getElementById('negative-marking-config'),
        negativeMarkingType: document.getElementById('negative_marking_type'),
        passingMarksCeiling: document.getElementById('passing-marks-ceiling'),

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

        addExamPartBtn: document.getElementById('add-exam-part'),
        partsList: document.getElementById('exam-parts-list'),
        partTemplate: document.getElementById('exam-part-template'),

        instructionTemplate: document.getElementById('instruction_template'),
        applyInstructionTemplate: document.getElementById('apply-instruction-template'),
        instructions: document.getElementById('candidate_instructions'),
        instructionsCount: document.getElementById('instructions-char-count'),
        instructionRulesList: document.getElementById('instruction-rules-list'),
        instructionRulesHidden: document.getElementById('predefined_instruction_rules'),
        instructionRulesCount: document.getElementById('selected-instruction-rules-count'),

        examCategory: document.getElementById('exam_category_id'),
        sidebarValidationList: document.getElementById('sidebar-validation-list'),
        sidebarReadinessBadge: document.getElementById('sidebar-readiness-badge'),
        sidebarProgressFill: document.getElementById('sidebar-progress-fill'),
        sidebarProgressLabel: document.getElementById('sidebar-progress-label'),
    };

    if (!refs.page || !refs.form || !refs.partsList || !refs.partTemplate) {
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
            pricingOptions: [],
            distributionTypes: [],
            instructionTemplates: [],
            instructionRules: [],
            currencies: [],
            examFormats: [],
        },
        categoryHierarchyIndex: { childrenByParent: new Map() },

        parts: new Map(), // partKey -> partState
        partKeySeq: 0,
        lastAddQuestionPartKey: null,

        selectedDiscounts: new Set(),
        discountPercentages: {},
        customDiscounts: [],
        selectedPricing: 'free',
        selectedVisibility: 'public',
        selectedMode: 'standard',
        selectedExamFormat: new Set(['mcq']),
        selectedScheduleType: 'any_time',
        selectedAttemptLimitType: 'unlimited',
        activeCandidateTab: 'import',
        importedCandidates: [],
        manualEmails: [],
        activeFreeCandidateTab: 'import',
        freeImportedCandidates: [],
        freeManualEmails: [],
        tags: [],
        selectedInstructionRules: new Set(),

        richEditors: new Map(),
        richEditorsInitializing: false,
        richEditorsReady: false,
        eventsBound: false,
        schedulePickers: { start: null, end: null },

        isEditMode: false,
        examConfig: null,
    };

    const tagInput = new ChipInput(refs.tagsChip, {
        normalize: (value) => cleanText(value.replace(/,/g, ' ')).replace(/\s+/g, ' '),
        onChange: (values) => {
            state.tags = values;
            refs.tagsHidden.value = JSON.stringify(values);
            updateWorkflowAndSnapshot();
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
        hideLoader();
        try {
            renderInitialControls();
            mountInitialParts();
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

        const editorsReady = initRichTextEditors().catch((error) => {
            console.warn(error);
        });

        try {
            const endpoints = window.examCreateConfig?.bootstrapEndpoints
                || { categories: window.examCreateConfig?.endpoints?.categories };
            const staticOptions = window.examCreateConfig?.options || {};
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
                pricingOptions: Array.isArray(configData.pricingOptions) ? configData.pricingOptions : [],
                distributionTypes: Array.isArray(configData.distributionTypes) ? configData.distributionTypes : [],
                instructionTemplates: Array.isArray(configData.instructionTemplates) ? configData.instructionTemplates : [],
                instructionRules: Array.isArray(configData.instructionRules) ? configData.instructionRules : [],
                currencies: Array.isArray(configData.currencies) ? configData.currencies : [],
            };
            state.categoryHierarchyIndex = buildCategoryHierarchyIndex(state.config.categories);

            state.config.discountRules.forEach((rule) => {
                state.discountPercentages[rule.id] = rule.default_percentage || 0;
            });

            hydrateFromExamConfig();
            renderInitialControls();
            applyExamConfigToSelects();
            initScheduleDateTimePickers();
            mountInitialParts();
            bindEvents();

            window.clearTimeout(emergencyHide);
            emergencyHide = null;
            hideLoader();

            await editorsReady;
            safeUpdateAll();
        } catch (error) {
            console.error(error);
            hideLoader();
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
                    };
                    hydrateFromExamConfig();
                    renderInitialControls();
                    applyExamConfigToSelects();
                    mountInitialParts();
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

    // ── Edit-mode hydration ────────────────────────────────────────────

    function hydrateFromExamConfig() {
        const cfg = window.examFormConfig || null;
        if (!cfg || typeof cfg !== 'object') {
            state.isEditMode = false;
            state.examConfig = null;
            return;
        }

        state.isEditMode = true;
        state.examConfig = cfg;

        if (cfg.pricing_option) {
            state.selectedPricing = String(cfg.pricing_option);
        }

        if (Array.isArray(cfg.exam_format) && cfg.exam_format.length) {
            state.selectedExamFormat = new Set(cfg.exam_format.map((f) => normalizeExamFormat(f)));
        }

        if (cfg.schedule_type) {
            state.selectedScheduleType = normalizeScheduleType(cfg.schedule_type);
        }
        if (cfg.attempt_limit_type) {
            state.selectedAttemptLimitType = normalizeAttemptLimitType(cfg.attempt_limit_type);
        }

        if (Array.isArray(cfg.tags)) {
            state.tags = cfg.tags.map((tag) => cleanText(String(tag))).filter(Boolean);
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

        if (Array.isArray(cfg.imported_candidates)) {
            state.importedCandidates = cfg.imported_candidates;
        }
        if (Array.isArray(cfg.manual_candidate_emails)) {
            state.manualEmails = cfg.manual_candidate_emails
                .map((email) => cleanText(String(email).toLowerCase()))
                .filter(Boolean);
        }
        if (Array.isArray(cfg.free_imported_candidates)) {
            state.freeImportedCandidates = cfg.free_imported_candidates;
        }
        if (Array.isArray(cfg.free_manual_candidate_emails)) {
            state.freeManualEmails = cfg.free_manual_candidate_emails
                .map((email) => cleanText(String(email).toLowerCase()))
                .filter(Boolean);
        }

        // Preserve empty arrays on edit — do not fall back to default rules.
        if (Array.isArray(cfg.predefined_instruction_rules)) {
            state.selectedInstructionRules = new Set(
                normalizeInstructionRuleSelection(cfg.predefined_instruction_rules)
            );
        }
    }

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
        setSelectValueIfAvailable(refs.negativeMarkingType, cfg.negative_marking_type);

        if (refs.enableExamTimer && cfg.enable_exam_timer !== undefined) {
            refs.enableExamTimer.checked = Boolean(cfg.enable_exam_timer);
        }
        if (refs.autoSubmitOnTimerEnd && cfg.auto_submit_on_timer_end !== undefined) {
            refs.autoSubmitOnTimerEnd.checked = Boolean(cfg.auto_submit_on_timer_end);
        }
        if (refs.examDurationMinutes && cfg.exam_duration_minutes !== undefined && cfg.exam_duration_minutes !== null) {
            refs.examDurationMinutes.value = String(toInt(cfg.exam_duration_minutes, 60));
        }
        if (refs.passingMarks && cfg.passing_marks !== undefined && cfg.passing_marks !== null) {
            refs.passingMarks.value = String(toInt(cfg.passing_marks, 0));
        }
        if (refs.enableNegativeMarking && cfg.enable_negative_marking !== undefined) {
            refs.enableNegativeMarking.checked = Boolean(cfg.enable_negative_marking);
            if (refs.negativeMarkingConfig) {
                refs.negativeMarkingConfig.hidden = !refs.enableNegativeMarking.checked;
            }
        }

        if (refs.scheduleTypeHidden && cfg.schedule_type) {
            refs.scheduleTypeHidden.value = normalizeScheduleType(cfg.schedule_type);
            state.selectedScheduleType = normalizeScheduleType(cfg.schedule_type);
        }
        if (refs.attemptLimitTypeHidden && cfg.attempt_limit_type) {
            refs.attemptLimitTypeHidden.value = normalizeAttemptLimitType(cfg.attempt_limit_type);
            state.selectedAttemptLimitType = normalizeAttemptLimitType(cfg.attempt_limit_type);
        }
        if (refs.scheduleStartAt && cfg.schedule_start_at) {
            refs.scheduleStartAt.value = normalizeScheduleDateTimeValue(cfg.schedule_start_at);
        }
        if (refs.scheduleEndAt && cfg.schedule_end_at) {
            refs.scheduleEndAt.value = normalizeScheduleDateTimeValue(cfg.schedule_end_at);
        }
        if (refs.attemptLimitCount && cfg.attempt_limit_count !== undefined && cfg.attempt_limit_count !== null) {
            refs.attemptLimitCount.value = String(Math.max(2, toInt(cfg.attempt_limit_count, 2)));
        }

        const amountInput = document.getElementById('exam_amount');
        if (amountInput && cfg.exam_amount !== undefined && cfg.exam_amount !== null && cfg.exam_amount !== '') {
            amountInput.value = String(cfg.exam_amount);
        }

        if (refs.examFormatHidden && Array.isArray(cfg.exam_format)) {
            refs.examFormatHidden.value = JSON.stringify(cfg.exam_format);
            state.selectedExamFormat = new Set(cfg.exam_format.map((f) => normalizeExamFormat(f)));
        }

        if (refs.tagsHidden && Array.isArray(cfg.tags)) {
            refs.tagsHidden.value = JSON.stringify(cfg.tags);
        }
        if (refs.manualEmailsHidden && Array.isArray(cfg.manual_candidate_emails)) {
            refs.manualEmailsHidden.value = JSON.stringify(cfg.manual_candidate_emails);
        }
        if (refs.freeManualEmailsHidden && Array.isArray(cfg.free_manual_candidate_emails)) {
            refs.freeManualEmailsHidden.value = JSON.stringify(cfg.free_manual_candidate_emails);
        }
        if (refs.instructionRulesHidden && Array.isArray(cfg.predefined_instruction_rules)) {
            refs.instructionRulesHidden.value = JSON.stringify(cfg.predefined_instruction_rules);
        }

        // Exam category (Tom Select may already be initialized).
        if (refs.examCategory && cfg.exam_category_id) {
            const categoryId = String(cfg.exam_category_id);
            if (refs.examCategory.tomselect) {
                refs.examCategory.tomselect.setValue(categoryId, true);
            } else {
                refs.examCategory.value = categoryId;
            }
        }

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

    function initEnhancedSelects() {
        if (!window.EmsSelect || typeof window.EmsSelect.initAll !== 'function') {
            return;
        }
        window.EmsSelect.initAll(document, 'select.panel-input:not([data-field="selected_categories_select"])');
    }

    // ── Schedule date-time helpers ─────────────────────────────────────

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

    function parseDateTimeValue(value) {
        const parsed = parseDateTimeObject(value);
        return parsed ? parsed.getTime() : null;
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
            onChange: () => { syncScheduleEndPickerMinDate(); safeUpdateAll(); },
            onClose: () => { syncScheduleEndPickerMinDate(); safeUpdateAll(); },
        });

        state.schedulePickers.end = window.EmsFormUtils.initDateTimePicker(refs.scheduleEndAt, {
            ...pickerOptions,
            onChange: () => { safeUpdateAll(); },
            onClose: () => { safeUpdateAll(); },
        });

        syncScheduleEndPickerMinDate();
    }

    // ── Initial exam-level control rendering ──────────────────────────

    function renderInitialControls() {
        populateSelect(refs.difficulty, state.config.difficultyLevels, 'Select difficulty');
        populateSelect(refs.status, state.config.examStatus, 'Select status');
        populateSelect(refs.mode, state.config.examModes, 'Select mode');
        populateSelect(refs.visibility, state.config.visibilityOptions, 'Select visibility');

        if (!state.isEditMode) {
            setSelectDefault(refs.difficulty, 'medium');
            setSelectDefault(refs.status, 'active');
            setSelectDefault(refs.mode, 'standard');
            setSelectDefault(refs.visibility, 'public');
        }

        state.selectedMode = refs.mode.value || 'standard';
        state.selectedVisibility = refs.visibility.value || 'public';

        renderPricingOptions();
        renderDiscountRules();

        const defaultCustomDiscounts = jsonSafeParse(refs.customDiscountsHidden.value);
        if (Array.isArray(defaultCustomDiscounts) && defaultCustomDiscounts.length) {
            state.customDiscounts = defaultCustomDiscounts;
        }
        renderCustomDiscounts();

        let initialFormats = ['mcq'];
        if (state.isEditMode && state.selectedExamFormat instanceof Set && state.selectedExamFormat.size > 0) {
            initialFormats = [...state.selectedExamFormat];
        } else if (refs.examFormatHidden && refs.examFormatHidden.value) {
            try {
                const parsed = JSON.parse(refs.examFormatHidden.value);
                if (Array.isArray(parsed) && parsed.length) {
                    initialFormats = parsed;
                } else if (typeof parsed === 'string' && parsed) {
                    initialFormats = [parsed];
                }
            } catch (e) {
                const split = refs.examFormatHidden.value.split(',').map((s) => s.trim()).filter(Boolean);
                if (split.length) initialFormats = split;
            }
        }
        state.selectedExamFormat = new Set(initialFormats.map((f) => normalizeExamFormat(f)));
        renderExamFormatOptions();
        if (!state.selectedScheduleType) {
            state.selectedScheduleType = normalizeScheduleType(refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time');
        } else {
            state.selectedScheduleType = normalizeScheduleType(state.selectedScheduleType);
        }
        if (!state.selectedAttemptLimitType) {
            state.selectedAttemptLimitType = normalizeAttemptLimitType(refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'unlimited');
        } else {
            state.selectedAttemptLimitType = normalizeAttemptLimitType(state.selectedAttemptLimitType);
        }
        renderScheduleTypeOptions();
        renderAttemptLimitOptions();
        updateScheduleConfigState();
        updateTimerConfigState();

        if (refs.enableNegativeMarking && refs.negativeMarkingConfig) {
            refs.negativeMarkingConfig.hidden = !refs.enableNegativeMarking.checked;
        }

        renderInstructionTemplates();
        const parsedRules = jsonSafeParse(refs.instructionRulesHidden ? refs.instructionRulesHidden.value : '[]');
        let seedRules;
        if (state.isEditMode && Array.isArray(state.examConfig?.predefined_instruction_rules)) {
            seedRules = state.examConfig.predefined_instruction_rules;
        } else if (Array.isArray(parsedRules) && parsedRules.length) {
            seedRules = parsedRules;
        } else if (state.isEditMode && Array.isArray(parsedRules)) {
            seedRules = parsedRules;
        } else {
            seedRules = defaultInstructionRuleIds();
        }
        state.selectedInstructionRules = new Set(normalizeInstructionRuleSelection(seedRules));
        renderInstructionRules();

        refs.manualEmailFeedback.textContent = 'Type email and press Enter to add.';

        const defaultTags = Array.isArray(state.tags) && state.tags.length
            ? state.tags
            : jsonSafeParse(refs.tagsHidden.value);
        if (Array.isArray(defaultTags) && defaultTags.length) {
            tagInput.setValues(defaultTags);
        }

        const defaultEmails = Array.isArray(state.manualEmails) && state.manualEmails.length
            ? state.manualEmails
            : jsonSafeParse(refs.manualEmailsHidden.value);
        if (Array.isArray(defaultEmails) && defaultEmails.length) {
            emailInput.setValues(defaultEmails);
        }

        refs.freeManualEmailFeedback.textContent = 'Type email and press Enter to add.';
        const defaultFreeEmails = Array.isArray(state.freeManualEmails) && state.freeManualEmails.length
            ? state.freeManualEmails
            : jsonSafeParse(refs.freeManualEmailsHidden.value);
        if (Array.isArray(defaultFreeEmails) && defaultFreeEmails.length) {
            freeEmailInput.setValues(defaultFreeEmails);
        }

        initEnhancedSelects();
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

    // ── Category helpers (shared, read-only lookups) ──────────────────

    function getAssignableCategories() {
        return state.config.categories;
    }

    function getCategoryById(categoryId) {
        return state.config.categories.find((category) => category.id === String(categoryId)) || null;
    }

    function getCategoryLabelById(categoryId) {
        const category = getCategoryById(categoryId);
        if (!category) {
            return String(categoryId);
        }
        const parent = getCategoryParent(category, state.config.categories);
        if (!parent) {
            return category.name;
        }
        return `${parent.name} / ${category.name}`;
    }

    // ── Exam format / schedule / attempt-limit helpers ────────────────

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
        const autoSubmitEnabled = Boolean(refs.autoSubmitOnTimerEnd && refs.autoSubmitOnTimerEnd.checked);
        const duration = toInt(refs.examDurationMinutes?.value, 0);

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

    // ── Pricing / discounts (exam-level, unchanged from single-part era) ─

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
            [...state.selectedDiscounts].map((id) => ({ id, percentage: state.discountPercentages[id] }))
        );
        bindDiscountInputs();
    }

    function bindDiscountInputs() {
        refs.discountRules.querySelectorAll('.discount-percentage-input').forEach((input) => {
            input.addEventListener('input', (e) => {
                const ruleId = e.target.dataset.ruleId;
                const errEl = document.getElementById(`err-predefined-${ruleId}`);
                const rawVal = e.target.value;
                const val = rawVal === '' ? NaN : parseInt(rawVal, 10);

                let isInvalid = false;
                let errMsg = '';
                if (isNaN(val)) { isInvalid = true; errMsg = 'Percentage value is required.'; }
                else if (val < 0) { isInvalid = true; errMsg = 'Discount percentage cannot be less than 0%.'; }
                else if (val > 100) { isInvalid = true; errMsg = 'Discount percentage cannot exceed 100%.'; }

                state.discountPercentages[ruleId] = isNaN(val) ? 0 : val;

                if (errEl) {
                    errEl.hidden = !isInvalid;
                    if (isInvalid) errEl.textContent = errMsg;
                }

                refs.discountHidden.value = JSON.stringify(
                    [...state.selectedDiscounts].map((id) => ({ id, percentage: state.discountPercentages[id] }))
                );
                renderDiscountSummary();
                updateWorkflowAndSnapshot();
            });
        });
    }

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

        refs.customDiscountsContainer.querySelectorAll('.remove-custom-discount-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.rowIndex, 10);
                state.customDiscounts.splice(index, 1);
                renderCustomDiscounts();
            });
        });

        refs.customDiscountsContainer.querySelectorAll('.custom-discount-name').forEach((input) => {
            input.addEventListener('input', (e) => {
                const index = parseInt(e.target.dataset.rowIndex, 10);
                const val = e.target.value;
                state.customDiscounts[index].name = val;
                const errEl = document.getElementById(`err-custom-name-${index}`);
                if (errEl) {
                    errEl.hidden = Boolean(val.trim());
                    if (!val.trim()) errEl.textContent = 'Offer name is required.';
                }
                refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts);
                updateWorkflowAndSnapshot();
            });
        });

        refs.customDiscountsContainer.querySelectorAll('.custom-discount-desc').forEach((input) => {
            input.addEventListener('input', (e) => {
                const index = parseInt(e.target.dataset.rowIndex, 10);
                state.customDiscounts[index].description = e.target.value;
                refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts);
            });
        });

        refs.customDiscountsContainer.querySelectorAll('.custom-discount-pct').forEach((input) => {
            input.addEventListener('input', (e) => {
                const index = parseInt(e.target.dataset.rowIndex, 10);
                const rawVal = e.target.value;
                const val = rawVal === '' ? NaN : parseInt(rawVal, 10);
                state.customDiscounts[index].percentage = val;
                const errEl = document.getElementById(`err-custom-pct-${index}`);
                if (errEl) {
                    if (isNaN(val)) { errEl.textContent = 'Discount percentage is required.'; errEl.hidden = false; }
                    else if (val < 0) { errEl.textContent = 'Discount percentage cannot be less than 0%.'; errEl.hidden = false; }
                    else if (val > 100) { errEl.textContent = 'Discount percentage cannot exceed 100%.'; errEl.hidden = false; }
                    else { errEl.hidden = true; }
                }
                refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts);
                updateWorkflowAndSnapshot();
            });
        });
    }

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

    // ── Instruction templates / rules (exam-level) ────────────────────

    function renderInstructionTemplates() {
        const templates = Array.isArray(state.config.instructionTemplates) ? state.config.instructionTemplates : [];
        refs.instructionTemplate.innerHTML = [`<option value="">Choose template</option>`]
            .concat(templates.map((template) => `<option value="${escapeHtml(template.id)}">${escapeHtml(template.label)}</option>`))
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
        return [...new Set(source.map((value) => cleanText(String(value || ''))).filter((value) => validIds.has(value)))];
    }

    function defaultInstructionRuleIds() {
        return getInstructionRulesConfig().filter((rule) => rule.is_default || rule.is_required).map((rule) => rule.id);
    }

    function syncInstructionRulesHidden() {
        if (!refs.instructionRulesHidden) return;
        refs.instructionRulesHidden.value = JSON.stringify([...state.selectedInstructionRules]);
    }

    function renderInstructionRules() {
        if (!refs.instructionRulesList) return;

        const rules = getInstructionRulesConfig();
        if (!rules.length) {
            refs.instructionRulesList.innerHTML = `<p class="exam-help">No instruction rules are configured for this organization yet.</p>`;
            if (refs.instructionRulesCount) refs.instructionRulesCount.textContent = '0';
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

        if (refs.instructionRulesCount) refs.instructionRulesCount.textContent = String(state.selectedInstructionRules.size);
        syncInstructionRulesHidden();
    }

    // ── Candidate access (exam-level, unchanged) ──────────────────────

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
        if (!firstSheet) return [];

        const rows = window.XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });
        if (rows.length <= 1) return [];

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
            return { name: cleanText(namePart || 'Candidate'), email: cleanText(emailPart || '').toLowerCase() };
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
        const topItems = state.importedCandidates.slice(0, 5)
            .map((candidate) => `${escapeHtml(candidate.name)} (${escapeHtml(candidate.email)})`)
            .join('<br>');
        refs.importedCandidatePreview.hidden = false;
        refs.importedCandidatePreview.innerHTML = `<strong>${state.importedCandidates.length}</strong> candidates loaded from <strong>${escapeHtml(fileName)}</strong>.<br>${topItems}`;
    }

    function renderFreeImportedCandidatePreview(fileName) {
        if (!state.freeImportedCandidates.length) {
            refs.freeImportedCandidatePreview.hidden = true;
            refs.freeImportedCandidatePreview.textContent = '';
            return;
        }
        const topItems = state.freeImportedCandidates.slice(0, 5)
            .map((candidate) => `${escapeHtml(candidate.name)} (${escapeHtml(candidate.email)})`)
            .join('<br>');
        refs.freeImportedCandidatePreview.hidden = false;
        refs.freeImportedCandidatePreview.innerHTML = `<strong>${state.freeImportedCandidates.length}</strong> candidates loaded from <strong>${escapeHtml(fileName)}</strong>.<br>${topItems}`;
    }

    // ── Section numbering / conditional visibility ────────────────────

    function updateSectionNumbers() {
        const sections = [
            { id: 'exam-basic-information', defaultTitle: 'Exam Basic Information' },
            { id: 'candidate-access-section', defaultTitle: 'Candidate Access Management' },
            { id: 'timer-section', defaultTitle: 'Timer & Duration Management' },
            { id: 'schedule-section', defaultTitle: 'Schedule & Attempt Management' },
            { id: 'exam-format-section', defaultTitle: 'Exam Format Management' },
            { id: 'exam-parts-section', defaultTitle: 'Exam Parts' },
            { id: 'exam-scoring-section', defaultTitle: 'Passing Marks & Negative Marking' },
            { id: 'pricing-section', defaultTitle: 'Pricing and Discount Rules' },
            { id: 'instructions-rules-section', defaultTitle: 'Exam Instructions & Rules Management' },
            { id: 'instructions-section', defaultTitle: 'Instructions for Candidates' },
        ];

        let visibleCount = 0;
        sections.forEach((sec) => {
            const el = document.getElementById(sec.id);
            if (el && !el.hidden) {
                visibleCount++;
                const h2 = el.querySelector('.exam-section__head h2');
                if (h2) h2.textContent = `${visibleCount}. ${sec.defaultTitle}`;
            }
        });
    }

    function setSectionCollapsed(section, collapsed) {
        if (!section) return;
        const body = section.querySelector(':scope > .exam-section__body');
        const toggle = section.querySelector('[data-section-toggle]');
        section.classList.toggle('is-collapsed', Boolean(collapsed));
        if (body) body.hidden = Boolean(collapsed);
        if (toggle) {
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            const label = collapsed ? 'Expand section' : 'Collapse section';
            toggle.setAttribute('aria-label', label);
            toggle.title = label;
        }
    }

    function initSectionCollapse() {
        const sections = [...document.querySelectorAll('.exam-create-main > .exam-section')];
        sections.forEach((section) => {
            if (section.dataset.collapseReady === '1') return;

            const head = section.querySelector(':scope > .exam-section__head');
            const body = section.querySelector(':scope > .exam-section__body');
            if (!head || !body) return;

            head.classList.add('exam-section__head--collapsible');

            let copy = head.querySelector('.exam-section__head-copy');
            if (!copy) {
                copy = document.createElement('div');
                copy.className = 'exam-section__head-copy';
                const actionSelector = '#add-exam-part, button.panel-button-secondary, [data-section-action]';
                const actionEl = head.querySelector(actionSelector);
                [...head.childNodes].forEach((node) => {
                    if (node === actionEl) return;
                    if (node.nodeType === 1 && node.matches && node.matches(actionSelector)) return;
                    copy.appendChild(node);
                });
                if (actionEl) {
                    head.insertBefore(copy, actionEl);
                    head.classList.add('exam-section__head--with-action');
                } else {
                    head.appendChild(copy);
                }
            }

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'exam-section__toggle';
            toggle.setAttribute('data-section-toggle', '');
            toggle.innerHTML = '<span class="exam-section__chevron" aria-hidden="true"></span>';
            head.insertBefore(toggle, head.firstChild);

            if (!body.id) body.id = `${section.id || 'exam-section'}-body`;
            toggle.setAttribute('aria-controls', body.id);

            const onToggle = (event) => {
                if (event.target.closest('a, input, select, textarea, label, button:not([data-section-toggle])')) return;
                event.preventDefault();
                setSectionCollapsed(section, !section.classList.contains('is-collapsed'));
            };

            toggle.addEventListener('click', onToggle);
            copy.addEventListener('click', onToggle);

            setSectionCollapsed(section, section.getAttribute('data-collapsed-default') === 'true');
            section.dataset.collapseReady = '1';
        });
    }

    function updateConditionalSections() {
        const hasRestrictedAccess = ['private', 'invite_only'].includes(state.selectedVisibility);
        const importedFree = state.selectedPricing === 'free_for_imported';

        refs.candidateSection.hidden = !hasRestrictedAccess;
        refs.negativeMarkingConfig.hidden = !refs.enableNegativeMarking.checked;
        refs.freeCandidatesWrap.hidden = !importedFree;
        refs.pricingImportedNote.hidden = !importedFree;
        renderCandidateTabs();
        renderFreeCandidateTabs();
        refs.pricingSection.hidden = state.selectedMode === 'practice';

        updateSectionNumbers();
    }

    // ────────────────────────────────────────────────────────────────
    // PART LIFECYCLE
    // ────────────────────────────────────────────────────────────────

    function generatePartKey() {
        state.partKeySeq += 1;
        return `part-${Date.now().toString(36)}-${state.partKeySeq}`;
    }

    function nextPartLetterName() {
        const count = state.parts.size;
        const letterIndex = Math.min(PART_LETTERS.length - 1, Math.max(1, count));
        return `Part ${PART_LETTERS[letterIndex]}`;
    }

    function nextPartLetterNameForIndex(index) {
        const letterIndex = Math.min(PART_LETTERS.length - 1, Math.max(1, index));
        return `Part ${PART_LETTERS[letterIndex]}`;
    }

    function partField(root, field) {
        return root ? root.querySelector(`[data-field="${field}"]`) : null;
    }

    function setFieldValue(root, field, value) {
        const el = partField(root, field);
        if (el) el.value = value === null || value === undefined ? '' : value;
    }

    function setCheckbox(root, field, checked) {
        const el = partField(root, field);
        if (el) el.checked = Boolean(checked);
    }

    function defaultPartSeed(overrides = {}) {
        return {
            total_questions: 30,
            total_marks: 50,
            use_question_pool: true,
            maximum_questions: 50,
            fixed_questions: false,
            fixed_paper_set: false,
            paper_sets: 1,
            fix_category_questions: false,
            fix_category_marks: false,
            fix_marks_each_question: false,
            shuffle_questions: true,
            shuffle_categories: true,
            shuffle_options: true,
            distributionType: 'mixed',
            selectedCategories: [],
            selectedMarks: [],
            extraQuestionsAllocations: {},
            extraMarksAllocations: {},
            questionIds: [],
            ...overrides,
        };
    }

    function createPartState(overrides = {}) {
        const seed = defaultPartSeed(overrides);
        const partKey = overrides.partKey || generatePartKey();
        const questionIds = Array.isArray(seed.questionIds)
            ? seed.questionIds.map((id) => toInt(id, 0)).filter((id) => id > 0)
            : [];
        const selectedMarksSeed = Array.isArray(seed.selectedMarks)
            ? seed.selectedMarks.map(Number).filter((n) => Number.isFinite(n) && n > 0)
            : [];

        const selectedCategories = new Set((seed.selectedCategories || []).map(String));

        return {
            partKey,
            id: seed.id ?? null,
            isDefault: Boolean(seed.isDefault),
            name: seed.name || (seed.isDefault ? 'Default Part' : nextPartLetterName()),
            expanded: seed.expanded !== undefined ? seed.expanded : Boolean(seed.isDefault),
            root: null,
            catSelectId: `part-cat-select-${partKey}`,
            categorySelectBound: false,
            isSyncingCategories: false,
            suppressCategorySelectEvents: false,
            selectedCategories,
            selectedMarks: new Set(selectedMarksSeed),
            selectedDistributionType: seed.distributionType || 'mixed',
            extraQuestionsCategoryIds: Array.isArray(seed.extraQuestionsCategoryIds) && seed.extraQuestionsCategoryIds.length
                ? seed.extraQuestionsCategoryIds.map(String)
                : [...selectedCategories],
            extraQuestionsAllocations: { ...(seed.extraQuestionsAllocations || {}) },
            extraMarksAllocations: { ...(seed.extraMarksAllocations || {}) },
            categoryQuestionRules: Array.isArray(seed.categoryQuestionRules) ? seed.categoryQuestionRules : [],
            categoryQuestionCountsKey: '',
            categoryMarksCountsKey: '',
            questionBank: [],
            questionBankMeta: { total: 0 },
            categoryCounts: {},
            categoryLoadState: {},
            expandedCards: new Set(),
            countsAbortController: null,
            selectedQuestions: new Set(questionIds),
            selectedQuestionCache: {},
            hydratedQuestionIds: questionIds.length ? questionIds : null,
            hasHydratedSelectedQuestions: !questionIds.length,
            lastFetchedKey: '',
            syncTimer: null,
            seed,
        };
    }

    function buildPartHtml(partState, index) {
        let html = refs.partTemplate.innerHTML;
        const replacements = {
            __INDEX__: String(index),
            __PART_KEY__: escapeHtml(partState.partKey),
            __PART_NAME__: escapeHtml(partState.name || ''),
            __IS_DEFAULT__: partState.isDefault ? '1' : '0',
        };
        Object.entries(replacements).forEach(([token, value]) => {
            html = html.split(token).join(value);
        });
        return html.trim();
    }

    function applyPartSeedToDom(partState) {
        const root = partState.root;
        const seed = defaultPartSeed(partState.seed || {});
        setFieldValue(root, 'id', seed.id ?? '');
        setFieldValue(root, 'total_questions', toInt(seed.total_questions, 30));
        setFieldValue(root, 'total_marks', toInt(seed.total_marks, 50));
        setCheckbox(root, 'use_question_pool', Boolean(seed.use_question_pool));
        setFieldValue(root, 'maximum_questions', seed.maximum_questions ?? 50);
        setCheckbox(root, 'fixed_questions', Boolean(seed.fixed_questions));
        setCheckbox(root, 'fixed_paper_set', Boolean(seed.fixed_paper_set));
        setFieldValue(root, 'paper_sets', toInt(seed.paper_sets, 1));
        setCheckbox(root, 'fix_category_questions', Boolean(seed.fix_category_questions));
        setCheckbox(root, 'fix_category_marks', Boolean(seed.fix_category_marks));
        setCheckbox(root, 'shuffle_questions', Boolean(seed.shuffle_questions));
        setCheckbox(root, 'shuffle_categories', Boolean(seed.shuffle_categories));
        setCheckbox(root, 'shuffle_options', Boolean(seed.shuffle_options));
        setCheckbox(root, 'fix_marks_each_question', Boolean(seed.fix_marks_each_question));
    }

    function mountPart(partState, { insertAfter = null } = {}) {
        const index = refs.partsList.children.length;
        const html = buildPartHtml(partState, index);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const root = wrapper.firstElementChild;

        if (insertAfter && insertAfter.parentNode === refs.partsList) {
            insertAfter.after(root);
        } else {
            refs.partsList.appendChild(root);
        }

        partState.root = root;
        applyPartSeedToDom(partState);
        bindPartEvents(partState);
        initPartCategorySelect(partState);
        setPartExpanded(partState, partState.expanded);
        updatePartUi(partState);
        return root;
    }

    function reindexParts() {
        const partRoots = [...refs.partsList.children].filter((el) => el.matches('[data-exam-part]'));
        partRoots.forEach((root, index) => {
            root.dataset.partIndex = String(index);
            root.querySelectorAll('[name^="parts["]').forEach((el) => {
                el.name = el.name.replace(/^parts\[\d+\]/, `parts[${index}]`);
            });

            const partState = state.parts.get(root.dataset.partKey);
            const badge = root.querySelector('[data-part-badge]');
            if (badge) {
                badge.textContent = partState?.isDefault ? 'Default Part' : `Part ${index + 1}`;
            }
            const deleteBtn = root.querySelector('[data-part-delete]');
            if (deleteBtn) {
                deleteBtn.hidden = Boolean(partState?.isDefault);
            }
        });
    }

    function getOrderedParts() {
        return [...refs.partsList.children]
            .filter((el) => el.matches('[data-exam-part]'))
            .map((el) => state.parts.get(el.dataset.partKey))
            .filter(Boolean);
    }

    function getPartByKey(key) {
        return key ? state.parts.get(key) || null : null;
    }

    function getFirstPart() {
        return getOrderedParts()[0] || null;
    }

    function setPartExpanded(partState, expanded) {
        partState.expanded = expanded;
        const root = partState.root;
        if (!root) return;
        root.classList.toggle('is-expanded', expanded);
        const toggleBtn = root.querySelector('[data-part-action="toggle"]');
        toggleBtn?.setAttribute('aria-expanded', String(expanded));
        const body = root.querySelector('[data-part-body]');
        if (body) body.hidden = !expanded;
    }

    function togglePartExpanded(partState) {
        setPartExpanded(partState, !partState.expanded);
    }

    function serializePartToSeed(partState) {
        const root = partState.root;
        return {
            total_questions: toInt(partField(root, 'total_questions')?.value, 50),
            total_marks: toInt(partField(root, 'total_marks')?.value, 100),
            use_question_pool: Boolean(partField(root, 'use_question_pool')?.checked),
            maximum_questions: partField(root, 'maximum_questions')?.value || '',
            fixed_questions: Boolean(partField(root, 'fixed_questions')?.checked),
            fixed_paper_set: Boolean(partField(root, 'fixed_paper_set')?.checked),
            paper_sets: toInt(partField(root, 'paper_sets')?.value, 1),
            fix_category_questions: Boolean(partField(root, 'fix_category_questions')?.checked),
            fix_category_marks: Boolean(partField(root, 'fix_category_marks')?.checked),
            fix_marks_each_question: Boolean(partField(root, 'fix_marks_each_question')?.checked),
            shuffle_questions: Boolean(partField(root, 'shuffle_questions')?.checked),
            shuffle_categories: Boolean(partField(root, 'shuffle_categories')?.checked),
            shuffle_options: Boolean(partField(root, 'shuffle_options')?.checked),
            distributionType: partState.selectedDistributionType,
            selectedCategories: [...partState.selectedCategories],
            selectedMarks: [...partState.selectedMarks],
            extraQuestionsAllocations: { ...partState.extraQuestionsAllocations },
            extraMarksAllocations: { ...partState.extraMarksAllocations },
            questionIds: [...partState.selectedQuestions],
        };
    }

    function addPartButtonHandler() {
        const partState = createPartState({ isDefault: false, expanded: true });
        state.parts.set(partState.partKey, partState);
        mountPart(partState);
        reindexParts();
        updateExamSummary();
        updateWorkflowAndSnapshot();
        partState.root.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function duplicatePart(partState) {
        const currentName = cleanText(partField(partState.root, 'name')?.value || partState.name || 'Part');
        const seed = serializePartToSeed(partState);
        seed.isDefault = false;
        seed.id = null;
        seed.name = `${currentName} Copy`;
        seed.expanded = true;

        const newPart = createPartState(seed);
        state.parts.set(newPart.partKey, newPart);
        mountPart(newPart, { insertAfter: partState.root });
        reindexParts();
        updateExamSummary();
        updateWorkflowAndSnapshot();
        newPart.root.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function deletePart(partState) {
        if (partState.isDefault) return;
        const name = cleanText(partField(partState.root, 'name')?.value || partState.name || 'this part');
        if (!window.confirm(`Delete "${name}"? This cannot be undone once the exam is saved.`)) {
            return;
        }
        window.clearTimeout(partState.syncTimer);
        partState.countsAbortController?.abort();
        partState.root.remove();
        state.parts.delete(partState.partKey);
        reindexParts();
        updateExamSummary();
        updateWorkflowAndSnapshot();
    }

    function mountInitialParts() {
        const cfgParts = state.isEditMode && Array.isArray(state.examConfig?.parts) ? state.examConfig.parts : [];

        if (cfgParts.length) {
            cfgParts.forEach((partCfg, idx) => {
                const isDefault = Boolean(partCfg.is_default) || (idx === 0 && !cfgParts.some((p) => p.is_default));
                const overrides = {
                    id: partCfg.id ?? null,
                    isDefault,
                    name: partCfg.name || (isDefault ? 'Default Part' : nextPartLetterNameForIndex(idx)),
                    expanded: true,
                    total_questions: toInt(partCfg.total_questions, 50),
                    total_marks: toInt(partCfg.total_marks, 100),
                    use_question_pool: Boolean(partCfg.use_question_pool),
                    maximum_questions: partCfg.maximum_questions ?? '',
                    fixed_questions: Boolean(partCfg.fixed_questions),
                    fixed_paper_set: Boolean(partCfg.fixed_paper_set),
                    paper_sets: toInt(partCfg.paper_sets, 1),
                    fix_category_questions: Boolean(partCfg.fix_category_questions),
                    fix_category_marks: Boolean(partCfg.fix_category_marks),
                    fix_marks_each_question: Boolean(partCfg.fix_marks_each_question),
                    shuffle_questions: Boolean(partCfg.shuffle_questions),
                    shuffle_categories: Boolean(partCfg.shuffle_categories),
                    shuffle_options: Boolean(partCfg.shuffle_options),
                    distributionType: partCfg.distribution_type || 'mixed',
                    selectedCategories: Array.isArray(partCfg.selected_categories) ? partCfg.selected_categories.map(String) : [],
                    selectedMarks: Array.isArray(partCfg.question_marks_filter)
                        ? partCfg.question_marks_filter.map(Number).filter((n) => Number.isFinite(n) && n > 0)
                        : [],
                    extraQuestionsCategoryIds: Array.isArray(partCfg.extra_questions_categories)
                        ? partCfg.extra_questions_categories.map(String)
                        : (Array.isArray(partCfg.selected_categories) ? partCfg.selected_categories.map(String) : []),
                    extraQuestionsAllocations: (partCfg.extra_questions_allocations && typeof partCfg.extra_questions_allocations === 'object')
                        ? { ...partCfg.extra_questions_allocations }
                        : {},
                    extraMarksAllocations: (partCfg.extra_marks_allocations && typeof partCfg.extra_marks_allocations === 'object')
                        ? { ...partCfg.extra_marks_allocations }
                        : {},
                    categoryQuestionRules: Array.isArray(partCfg.category_question_rules)
                        ? partCfg.category_question_rules
                        : [],
                    questionIds: Array.isArray(partCfg.question_ids) ? partCfg.question_ids : [],
                };
                const partState = createPartState(overrides);
                state.parts.set(partState.partKey, partState);
                mountPart(partState);
            });
        } else {
            const defaultPart = createPartState({ isDefault: true, expanded: true, name: 'Default Part' });
            state.parts.set(defaultPart.partKey, defaultPart);
            mountPart(defaultPart);
        }

        reindexParts();
    }

    // ────────────────────────────────────────────────────────────────
    // PART CONFIGURATION RENDERING
    // ────────────────────────────────────────────────────────────────

    function applyPartCategorySelectionRules(rawIds) {
        const validIds = new Set(getAssignableCategories().map((category) => category.id));
        const filtered = [...rawIds].filter((id) => validIds.has(id));
        return [...pruneDescendantSelections(filtered, state.categoryHierarchyIndex)];
    }

    function buildPartCategorySelectOptionsHtml(selectedSet) {
        const categories = getAssignableCategories();
        const visibleCategories = categories.filter((category) => isCategoryVisibleInDropdown(
            category.id, selectedSet, state.config.categories
        ));
        return visibleCategories
            .map((category) => buildCategoryOptionMarkup(category, state.config.categories, selectedSet.has(category.id)))
            .join('');
    }

    function syncPartCategoryFeedback(partState) {
        const hidden = partField(partState.root, 'selected_categories');
        if (hidden) hidden.value = JSON.stringify([...partState.selectedCategories]);
        const help = partState.root.querySelector('[data-field-help="category_selection"]');
        if (help) {
            const n = partState.selectedCategories.size;
            help.textContent = n
                ? `${n} categor${n === 1 ? 'y' : 'ies'} selected.`
                : 'Select one or more categories for this part.';
        }
    }

    function initPartCategorySelect(partState) {
        const root = partState.root;
        const select = partField(root, 'selected_categories_select');
        if (!select) return;

        select.id = partState.catSelectId;
        select.dataset.maxItems = String(Math.max(1, getAssignableCategories().length));
        select.innerHTML = buildPartCategorySelectOptionsHtml(partState.selectedCategories);

        if (window.EmsSelect && typeof window.EmsSelect.initAll === 'function') {
            window.EmsSelect.initAll(root, '[data-field="selected_categories_select"]');
        }

        bindPartCategorySelect(partState);
        syncPartCategoryFeedback(partState);
    }

    function bindPartCategorySelect(partState) {
        if (partState.categorySelectBound || !window.EmsSelect || typeof window.EmsSelect.onChange !== 'function') {
            return;
        }

        window.EmsSelect.onChange(partState.catSelectId, () => {
            if (partState.isSyncingCategories || partState.suppressCategorySelectEvents) return;

            const rawValue = window.EmsSelect.getValue(partState.catSelectId);
            const selectedValues = Array.isArray(rawValue) ? rawValue : (rawValue ? [rawValue] : []);
            const normalized = applyPartCategorySelectionRules(selectedValues);

            partState.selectedCategories = new Set(normalized);
            partState.categoryQuestionCountsKey = '';
            partState.categoryMarksCountsKey = '';
            partState.lastFetchedKey = '';
            renderPartCategorySelector(partState);
            updatePartUi(partState);
            updateExamSummary();
            updateWorkflowAndSnapshot();
        });

        partState.categorySelectBound = true;
    }

    function renderPartCategorySelector(partState) {
        if (partState.isSyncingCategories) return;
        partState.isSyncingCategories = true;
        partState.suppressCategorySelectEvents = true;

        try {
            const normalized = applyPartCategorySelectionRules([...partState.selectedCategories]);
            partState.selectedCategories = new Set(normalized);
            const html = buildPartCategorySelectOptionsHtml(partState.selectedCategories);
            const values = [...partState.selectedCategories];
            const maxItems = Math.max(1, getAssignableCategories().length);

            const select = partField(partState.root, 'selected_categories_select');
            if (select) select.dataset.maxItems = String(maxItems);

            if (window.EmsSelect && typeof window.EmsSelect.replaceOptions === 'function') {
                window.EmsSelect.replaceOptions(partState.catSelectId, html, values, maxItems);
                bindPartCategorySelect(partState);
            } else if (select) {
                select.innerHTML = html;
            }

            syncPartCategoryFeedback(partState);
        } finally {
            partState.suppressCategorySelectEvents = false;
            partState.isSyncingCategories = false;
        }
    }

    function renderPartDistributionTypes(partState) {
        const root = partState.root;
        const group = root.querySelector('[data-field-ui="distribution_type"]');
        const hidden = partField(root, 'distribution_type');
        if (!group || !hidden) return;

        if (!partState.selectedDistributionType) {
            partState.selectedDistributionType = 'mixed';
        }

        group.innerHTML = state.config.distributionTypes
            .map((type) => {
                const active = type.id === partState.selectedDistributionType ? 'is-active' : '';
                return `<button type="button" class="pill ${active}" data-distribution-id="${escapeHtml(type.id)}">${escapeHtml(type.label)}</button>`;
            })
            .join('');

        hidden.value = partState.selectedDistributionType || 'mixed';
    }

    function renderPartQuestionMarks(partState) {
        const root = partState.root;
        const pillGroup = root.querySelector('[data-field-ui="question_marks_filter"]');
        const hidden = partField(root, 'question_marks_filter');
        const countEl = root.querySelector('[data-field-ui="selected_marks_count"]');

        const fixEach = Boolean(partField(root, 'fix_marks_each_question')?.checked);
        if (fixEach && partState.selectedMarks.size > 1) {
            const first = [...partState.selectedMarks][0];
            partState.selectedMarks = new Set([first]);
        }

        if (pillGroup) {
            pillGroup.innerHTML = state.config.questionMarks
                .map((item) => {
                    const mark = Number(item.value);
                    const active = partState.selectedMarks.has(mark) ? 'is-active' : '';
                    return `<button type="button" class="pill ${active}" data-mark-value="${mark}">${escapeHtml(item.label)}</button>`;
                })
                .join('');
        }
        if (hidden) hidden.value = JSON.stringify([...partState.selectedMarks]);
        if (countEl) countEl.textContent = String(partState.selectedMarks.size);
    }

    // ── Marks calculation (fixed marks per question) ──────────────────

    function computeMarksCalculationForPart(partState) {
        const root = partState.root;
        const fixEnabled = Boolean(partField(root, 'fix_marks_each_question')?.checked);
        const totalQuestions = Math.max(0, toInt(partField(root, 'total_questions')?.value, 0));
        const totalMarks = Math.max(0, toInt(partField(root, 'total_marks')?.value, 0));
        const selectedMark = partState.selectedMarks.size === 1 ? Number([...partState.selectedMarks][0]) : 0;
        const hasSelectedMark = Number.isFinite(selectedMark) && selectedMark > 0;
        const expectedTotalMarks = hasSelectedMark ? totalQuestions * selectedMark : 0;
        const rawQuestionCountFromMarks = hasSelectedMark ? (totalMarks / selectedMark) : 0;
        const hasExactQuestionCount = hasSelectedMark && rawQuestionCountFromMarks > 0 && Number.isInteger(rawQuestionCountFromMarks);
        const suggestedQuestionCount = hasSelectedMark
            ? Math.max(1, hasExactQuestionCount ? rawQuestionCountFromMarks : Math.round(rawQuestionCountFromMarks || 1))
            : 0;
        const suggestedTotalMarks = hasSelectedMark ? suggestedQuestionCount * selectedMark : 0;
        const isValid = !fixEnabled || (hasSelectedMark && totalQuestions > 0 && totalMarks > 0 && totalMarks === expectedTotalMarks);

        return {
            fixEnabled, totalQuestions, totalMarks, selectedMark, hasSelectedMark,
            expectedTotalMarks, hasExactQuestionCount, suggestedQuestionCount, suggestedTotalMarks, isValid,
        };
    }

    function renderPartMarksCalculation(partState) {
        const root = partState.root;
        const wrap = root.querySelector('[data-field-wrap="marks_calculation_management"]');
        const summaryEl = root.querySelector('[data-field-ui="marks_calculation_summary"]');
        const warningEl = root.querySelector('[data-field-ui="marks_calculation_warning"]');
        const suggestionEl = root.querySelector('[data-field-ui="marks_calculation_suggestion"]');
        const actionsEl = root.querySelector('[data-field-wrap="marks_calculation_actions"]');
        if (!wrap || !summaryEl || !warningEl || !suggestionEl || !actionsEl) return;

        const calc = computeMarksCalculationForPart(partState);

        if (!calc.fixEnabled) {
            wrap.hidden = true;
            wrap.classList.remove('is-valid', 'is-warning');
            summaryEl.textContent = '';
            warningEl.textContent = ''; warningEl.hidden = true;
            suggestionEl.textContent = ''; suggestionEl.hidden = true;
            actionsEl.hidden = true;
            return;
        }

        wrap.hidden = false;
        wrap.classList.remove('is-valid', 'is-warning');

        if (!calc.hasSelectedMark) {
            summaryEl.textContent = 'Select one question mark value to validate fixed marks calculation.';
            warningEl.textContent = 'Fixed marks mode requires one selected mark value.'; warningEl.hidden = false;
            suggestionEl.textContent = 'Choose a mark from Question Marks Filter, then use suggested auto-fix actions if needed.'; suggestionEl.hidden = false;
            actionsEl.hidden = true;
            wrap.classList.add('is-warning');
            return;
        }

        summaryEl.textContent = `Current formula: ${calc.totalQuestions} questions x ${calc.selectedMark} mark(s) = ${calc.expectedTotalMarks} expected total marks. Current Total Marks: ${calc.totalMarks}.`;

        if (calc.isValid) {
            warningEl.textContent = ''; warningEl.hidden = true;
            suggestionEl.textContent = 'Marks configuration is valid and ready.'; suggestionEl.hidden = false;
            actionsEl.hidden = true;
            wrap.classList.add('is-valid');
            return;
        }

        warningEl.textContent = 'The selected marks configuration does not match the total questions and total marks. Please adjust the values.';
        warningEl.hidden = false;

        if (calc.hasExactQuestionCount) {
            suggestionEl.textContent = `Suggested fix: set Total Marks to ${calc.expectedTotalMarks}, or set Total Questions to ${calc.suggestedQuestionCount}.`;
        } else {
            suggestionEl.textContent = `Suggested fix: set Total Marks to ${calc.expectedTotalMarks}, or set Total Questions to ${calc.suggestedQuestionCount} (nearest whole number, then Total Marks will sync to ${calc.suggestedTotalMarks}).`;
        }
        suggestionEl.hidden = false;
        actionsEl.hidden = false;
        wrap.classList.add('is-warning');

        const fixMarksBtn = root.querySelector('[data-part-action="fix_total_marks"]');
        const fixQuestionsBtn = root.querySelector('[data-part-action="fix_total_questions"]');
        if (fixMarksBtn) { fixMarksBtn.textContent = `Update Total Marks (${calc.expectedTotalMarks})`; fixMarksBtn.disabled = false; }
        if (fixQuestionsBtn) { fixQuestionsBtn.textContent = `Update Total Questions (${calc.suggestedQuestionCount})`; fixQuestionsBtn.disabled = false; }
    }

    function applyMarksCalculationFixForPart(partState, fixType) {
        const calc = computeMarksCalculationForPart(partState);
        if (!calc.fixEnabled || !calc.hasSelectedMark) return;
        const root = partState.root;

        if (fixType === 'total_marks') {
            setFieldValue(root, 'total_marks', String(calc.expectedTotalMarks));
        }
        if (fixType === 'total_questions') {
            setFieldValue(root, 'total_questions', String(calc.suggestedQuestionCount));
            if (!calc.hasExactQuestionCount) {
                setFieldValue(root, 'total_marks', String(calc.suggestedTotalMarks));
            }
        }

        updatePartUi(partState);
        updateExamSummary();
        updateWorkflowAndSnapshot();
    }

    // ── Fixed category question / marks allocation ────────────────────

    function syncPartExtraQuestionsAllocationsHidden(partState) {
        const hidden = partField(partState.root, 'extra_questions_allocations');
        if (hidden) hidden.value = JSON.stringify(partState.extraQuestionsAllocations);
        const catHidden = partField(partState.root, 'extra_questions_categories');
        if (catHidden) catHidden.value = JSON.stringify(partState.extraQuestionsCategoryIds);
    }

    function syncPartExtraMarksAllocationsHidden(partState) {
        const hidden = partField(partState.root, 'extra_marks_allocations');
        if (hidden) hidden.value = JSON.stringify(partState.extraMarksAllocations);
    }

    function ensureCategoryQuestionCountsForPart(partState, selectedIds, totalQuestions) {
        const normalizedIds = selectedIds.map(String);
        const { minPerCategory } = getCategoryAllocationBounds(totalQuestions, normalizedIds.length);
        const structureKey = `${totalQuestions}|${[...normalizedIds].sort().join(',')}`;

        if (partState.categoryQuestionCountsKey === structureKey) {
            let needsRebuild = false;
            normalizedIds.forEach((cid) => {
                if (typeof partState.extraQuestionsAllocations[cid] === 'undefined') { needsRebuild = true; return; }
                if (toInt(partState.extraQuestionsAllocations[cid], 0) < minPerCategory) needsRebuild = true;
            });
            Object.keys(partState.extraQuestionsAllocations).forEach((cid) => {
                if (!normalizedIds.includes(cid)) { delete partState.extraQuestionsAllocations[cid]; needsRebuild = true; }
            });
            if (needsRebuild) {
                partState.extraQuestionsAllocations = buildEvenCategoryCounts(normalizedIds, totalQuestions);
                syncPartExtraQuestionsAllocationsHidden(partState);
            }
            return;
        }

        const existingIds = Object.keys(partState.extraQuestionsAllocations);
        const sameCategorySet = normalizedIds.length > 0
            && normalizedIds.length === existingIds.length
            && normalizedIds.every((cid) => Object.prototype.hasOwnProperty.call(partState.extraQuestionsAllocations, cid));
        const existingSum = existingIds.reduce((sum, cid) => sum + Math.max(0, toInt(partState.extraQuestionsAllocations[cid], 0)), 0);

        if (sameCategorySet && existingSum === totalQuestions && allocationsMeetMinimum(partState.extraQuestionsAllocations, normalizedIds, minPerCategory)) {
            partState.categoryQuestionCountsKey = structureKey;
            partState.extraQuestionsCategoryIds = normalizedIds.slice();
            syncPartExtraQuestionsAllocationsHidden(partState);
            return;
        }

        partState.extraQuestionsAllocations = buildEvenCategoryCounts(normalizedIds, totalQuestions);
        partState.categoryQuestionCountsKey = structureKey;
        partState.extraQuestionsCategoryIds = normalizedIds.slice();
        syncPartExtraQuestionsAllocationsHidden(partState);
    }

    function computeFixedCategoryDistributionForPart(partState) {
        const root = partState.root;
        const totalQuestions = Math.max(1, toInt(partField(root, 'total_questions')?.value, 1));
        const selectedIds = [...partState.selectedCategories];
        const selectedCount = selectedIds.length;

        if (!selectedCount) {
            partState.extraQuestionsAllocations = {};
            partState.extraQuestionsCategoryIds = [];
            partState.categoryQuestionCountsKey = '';
            syncPartExtraQuestionsAllocationsHidden(partState);
            return { totalQuestions, selectedCount: 0, base: 0, remainder: 0, minPerCategory: 0, maxPerCategory: 0, rows: [], totalAllocated: 0, isComplete: false };
        }

        ensureCategoryQuestionCountsForPart(partState, selectedIds, totalQuestions);

        const bounds = getCategoryAllocationBounds(totalQuestions, selectedCount);
        const rows = selectedIds.map((cid) => ({
            categoryId: cid,
            count: Math.max(bounds.minPerCategory, toInt(partState.extraQuestionsAllocations[String(cid)], bounds.minPerCategory)),
        }));
        const totalAllocated = rows.reduce((sum, r) => sum + r.count, 0);
        const meetsMinimum = rows.every((r) => r.count >= bounds.minPerCategory);

        partState.extraQuestionsCategoryIds = selectedIds.map(String);
        syncPartExtraQuestionsAllocationsHidden(partState);

        return {
            totalQuestions, selectedCount, base: bounds.base, remainder: bounds.remainder,
            minPerCategory: bounds.minPerCategory, maxPerCategory: bounds.maxPerCategory,
            rows, totalAllocated, isComplete: totalAllocated === totalQuestions && meetsMinimum,
        };
    }

    function ensureCategoryMarksCountsForPart(partState, selectedIds, totalMarks) {
        const normalizedIds = selectedIds.map(String);
        const { minPerCategory } = getCategoryAllocationBounds(totalMarks, normalizedIds.length);
        const structureKey = `${totalMarks}|${[...normalizedIds].sort().join(',')}`;

        if (partState.categoryMarksCountsKey === structureKey) {
            let needsRebuild = false;
            normalizedIds.forEach((cid) => {
                if (typeof partState.extraMarksAllocations[cid] === 'undefined') { needsRebuild = true; return; }
                if (toInt(partState.extraMarksAllocations[cid], 0) < minPerCategory) needsRebuild = true;
            });
            Object.keys(partState.extraMarksAllocations).forEach((cid) => {
                if (!normalizedIds.includes(cid)) { delete partState.extraMarksAllocations[cid]; needsRebuild = true; }
            });
            if (needsRebuild) {
                partState.extraMarksAllocations = buildEvenCategoryCounts(normalizedIds, totalMarks);
                syncPartExtraMarksAllocationsHidden(partState);
            }
            return;
        }

        const existingIds = Object.keys(partState.extraMarksAllocations);
        const sameCategorySet = normalizedIds.length > 0
            && normalizedIds.length === existingIds.length
            && normalizedIds.every((cid) => Object.prototype.hasOwnProperty.call(partState.extraMarksAllocations, cid));
        const existingSum = existingIds.reduce((sum, cid) => sum + Math.max(0, toInt(partState.extraMarksAllocations[cid], 0)), 0);

        if (sameCategorySet && existingSum === totalMarks && allocationsMeetMinimum(partState.extraMarksAllocations, normalizedIds, minPerCategory)) {
            partState.categoryMarksCountsKey = structureKey;
            syncPartExtraMarksAllocationsHidden(partState);
            return;
        }

        partState.extraMarksAllocations = buildEvenCategoryCounts(normalizedIds, totalMarks);
        partState.categoryMarksCountsKey = structureKey;
        syncPartExtraMarksAllocationsHidden(partState);
    }

    function computeFixedCategoryMarksDistributionForPart(partState) {
        const root = partState.root;
        const totalMarks = Math.max(1, toInt(partField(root, 'total_marks')?.value, 1));
        const selectedIds = [...partState.selectedCategories];
        const selectedCount = selectedIds.length;

        if (!selectedCount) {
            partState.extraMarksAllocations = {};
            partState.categoryMarksCountsKey = '';
            syncPartExtraMarksAllocationsHidden(partState);
            return { totalMarks, selectedCount: 0, base: 0, remainder: 0, minPerCategory: 0, maxPerCategory: 0, totalAllocated: 0, rows: [], isComplete: false };
        }

        ensureCategoryMarksCountsForPart(partState, selectedIds, totalMarks);

        const bounds = getCategoryAllocationBounds(totalMarks, selectedCount);
        const rows = selectedIds.map((cid) => {
            const marks = Math.max(bounds.minPerCategory, toInt(partState.extraMarksAllocations[String(cid)], bounds.minPerCategory));
            return { categoryId: cid, marks, extraMarks: Math.max(0, marks - bounds.base) };
        });
        const totalAllocated = rows.reduce((sum, r) => sum + r.marks, 0);
        const meetsMinimum = rows.every((r) => r.marks >= bounds.minPerCategory);

        syncPartExtraMarksAllocationsHidden(partState);

        return {
            totalMarks, selectedCount, base: bounds.base, remainder: bounds.remainder,
            minPerCategory: bounds.minPerCategory, maxPerCategory: bounds.maxPerCategory,
            totalAllocated, rows, isComplete: totalAllocated === totalMarks && meetsMinimum,
        };
    }

    function renderPartFixedCategoryDistribution(partState) {
        const root = partState.root;
        const outerWrap = root.querySelector('[data-field-wrap="fixed_category_distribution"]');
        const innerWrap = root.querySelector('[data-field-wrap="extra_questions_allocations"]');
        const listEl = root.querySelector('[data-field-ui="extra_questions_allocation_list"]');
        const helpEl = root.querySelector('[data-field-help="fixed_distribution"]');
        const allocatedEl = root.querySelector('[data-field-ui="allocated_count"]');
        const remainingEl = root.querySelector('[data-field-ui="remaining_count"]');
        if (!outerWrap) return;

        const enabled = Boolean(partField(root, 'fix_category_questions')?.checked);
        if (!enabled || partState.selectedCategories.size === 0) {
            outerWrap.hidden = true;
            if (innerWrap) innerWrap.hidden = true;
            if (listEl) { listEl.innerHTML = ''; listEl.dataset.structureKey = ''; }
            partState.categoryQuestionCountsKey = '';
            return;
        }

        const distribution = computeFixedCategoryDistributionForPart(partState);
        outerWrap.hidden = false;
        if (innerWrap) innerWrap.hidden = false;

        let helperText = `Set how many questions each category contributes. Totals must equal ${distribution.totalQuestions}.`;
        helperText += ` Equally distributed with a minimum of ${distribution.minPerCategory} question(s) per category`;
        if (distribution.remainder > 0) helperText += ' (leftover +1 from the first category onward)';
        helperText += '.';
        if (helpEl) helpEl.textContent = helperText;

        if (allocatedEl) allocatedEl.textContent = String(distribution.totalAllocated);
        if (remainingEl) remainingEl.textContent = String(distribution.totalQuestions);

        if (!listEl) return;

        const minAllowed = distribution.minPerCategory;
        const maxAllowed = distribution.maxPerCategory;
        const structureKey = `${distribution.rows.map((r) => String(r.categoryId)).join(',')}|${minAllowed}|${maxAllowed}`;

        if (listEl.dataset.structureKey !== structureKey) {
            listEl.innerHTML = distribution.rows.map((row) => {
                const name = escapeHtml(getCategoryLabelById(row.categoryId));
                const count = Math.max(minAllowed, toInt(partState.extraQuestionsAllocations[String(row.categoryId)], minAllowed));
                return `
                    <div>
                        <label class="exam-label">${name}</label>
                        <input type="number" class="panel-input category-question-count-input" data-category-id="${escapeHtml(String(row.categoryId))}" value="${count}" min="${minAllowed}" max="${maxAllowed}" step="1">
                    </div>
                `;
            }).join('');
            listEl.dataset.structureKey = structureKey;
        } else {
            listEl.querySelectorAll('.category-question-count-input').forEach((input) => {
                input.setAttribute('min', String(minAllowed));
                input.setAttribute('max', String(maxAllowed));
                if (document.activeElement === input) return;
                const cid = String(input.dataset.categoryId || '');
                const val = Math.max(minAllowed, toInt(partState.extraQuestionsAllocations[cid], minAllowed));
                if (String(input.value) !== String(val)) input.value = String(val);
            });
        }

        syncPartExtraQuestionsAllocationsHidden(partState);
    }

    function renderPartFixedCategoryMarksDistribution(partState) {
        const root = partState.root;
        const wrap = root.querySelector('[data-field-wrap="fixed_category_marks_distribution"]');
        const listEl = root.querySelector('[data-field-ui="extra_marks_allocation_list"]');
        const helpEl = root.querySelector('[data-field-help="fixed_category_marks"]');
        const allocatedEl = root.querySelector('[data-field-ui="marks_allocated_count"]');
        const remainingEl = root.querySelector('[data-field-ui="marks_remaining_count"]');
        if (!wrap) return;

        const enabled = Boolean(partField(root, 'fix_category_marks')?.checked);
        const distribution = computeFixedCategoryMarksDistributionForPart(partState);

        if (!enabled || distribution.selectedCount === 0) {
            wrap.hidden = true;
            if (listEl) { listEl.innerHTML = ''; listEl.dataset.structureKey = ''; }
            partState.categoryMarksCountsKey = '';
            return;
        }

        wrap.hidden = false;

        let helperText = `Set how many marks each category contributes. Totals must equal ${distribution.totalMarks}.`;
        helperText += ` Equally distributed with a minimum of ${distribution.minPerCategory} mark(s) per category`;
        if (distribution.remainder > 0) helperText += ' (leftover +1 from the first category onward)';
        helperText += '.';
        if (helpEl) helpEl.textContent = helperText;

        if (allocatedEl) allocatedEl.textContent = String(distribution.totalAllocated);
        if (remainingEl) remainingEl.textContent = String(distribution.totalMarks);

        if (!listEl) return;

        const minAllowed = distribution.minPerCategory;
        const maxAllowed = distribution.maxPerCategory;
        const structureKey = `${distribution.rows.map((r) => String(r.categoryId)).join(',')}|${minAllowed}|${maxAllowed}`;

        if (listEl.dataset.structureKey !== structureKey) {
            listEl.innerHTML = distribution.rows.map((row) => {
                const name = escapeHtml(getCategoryLabelById(row.categoryId));
                const marks = Math.max(minAllowed, toInt(partState.extraMarksAllocations[String(row.categoryId)], minAllowed));
                return `
                    <div>
                        <label class="exam-label">${name}</label>
                        <input type="number" class="panel-input category-marks-count-input" data-category-id="${escapeHtml(String(row.categoryId))}" value="${marks}" min="${minAllowed}" max="${maxAllowed}" step="1">
                    </div>
                `;
            }).join('');
            listEl.dataset.structureKey = structureKey;
        } else {
            listEl.querySelectorAll('.category-marks-count-input').forEach((input) => {
                input.setAttribute('min', String(minAllowed));
                input.setAttribute('max', String(maxAllowed));
                if (document.activeElement === input) return;
                const cid = String(input.dataset.categoryId || '');
                const val = Math.max(minAllowed, toInt(partState.extraMarksAllocations[cid], minAllowed));
                if (String(input.value) !== String(val)) input.value = String(val);
            });
        }

        syncPartExtraMarksAllocationsHidden(partState);
    }

    function handlePartAllocationInput(partState, event) {
        const qInput = event.target.closest('.category-question-count-input');
        if (qInput) {
            const cid = String(qInput.dataset.categoryId || '');
            const fieldMin = Math.max(0, toInt(qInput.getAttribute('min'), 0));
            const fieldMax = Math.max(fieldMin, toInt(qInput.getAttribute('max'), fieldMin));
            let val = toInt(qInput.value, fieldMin);
            if (Number.isNaN(val) || val < fieldMin) val = fieldMin;
            if (val > fieldMax) val = fieldMax;
            qInput.value = String(val);
            partState.extraQuestionsAllocations[cid] = val;
            syncPartExtraQuestionsAllocationsHidden(partState);
            updatePartUi(partState);
            updateExamSummary();
            return true;
        }

        const mInput = event.target.closest('.category-marks-count-input');
        if (mInput) {
            const cid = String(mInput.dataset.categoryId || '');
            const fieldMin = Math.max(0, toInt(mInput.getAttribute('min'), 0));
            const fieldMax = Math.max(fieldMin, toInt(mInput.getAttribute('max'), fieldMin));
            let val = toInt(mInput.value, fieldMin);
            if (Number.isNaN(val) || val < fieldMin) val = fieldMin;
            if (val > fieldMax) val = fieldMax;
            mInput.value = String(val);
            partState.extraMarksAllocations[cid] = val;
            syncPartExtraMarksAllocationsHidden(partState);
            updatePartUi(partState);
            updateExamSummary();
            return true;
        }

        return false;
    }

    // ── Conditional field wraps (pool / fixed questions / paper sets) ─

    function setWrapHidden(root, fieldKey, hidden) {
        const wrap = root.querySelector(`[data-field-wrap="${fieldKey}"]`);
        if (wrap) wrap.hidden = hidden;
    }

    function updatePartConditionalWraps(partState) {
        const root = partState.root;
        const usePool = Boolean(partField(root, 'use_question_pool')?.checked);
        const fixedQEl = partField(root, 'fixed_questions');
        const fixedPaperSet = Boolean(partField(root, 'fixed_paper_set')?.checked);
        const totalQuestions = Math.max(1, toInt(partField(root, 'total_questions')?.value, 1));

        setWrapHidden(root, 'maximum_questions', !usePool);
        const maxQEl = partField(root, 'maximum_questions');
        if (maxQEl) {
            maxQEl.min = String(totalQuestions + 1);
            if (usePool) {
                const currentMax = toInt(maxQEl.value, 0);
                if (currentMax <= totalQuestions) {
                    maxQEl.value = String(Math.max(totalQuestions + 1, 50));
                }
            } else {
                maxQEl.value = '';
            }
        }
        const helpEl = root.querySelector('[data-field-help="maximum_questions"]');
        if (helpEl) helpEl.textContent = `Enter at least ${totalQuestions + 1}. Each candidate will receive ${totalQuestions} question(s).`;

        // Question Pool and Fixed Questions are mutually exclusive — hide Fixed Questions while pool is on.
        setWrapHidden(root, 'fixed_questions', usePool);
        if (usePool && fixedQEl) {
            fixedQEl.checked = false;
            fixedQEl.disabled = true;
        } else if (fixedQEl) {
            fixedQEl.disabled = false;
        }

        setWrapHidden(root, 'paper_sets', !fixedPaperSet);
        const paperSetsEl = partField(root, 'paper_sets');
        if (paperSetsEl) {
            paperSetsEl.min = '1';
            paperSetsEl.max = String(totalQuestions);
            if (!fixedPaperSet) paperSetsEl.value = '1';
        }

        const supportsOptionShuffle = state.selectedExamFormat instanceof Set
            && (state.selectedExamFormat.has('mcq') || state.selectedExamFormat.has('multi_select'));
        setWrapHidden(root, 'shuffle_options', !supportsOptionShuffle);
        const shuffleOptionsEl = partField(root, 'shuffle_options');
        if (shuffleOptionsEl) {
            shuffleOptionsEl.disabled = !supportsOptionShuffle;
            if (!supportsOptionShuffle) shuffleOptionsEl.checked = false;
        }
    }

    function updatePartMetaSummary(partState) {
        const root = partState.root;
        const el = root.querySelector('[data-field-ui="part_meta_summary"]');
        if (!el) return;

        const totalQuestions = toInt(partField(root, 'total_questions')?.value, 0);
        const totalMarks = toInt(partField(root, 'total_marks')?.value, 0);
        const categories = partState.selectedCategories.size;
        el.textContent = `${totalQuestions} question(s) • ${totalMarks} mark(s) • ${categories} categor${categories === 1 ? 'y' : 'ies'} • ${partState.selectedQuestions.size} selected`;
    }

    function maybeScheduleQuestionBankSync(partState) {
        const root = partState.root;
        const categoriesKey = [...partState.selectedCategories].sort().join(',');
        const marksKey = [...partState.selectedMarks].sort().join(',');
        const formatsKey = [...state.selectedExamFormat].sort().join(',');
        const signature = `${categoriesKey}|${marksKey}|${formatsKey}`;

        if (partState.lastFetchedKey !== signature) {
            partState.lastFetchedKey = signature;
            scheduleQuestionBankSyncForPart(partState, 200);
        }
    }

    function updatePartUi(partState) {
        if (!partState.root) return;
        updatePartConditionalWraps(partState);
        renderPartDistributionTypes(partState);
        renderPartQuestionMarks(partState);
        renderPartCategorySelector(partState);
        renderPartMarksCalculation(partState);
        renderPartFixedCategoryDistribution(partState);
        renderPartFixedCategoryMarksDistribution(partState);
        maybeScheduleQuestionBankSync(partState);
        renderQuestionBankCardsForPart(partState);
        updatePartMetaSummary(partState);
    }

    // ────────────────────────────────────────────────────────────────
    // PART QUESTION BANK
    // ────────────────────────────────────────────────────────────────

    function getQuestionSelectionLimitsForPart(partState) {
        const root = partState.root;
        const totalQuestions = Math.max(1, toInt(partField(root, 'total_questions')?.value, 1));
        const usePool = Boolean(partField(root, 'use_question_pool')?.checked);
        const fixedQuestions = Boolean(partField(root, 'fixed_questions')?.checked);

        if (usePool) {
            const maximumQuestions = Math.max(totalQuestions + 1, toInt(partField(root, 'maximum_questions')?.value, totalQuestions + 1));
            return { viewOnly: false, min: totalQuestions, max: maximumQuestions, exact: null, target: maximumQuestions };
        }
        if (fixedQuestions) {
            return { viewOnly: false, min: totalQuestions, max: totalQuestions, exact: totalQuestions, target: totalQuestions };
        }
        return { viewOnly: true, min: 0, max: 0, exact: null, target: 0 };
    }

    function isQuestionPoolEnabledForPart(partState) {
        return Boolean(partField(partState.root, 'use_question_pool')?.checked);
    }

    function isManualQuestionSelectionEnabledForPart(partState) {
        return isQuestionPoolEnabledForPart(partState) || Boolean(partField(partState.root, 'fixed_questions')?.checked);
    }

    function questionMatchesSelectedCategory(question, categoryId) {
        const questionCategoryId = String(question?.categoryId || '');
        const selectedCategoryId = String(categoryId || '');
        if (!questionCategoryId || !selectedCategoryId) return false;
        if (questionCategoryId === selectedCategoryId) return true;
        const descendants = getAllDescendantIds(selectedCategoryId, state.categoryHierarchyIndex);
        return descendants.includes(questionCategoryId);
    }

    function resolveQuestionDisplayCategory(question, selectedCategoryIds) {
        const selectedCategorySet = new Set(selectedCategoryIds.map(String));
        const questionCategoryId = String(question?.categoryId || '');
        if (selectedCategorySet.has(questionCategoryId)) return questionCategoryId;
        for (const selectedId of selectedCategoryIds) {
            if (questionMatchesSelectedCategory(question, selectedId)) return String(selectedId);
        }
        return null;
    }

    function getQuestionByIdForPart(partState, questionId) {
        const key = String(questionId);
        return partState.questionBank.find((item) => String(item.id) === key)
            || partState.selectedQuestionCache[key]
            || null;
    }

    function rememberSelectedQuestionForPart(partState, question) {
        if (!question || question.id === undefined || question.id === null) return;
        partState.selectedQuestionCache[String(question.id)] = question;
    }

    function pruneSelectedQuestionsToVisibleBankForPart(partState) {
        if (!isManualQuestionSelectionEnabledForPart(partState)) {
            partState.selectedQuestions.clear();
            partState.selectedQuestionCache = {};
            return;
        }
        const limits = getQuestionSelectionLimitsForPart(partState);
        if (limits.max > 0 && partState.selectedQuestions.size > limits.max) {
            partState.selectedQuestions = new Set([...partState.selectedQuestions].slice(0, limits.max));
        }
        Object.keys(partState.selectedQuestionCache).forEach((id) => {
            if (!partState.selectedQuestions.has(Number(id)) && !partState.selectedQuestions.has(id)) {
                delete partState.selectedQuestionCache[id];
            }
        });
    }

    function updateQuestionSelectionControlsVisibilityForPart(partState) {
        const root = partState.root;
        const limits = getQuestionSelectionLimitsForPart(partState);
        const selectionEnabled = !limits.viewOnly;

        const randomBtn = root.querySelector('[data-part-action="random_select"]');
        if (randomBtn) randomBtn.hidden = !selectionEnabled;

        const rangeEl = root.querySelector('[data-field-ui="global_selection_range"]');
        if (rangeEl) {
            if (selectionEnabled && isQuestionPoolEnabledForPart(partState)) {
                rangeEl.hidden = false;
                rangeEl.textContent = `(select ${limits.min}–${limits.max})`;
            } else {
                rangeEl.hidden = true;
                rangeEl.textContent = '';
            }
        }
    }

    function computeQuestionShortagesForPart(partState) {
        const root = partState.root;
        const shortages = [];
        const selectedCategories = [...partState.selectedCategories];
        if (!selectedCategories.length) return shortages;

        const limits = getQuestionSelectionLimitsForPart(partState);
        const requiredTotal = limits.viewOnly ? Math.max(1, toInt(partField(root, 'total_questions')?.value, 1)) : limits.max;
        const countedTotal = Object.values(partState.categoryCounts || {}).reduce((sum, value) => sum + Number(value || 0), 0);
        const availableTotal = countedTotal > 0 ? countedTotal : Number(partState.questionBankMeta?.total ?? 0);

        if (availableTotal < requiredTotal) {
            shortages.push({ required: requiredTotal, available: availableTotal, missing: requiredTotal - availableTotal });
        }
        return shortages;
    }

    function renderQuestionShortagesForPart(partState) {
        const el = partState.root.querySelector('[data-field-ui="question_bank_shortages"]');
        if (!el) return;

        const shortages = computeQuestionShortagesForPart(partState);
        if (!shortages.length) {
            el.hidden = true;
            el.innerHTML = '';
            return;
        }

        el.hidden = false;
        el.innerHTML = `
            <div class="question-bank-shortages__title">Question shortages detected</div>
            <ul>${shortages.map((item) => `<li>Need ${item.missing} more matching question(s) overall (available ${item.available} / required ${item.required}).</li>`).join('')}</ul>
        `;
    }

    function syncPartQuestionIdsHidden(partState) {
        const hidden = partField(partState.root, 'question_ids');
        if (!hidden) return;
        const limits = getQuestionSelectionLimitsForPart(partState);
        hidden.value = JSON.stringify(
            limits.viewOnly ? [] : [...partState.selectedQuestions].map((id) => Number(id)).filter((id) => id > 0)
        );
    }

    function renderQuestionBankCardsForPart(partState) {
        const root = partState.root;
        const cardsEl = root.querySelector('[data-question-category-cards]');
        const feedbackEl = root.querySelector('[data-question-bank-feedback]');
        if (!cardsEl || !feedbackEl) return;

        const selectedCategoryIds = [...partState.selectedCategories].map(String);
        const hasMarksFilter = partState.selectedMarks.size > 0;
        const limits = getQuestionSelectionLimitsForPart(partState);
        const selectionEnabled = !limits.viewOnly;
        const totalQuestionsAllowed = limits.max;
        const fixedPerCategory = Boolean(partField(root, 'fix_category_questions')?.checked) && !isQuestionPoolEnabledForPart(partState);

        pruneSelectedQuestionsToVisibleBankForPart(partState);
        updateQuestionSelectionControlsVisibilityForPart(partState);
        renderQuestionShortagesForPart(partState);
        syncPartQuestionIdsHidden(partState);

        let fixedDistribution = { rows: [] };
        if (fixedPerCategory) fixedDistribution = computeFixedCategoryDistributionForPart(partState);

        const bankWithSelected = [...partState.questionBank];
        Object.values(partState.selectedQuestionCache || {}).forEach((question) => {
            if (!bankWithSelected.some((item) => String(item.id) === String(question.id))) bankWithSelected.push(question);
        });

        const byCategory = new Map();
        for (const q of bankWithSelected) {
            const targetCategoryId = resolveQuestionDisplayCategory(q, selectedCategoryIds);
            if (!targetCategoryId) continue;
            if (!byCategory.has(targetCategoryId)) byCategory.set(targetCategoryId, []);
            byCategory.get(targetCategoryId).push(q);
        }

        const totalSelectedGlobal = partState.selectedQuestions.size;
        const globalSelectedCountEl = root.querySelector('[data-field-ui="global_selected_count"]');
        const globalAllowedCountEl = root.querySelector('[data-field-ui="global_allowed_count"]');
        if (globalSelectedCountEl) globalSelectedCountEl.textContent = totalSelectedGlobal;
        if (globalAllowedCountEl) globalAllowedCountEl.textContent = selectionEnabled ? String(totalQuestionsAllowed) : '0';

        const globalLimitReached = selectionEnabled && totalSelectedGlobal >= totalQuestionsAllowed;

        if (!selectedCategoryIds.length) {
            cardsEl.innerHTML = '';
            feedbackEl.textContent = 'Select categories above to load the question bank.';
            updateQuestionBankLoadMetaForPart(partState);
            return;
        }

        cardsEl.innerHTML = selectedCategoryIds.map((categoryId) => {
            const categoryKey = String(categoryId);
            const categoryName = getCategoryLabelById(categoryKey);
            const questions = byCategory.get(categoryKey) || [];
            const expanded = partState.expandedCards.has(categoryKey);
            const serverCount = Number(partState.categoryCounts[categoryKey] ?? 0);
            const loadState = partState.categoryLoadState[categoryKey] || { status: 'idle', has_more: false };
            const loadedCount = questions.length;

            let categoryAllowedLimit = totalQuestionsAllowed;
            if (fixedPerCategory) {
                const row = fixedDistribution.rows.find((r) => String(r.categoryId) === categoryKey);
                categoryAllowedLimit = row ? row.count : 0;
            }

            const selectedInCategory = [...partState.selectedQuestions].filter((questionId) => {
                const question = getQuestionByIdForPart(partState, questionId);
                return question && questionMatchesSelectedCategory(question, categoryKey);
            }).length;
            const categoryLimitReached = selectionEnabled && fixedPerCategory ? selectedInCategory >= categoryAllowedLimit : false;

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
                            <button type="button" class="panel-button-secondary panel-button--small" data-action="load-category-questions" data-category-id="${escapeHtml(categoryKey)}">Load questions</button>
                        </div>
                    </li>
                `;
            } else if (loadState.status === 'error') {
                questionsList = `
                    <li class="question-accordion__empty">
                        Could not load questions.
                        <div style="margin-top:0.75rem;">
                            <button type="button" class="panel-button-secondary panel-button--small" data-action="load-category-questions" data-category-id="${escapeHtml(categoryKey)}">Retry</button>
                        </div>
                    </li>
                `;
            } else if (loadedCount === 0) {
                questionsList = `<li class="question-accordion__empty">No questions found for this category${hasMarksFilter ? ' matching the selected marks filter' : ''}.</li>`;
            } else {
                questionsList = questions.map((question) => {
                    const isSelected = partState.selectedQuestions.has(question.id)
                        || partState.selectedQuestions.has(String(question.id))
                        || partState.selectedQuestions.has(Number(question.id));
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
                }).join('');

                if (loadState.has_more) {
                    questionsList += `
                        <li class="question-accordion__empty">
                            <button type="button" class="panel-button-secondary panel-button--small" data-action="load-more-category-questions" data-category-id="${escapeHtml(categoryKey)}">Load more (${loadedCount} of ${serverCount})</button>
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
                    <button type="button" class="question-accordion__header" data-action="toggle-expand" data-category-id="${escapeHtml(categoryKey)}" aria-expanded="${expanded ? 'true' : 'false'}">
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
        }).join('');

        const modeHint = limits.viewOnly
            ? ' View-only mode: enable Fixed Questions or Question Pool to select specific questions.'
            : (isQuestionPoolEnabledForPart(partState)
                ? ` Select between ${limits.min} and ${limits.max} questions for the pool.`
                : ` Select exactly ${limits.exact} question(s).`);

        const totalAvailable = Object.values(partState.categoryCounts || {}).reduce((sum, n) => sum + Number(n || 0), 0);
        const loadedRows = partState.questionBank.length;
        feedbackEl.textContent = `${totalAvailable} matching question(s) across ${selectedCategoryIds.length} categor${selectedCategoryIds.length === 1 ? 'y' : 'ies'}. Expand a category or click Load questions to fetch rows (${loadedRows} loaded).${modeHint}`;
        updateQuestionBankLoadMetaForPart(partState);
    }

    function handleQuestionBankClick(partState, event) {
        const root = partState.root;
        const bankSection = root.querySelector('[data-question-bank]');
        if (!bankSection || !bankSection.contains(event.target)) return;

        const loadCategoryBtn = event.target.closest('[data-action="load-category-questions"]');
        const loadMoreCategoryBtn = event.target.closest('[data-action="load-more-category-questions"]');
        const expandButton = event.target.closest('[data-action="toggle-expand"]');
        const addButton = event.target.closest('[data-action="add-question"]');
        const randomSelectBtn = event.target.closest('[data-action="random-select-category"]');

        if (loadCategoryBtn) {
            const categoryId = String(loadCategoryBtn.dataset.categoryId || '');
            if (!categoryId) return;
            partState.expandedCards.add(categoryId);
            loadCategoryQuestionsForPart(partState, categoryId, { append: false });
            return;
        }
        if (loadMoreCategoryBtn) {
            const categoryId = String(loadMoreCategoryBtn.dataset.categoryId || '');
            if (!categoryId) return;
            loadCategoryQuestionsForPart(partState, categoryId, { append: true });
            return;
        }
        if (expandButton) {
            const categoryId = String(expandButton.dataset.categoryId || '');
            if (partState.expandedCards.has(categoryId)) {
                partState.expandedCards.delete(categoryId);
                renderQuestionBankCardsForPart(partState);
            } else {
                partState.expandedCards.add(categoryId);
                renderQuestionBankCardsForPart(partState);
                const loadState = partState.categoryLoadState[categoryId];
                if (!loadState || loadState.status === 'idle') {
                    loadCategoryQuestionsForPart(partState, categoryId, { append: false });
                }
            }
            return;
        }
        if (addButton) {
            openAddQuestionModalForPart(partState, addButton.dataset.categoryId || '');
            return;
        }
        if (randomSelectBtn) {
            randomSelectCategoryForPart(partState, randomSelectBtn.dataset.categoryId);
        }
    }

    function handleQuestionBankChange(partState, event) {
        const checkbox = event.target.closest('.question-checkbox');
        if (!checkbox) return;

        const questionId = Number(checkbox.dataset.questionId);
        if (checkbox.checked) {
            partState.selectedQuestions.add(questionId);
            const question = getQuestionByIdForPart(partState, questionId);
            if (question) rememberSelectedQuestionForPart(partState, question);
        } else {
            partState.selectedQuestions.delete(questionId);
            delete partState.selectedQuestionCache[String(questionId)];
        }

        renderQuestionBankCardsForPart(partState);
        updatePartMetaSummary(partState);
        updateExamSummary();
        updateWorkflowAndSnapshot();
    }

    function scheduleQuestionBankSyncForPart(partState, delayMs = 350) {
        window.clearTimeout(partState.syncTimer);
        partState.syncTimer = window.setTimeout(() => {
            syncQuestionBankFromServerForPart(partState);
        }, delayMs);
    }

    function buildSharedQuestionBankParamsForPart(partState) {
        const marks = [...partState.selectedMarks].join(',');
        const formats = [...state.selectedExamFormat].join(',');
        const keyword = cleanText(partState.root.querySelector('[data-question-search-input]')?.value || '');
        return { marks, formats, keyword };
    }

    function mergeQuestionBankRowsForPart(partState, rows, { append = false, replaceCategoryId = null } = {}) {
        const incoming = Array.isArray(rows) ? rows : [];
        if (replaceCategoryId) {
            const keep = partState.questionBank.filter((q) => !questionMatchesSelectedCategory(q, replaceCategoryId));
            partState.questionBank = keep.concat(incoming);
            return;
        }
        if (!append) {
            partState.questionBank = incoming.slice();
            return;
        }
        const seen = new Set(partState.questionBank.map((q) => String(q.id)));
        incoming.forEach((question) => {
            const key = String(question.id);
            if (seen.has(key)) return;
            seen.add(key);
            partState.questionBank.push(question);
        });
    }

    function updateQuestionBankLoadMetaForPart(partState) {
        const root = partState.root;
        const loaded = partState.questionBank.length;
        const total = Object.values(partState.categoryCounts || {}).reduce((sum, n) => sum + Number(n || 0), 0);
        const metaEl = root.querySelector('[data-question-bank-load-meta]');
        if (metaEl) {
            metaEl.textContent = total > 0
                ? `Counts ready: ${total} matching. Loaded rows: ${loaded}.`
                : (loaded > 0 ? `Loaded ${loaded} question(s).` : '');
        }
        const loadMoreWrap = root.querySelector('[data-field-wrap="question_bank_load_more"]');
        if (loadMoreWrap) loadMoreWrap.hidden = true;
    }

    function resetCategoryQuestionLoadsForPart(partState) {
        partState.questionBank = [];
        partState.categoryLoadState = {};
        [...partState.selectedCategories].forEach((categoryId) => {
            partState.categoryLoadState[String(categoryId)] = { status: 'idle', next_cursor: null, has_more: false, requestSeq: 0 };
        });
    }

    async function fetchSelectedQuestionMetadataForPart(partState, ids) {
        const endpoints = window.examCreateConfig?.endpoints || {};
        if (!endpoints.questionBank || !ids?.length) return;
        const missing = ids.filter((id) => !getQuestionByIdForPart(partState, id));
        if (!missing.length) return;

        const url = new URL(endpoints.questionBank, window.location.origin);
        url.searchParams.set('ids', missing.join(','));
        url.searchParams.set('per_page', String(Math.min(100, missing.length)));

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            rows.forEach((question) => rememberSelectedQuestionForPart(partState, question));
            mergeQuestionBankRowsForPart(partState, rows, { append: true });
        } catch (err) {
            console.error('Failed to hydrate selected questions', err);
        }
    }

    async function syncQuestionBankCountsForPart(partState) {
        const endpoints = window.examCreateConfig?.endpoints || {};
        const countsUrl = endpoints.questionBankCounts || endpoints.questionBank;
        if (!countsUrl) return;

        const categoryIds = [...partState.selectedCategories].map(String);
        if (!categoryIds.length) {
            partState.categoryCounts = {};
            partState.questionBankMeta = { total: 0 };
            resetCategoryQuestionLoadsForPart(partState);
            updateQuestionBankLoadMetaForPart(partState);
            renderQuestionBankCardsForPart(partState);
            return;
        }

        if (partState.countsAbortController) partState.countsAbortController.abort();
        partState.countsAbortController = new AbortController();

        const { marks, formats, keyword } = buildSharedQuestionBankParamsForPart(partState);
        const url = new URL(countsUrl, window.location.origin);
        url.searchParams.set('categories', categoryIds.join(','));
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);
        if (keyword) url.searchParams.set('q', keyword);

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: partState.countsAbortController.signal,
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            const counts = payload?.data && typeof payload.data === 'object' ? payload.data : {};
            partState.categoryCounts = {};
            categoryIds.forEach((id) => { partState.categoryCounts[id] = Number(counts[id] ?? counts[String(id)] ?? 0); });
            partState.questionBankMeta = {
                total: Number(payload?.meta?.total ?? Object.values(partState.categoryCounts).reduce((s, n) => s + Number(n || 0), 0)),
            };
        } catch (err) {
            if (err?.name === 'AbortError') return;
            console.error('Failed to load question bank counts', err);
            window.EmsToast?.error('Failed to load question counts.');
        } finally {
            partState.countsAbortController = null;
            updateQuestionBankLoadMetaForPart(partState);
        }
    }

    async function loadCategoryQuestionsForPart(partState, categoryId, { append = false } = {}) {
        const endpoints = window.examCreateConfig?.endpoints || {};
        if (!endpoints.questionBank) return;
        const categoryKey = String(categoryId || '');
        if (!categoryKey) return;

        const current = partState.categoryLoadState[categoryKey] || { status: 'idle', next_cursor: null, has_more: false, requestSeq: 0 };
        if (append && !current.has_more) return;

        const requestSeq = (current.requestSeq || 0) + 1;
        partState.categoryLoadState[categoryKey] = { ...current, status: 'loading', requestSeq };
        renderQuestionBankCardsForPart(partState);

        const { marks, formats, keyword } = buildSharedQuestionBankParamsForPart(partState);
        const url = new URL(endpoints.questionBank, window.location.origin);
        url.searchParams.set('categories', categoryKey);
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);
        if (keyword) url.searchParams.set('q', keyword);
        url.searchParams.set('per_page', '50');
        if (append && current.next_cursor) url.searchParams.set('cursor', String(current.next_cursor));

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            if ((partState.categoryLoadState[categoryKey]?.requestSeq || 0) !== requestSeq) return;

            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            const meta = payload?.meta && typeof payload.meta === 'object' ? payload.meta : {};

            if (append) mergeQuestionBankRowsForPart(partState, rows, { append: true });
            else mergeQuestionBankRowsForPart(partState, rows, { replaceCategoryId: categoryKey });

            rows.forEach((question) => {
                if (partState.selectedQuestions.has(question.id) || partState.selectedQuestions.has(String(question.id)) || partState.selectedQuestions.has(Number(question.id))) {
                    rememberSelectedQuestionForPart(partState, question);
                }
            });

            partState.categoryLoadState[categoryKey] = {
                status: 'loaded', next_cursor: meta.next_cursor ?? null, has_more: Boolean(meta.has_more), requestSeq,
            };
            if (typeof meta.total === 'number') partState.categoryCounts[categoryKey] = Number(meta.total);

            renderQuestionBankCardsForPart(partState);
            updatePartMetaSummary(partState);
            updateExamSummary();
            updateWorkflowAndSnapshot();
        } catch (err) {
            console.error('Failed to load category questions', err);
            partState.categoryLoadState[categoryKey] = { status: 'error', next_cursor: null, has_more: false, requestSeq };
            renderQuestionBankCardsForPart(partState);
            window.EmsToast?.error('Failed to load questions for ' + getCategoryLabelById(categoryKey));
        }
    }

    function hydratePartSelectedQuestions(partState) {
        partState.hasHydratedSelectedQuestions = true;
        if (!Array.isArray(partState.hydratedQuestionIds) || !partState.hydratedQuestionIds.length) return;

        partState.hydratedQuestionIds.forEach((questionId) => {
            partState.selectedQuestions.add(questionId);
            const question = getQuestionByIdForPart(partState, questionId);
            if (question) rememberSelectedQuestionForPart(partState, question);
        });

        syncPartQuestionIdsHidden(partState);
    }

    async function syncQuestionBankFromServerForPart(partState, { refreshBtn = null } = {}) {
        const feedbackEl = partState.root.querySelector('[data-question-bank-feedback]');
        if (feedbackEl) feedbackEl.textContent = 'Loading question counts...';
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.classList.add('is-loading');
            refreshBtn.setAttribute('aria-busy', 'true');
        }

        try {
            resetCategoryQuestionLoadsForPart(partState);
            await syncQuestionBankCountsForPart(partState);
            await fetchSelectedQuestionMetadataForPart(partState, [...partState.selectedQuestions]);
            if (state.isEditMode && !partState.hasHydratedSelectedQuestions) {
                await fetchSelectedQuestionMetadataForPart(partState, partState.hydratedQuestionIds || []);
                hydratePartSelectedQuestions(partState);
            }
            renderQuestionBankCardsForPart(partState);
            updatePartMetaSummary(partState);
            updateExamSummary();
            updateWorkflowAndSnapshot();
        } finally {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.classList.remove('is-loading');
                refreshBtn.removeAttribute('aria-busy');
            }
            updateQuestionBankLoadMetaForPart(partState);
        }
    }

    function refreshPartQuestionBank(partState, btnEl) {
        return syncQuestionBankFromServerForPart(partState, { refreshBtn: btnEl });
    }

    function showQuestionSelectionWarningForPart(partState, message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({ icon: 'warning', title: 'Insufficient Questions', text: message });
            return;
        }
        if (window.EmsToast && typeof window.EmsToast.warning === 'function') {
            window.EmsToast.warning(message);
            return;
        }
        const el = partState.root.querySelector('[data-question-bank-feedback]');
        if (el) {
            el.textContent = message;
            el.classList.add('is-invalid');
        }
    }

    async function randomSelectCategoryForPart(partState, categoryId) {
        if (!isManualQuestionSelectionEnabledForPart(partState)) {
            showQuestionSelectionWarningForPart(partState, 'Enable Fixed Questions or Question Pool before selecting questions.');
            return;
        }

        const root = partState.root;
        const limits = getQuestionSelectionLimitsForPart(partState);
        const totalQuestionsAllowed = limits.max;
        const fixedPerCategory = Boolean(partField(root, 'fix_category_questions')?.checked) && !isQuestionPoolEnabledForPart(partState);
        const endpoints = window.examCreateConfig?.endpoints || {};
        const endpoint = endpoints.questionBankRandom || endpoints.questionBank;
        if (!endpoint) {
            showQuestionSelectionWarningForPart(partState, 'Random selection endpoint is unavailable.');
            return;
        }

        let categoryAllowedLimit = totalQuestionsAllowed;
        if (fixedPerCategory) {
            const fixedDistribution = computeFixedCategoryDistributionForPart(partState);
            const row = fixedDistribution.rows.find((r) => String(r.categoryId) === String(categoryId));
            categoryAllowedLimit = row ? row.count : 0;
        } else {
            const selectedOutsideCategory = [...partState.selectedQuestions].filter((questionId) => {
                const question = getQuestionByIdForPart(partState, questionId);
                return question && !questionMatchesSelectedCategory(question, categoryId);
            }).length;
            categoryAllowedLimit = Math.max(0, totalQuestionsAllowed - selectedOutsideCategory);
        }

        const marks = [...partState.selectedMarks].join(',');
        const formats = [...state.selectedExamFormat].join(',');
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('categories', String(categoryId));
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);
        url.searchParams.set('count', String(categoryAllowedLimit));

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            if (rows.length < categoryAllowedLimit) {
                showQuestionSelectionWarningForPart(partState, `Only ${rows.length} matching question(s) are available in ${getCategoryLabelById(categoryId)}; ${categoryAllowedLimit} are required.`);
                return;
            }

            [...partState.selectedQuestions].forEach((questionId) => {
                const question = getQuestionByIdForPart(partState, questionId);
                if (question && questionMatchesSelectedCategory(question, categoryId)) partState.selectedQuestions.delete(questionId);
            });
            rows.forEach((question) => { rememberSelectedQuestionForPart(partState, question); partState.selectedQuestions.add(question.id); });
            mergeQuestionBankRowsForPart(partState, rows, { append: true });
            root.querySelector('[data-question-bank-feedback]')?.classList.remove('is-invalid');
            renderQuestionBankCardsForPart(partState);
            updatePartMetaSummary(partState);
            updateExamSummary();
            updateWorkflowAndSnapshot();
        } catch (err) {
            console.error('Category random select failed', err);
            showQuestionSelectionWarningForPart(partState, 'Could not complete random selection. Please try again.');
        }
    }

    async function randomSelectGlobalForPart(partState) {
        if (!isManualQuestionSelectionEnabledForPart(partState)) {
            showQuestionSelectionWarningForPart(partState, 'Enable Fixed Questions or Question Pool before selecting questions.');
            return;
        }

        const root = partState.root;
        const limits = getQuestionSelectionLimitsForPart(partState);
        const totalQuestionsAllowed = limits.max;
        const fixedPerCategory = Boolean(partField(root, 'fix_category_questions')?.checked) && !isQuestionPoolEnabledForPart(partState);
        const previousSelection = new Set(partState.selectedQuestions);
        const endpoints = window.examCreateConfig?.endpoints || {};
        const endpoint = endpoints.questionBankRandom || endpoints.questionBank;
        if (!endpoint) {
            showQuestionSelectionWarningForPart(partState, 'Random selection endpoint is unavailable.');
            return;
        }

        const categoryIds = [...partState.selectedCategories].join(',');
        const marks = [...partState.selectedMarks].join(',');
        const formats = [...state.selectedExamFormat].join(',');
        const url = new URL(endpoint, window.location.origin);
        if (categoryIds) url.searchParams.set('categories', categoryIds);
        if (marks) url.searchParams.set('marks', marks);
        if (formats) url.searchParams.set('formats', formats);

        let categoryQuotas = {};
        if (fixedPerCategory) {
            const fixedDistribution = computeFixedCategoryDistributionForPart(partState);
            categoryQuotas = Object.fromEntries(fixedDistribution.rows.map((row) => [String(row.categoryId), row.count]));
            url.searchParams.set('count', String(fixedDistribution.rows.reduce((sum, row) => sum + row.count, 0)));
            url.searchParams.set('category_quotas', JSON.stringify(categoryQuotas));
        } else {
            url.searchParams.set('count', String(totalQuestionsAllowed));
        }

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            const rows = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            const required = fixedPerCategory
                ? Object.values(categoryQuotas).reduce((sum, n) => sum + Number(n || 0), 0)
                : totalQuestionsAllowed;
            if (rows.length < required) {
                partState.selectedQuestions = previousSelection;
                showQuestionSelectionWarningForPart(partState, `Only ${rows.length} matching question(s) are available; ${required} are required.`);
                return;
            }

            partState.selectedQuestions.clear();
            rows.forEach((question) => { rememberSelectedQuestionForPart(partState, question); partState.selectedQuestions.add(question.id); });
            mergeQuestionBankRowsForPart(partState, rows, { append: true });
            root.querySelector('[data-question-bank-feedback]')?.classList.remove('is-invalid');
            renderQuestionBankCardsForPart(partState);
            updatePartMetaSummary(partState);
            updateExamSummary();
            updateWorkflowAndSnapshot();
        } catch (err) {
            partState.selectedQuestions = previousSelection;
            console.error('Random select failed', err);
            showQuestionSelectionWarningForPart(partState, 'Could not complete random selection. Please try again.');
        }
    }

    function openAddQuestionModalForPart(partState, categoryId, options = {}) {
        state.lastAddQuestionPartKey = partState.partKey;

        const endpoints = window.examCreateConfig?.endpoints || {};
        const createUrl = endpoints.questionCreate || '/admin/questions/create';
        const url = new URL(createUrl, window.location.origin);
        url.searchParams.set('source', 'exam-create');

        const resolvedCategoryId = categoryId
            || ([...partState.selectedCategories].length === 1 ? [...partState.selectedCategories][0] : '');
        if (resolvedCategoryId) url.searchParams.set('category_id', resolvedCategoryId);

        const marksValues = [];
        if (options.marks) marksValues.push(Number(options.marks));
        else marksValues.push(...[...partState.selectedMarks].map(Number));
        [...new Set(marksValues.filter((mark) => mark > 0))].forEach((mark) => url.searchParams.append('marks[]', String(mark)));

        [...state.selectedExamFormat].forEach((format) => url.searchParams.append('formats[]', format));
        if (refs.difficulty?.value) url.searchParams.set('difficulty', refs.difficulty.value);

        window.open(url.toString(), '_blank');
    }

    // ── Part-scoped event binding ──────────────────────────────────────

    function handlePartAction(partState, action, el) {
        switch (action) {
            case 'toggle': togglePartExpanded(partState); break;
            case 'duplicate': duplicatePart(partState); break;
            case 'delete': deletePart(partState); break;
            case 'refresh_bank': refreshPartQuestionBank(partState, el); break;
            case 'random_select': randomSelectGlobalForPart(partState); break;
            case 'add_question': openAddQuestionModalForPart(partState, ''); break;
            case 'load_more': break; // handled per category via delegated clicks
            case 'fix_total_marks': applyMarksCalculationFixForPart(partState, 'total_marks'); break;
            case 'fix_total_questions': applyMarksCalculationFixForPart(partState, 'total_questions'); break;
            default: break;
        }
    }

    function bindPartEvents(partState) {
        const root = partState.root;

        root.addEventListener('click', (event) => {
            const actionEl = event.target.closest('[data-part-action]');
            if (actionEl) {
                handlePartAction(partState, actionEl.dataset.partAction, actionEl);
                return;
            }

            const marksBtn = event.target.closest('[data-mark-value]');
            if (marksBtn) {
                const mark = Number(marksBtn.dataset.markValue);
                const fixEach = Boolean(partField(root, 'fix_marks_each_question')?.checked);
                if (!fixEach) {
                    if (partState.selectedMarks.has(mark)) partState.selectedMarks.delete(mark);
                    else partState.selectedMarks.add(mark);
                } else {
                    partState.selectedMarks = new Set([mark]);
                }
                renderPartQuestionMarks(partState);
                partState.lastFetchedKey = '';
                updatePartUi(partState);
                updateExamSummary();
                updateWorkflowAndSnapshot();
                return;
            }

            const distBtn = event.target.closest('[data-distribution-id]');
            if (distBtn) {
                partState.selectedDistributionType = distBtn.dataset.distributionId;
                renderPartDistributionTypes(partState);
                updateExamSummary();
                return;
            }

            handleQuestionBankClick(partState, event);
        });

        root.addEventListener('change', (event) => {
            handleQuestionBankChange(partState, event);

            const field = event.target?.dataset?.field;
            if (field && PART_REACTIVE_FIELDS.has(field)) {
                updatePartUi(partState);
                updateExamSummary();
                updateWorkflowAndSnapshot();
            }
        });

        root.addEventListener('input', (event) => {
            if (handlePartAllocationInput(partState, event)) {
                return;
            }
            const field = event.target?.dataset?.field;
            if (!field || field === 'question_search' || field === 'name') {
                return;
            }
            if (PART_REACTIVE_FIELDS.has(field)) {
                updatePartUi(partState);
                updateExamSummary();
                updateWorkflowAndSnapshot();
            }
        });

        const searchInput = root.querySelector('[data-question-search-input]');
        searchInput?.addEventListener('input', () => {
            partState.lastFetchedKey = '';
            scheduleQuestionBankSyncForPart(partState, 400);
            renderQuestionBankCardsForPart(partState);
        });
    }

    // ────────────────────────────────────────────────────────────────
    // EXAM SUMMARY (aggregated across all parts)
    // ────────────────────────────────────────────────────────────────

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function updateExamSummary() {
        const parts = getOrderedParts();
        let totalQuestions = 0;
        let totalMarks = 0;
        const totalDuration = toInt(refs.examDurationMinutes?.value, 0);
        const categoryTotals = new Map();
        const distributionCounts = new Map();

        parts.forEach((partState) => {
            const root = partState.root;
            const totalQ = toInt(partField(root, 'total_questions')?.value, 0);
            const totalM = toInt(partField(root, 'total_marks')?.value, 0);
            totalQuestions += totalQ;
            totalMarks += totalM;

            const selectedIds = [...partState.selectedCategories];
            if (selectedIds.length) {
                const perCategoryQuestions = Boolean(partField(root, 'fix_category_questions')?.checked)
                    ? partState.extraQuestionsAllocations
                    : buildEvenCategoryCounts(selectedIds, totalQ);

                selectedIds.forEach((cid) => {
                    const key = String(cid);
                    const count = toInt(perCategoryQuestions[key], 0);
                    const existing = categoryTotals.get(key) || { name: getCategoryLabelById(key), questions: 0 };
                    existing.questions += count;
                    categoryTotals.set(key, existing);
                });
            }

            if (partState.selectedDistributionType) {
                const label = state.config.distributionTypes.find((d) => d.id === partState.selectedDistributionType)?.label
                    || partState.selectedDistributionType;
                distributionCounts.set(label, (distributionCounts.get(label) || 0) + 1);
            }
        });

        setText('summary-total-parts', String(parts.length));
        setText('summary-total-questions', String(totalQuestions));
        setText('summary-total-marks', String(totalMarks));
        setText('summary-total-duration', `${totalDuration} min`);

        const passingMarks = toInt(refs.passingMarks?.value, 0);
        setText('summary-passing-marks', String(passingMarks));

        const negativeEnabled = Boolean(refs.enableNegativeMarking?.checked);
        const negativeType = refs.negativeMarkingType?.value || '';
        setText('summary-negative-marks', negativeEnabled ? `${negativeType}%` : 'Off');

        if (refs.passingMarksCeiling) refs.passingMarksCeiling.textContent = String(totalMarks);

        const distributionListEl = document.getElementById('summary-question-distribution');
        if (distributionListEl) {
            distributionListEl.innerHTML = distributionCounts.size
                ? [...distributionCounts.entries()].map(([label, count]) => `<li>${escapeHtml(label)}: <strong>${count}</strong> part(s)</li>`).join('')
                : '<li>No distribution configured yet.</li>';
        }

        const categoryListEl = document.getElementById('summary-category-list');
        if (categoryListEl) {
            categoryListEl.innerHTML = categoryTotals.size
                ? [...categoryTotals.values()].map((c) => `<li>${escapeHtml(c.name)}: <strong>${c.questions}</strong> question(s)</li>`).join('')
                : '<li>No categories selected yet.</li>';
        }

        const overviewListEl = document.getElementById('summary-overview-list');
        if (overviewListEl) {
            overviewListEl.innerHTML = [
                `<li>Parts: <strong>${parts.length}</strong></li>`,
                `<li>Total questions: <strong>${totalQuestions}</strong></li>`,
                `<li>Total marks: <strong>${totalMarks}</strong></li>`,
                `<li>Total duration: <strong>${totalDuration} min</strong></li>`,
                `<li>Passing marks: <strong>${passingMarks}</strong> / ${totalMarks}</li>`,
                `<li>${passingMarks <= totalMarks ? 'Passing marks are within range.' : 'Warning: passing marks exceed total marks.'}</li>`,
            ].join('');
        }
    }

    // ────────────────────────────────────────────────────────────────
    // WORKFLOW CHECKLIST + LIVE SNAPSHOT
    // ────────────────────────────────────────────────────────────────

    function getDescriptionTextLength() {
        const html = getRichTextValue('exam_description');
        return cleanText(String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ')).length;
    }

    function getExamCategoryValue() {
        if (!refs.examCategory) return '';
        if (refs.examCategory.tomselect) {
            return cleanText(String(refs.examCategory.tomselect.getValue() || ''));
        }
        return cleanText(String(refs.examCategory.value || ''));
    }

    function getExamCategoryLabel() {
        const value = getExamCategoryValue();
        if (!value || !refs.examCategory) return '';
        const option = [...refs.examCategory.options].find((opt) => String(opt.value) === value);
        return cleanText(option?.dataset?.categoryName || option?.textContent || '');
    }

    function sidebarCheck(label, status, meta, required = false) {
        return { label, status, meta: meta || '', required: Boolean(required) };
    }

    function buildSidebarValidationChecks() {
        const checks = [];
        const title = cleanText(refs.title?.value || '');
        if (title.length >= 3) {
            checks.push(sidebarCheck('Title', 'ok', title.length > 48 ? `${title.slice(0, 48)}…` : title, true));
        } else if (title.length > 0) {
            checks.push(sidebarCheck('Title', 'error', 'Need at least 3 characters.', true));
        } else {
            checks.push(sidebarCheck('Title', 'error', 'Required — enter an exam title.', true));
        }

        const categoryValue = getExamCategoryValue();
        const categoryLabel = getExamCategoryLabel();
        if (categoryValue) {
            checks.push(sidebarCheck('Exam Category', 'ok', categoryLabel || `Category #${categoryValue}`, true));
        } else {
            checks.push(sidebarCheck('Exam Category', 'error', 'Required — select a category.', true));
        }

        const descriptionLength = getDescriptionTextLength();
        if (descriptionLength > 0) {
            checks.push(sidebarCheck('Description', 'ok', `${descriptionLength} character${descriptionLength === 1 ? '' : 's'}`, false));
        } else {
            checks.push(sidebarCheck('Description', 'warn', 'Optional — add scope, audience, or outcomes.', false));
        }

        const difficulty = cleanText(refs.difficulty?.value || '');
        checks.push(difficulty
            ? sidebarCheck('Difficulty', 'ok', difficulty.replace(/_/g, ' '), false)
            : sidebarCheck('Difficulty', 'warn', 'Optional — choose a difficulty level.', false));

        const statusValue = cleanText(refs.status?.value || '');
        checks.push(statusValue
            ? sidebarCheck('Status', 'ok', statusValue.replace(/_/g, ' '), true)
            : sidebarCheck('Status', 'error', 'Required — choose a status.', true));

        const modeValue = cleanText(refs.mode?.value || state.selectedMode || '');
        checks.push(modeValue
            ? sidebarCheck('Exam Mode', 'ok', modeValue.replace(/_/g, ' '), true)
            : sidebarCheck('Exam Mode', 'error', 'Required — choose an exam mode.', true));

        const visibilityValue = cleanText(refs.visibility?.value || state.selectedVisibility || '');
        checks.push(visibilityValue
            ? sidebarCheck('Visibility', 'ok', visibilityValue.replace(/_/g, ' '), true)
            : sidebarCheck('Visibility', 'error', 'Required — choose visibility.', true));

        const tagCount = Array.isArray(state.tags) ? state.tags.length : 0;
        checks.push(tagCount > 0
            ? sidebarCheck('Tags', 'ok', `${tagCount} tag${tagCount === 1 ? '' : 's'}`, false)
            : sidebarCheck('Tags', 'warn', 'Optional — tags help search and reporting.', false));

        const timerEnabled = Boolean(refs.enableExamTimer && refs.enableExamTimer.checked);
        const duration = toInt(refs.examDurationMinutes?.value, 0);
        if (!timerEnabled) {
            checks.push(sidebarCheck('Timer & Duration', 'ok', 'Timer disabled', true));
        } else if (duration >= 1) {
            checks.push(sidebarCheck('Timer & Duration', 'ok', `${duration} min · auto-submit ${refs.autoSubmitOnTimerEnd?.checked ? 'on' : 'off'}`, true));
        } else {
            checks.push(sidebarCheck('Timer & Duration', 'error', 'Duration must be at least 1 minute.', true));
        }

        const formatCount = state.selectedExamFormat instanceof Set ? state.selectedExamFormat.size : 0;
        if (formatCount > 0) {
            const formats = [...state.selectedExamFormat].map((id) => String(id).toUpperCase()).join(', ');
            checks.push(sidebarCheck('Exam Format', 'ok', formats, true));
        } else {
            checks.push(sidebarCheck('Exam Format', 'error', 'Select at least one format.', true));
        }

        const scheduleType = normalizeScheduleType(state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time'));
        const scheduleStartAt = cleanText(refs.scheduleStartAt ? refs.scheduleStartAt.value : '');
        const scheduleEndAt = cleanText(refs.scheduleEndAt ? refs.scheduleEndAt.value : '');
        const scheduleStartTs = parseDateTimeValue(scheduleStartAt);
        const scheduleEndTs = parseDateTimeValue(scheduleEndAt);
        if (scheduleType !== 'fixed_window') {
            checks.push(sidebarCheck('Schedule', 'ok', scheduleType.replace(/_/g, ' '), true));
        } else if (!scheduleStartAt || !scheduleEndAt) {
            checks.push(sidebarCheck('Schedule', 'error', 'Set start and end for the fixed window.', true));
        } else if (scheduleStartTs === null || scheduleEndTs === null) {
            checks.push(sidebarCheck('Schedule', 'error', 'Schedule date-time values are invalid.', true));
        } else if (scheduleEndTs <= scheduleStartTs) {
            checks.push(sidebarCheck('Schedule', 'error', 'End must be later than start.', true));
        } else {
            checks.push(sidebarCheck('Schedule', 'ok', 'Fixed window configured', true));
        }

        const attemptLimitType = normalizeAttemptLimitType(state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once'));
        const fixedAttemptCount = Math.max(0, toInt(refs.attemptLimitCount ? refs.attemptLimitCount.value : 0, 0));
        if (attemptLimitType === 'fixed_count' && fixedAttemptCount < 2) {
            checks.push(sidebarCheck('Attempts', 'error', 'Fixed attempt limit must be at least 2.', true));
        } else if (attemptLimitType === 'fixed_count') {
            checks.push(sidebarCheck('Attempts', 'ok', `${fixedAttemptCount} attempts`, true));
        } else {
            checks.push(sidebarCheck('Attempts', 'ok', attemptLimitType.replace(/_/g, ' '), true));
        }

        const candidateVisible = Boolean(refs.candidateSection && !refs.candidateSection.hidden);
        if (candidateVisible) {
            const candidateCount = state.importedCandidates.length + state.manualEmails.length;
            checks.push(candidateCount > 0
                ? sidebarCheck('Candidate Access', 'ok', `${candidateCount} candidate${candidateCount === 1 ? '' : 's'}`, true)
                : sidebarCheck('Candidate Access', 'error', 'Add at least one candidate email.', true));
        }

        const parts = getOrderedParts();
        if (!parts.length) {
            checks.push(sidebarCheck('Exam Parts', 'error', 'Add at least one exam part.', true));
        } else {
            const partIssues = [];
            parts.forEach((partState, index) => {
                const displayName = cleanText(partField(partState.root, 'name')?.value || '') || `Part ${index + 1}`;
                const errors = validatePart(partState, '');
                if (errors.length) {
                    partIssues.push({ name: displayName, message: errors[0].replace(/^:\s*/, '') });
                }
            });

            const totalQuestions = parts.reduce((sum, p) => sum + toInt(partField(p.root, 'total_questions')?.value, 0), 0);
            if (partIssues.length) {
                checks.push(sidebarCheck(
                    'Exam Parts',
                    'error',
                    `${partIssues.length} of ${parts.length} part${parts.length === 1 ? '' : 's'} need attention`,
                    true
                ));
                partIssues.forEach((issue) => {
                    checks.push(sidebarCheck(issue.name, 'error', issue.message, true));
                });
            } else {
                checks.push(sidebarCheck(
                    'Exam Parts',
                    'ok',
                    `${parts.length} part${parts.length === 1 ? '' : 's'} · ${totalQuestions} question${totalQuestions === 1 ? '' : 's'}`,
                    true
                ));
            }
        }

        const totalMarksSum = parts.reduce((sum, p) => sum + toInt(partField(p.root, 'total_marks')?.value, 0), 0);
        const passingMarks = toInt(refs.passingMarks?.value, 0);
        if (Number.isNaN(passingMarks) || passingMarks < 0) {
            checks.push(sidebarCheck('Passing Marks', 'error', 'Passing marks is required.', true));
        } else if (totalMarksSum > 0 && passingMarks > totalMarksSum) {
            checks.push(sidebarCheck('Passing Marks', 'error', `Cannot exceed total marks (${totalMarksSum}).`, true));
        } else {
            checks.push(sidebarCheck('Passing Marks', 'ok', `${passingMarks} / ${totalMarksSum || '—'}`, true));
        }

        const negativeOn = Boolean(refs.enableNegativeMarking?.checked);
        const negativeType = cleanText(refs.negativeMarkingType?.value || '');
        checks.push(negativeOn
            ? sidebarCheck('Negative Marking', 'ok', negativeType ? negativeType.replace(/_/g, ' ') : 'Enabled', false)
            : sidebarCheck('Negative Marking', 'warn', 'Optional — currently off.', false));

        if (refs.pricingSection && !refs.pricingSection.hidden) {
            const freeCount = state.freeImportedCandidates.length + state.freeManualEmails.length;
            if (!state.selectedPricing) {
                checks.push(sidebarCheck('Pricing', 'error', 'Select a pricing option.', true));
            } else if (state.selectedPricing === 'free_for_imported' && freeCount < 1) {
                checks.push(sidebarCheck('Pricing', 'error', 'Add free-access candidate emails.', true));
            } else {
                const discountCount = (state.selectedDiscounts?.size || 0) + (state.customDiscounts?.length || 0);
                checks.push(sidebarCheck(
                    'Pricing',
                    'ok',
                    `${String(state.selectedPricing).replace(/_/g, ' ')}${discountCount ? ` · ${discountCount} discount${discountCount === 1 ? '' : 's'}` : ''}`,
                    true
                ));
            }
        }

        const ruleCount = state.selectedInstructionRules?.size || 0;
        checks.push(ruleCount > 0
            ? sidebarCheck('Exam Rules', 'ok', `${ruleCount} rule${ruleCount === 1 ? '' : 's'} selected`, false)
            : sidebarCheck('Exam Rules', 'warn', 'Optional — select predefined rules.', false));

        const instructionLength = getInstructionTextLength();
        if (instructionLength > 20) {
            checks.push(sidebarCheck('Instructions', 'ok', `${instructionLength} characters`, false));
        } else if (instructionLength > 0) {
            checks.push(sidebarCheck('Instructions', 'warn', 'Add a bit more detail for candidates.', false));
        } else {
            checks.push(sidebarCheck('Instructions', 'warn', 'Optional — candidate instructions are empty.', false));
        }

        return checks;
    }

    function updateWorkflowAndSnapshot() {
        if (!refs.sidebarValidationList) return;

        const checks = buildSidebarValidationChecks();
        const icons = { ok: '✓', warn: '!', error: '✕' };

        refs.sidebarValidationList.innerHTML = checks.map((item) => `
            <li class="exam-aside-check is-${escapeHtml(item.status)}" data-required="${item.required ? '1' : '0'}">
                <span class="exam-aside-check__icon" aria-hidden="true">${icons[item.status] || '•'}</span>
                <span class="exam-aside-check__body">
                    <span class="exam-aside-check__label">${escapeHtml(item.label)}${item.required ? ' *' : ''}</span>
                    <span class="exam-aside-check__meta">${escapeHtml(item.meta)}</span>
                </span>
            </li>
        `).join('');

        const requiredChecks = checks.filter((item) => item.required);
        const readyRequired = requiredChecks.filter((item) => item.status === 'ok').length;
        const requiredTotal = requiredChecks.length || checks.length;
        const errorCount = checks.filter((item) => item.status === 'error').length;
        const warnCount = checks.filter((item) => item.status === 'warn').length;
        const okCount = checks.filter((item) => item.status === 'ok').length;
        const progressPct = requiredTotal > 0 ? Math.round((readyRequired / requiredTotal) * 100) : 0;

        if (refs.sidebarProgressFill) {
            refs.sidebarProgressFill.style.width = `${progressPct}%`;
            refs.sidebarProgressFill.classList.toggle('is-blocked', errorCount > 0);
            refs.sidebarProgressFill.classList.toggle('is-attention', errorCount === 0 && warnCount > 0);
        }
        if (refs.sidebarProgressLabel) {
            refs.sidebarProgressLabel.textContent = `${readyRequired} / ${requiredTotal} required ready · ${okCount} ok · ${warnCount} optional · ${errorCount} missing`;
        }
        if (refs.sidebarReadinessBadge) {
            refs.sidebarReadinessBadge.classList.remove('is-ready', 'is-blocked', 'is-attention');
            if (errorCount > 0) {
                refs.sidebarReadinessBadge.textContent = 'Blocked';
                refs.sidebarReadinessBadge.classList.add('is-blocked');
            } else if (warnCount > 0) {
                refs.sidebarReadinessBadge.textContent = 'Review';
                refs.sidebarReadinessBadge.classList.add('is-attention');
            } else {
                refs.sidebarReadinessBadge.textContent = 'Ready';
                refs.sidebarReadinessBadge.classList.add('is-ready');
            }
        }
    }

    // ── Rich text editors ──────────────────────────────────────────────

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
            if (!input.style.minHeight) input.style.minHeight = '180px';
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
            if (refs.instructions) refs.instructions.addEventListener('input', updateInstructionCounter);
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
            if (refs.instructions) refs.instructions.addEventListener('input', updateInstructionCounter);
        }
        state.richEditors = registry instanceof Map ? registry : new Map();

        if (!state.richEditors.has('exam_description')) revealFallback(refs.description);
        if (!state.richEditors.has('candidate_instructions')) {
            revealFallback(refs.instructions);
            if (refs.instructions) refs.instructions.addEventListener('input', updateInstructionCounter);
        }

        const descriptionEditor = getRichEditor('exam_description');
        const instructionEditor = getRichEditor('candidate_instructions');

        if (descriptionEditor) {
            descriptionEditor.onChange(() => updateWorkflowAndSnapshot());
        }
        if (instructionEditor) {
            instructionEditor.onChange(() => { updateInstructionCounter(); updateWorkflowAndSnapshot(); });
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
        if (!(state.richEditors instanceof Map)) return null;
        return state.richEditors.get(inputId) || null;
    }

    function getRichTextValue(inputId) {
        const editor = getRichEditor(inputId);
        if (editor) return editor.getData();
        const field = document.getElementById(inputId);
        return field ? field.value : '';
    }

    function syncRichTextFields() {
        if (!(state.richEditors instanceof Map) || state.richEditors.size === 0) return;
        state.richEditors.forEach((editor) => {
            if (editor && typeof editor.sync === 'function') editor.sync();
        });
    }

    function updateInstructionCounter() {
        syncRichTextFields();
        refs.instructionsCount.textContent = String(getInstructionTextLength());
    }

    // ────────────────────────────────────────────────────────────────
    // VALIDATION + SUBMIT
    // ────────────────────────────────────────────────────────────────

    function validatePart(partState, partLabel) {
        const errors = [];
        const root = partState.root;
        const prefix = partLabel ? `${partLabel}: ` : '';

        const name = cleanText(partField(root, 'name')?.value || '');
        if (!name) errors.push(`${prefix}enter a part name.`);

        const totalQuestions = toInt(partField(root, 'total_questions')?.value, 0);
        if (totalQuestions < 1) errors.push(`${prefix}total questions must be at least 1.`);

        const totalMarks = toInt(partField(root, 'total_marks')?.value, 0);
        if (totalMarks < 1) errors.push(`${prefix}total marks must be at least 1.`);

        if (partState.selectedCategories.size < 1) errors.push(`${prefix}select at least one question category.`);
        if (partState.selectedMarks.size < 1) errors.push(`${prefix}select at least one question marks filter.`);

        const usePool = Boolean(partField(root, 'use_question_pool')?.checked);
        const maximumQuestions = toInt(partField(root, 'maximum_questions')?.value, 0);
        if (usePool && maximumQuestions <= totalQuestions) {
            errors.push(`${prefix}maximum questions in the pool must be greater than total questions.`);
        }

        const hasFixedPaperSets = Boolean(partField(root, 'fixed_paper_set')?.checked);
        const paperSets = toInt(partField(root, 'paper_sets')?.value, 0);
        if (hasFixedPaperSets && (!Number.isInteger(paperSets) || paperSets < 1 || paperSets > totalQuestions)) {
            errors.push(`${prefix}paper sets must be a whole number between 1 and total questions.`);
        }

        const selectionLimits = getQuestionSelectionLimitsForPart(partState);
        const selectedQuestionCount = partState.selectedQuestions.size;
        if (!selectionLimits.viewOnly) {
            if (selectionLimits.exact !== null && selectedQuestionCount !== selectionLimits.exact) {
                errors.push(`${prefix}select exactly ${selectionLimits.exact} question(s).`);
            } else if (selectedQuestionCount < selectionLimits.min || selectedQuestionCount > selectionLimits.max) {
                errors.push(`${prefix}select between ${selectionLimits.min} and ${selectionLimits.max} question(s) for the pool.`);
            }
        }

        const shortages = computeQuestionShortagesForPart(partState);
        shortages.forEach((item) => {
            errors.push(`${prefix}not enough matching questions available (available ${item.available} / required ${item.required}).`);
        });

        if (Boolean(partField(root, 'fix_category_questions')?.checked)) {
            const distribution = computeFixedCategoryDistributionForPart(partState);
            if (!distribution.isComplete) {
                errors.push(`${prefix}allocate exactly ${distribution.totalQuestions} questions across categories (currently ${distribution.totalAllocated}).`);
            }
        }

        if (Boolean(partField(root, 'fix_category_marks')?.checked)) {
            const marksDistribution = computeFixedCategoryMarksDistributionForPart(partState);
            if (!marksDistribution.isComplete) {
                errors.push(`${prefix}allocate exactly ${marksDistribution.totalMarks} marks across categories (currently ${marksDistribution.totalAllocated}).`);
            }
        }

        const marksCalculation = computeMarksCalculationForPart(partState);
        if (marksCalculation.fixEnabled) {
            if (!marksCalculation.hasSelectedMark || partState.selectedMarks.size !== 1) {
                errors.push(`${prefix}select exactly one question marks filter when Fix Marks Each Question is enabled.`);
            } else if (!marksCalculation.isValid) {
                errors.push(`${prefix}fixed marks mismatch (${marksCalculation.totalQuestions} x ${marksCalculation.selectedMark} should equal ${marksCalculation.expectedTotalMarks}).`);
            }
        }

        return errors;
    }

    function collectSubmissionErrors() {
        const errors = [];
        const timerEnabled = Boolean(refs.enableExamTimer && refs.enableExamTimer.checked);
        const scheduleType = normalizeScheduleType(state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time'));
        const attemptLimitType = normalizeAttemptLimitType(state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once'));
        const scheduleStartAt = cleanText(refs.scheduleStartAt ? refs.scheduleStartAt.value : '');
        const scheduleEndAt = cleanText(refs.scheduleEndAt ? refs.scheduleEndAt.value : '');
        const scheduleStartTs = parseDateTimeValue(scheduleStartAt);
        const scheduleEndTs = parseDateTimeValue(scheduleEndAt);
        const attemptLimitCount = Math.max(0, toInt(refs.attemptLimitCount ? refs.attemptLimitCount.value : 0, 0));

        if (cleanText(refs.title.value).length < 3) errors.push('Exam title must be at least 3 characters long.');
        if (!getExamCategoryValue()) errors.push('Select an exam category.');
        if (!state.selectedExamFormat || state.selectedExamFormat.size === 0) errors.push('Select at least one exam format.');
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

        const parts = getOrderedParts();
        if (!parts.length) {
            errors.push('Add at least one exam part.');
        }

        let totalMarksSum = 0;
        parts.forEach((partState, index) => {
            const displayName = cleanText(partField(partState.root, 'name')?.value || '') || `#${index + 1}`;
            errors.push(...validatePart(partState, `Part "${displayName}"`));
            totalMarksSum += toInt(partField(partState.root, 'total_marks')?.value, 0);
        });

        const passingMarks = toInt(refs.passingMarks.value, 0);
        if (passingMarks < 0) errors.push('Passing marks is required.');
        if (totalMarksSum > 0 && passingMarks > totalMarksSum) {
            errors.push(`Passing marks cannot exceed the total marks across all parts (${totalMarksSum}).`);
        }
        if (timerEnabled) {
            const duration = toInt(refs.examDurationMinutes?.value, 0);
            if (duration < 1) errors.push('Exam duration must be at least 1 minute when the timer is enabled.');
        }

        if (!refs.candidateSection.hidden && state.importedCandidates.length + state.manualEmails.length === 0) {
            errors.push('Add at least one candidate email for the current access configuration.');
        }
        if (!refs.pricingSection.hidden && !state.selectedPricing) {
            errors.push('Select one pricing option.');
        } else if (!refs.pricingSection.hidden && (state.selectedPricing === 'paid' || state.selectedPricing === 'free_for_imported')) {
            if (state.selectedPricing === 'free_for_imported' && state.freeImportedCandidates.length + state.freeManualEmails.length === 0) {
                errors.push('Add at least one candidate email to the Free Candidate List for the free access configuration.');
            }
            state.selectedDiscounts.forEach((id) => {
                const pct = state.discountPercentages[id];
                if (pct === undefined || isNaN(pct) || pct < 0 || pct > 100) {
                    errors.push('All selected predefined discounts must have a percentage value between 0% and 100%.');
                }
            });
            state.customDiscounts.forEach((item, index) => {
                if (!item.name || !item.name.trim()) {
                    errors.push(`Custom Discount Offer #${index + 1}: Offer name is required.`);
                }
                if (item.percentage === undefined || isNaN(item.percentage) || item.percentage < 0 || item.percentage > 100) {
                    errors.push(`Custom Discount Offer #${index + 1}: Discount percentage must be between 0% and 100%.`);
                }
            });
        }

        // Sync exam-level hidden fields right before submit.
        if (refs.discountHidden) {
            refs.discountHidden.value = JSON.stringify(
                [...state.selectedDiscounts].map((id) => ({ id, percentage: state.discountPercentages[id] }))
            );
        }
        if (refs.customDiscountsHidden) refs.customDiscountsHidden.value = JSON.stringify(state.customDiscounts || []);
        if (refs.pricingOptionHidden) refs.pricingOptionHidden.value = state.selectedPricing || '';
        refs.tagsHidden.value = JSON.stringify(state.tags);
        if (refs.examFormatHidden) refs.examFormatHidden.value = JSON.stringify([...state.selectedExamFormat]);
        if (refs.scheduleTypeHidden) refs.scheduleTypeHidden.value = scheduleType;
        if (refs.attemptLimitTypeHidden) refs.attemptLimitTypeHidden.value = attemptLimitType;
        refs.manualEmailsHidden.value = JSON.stringify(state.manualEmails);
        refs.importedCandidatesHidden.value = JSON.stringify(state.importedCandidates);
        refs.freeManualEmailsHidden.value = JSON.stringify(state.freeManualEmails);
        refs.freeImportedCandidatesHidden.value = JSON.stringify(state.freeImportedCandidates);
        state.selectedInstructionRules = new Set(normalizeInstructionRuleSelection([...state.selectedInstructionRules]));
        syncInstructionRulesHidden();

        // Re-sync every part's hidden fields (categories/allocations/marks/question ids)
        // and make sure `parts[N]` indexes are sequential before the browser serializes the form.
        parts.forEach((partState) => {
            syncPartCategoryFeedback(partState);
            syncPartExtraQuestionsAllocationsHidden(partState);
            syncPartExtraMarksAllocationsHidden(partState);
            renderPartQuestionMarks(partState);
            syncPartQuestionIdsHidden(partState);
        });
        reindexParts();

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
        if (!refs.loader) return;
        refs.loader.classList.add('is-hidden');
        if (refs.page) refs.page.setAttribute('data-page-ready', 'true');
        window.setTimeout(() => setDefaultFocusOnTitle(), 50);
    }

    function setDefaultFocusOnTitle() {
        if (!refs.title || refs.title.disabled) return;
        window.requestAnimationFrame(() => refs.title.focus({ preventScroll: true }));
    }

    // ────────────────────────────────────────────────────────────────
    // MASTER UPDATE + EVENT BINDING
    // ────────────────────────────────────────────────────────────────

    function updateAll() {
        if (!state.selectedExamFormat || !(state.selectedExamFormat instanceof Set) || state.selectedExamFormat.size === 0) {
            state.selectedExamFormat = new Set(['mcq']);
        }
        state.selectedScheduleType = normalizeScheduleType(state.selectedScheduleType || (refs.scheduleTypeHidden ? refs.scheduleTypeHidden.value : 'any_time'));
        state.selectedAttemptLimitType = normalizeAttemptLimitType(state.selectedAttemptLimitType || (refs.attemptLimitTypeHidden ? refs.attemptLimitTypeHidden.value : 'once'));

        renderExamFormatOptions();
        renderScheduleTypeOptions();
        renderAttemptLimitOptions();
        updateScheduleConfigState();
        updateTimerConfigState();
        updateConditionalSections();
        renderDiscountSummary();

        getOrderedParts().forEach((partState) => updatePartUi(partState));

        updateExamSummary();
        updateWorkflowAndSnapshot();
    }

    function bindEvents() {
        if (state.eventsBound) return;
        try {
            bindEventsInternal();
            state.eventsBound = true;
        } catch (error) {
            state.eventsBound = false;
            throw error;
        }
    }

    function bindEventsInternal() {
        initSectionCollapse();

        refs.mode.addEventListener('change', () => { state.selectedMode = refs.mode.value; updateAll(); });
        refs.visibility.addEventListener('change', () => {
            state.selectedVisibility = refs.visibility.value;
            renderPricingOptions();
            updateAll();
        });

        if (refs.enableExamTimer) {
            refs.enableExamTimer.addEventListener('change', () => { updateTimerConfigState(); updateWorkflowAndSnapshot(); updateExamSummary(); });
        }
        if (refs.examDurationMinutes) {
            refs.examDurationMinutes.addEventListener('input', () => { updateTimerConfigState(); updateWorkflowAndSnapshot(); updateExamSummary(); });
        }
        if (refs.autoSubmitOnTimerEnd) {
            refs.autoSubmitOnTimerEnd.addEventListener('change', () => { updateTimerConfigState(); });
        }

        if (refs.examFormatOptions) {
            refs.examFormatOptions.addEventListener('click', (event) => {
                const card = event.target.closest('[data-format-id]');
                if (!card) return;
                const formatId = card.dataset.formatId;
                if (state.selectedExamFormat.has(formatId)) {
                    if (state.selectedExamFormat.size > 1) state.selectedExamFormat.delete(formatId);
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

        if (refs.enableNegativeMarking) {
            refs.enableNegativeMarking.addEventListener('change', () => {
                refs.negativeMarkingConfig.hidden = !refs.enableNegativeMarking.checked;
                updateExamSummary();
                updateWorkflowAndSnapshot();
            });
        }
        if (refs.negativeMarkingType) {
            refs.negativeMarkingType.addEventListener('change', () => {
                updateExamSummary();
                updateWorkflowAndSnapshot();
            });
        }
        if (refs.passingMarks) {
            refs.passingMarks.addEventListener('input', () => { updateExamSummary(); updateWorkflowAndSnapshot(); });
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
            if (state.selectedDiscounts.has(id)) state.selectedDiscounts.delete(id);
            else state.selectedDiscounts.add(id);
            renderDiscountRules();
            updateAll();
        });

        refs.candidateTabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                state.activeCandidateTab = button.dataset.candidateTab;
                renderCandidateTabs();
            });
        });

        refs.dropZone.addEventListener('dragover', (event) => { event.preventDefault(); refs.dropZone.classList.add('is-active'); });
        refs.dropZone.addEventListener('dragleave', () => refs.dropZone.classList.remove('is-active'));
        refs.dropZone.addEventListener('drop', async (event) => {
            event.preventDefault();
            refs.dropZone.classList.remove('is-active');
            const file = event.dataTransfer?.files?.[0];
            if (file) { refs.candidateFile.files = event.dataTransfer.files; await handleCandidateFile(file); }
        });
        refs.candidateFile.addEventListener('change', async (event) => {
            const file = event.target.files?.[0];
            if (file) await handleCandidateFile(file);
        });

        refs.freeCandidateTabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                state.activeFreeCandidateTab = button.dataset.freeCandidateTab;
                renderFreeCandidateTabs();
            });
        });

        if (refs.freeDropZone) {
            refs.freeDropZone.addEventListener('dragover', (event) => { event.preventDefault(); refs.freeDropZone.classList.add('is-active'); });
            refs.freeDropZone.addEventListener('dragleave', () => refs.freeDropZone.classList.remove('is-active'));
            refs.freeDropZone.addEventListener('drop', async (event) => {
                event.preventDefault();
                refs.freeDropZone.classList.remove('is-active');
                const file = event.dataTransfer?.files?.[0];
                if (file) { refs.freeCandidateFile.files = event.dataTransfer.files; await handleFreeCandidateFile(file); }
            });
        }
        if (refs.freeCandidateFile) {
            refs.freeCandidateFile.addEventListener('change', async (event) => {
                const file = event.target.files?.[0];
                if (file) await handleFreeCandidateFile(file);
            });
        }

        if (refs.customDiscountsBtn) {
            refs.customDiscountsBtn.addEventListener('click', () => {
                state.customDiscounts.push({ name: '', description: '', percentage: 10 });
                renderCustomDiscounts();
            });
        }

        refs.applyInstructionTemplate.addEventListener('click', applyInstructionTemplate);

        if (refs.instructionRulesList) {
            refs.instructionRulesList.addEventListener('change', (event) => {
                const checkbox = event.target.closest('input[data-rule-id]');
                if (!checkbox) return;
                const ruleId = cleanText(checkbox.dataset.ruleId || '');
                if (!ruleId) return;

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

                if (refs.instructionRulesCount) refs.instructionRulesCount.textContent = String(state.selectedInstructionRules.size);
                syncInstructionRulesHidden();
                updateWorkflowAndSnapshot();
            });
        }

        if (refs.addExamPartBtn) {
            refs.addExamPartBtn.addEventListener('click', addPartButtonHandler);
        }

        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) return;
            const payload = event.data;
            if (!payload || payload.type !== 'exam-create-question-created') return;

            const questionId = Number(payload.question?.id || 0);
            const targetPart = getPartByKey(state.lastAddQuestionPartKey) || getFirstPart();
            if (!targetPart) return;

            syncQuestionBankFromServerForPart(targetPart).then(() => {
                if (questionId > 0 && isManualQuestionSelectionEnabledForPart(targetPart)) {
                    const limits = getQuestionSelectionLimitsForPart(targetPart);
                    if (targetPart.selectedQuestions.size < limits.max) {
                        targetPart.selectedQuestions.add(questionId);
                    }
                    renderQuestionBankCardsForPart(targetPart);
                    updateExamSummary();
                }
                window.EmsToast?.success('Question created and question bank refreshed.');
            });
        });

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
            refs.title, refs.description, refs.difficulty, refs.status, refs.mode, refs.visibility,
            refs.examCategory,
            refs.enableExamTimer, refs.examDurationMinutes, refs.autoSubmitOnTimerEnd,
            refs.scheduleStartAt, refs.scheduleEndAt, refs.attemptLimitCount,
            refs.passingMarks, refs.enableNegativeMarking, refs.negativeMarkingType,
        ].forEach((field) => {
            if (!field) return;
            field.addEventListener('input', updateWorkflowAndSnapshot);
            field.addEventListener('change', updateWorkflowAndSnapshot);
        });

        // Tom Select may initialize after bindEvents; re-hook category changes.
        window.setTimeout(() => {
            const select = refs.examCategory;
            if (!select) return;
            const ts = select.tomselect;
            if (ts && typeof ts.on === 'function') {
                ts.on('change', updateWorkflowAndSnapshot);
            }
        }, 0);

        window.examCreateUpdateSidebar = updateWorkflowAndSnapshot;
    }

    document.querySelectorAll('.js-warning-limit-preset').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById('focus_violation_limit');
            if (!input) return;
            input.value = String(btn.getAttribute('data-value') || '0');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    // ── Public surface for question-bank-init.js / legacy callers ────

    window.examCreateState = state;
    window.examCreateUpdateAll = updateAll;

    window.syncQuestionBankFromServer = function syncQuestionBankFromServer() {
        const expandedParts = getOrderedParts().filter((p) => p.expanded);
        const targets = expandedParts.length ? expandedParts : getOrderedParts();
        return Promise.all(targets.map((p) => syncQuestionBankFromServerForPart(p)));
    };

    window.loadCategoryQuestions = function loadCategoryQuestions(categoryId, opts) {
        const part = getOrderedParts().find((p) => p.expanded) || getFirstPart();
        if (!part) return Promise.resolve();
        return loadCategoryQuestionsForPart(part, categoryId, opts);
    };
});

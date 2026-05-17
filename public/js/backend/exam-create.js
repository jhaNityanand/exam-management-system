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
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) {
                    throw new Error(`Failed to load ${key}`);
                }
                return [key, await response.json()];
            } finally {
                window.clearTimeout(timeoutId);
            }
        })
    );

    return Object.fromEntries(entries);
}

async function loadJsonMapWithTimeout(endpoints, timeoutMs = 15000) {
    return Promise.race([
        loadJsonMap(endpoints),
        new Promise((_, reject) => {
            window.setTimeout(() => reject(new Error('Exam configuration load timed out')), timeoutMs);
        }),
    ]);
}

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
        totalCategories: document.getElementById('total_categories'),
        totalMarks: document.getElementById('total_marks'),
        passingMarks: document.getElementById('passing_marks'),
        paperSets: document.getElementById('paper_sets'),
        fixCategoryQuestions: document.getElementById('fix_category_questions'),
        paperSetsHelper: document.getElementById('paper-sets-helper'),
        categoryTargetHelper: document.getElementById('category-target-helper'),

        distributionTypeGroup: document.getElementById('distribution-type-group'),
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

        configPreviewList: document.getElementById('config-preview-list'),
        configValidationList: document.getElementById('config-validation-list'),

        marksFilter: document.getElementById('question-marks-filter'),
        marksHidden: document.getElementById('question_marks_filter'),
        marksCount: document.getElementById('selected-marks-count'),
        mixedMarksQuestions: document.getElementById('mixed_marks_questions'),

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
        questionCategoryCards: document.getElementById('question-category-cards'),
        openAddQuestionModal: document.getElementById('open-add-question-modal'),

        instructionTemplate: document.getElementById('instruction_template'),
        applyInstructionTemplate: document.getElementById('apply-instruction-template'),
        instructions: document.getElementById('candidate_instructions'),
        instructionsCount: document.getElementById('instructions-char-count'),

        workflowStatusList: document.getElementById('workflow-status-list'),

        snapshotVisibility: document.getElementById('snapshot-visibility'),
        snapshotMode: document.getElementById('snapshot-mode'),
        snapshotCategories: document.getElementById('snapshot-categories'),
        snapshotMarks: document.getElementById('snapshot-marks'),
        snapshotCandidates: document.getElementById('snapshot-candidates'),
        snapshotDiscounts: document.getElementById('snapshot-discounts'),

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
            currencies: [],
        },
        questionBank: [],
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
        activeCandidateTab: 'import',
        importedCandidates: [],
        manualEmails: [],
        activeFreeCandidateTab: 'import',
        freeImportedCandidates: [],
        freeManualEmails: [],
        tags: [],
        expandedCards: new Set(),
        categoryTree: [],
        extraQuestionsCategoryIds: [],
        extraQuestionsAllocations: {},
        extraQuestionsOptionsKey: '',
        extraQuestionsSelectBound: false,
        mainCategorySelectBound: false,
        categoryHierarchyIndex: { childrenByParent: new Map() },
        isSyncingCategories: false,
        isSyncingExtraQuestions: false,
        suppressCategorySelectEvents: false,
        suppressExtraSelectEvents: false,
        richEditors: new Map(),
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
        showFormErrors(['Unable to load exam configuration. Please refresh the page and try again.']);
        hideLoader();
    });

    async function initialize() {
        let emergencyHide = window.setTimeout(() => {
            console.warn('Exam create page init exceeded time limit; revealing form.');
            hideLoader();
        }, 5000);

        showLoader();

        try {
            const endpoints = window.examCreateConfig?.endpoints || {};
            const configData = await loadJsonMapWithTimeout(endpoints, 15000);
            const categoryTree = Array.isArray(configData.categories) ? configData.categories : [];
            const flatCategories = flattenCategoryTree(categoryTree);

            state.config = {
                difficultyLevels: Array.isArray(configData.difficultyLevels) ? configData.difficultyLevels : [],
                examStatus: Array.isArray(configData.examStatus) ? configData.examStatus : [],
                examModes: Array.isArray(configData.examModes) ? configData.examModes : [],
                visibilityOptions: Array.isArray(configData.visibilityOptions) ? configData.visibilityOptions : [],
                categories: flatCategories,
                discountRules: Array.isArray(configData.discountRules) ? configData.discountRules : [],
                questionMarks: Array.isArray(configData.questionMarks) ? configData.questionMarks : [],
                questionBank: Array.isArray(configData.questionBank) ? configData.questionBank : [],
                pricingOptions: Array.isArray(configData.pricingOptions) ? configData.pricingOptions : [],
                distributionTypes: Array.isArray(configData.distributionTypes) ? configData.distributionTypes : [],
                instructionTemplates: Array.isArray(configData.instructionTemplates) ? configData.instructionTemplates : [],
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

            renderInitialControls();
            initEnhancedSelects();
            renderCategorySelector();
            bindEvents();
            bindMainCategorySelect();
            bindExtraQuestionsCategorySelect();

            window.clearTimeout(emergencyHide);
            emergencyHide = null;
            hideLoader();

            initRichTextEditors().catch((error) => {
                console.warn(error);
            });
            safeUpdateAll();
        } catch (error) {
            console.error(error);
            showFormErrors(['Unable to load exam configuration. Please refresh the page and try again.']);
            throw error;
        } finally {
            if (emergencyHide) {
                window.clearTimeout(emergencyHide);
            }
            hideLoader();
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

    function renderInitialControls() {
        populateSelect(refs.difficulty, state.config.difficultyLevels, 'Select difficulty');
        populateSelect(refs.status, state.config.examStatus, 'Select status');
        populateSelect(refs.mode, state.config.examModes, 'Select mode');
        populateSelect(refs.visibility, state.config.visibilityOptions, 'Select visibility');

        setSelectDefault(refs.difficulty, 'medium');
        setSelectDefault(refs.status, 'draft');
        setSelectDefault(refs.mode, 'standard');
        setSelectDefault(refs.visibility, 'public');

        state.selectedMode = refs.mode.value;
        state.selectedVisibility = refs.visibility.value;

        renderTotalCategoryOptions();
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

        renderInstructionTemplates();
        renderModalSelects();
        refs.manualEmailFeedback.textContent = 'Type email and press Enter to add.';

        const defaultTags = jsonSafeParse(refs.tagsHidden.value);
        if (Array.isArray(defaultTags) && defaultTags.length) {
            tagInput.setValues(defaultTags);
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

    function renderTotalCategoryOptions() {
        const categories = getAssignableCategories();
        const max = Math.max(1, categories.length);
        const preferred = Math.min(3, max);

        refs.totalCategories.min = '1';
        refs.totalCategories.max = String(max);

        const currentValue = toInt(refs.totalCategories.value, preferred);
        refs.totalCategories.value = String(Math.min(Math.max(1, currentValue), max));

        state.selectedCategories = new Set(categories.slice(0, preferred).map((category) => category.id));
        refs.selectedCategoriesHidden.value = JSON.stringify([...state.selectedCategories]);

        state.extraQuestionsCategoryIds = categories[0]?.id ? [categories[0].id] : [];
    }

    function renderDistributionTypes() {
        if (!state.selectedDistributionType && state.config.distributionTypes.length) {
            state.selectedDistributionType = state.config.distributionTypes[0].id;
        }

        refs.distributionTypeGroup.innerHTML = state.config.distributionTypes
            .map((type) => {
                const active = type.id === state.selectedDistributionType ? 'is-active' : '';
                return `<button type="button" class="pill ${active}" data-distribution-id="${escapeHtml(type.id)}">${escapeHtml(type.label)}</button>`;
            })
            .join('');
    }

    function applyMainCategorySelectionRules(rawIds, limit) {
        const validIds = new Set(getAssignableCategories().map((category) => category.id));
        const filtered = [...rawIds].filter((id) => validIds.has(id));
        const pruned = [...pruneDescendantSelections(filtered, state.categoryHierarchyIndex)];
        return pruned.slice(0, Math.max(1, limit));
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
        const limit = toInt(refs.totalCategories.value, 0);
        const selectedCount = state.selectedCategories.size;

        refs.selectedCategoriesHidden.value = JSON.stringify([...state.selectedCategories]);
        refs.categoryFeedback.textContent = `Selected ${selectedCount} of ${limit} required categories.`;

        const isSelectionComplete = limit > 0 && selectedCount >= limit;
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
        const limit = toInt(refs.totalCategories.value, 0);
        const normalized = applyMainCategorySelectionRules([...state.selectedCategories], limit);
        state.selectedCategories = new Set(normalized);
        refs.selectedCategoriesSelect.dataset.maxItems = String(Math.max(1, limit));
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

            const limit = Math.max(1, toInt(refs.totalCategories.value, 1));
            const rawValue = window.EmsSelect.getValue('selected_categories_select');
            const selectedValues = Array.isArray(rawValue) ? rawValue : (rawValue ? [rawValue] : []);
            const normalized = applyMainCategorySelectionRules(selectedValues, limit);

            state.selectedCategories = new Set(normalized);
            state.extraQuestionsOptionsKey = '';
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
            const limit = toInt(refs.totalCategories.value, 0);
            const normalized = applyMainCategorySelectionRules([...state.selectedCategories], limit);
            state.selectedCategories = new Set(normalized);
            const html = buildCategorySelectOptionsHtml(state.selectedCategories);
            const values = [...state.selectedCategories];
            const maxItems = Math.max(1, limit);

            const isSelectionComplete = limit > 0 && state.selectedCategories.size >= limit;

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
                        <div class="mt-2" ${selected ? '' : 'hidden'}>
                            <label class="text-xs font-semibold text-gray-700">Discount Percentage (%)</label>
                            <input type="number" class="panel-input discount-percentage-input" data-rule-id="${escapeHtml(rule.id)}" value="${percentage}" min="0" max="100" style="margin-top: 4px; padding: 4px 8px;">
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
                updateWorkflowAndSnapshot();
            });
        });
    }

    /* ── Custom Discount Management ── */
    function renderCustomDiscounts() {
        if (!refs.customDiscountsContainer) return;

        if (state.customDiscounts.length === 0) {
            refs.customDiscountsContainer.innerHTML = `
                <div class="text-center py-4 px-3 border border-dashed border-slate-200 rounded-lg text-slate-400 text-xs bg-slate-50/50">
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
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Offer Name <span class="text-red-500">*</span></label>
                                <input type="text" class="panel-input custom-discount-name" data-row-index="${index}" placeholder="e.g. Summer Sale" value="${name}">
                                <p class="exam-help is-invalid mt-1 text-xs" id="err-custom-name-${index}" hidden></p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                                <input type="text" class="panel-input custom-discount-desc" data-row-index="${index}" placeholder="e.g. Valid for standard exams" value="${desc}">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Percentage (%) <span class="text-red-500">*</span></label>
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

    function renderInstructionTemplates() {
        refs.instructionTemplate.innerHTML = [`<option value="">Choose template</option>`]
            .concat(
                state.config.instructionTemplates.map(
                    (template) => `<option value="${escapeHtml(template.id)}">${escapeHtml(template.label)}</option>`
                )
            )
            .join('');
    }

    function renderModalSelects() {
        const categories = getAssignableCategories();

        refs.newQuestionCategory.innerHTML = categories
            .map((category) => buildCategoryOptionMarkup(category, state.config.categories))
            .join('');

        refs.newQuestionMarks.innerHTML = state.config.questionMarks
            .map((mark) => `<option value="${Number(mark.value)}">${escapeHtml(mark.label)}</option>`)
            .join('');

        refs.newQuestionDifficulty.innerHTML = state.config.difficultyLevels
            .map((level) => `<option value="${escapeHtml(level.id)}">${escapeHtml(level.label)}</option>`)
            .join('');

        if (!state.extraQuestionsCategoryIds.length && categories.length) {
            state.extraQuestionsCategoryIds = [categories[0].id];
        }
        syncExtraQuestionsHidden();
    }

    function bindEvents() {
        refs.mode.addEventListener('change', () => {
            state.selectedMode = refs.mode.value;
            updateAll();
        });

        refs.visibility.addEventListener('change', () => {
            state.selectedVisibility = refs.visibility.value;
            renderPricingOptions();
            updateAll();
        });

        const handleTotalCategoriesInput = () => {
            normalizeTotalCategoriesInput();
            enforceCategoryLimit();
            renderCategorySelector();
            updateAll();
        };
        refs.totalCategories.addEventListener('input', handleTotalCategoriesInput);
        refs.totalCategories.addEventListener('change', handleTotalCategoriesInput);

        bindExtraQuestionsCategorySelect();

        [
            refs.totalQuestions,
            refs.totalMarks,
            refs.passingMarks,
            refs.paperSets,
            refs.fixCategoryQuestions,
        ].forEach((field) => {
            field.addEventListener('input', updateAll);
            field.addEventListener('change', updateAll);
        });

        refs.distributionTypeGroup.addEventListener('click', (event) => {
            const button = event.target.closest('[data-distribution-id]');
            if (!button) return;
            state.selectedDistributionType = button.dataset.distributionId;
            renderDistributionTypes();
            updateAll();
        });

        bindMainCategorySelect();

        refs.marksFilter.addEventListener('click', (event) => {
            const button = event.target.closest('[data-mark-value]');
            if (!button) return;

            const mark = Number(button.dataset.markValue);
            
            if (refs.mixedMarksQuestions && refs.mixedMarksQuestions.checked) {
                if (state.selectedMarks.has(mark)) {
                    state.selectedMarks.delete(mark);
                } else {
                    state.selectedMarks.add(mark);
                }
            } else {
                state.selectedMarks.clear();
                state.selectedMarks.add(mark);
            }

            renderQuestionMarks();
            updateAll();
        });

        if (refs.mixedMarksQuestions) {
            refs.mixedMarksQuestions.addEventListener('change', () => {
                if (!refs.mixedMarksQuestions.checked && state.selectedMarks.size > 1) {
                    const firstMark = Array.from(state.selectedMarks)[0];
                    state.selectedMarks.clear();
                    if (firstMark) state.selectedMarks.add(firstMark);
                }
                renderQuestionMarks();
                updateAll();
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
            if (event.target.closest('input')) return;
            
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

        refs.questionSearch.addEventListener('input', updateQuestionBankCards);

        refs.questionCategoryCards.addEventListener('click', (event) => {
            const expandButton = event.target.closest('[data-action="toggle-expand"]');
            const addButton = event.target.closest('[data-action="add-question"]');

            if (expandButton) {
                const categoryId = expandButton.dataset.categoryId;
                if (state.expandedCards.has(categoryId)) {
                    state.expandedCards.delete(categoryId);
                } else {
                    state.expandedCards.add(categoryId);
                }
                updateQuestionBankCards();
                return;
            }

            if (addButton) {
                openAddQuestionModal(addButton.dataset.categoryId || '');
            }
        });

        refs.questionCategoryCards.addEventListener('change', (event) => {
            const checkbox = event.target.closest('[data-role="category-toggle"]');
            if (!checkbox) return;

            const categoryId = checkbox.dataset.categoryId;
            const limit = toInt(refs.totalCategories.value, 0);

            if (checkbox.checked) {
                if (!state.selectedCategories.has(categoryId) && state.selectedCategories.size >= limit) {
                    checkbox.checked = false;
                    refs.categoryFeedback.textContent = `Cannot select more than ${limit} categories.`;
                    return;
                }
                const nextSelection = applyMainCategorySelectionRules(
                    [...state.selectedCategories, categoryId],
                    limit
                );
                state.selectedCategories = new Set(nextSelection);
            } else {
                state.selectedCategories.delete(categoryId);
            }

            state.extraQuestionsOptionsKey = '';
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

        refs.openAddQuestionModal.addEventListener('click', () => openAddQuestionModal(''));
        refs.modalCloseButtons.forEach((button) => button.addEventListener('click', closeAddQuestionModal));
        refs.addQuestionForm.addEventListener('submit', (event) => {
            event.preventDefault();
            addQuestionFromModal();
        });

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
            refs.totalQuestions,
            refs.totalMarks,
            refs.passingMarks,
            refs.paperSets,
        ].forEach((field) => {
            field.addEventListener('input', updateWorkflowAndSnapshot);
            field.addEventListener('change', updateWorkflowAndSnapshot);
        });
    }

    function enforceCategoryLimit() {
        const limit = normalizeTotalCategoriesInput();
        state.selectedCategories = new Set([...state.selectedCategories].slice(0, limit));
        refs.selectedCategoriesHidden.value = JSON.stringify([...state.selectedCategories]);
    }

    function normalizeTotalCategoriesInput() {
        const max = Math.max(1, getAssignableCategories().length);
        const min = 1;
        const value = toInt(refs.totalCategories.value, min);
        const clamped = Math.min(Math.max(min, value), max);
        refs.totalCategories.min = String(min);
        refs.totalCategories.max = String(max);
        refs.totalCategories.value = String(clamped);
        return clamped;
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
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        let parsed = [];

        if (extension === 'csv') {
            parsed = parseCandidateCsv(await file.text());
        } else {
            parsed = [
                { name: 'Imported Candidate 1', email: 'candidate1@example.com' },
                { name: 'Imported Candidate 2', email: 'candidate2@example.com' },
                { name: 'Imported Candidate 3', email: 'candidate3@example.com' },
            ];
        }

        state.importedCandidates = parsed.filter((candidate) => isValidEmail(candidate.email));
        refs.importedCandidatesHidden.value = JSON.stringify(state.importedCandidates);
        renderImportedCandidatePreview(file.name);
        updateWorkflowAndSnapshot();
    }

    async function handleFreeCandidateFile(file) {
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        let parsed = [];

        if (extension === 'csv') {
            parsed = parseCandidateCsv(await file.text());
        } else {
            parsed = [
                { name: 'Imported Candidate 1', email: 'candidate1@example.com' },
                { name: 'Imported Candidate 2', email: 'candidate2@example.com' },
                { name: 'Imported Candidate 3', email: 'candidate3@example.com' },
            ];
        }

        state.freeImportedCandidates = parsed.filter((candidate) => isValidEmail(candidate.email));
        refs.freeImportedCandidatesHidden.value = JSON.stringify(state.freeImportedCandidates);
        renderFreeImportedCandidatePreview(file.name);
        updateWorkflowAndSnapshot();
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
            { id: 'exam-configuration-section', defaultTitle: 'Exam Configuration' },
            { id: 'question-rules-section', defaultTitle: 'Question Rules and Filters' },
            { id: 'pricing-section', defaultTitle: 'Pricing and Discount Rules' },
            { id: 'question-bank-section', defaultTitle: 'Question Bank Management' },
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
        const isPrivate = state.selectedVisibility === 'private';
        const importedFree = state.selectedPricing === 'free_for_imported';
        
        // Hide Section 2 by default. Only show if visibility is private (Private candidates different concept!)
        refs.candidateSection.hidden = !isPrivate;
        
        // Show/hide Free Candidate List section depending on selected pricing option
        refs.freeCandidatesWrap.hidden = !importedFree;
        
        refs.pricingImportedNote.hidden = !importedFree;
        renderCandidateTabs();
        renderFreeCandidateTabs();

        refs.pricingSection.hidden = state.selectedMode === 'practice';
        
        // Update the visible sections dynamic numbering
        updateSectionNumbers();
    }

    function computeCategoryTarget() {
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions.value, 1));
        const totalCategories = Math.max(1, toInt(refs.totalCategories.value, 1));
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

    function normalizeExtraQuestionCategoryIds(selectedIds, remainder) {
        const maxExtra = Math.max(1, remainder);
        let extraIds = state.extraQuestionsCategoryIds.filter((id) => selectedIds.includes(id));

        if (!remainder) {
            state.extraQuestionsCategoryIds = [];
            syncExtraQuestionsHidden();
            return;
        }

        if (!extraIds.length) {
            extraIds = [selectedIds[0]];
        }

        state.extraQuestionsCategoryIds = applyExtraCategorySelectionRules(extraIds, maxExtra, selectedIds);
        syncExtraQuestionsHidden();
    }

    function computeFixedCategoryDistribution() {
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions.value, 1));
        const selectedIds = [...state.selectedCategories];
        const selectedCount = selectedIds.length;

        if (!selectedCount) {
            return {
                totalQuestions,
                selectedCount: 0,
                base: 0,
                remainder: 0,
                extraCategoryIds: [],
                rows: [],
                totalAllocated: 0,
            };
        }

        const base = Math.floor(totalQuestions / selectedCount);
        const remainder = totalQuestions % selectedCount;

        const extraCountMap = new Map();
        const extraCategoryIds = state.extraQuestionsCategoryIds
            .filter((id) => selectedIds.includes(id))
            .slice(0, Math.max(1, remainder));

        let totalAllocated = 0;

        if (remainder > 0 && extraCategoryIds.length > 0) {
            const S = extraCategoryIds.length;
            const E = remainder;

            if (S === 1) {
                extraCountMap.set(extraCategoryIds[0], E);
                totalAllocated = E;
            } else if (S === E) {
                extraCategoryIds.forEach((categoryId) => {
                    extraCountMap.set(categoryId, 1);
                });
                totalAllocated = E;
            } else if (1 < S && S < E) {
                let hasInitializedAny = false;
                extraCategoryIds.forEach((categoryId) => {
                    if (typeof state.extraQuestionsAllocations[categoryId] === 'undefined') {
                        state.extraQuestionsAllocations[categoryId] = 1;
                        hasInitializedAny = true;
                    }
                });

                Object.keys(state.extraQuestionsAllocations).forEach(key => {
                    if (!extraCategoryIds.includes(key)) {
                        delete state.extraQuestionsAllocations[key];
                    }
                });

                if (hasInitializedAny && extraCategoryIds.length > 0) {
                    let currentSum = extraCategoryIds.reduce((sum, id) => sum + state.extraQuestionsAllocations[id], 0);
                    if (currentSum < E) {
                        state.extraQuestionsAllocations[extraCategoryIds[0]] += (E - currentSum);
                    }
                }

                let manualAllocated = 0;
                extraCategoryIds.forEach((categoryId) => {
                    const allocatedCount = toInt(state.extraQuestionsAllocations[categoryId], 1);
                    extraCountMap.set(categoryId, allocatedCount);
                    manualAllocated += allocatedCount;
                });
                totalAllocated = manualAllocated;
            }
        }

        const rows = selectedIds.map((categoryId) => ({
            categoryId,
            count: base + (extraCountMap.get(categoryId) || 0),
        }));

        return {
            totalQuestions,
            selectedCount,
            base,
            remainder,
            extraCategoryIds,
            rows,
            totalAllocated,
        };
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
        const distribution = computeFixedCategoryDistribution();
        refs.fixedDistributionList.innerHTML = distribution.rows
            .map((row) => `<li>${escapeHtml(getCategoryLabelById(row.categoryId))}: <strong>${row.count}</strong> questions</li>`)
            .join('');
    }

    function renderExtraQuestionsAllocations(distribution) {
        const S = distribution.extraCategoryIds.length;
        const E = distribution.remainder;

        if (S > 1 && S < E) {
            refs.extraQuestionsAllocationsWrap.hidden = false;
            refs.remainingCount.textContent = String(E);
            refs.allocatedCount.textContent = String(distribution.totalAllocated);

            const maxAllowed = E - S + 1;

            const allocationsHtml = distribution.extraCategoryIds.map((categoryId) => {
                const categoryName = escapeHtml(getCategoryLabelById(categoryId));
                const count = state.extraQuestionsAllocations[categoryId] || 1;
                return `
                    <div>
                        <label class="exam-label">${categoryName}</label>
                        <input type="number" class="panel-input extra-question-allocation-input" data-category-id="${escapeHtml(categoryId)}" value="${count}" min="1" max="${maxAllowed}">
                    </div>
                `;
            }).join('');

            const structureKey = distribution.extraCategoryIds.join(',');
            if (refs.extraQuestionsAllocationList.dataset.structureKey !== structureKey) {
                refs.extraQuestionsAllocationList.innerHTML = allocationsHtml;
                refs.extraQuestionsAllocationList.dataset.structureKey = structureKey;

                refs.extraQuestionsAllocationList.querySelectorAll('.extra-question-allocation-input').forEach((input) => {
                    input.addEventListener('input', (e) => {
                        const cid = e.target.dataset.categoryId;
                        let rawVal = parseInt(e.target.value, 10);
                        const maxVal = parseInt(e.target.getAttribute('max'), 10) || 1;

                        if (isNaN(rawVal) || rawVal < 1) {
                            rawVal = 1;
                            e.target.value = rawVal;
                        } else if (rawVal > maxVal) {
                            rawVal = maxVal;
                            e.target.value = rawVal;
                        }

                        state.extraQuestionsAllocations[cid] = rawVal;
                        syncExtraQuestionsAllocations();
                        updateAll();
                    });
                });
            } else {
                refs.extraQuestionsAllocationList.querySelectorAll('.extra-question-allocation-input').forEach((input) => {
                    if (document.activeElement === input) return;
                    const cid = input.dataset.categoryId;
                    const val = state.extraQuestionsAllocations[cid] || 1;
                    if (String(input.value) !== String(val)) {
                        input.value = val;
                    }
                });
            }

            syncExtraQuestionsAllocations();

        } else {
            refs.extraQuestionsAllocationsWrap.hidden = true;
            refs.extraQuestionsAllocationList.innerHTML = '';
            refs.extraQuestionsAllocationList.dataset.structureKey = '';
            
            refs.allocatedCount.textContent = String(distribution.totalAllocated || 0);
            refs.remainingCount.textContent = String(E);
        }
    }

    function renderFixedCategoryDistribution() {
        if (!refs.fixCategoryQuestions.checked || state.selectedCategories.size === 0) {
            refs.fixedDistributionCard.hidden = true;
            refs.fixedDistributionList.innerHTML = '';
            refs.extraQuestionsWrap.hidden = true;
            if (refs.extraQuestionsAllocationsWrap) refs.extraQuestionsAllocationsWrap.hidden = true;
            state.extraQuestionsOptionsKey = '';
            return;
        }

        const distribution = computeFixedCategoryDistribution();

        refs.fixedDistributionCard.hidden = false;
        
        let helperText = `Exact allocation is ${distribution.base} questions per selected category.`;
        if (distribution.remainder > 0) {
            if (distribution.remainder > 1) {
                helperText = `Base allocation is ${distribution.base} per category. Choose up to ${distribution.remainder} categories to receive extra questions.`;
            } else {
                helperText = `Base allocation is ${distribution.base} per category. Choose one category to receive the extra question.`;
            }
        }
        refs.fixedDistributionHelper.textContent = helperText;

        if (distribution.remainder > 0) {
            refs.extraQuestionsWrap.hidden = false;
            renderExtraQuestionsCategorySelect(distribution);
        } else {
            refs.extraQuestionsWrap.hidden = true;
            state.extraQuestionsOptionsKey = '';
        }

        renderExtraQuestionsAllocations(distribution);
        renderFixedDistributionListOnly();
    }

    function updateConfigPreview() {
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions.value, 1));
        const totalCategories = Math.max(1, toInt(refs.totalCategories.value, 1));
        const selectedCount = state.selectedCategories.size;
        const paperSets = Math.max(1, toInt(refs.paperSets.value, 1));
        const totalMarks = Math.max(0, toInt(refs.totalMarks.value, 0));
        const passingMarks = Math.max(0, toInt(refs.passingMarks.value, 0));
        const fixedPerCategory = refs.fixCategoryQuestions.checked;
        const perCategoryTarget = computeCategoryTarget();
        const distribution = state.config.distributionTypes.find((item) => item.id === state.selectedDistributionType);

        refs.paperSets.min = '1';
        refs.paperSets.max = String(totalQuestions);
        refs.paperSetsHelper.textContent = `Allowed range: 1 to ${totalQuestions} set(s).`;
        refs.categoryTargetHelper.textContent = `Target category count: ${totalCategories}. Current selection: ${selectedCount}.`;

        const fixedDistribution = computeFixedCategoryDistribution();
        const fixedInfo = fixedPerCategory
            ? (
                fixedDistribution.selectedCount > 0
                    ? `Fixed allocation: ${fixedDistribution.base} each${fixedDistribution.remainder ? ` + ${fixedDistribution.remainder} extra distributed among ${fixedDistribution.extraCategoryIds.map((id) => getCategoryLabelById(id)).join(', ')}` : ''}`
                    : 'Fixed allocation enabled. Select categories to calculate distribution.'
            )
            : 'Fixed allocation disabled';

        refs.configPreviewList.innerHTML = [
            `<li>Total questions planned: <strong>${totalQuestions}</strong></li>`,
            `<li>Distribution mode: <strong>${escapeHtml(distribution?.label || '-')}</strong></li>`,
            `<li>Paper sets: <strong>${paperSets}</strong> / ${totalQuestions}</li>`,
            `<li>Category requirement: <strong>${selectedCount}</strong> selected of ${totalCategories}</li>`,
            `<li>Passing threshold: <strong>${passingMarks}</strong> of ${totalMarks} marks</li>`,
            `<li>${escapeHtml(fixedInfo)}</li>`,
        ].join('');

        const validations = [];
        validations.push(
            selectedCount === totalCategories
                ? '<li class="status-ok">Category selection count is exact.</li>'
                : '<li class="status-warning">Select exactly the configured number of categories.</li>'
        );
        validations.push(
            paperSets >= 1 && paperSets <= totalQuestions
                ? '<li class="status-ok">Paper sets are within allowed limits.</li>'
                : '<li class="status-error">Paper sets must be between 1 and total questions.</li>'
        );
        validations.push(
            passingMarks <= totalMarks
                ? '<li class="status-ok">Passing marks do not exceed total marks.</li>'
                : '<li class="status-error">Passing marks cannot exceed total marks.</li>'
        );
        if (fixedPerCategory) {
            if (fixedDistribution.remainder > 0) {
                if (fixedDistribution.extraCategoryIds.length === 0) {
                    validations.push(`<li class="status-warning">Select ${fixedDistribution.remainder === 1 ? 'a category' : 'categories'} for extra questions.</li>`);
                } else if (fixedDistribution.totalAllocated < fixedDistribution.remainder) {
                    validations.push(`<li class="status-warning">Please allocate exactly ${fixedDistribution.remainder} extra questions. Currently allocated: ${fixedDistribution.totalAllocated}.</li>`);
                } else if (fixedDistribution.totalAllocated > fixedDistribution.remainder) {
                    validations.push(`<li class="status-error">Allocated extra questions (${fixedDistribution.totalAllocated}) exceeds limit (${fixedDistribution.remainder}).</li>`);
                } else {
                    validations.push('<li class="status-ok">Extra question categories are configured.</li>');
                }
            } else {
                validations.push('<li class="status-ok">Exact category distribution can be applied.</li>');
            }
        }

        refs.configValidationList.innerHTML = validations.join('');
    }

    function updateQuestionBankCards() {
        const totalCategories = Math.max(1, toInt(refs.totalCategories.value, 1));
        const totalQuestions = Math.max(1, toInt(refs.totalQuestions.value, 1));
        const fixedPerCategory = refs.fixCategoryQuestions.checked;
        const query = cleanText(refs.questionSearch.value).toLowerCase();
        const perCategoryTarget = computeCategoryTarget();
        const categoryPool = getAssignableCategories();
        const fixedDistribution = computeFixedCategoryDistribution();
        const distributionMap = new Map(fixedDistribution.rows.map((row) => [row.categoryId, row.count]));
        let matchingSnippets = 0;

        refs.questionCategoryCards.innerHTML = categoryPool
            .map((category) => {
                const selected = state.selectedCategories.has(category.id);
                const available = Math.max(0, toInt(state.categoryAvailability[category.id], 0));
                const target = selected
                    ? (fixedPerCategory ? (distributionMap.get(category.id) || 0) : available)
                    : 0;
                const shortage = fixedPerCategory && selected && target > 0 && available < target;
                const progress = target > 0 ? Math.min(100, Math.round((available / target) * 100)) : 0;
                const statusClass = shortage ? 'status-warning' : 'status-good';
                const statusLabel = shortage
                    ? 'Insufficient questions'
                    : (fixedPerCategory ? 'Sufficient availability' : 'Flexible allocation');

                const snippets = state.questionBank
                    .filter((question) => question.categoryId === category.id)
                    .filter((question) => state.selectedMarks.has(Number(question.marks)))
                    .filter((question) => !query || String(question.text || '').toLowerCase().includes(query))
                    .slice(0, 6);

                matchingSnippets += snippets.length;
                const expanded = state.expandedCards.has(category.id);

                const listHtml = snippets.length
                    ? snippets.map((question) => `<li>#${question.id} (${question.marks}m) - ${escapeHtml(question.text)}</li>`).join('')
                    : '<li>No questions match current marks/search filters.</li>';

                return `
                    <article class="question-category-card ${shortage ? 'is-warning' : ''} ${expanded ? 'is-expanded' : ''}">
                        <div class="question-category-head">
                            <div class="question-category-main">
                                <input type="checkbox" data-role="category-toggle" data-category-id="${escapeHtml(category.id)}" ${selected ? 'checked' : ''}>
                                <div>
                                    <h4 class="question-category-title">${getCategoryDisplayHtml(category.id)} <span class="question-category-count">(${available}/${target || 0})</span></h4>
                                    <p class="question-category-meta">${fixedPerCategory ? `Required target: ${target || 0}` : 'Required target: Flexible'} | Available: ${available}</p>
                                    <span class="question-status ${statusClass}">${statusLabel}</span>
                                </div>
                            </div>
                            <div class="question-category-actions">
                                <button type="button" class="panel-button-secondary" data-action="add-question" data-category-id="${escapeHtml(category.id)}">Add Questions</button>
                                <button type="button" class="icon-btn" data-action="toggle-expand" data-category-id="${escapeHtml(category.id)}">${expanded ? 'Hide' : 'View'}</button>
                            </div>
                        </div>
                        <div class="question-progress">
                            <div class="question-progress-bar"><span style="width:${progress}%;"></span></div>
                            <p class="question-progress-text">${getCategoryDisplayHtml(category.id)} progress: ${available}/${target || 0}</p>
                        </div>
                        <div class="question-category-body">
                            ${shortage ? '<p class="exam-help">This category is below required question count. Use Add Questions to fill the gap.</p>' : ''}
                            <ul class="question-snippet-list">${listHtml}</ul>
                        </div>
                    </article>
                `;
            })
            .join('');

        refs.questionBankFeedback.textContent = !state.selectedMarks.size
            ? 'Select at least one marks filter to fetch matching questions.'
            : `Loaded ${matchingSnippets} matching sample question(s) across ${categoryPool.length} categories.`;
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

    function updateWorkflowAndSnapshot() {
        const basicComplete = cleanText(refs.title.value).length >= 3
            && refs.difficulty.value && refs.status.value && refs.mode.value && refs.visibility.value;
        const candidateVisible = !refs.candidateSection.hidden;
        const candidateComplete = !candidateVisible || state.importedCandidates.length > 0 || state.manualEmails.length > 0;
        const categoryLimit = toInt(refs.totalCategories.value, 0);
        const configComplete = state.selectedCategories.size === categoryLimit && toInt(refs.totalQuestions.value, 0) > 0 &&
            (!refs.fixCategoryQuestions.checked || computeFixedCategoryDistribution().totalAllocated === computeFixedCategoryDistribution().remainder);
        const marksComplete = state.selectedMarks.size > 0;
        
        const freeCount = state.freeImportedCandidates.length + state.freeManualEmails.length;
        const pricingComplete = refs.pricingSection.hidden || (Boolean(state.selectedPricing) && (state.selectedPricing !== 'free_for_imported' || freeCount > 0));
        
        const fixedDistribution = computeFixedCategoryDistribution();
        const distributionMap = new Map(fixedDistribution.rows.map((row) => [row.categoryId, row.count]));
        const shortages = refs.fixCategoryQuestions.checked
            ? [...state.selectedCategories].filter((id) => {
                const required = distributionMap.get(id) || 0;
                return toInt(state.categoryAvailability[id], 0) < required;
            }).length
            : (() => {
                const totalAvailable = [...state.selectedCategories].reduce(
                    (sum, id) => sum + toInt(state.categoryAvailability[id], 0),
                    0
                );
                return totalAvailable >= Math.max(1, toInt(refs.totalQuestions.value, 1)) ? 0 : 1;
            })();
        const questionBankComplete = state.selectedCategories.size > 0 && shortages === 0;
        const instructionsComplete = getInstructionTextLength() > 20;

        const checklist = [
            { label: 'Basic Information', complete: basicComplete, show: true },
            { label: 'Candidate Access', complete: candidateComplete, show: candidateVisible },
            { label: 'Exam Configuration', complete: configComplete, show: true },
            { label: 'Question Rules', complete: marksComplete, show: true },
            { label: 'Pricing and Discount', complete: pricingComplete, show: !refs.pricingSection.hidden },
            { label: 'Question Bank', complete: questionBankComplete, show: true },
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
        
        const privateCount = state.importedCandidates.length + state.manualEmails.length;
        if (state.selectedPricing === 'free_for_imported') {
            refs.snapshotCandidates.textContent = `${privateCount} (Private) / ${freeCount} (Free)`;
        } else {
            refs.snapshotCandidates.textContent = String(privateCount);
        }
        
        refs.snapshotDiscounts.textContent = String(state.selectedDiscounts.size);
    }

    async function initRichTextEditors() {
        if (!window.EmsRichTextEditor || typeof window.EmsRichTextEditor.initAll !== 'function') {
            if (refs.description) {
                refs.description.classList.remove('hidden');
                refs.description.classList.add('panel-input');
            }
            if (refs.instructions) {
                refs.instructions.classList.remove('hidden');
                refs.instructions.classList.add('panel-input');
                refs.instructions.addEventListener('input', updateInstructionCounter);
            }
            updateInstructionCounter();
            return;
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
            if (refs.description) {
                refs.description.classList.remove('hidden');
                refs.description.classList.add('panel-input');
            }
            if (refs.instructions) {
                refs.instructions.classList.remove('hidden');
                refs.instructions.classList.add('panel-input');
                refs.instructions.addEventListener('input', updateInstructionCounter);
            }
        }
        state.richEditors = registry instanceof Map ? registry : new Map();

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

    function openAddQuestionModal(categoryId) {
        if (categoryId) {
            if (window.EmsSelect && typeof window.EmsSelect.setValue === 'function') {
                window.EmsSelect.setValue('new_question_category', categoryId);
            } else {
                refs.newQuestionCategory.value = categoryId;
            }
        }
        refs.modal.hidden = false;
    }

    function closeAddQuestionModal() {
        refs.modal.hidden = true;
        refs.addQuestionForm.reset();

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
        const categoryId = refs.newQuestionCategory.value;
        const marks = toInt(refs.newQuestionMarks.value, 1);
        const difficulty = refs.newQuestionDifficulty.value || 'medium';
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
        const totalCategories = normalizeTotalCategoriesInput();
        const totalMarks = toInt(refs.totalMarks.value, 0);
        const passingMarks = toInt(refs.passingMarks.value, 0);
        const paperSets = toInt(refs.paperSets.value, 0);

        if (cleanText(refs.title.value).length < 3) errors.push('Exam title must be at least 3 characters long.');
        if (totalQuestions < 1) errors.push('Total questions must be at least 1.');
        if (totalCategories < 1) errors.push('Total categories must be at least 1.');
        if (state.selectedCategories.size !== totalCategories) errors.push('Selected categories must exactly match Total Categories Used.');
        if (!Number.isInteger(paperSets) || paperSets < 1 || paperSets > totalQuestions) errors.push('Paper sets must be a whole number between 1 and total questions.');
        if (passingMarks > totalMarks) errors.push('Passing marks cannot exceed total marks.');
        if (refs.fixCategoryQuestions.checked) {
            const distribution = computeFixedCategoryDistribution();
            if (distribution.remainder === 1 && !distribution.extraCategoryIds.length) {
                errors.push('Select a category to receive the remainder question in fixed allocation mode.');
            }
            if (distribution.remainder > 1 && !distribution.extraCategoryIds.length) {
                errors.push('Select at least one category to receive extra questions in fixed allocation mode.');
            }
            if (distribution.remainder > 1 && distribution.extraCategoryIds.length > 0 && distribution.totalAllocated !== distribution.remainder) {
                errors.push(`Please allocate exactly ${distribution.remainder} extra questions. Currently allocated: ${distribution.totalAllocated}.`);
            }
        } else {
            const totalAvailable = [...state.selectedCategories].reduce(
                (sum, categoryId) => sum + toInt(state.categoryAvailability[categoryId], 0),
                0
            );
            if (totalAvailable < totalQuestions) {
                errors.push('Selected categories do not have enough total questions for flexible allocation.');
            }
        }
        if (!state.selectedMarks.size) errors.push('Select at least one question marks filter.');
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
        refs.discountHidden.value = JSON.stringify([...state.selectedDiscounts]);
        refs.pricingOptionHidden.value = state.selectedPricing;
        refs.tagsHidden.value = JSON.stringify(state.tags);
        refs.manualEmailsHidden.value = JSON.stringify(state.manualEmails);
        refs.importedCandidatesHidden.value = JSON.stringify(state.importedCandidates);
        
        refs.freeManualEmailsHidden.value = JSON.stringify(state.freeManualEmails);
        refs.freeImportedCandidatesHidden.value = JSON.stringify(state.freeImportedCandidates);
        
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
        updateConditionalSections();
        updateConfigPreview();
        renderFixedCategoryDistribution();
        updateQuestionBankCards();
        renderDiscountSummary();
        updateWorkflowAndSnapshot();
    }
});

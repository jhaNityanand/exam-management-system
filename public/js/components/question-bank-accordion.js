/**
 * QuestionBankAccordion Component
 * 
 * Manages category-wise accordion display of questions
 * Features:
 * - Collapsible/expandable accordion sections per category
 * - Smooth expand/collapse animations
 * - Displays question count per category
 * - Filters questions based on selected categories
 * - Dynamic loading from static JSON
 */

class QuestionBankAccordion {
    constructor(container, config = {}) {
        this.container = container;
        this.config = {
            questionBankEndpoint: config.questionBankEndpoint || null,
            categoriesEndpoint: config.categoriesEndpoint || null,
            onCategoryToggle: config.onCategoryToggle || (() => {}),
            animationDuration: config.animationDuration || 300,
            ...config
        };

        this.state = {
            allQuestions: [],
            allCategories: [],
            selectedCategories: new Set(),
            expandedCategories: new Set(),
            filteredQuestions: []
        };

        this.refs = {
            container: container,
            searchInput: container?.querySelector('[data-question-search-input]') || null,
            categoryCardsContainer: container?.querySelector('[data-question-category-cards]') || null,
            feedbackElement: container?.querySelector('[data-question-bank-feedback]') || null
        };

        if (this.refs.container) {
            this.initialize();
        }
    }

    async initialize() {
        try {
            await this.loadData();
            this.bindEvents();
        } catch (error) {
            console.error('QuestionBankAccordion initialization error:', error);
            this.showFeedback('Failed to load question bank data', 'error');
        }
    }

    async loadData() {
        if (!this.config.questionBankEndpoint || !this.config.categoriesEndpoint) {
            throw new Error('Missing required endpoints');
        }

        const [questionsData, categoriesData] = await Promise.all([
            this.fetchJson(this.config.questionBankEndpoint),
            this.fetchJson(this.config.categoriesEndpoint)
        ]);

        this.state.allQuestions = questionsData || [];
        this.state.allCategories = this.flattenCategories(categoriesData || []);
    }

    async fetchJson(url) {
        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), 12000);

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } finally {
            window.clearTimeout(timeoutId);
        }
    }

    /**
     * Flatten hierarchical categories into a flat array
     * for easier lookup
     */
    flattenCategories(categories, parentId = null) {
        const flattened = [];
        
        categories.forEach(category => {
            const flatCategory = {
                id: category.id,
                name: category.name,
                parentId: parentId,
                level: parentId ? 1 : 0,
                availableQuestions: category.availableQuestions || 0,
                children: category.children || []
            };
            flattened.push(flatCategory);
            
            if (category.children && category.children.length > 0) {
                flattened.push(...this.flattenCategories(category.children, category.id));
            }
        });

        return flattened;
    }

    /**
     * Get leaf-level categories (those with questions)
     */
    getLeafCategories() {
        return this.state.allCategories.filter(cat => {
            const hasChildren = this.state.allCategories.some(c => c.parentId === cat.id);
            return !hasChildren;
        });
    }

    /**
     * Set selected categories and render accordion
     */
    setSelectedCategories(categoryIds) {
        this.state.selectedCategories = new Set(categoryIds);
        this.filterQuestions();
        this.renderAccordion();
    }

    /**
     * Filter questions based on selected categories
     */
    filterQuestions() {
        if (this.state.selectedCategories.size === 0) {
            this.state.filteredQuestions = [];
            return;
        }

        this.state.filteredQuestions = this.state.allQuestions.filter(q =>
            this.state.selectedCategories.has(q.categoryId)
        );
    }

    /**
     * Get questions for a specific category
     */
    getQuestionsForCategory(categoryId) {
        return this.state.filteredQuestions.filter(q => q.categoryId === categoryId);
    }

    /**
     * Render the accordion structure
     */
    renderAccordion() {
        if (!this.refs.categoryCardsContainer) return;

        const leafCategories = this.getLeafCategories().filter(cat =>
            this.state.selectedCategories.has(cat.id)
        );

        if (leafCategories.length === 0) {
            this.refs.categoryCardsContainer.innerHTML = '';
            this.showFeedback('No categories selected. Please select categories to view questions.', 'info');
            return;
        }

        this.showFeedback('');

        const accordionHtml = leafCategories.map(category => {
            const questions = this.getQuestionsForCategory(category.id);
            const questionCount = questions.length;
            const isExpanded = this.state.expandedCategories.has(category.id);

            return this.buildAccordionItem(category, questions, isExpanded, questionCount);
        }).join('');

        this.refs.categoryCardsContainer.innerHTML = accordionHtml;
        this.attachAccordionEventListeners();
    }

    /**
     * Build a single accordion item HTML
     */
    buildAccordionItem(category, questions, isExpanded, questionCount) {
        const categoryId = escapeHtml(category.id);
        const categoryName = escapeHtml(category.name);
        const expandedClass = isExpanded ? 'is-expanded' : '';

        return `
            <div class="question-category-card ${expandedClass}" data-category-id="${categoryId}">
                <div class="question-category-head" role="button" tabindex="0" data-accordion-toggle aria-expanded="${isExpanded}">
                    <div class="question-category-main">
                        <div class="question-category-toggle">
                            <span class="toggle-icon" aria-hidden="true">▶</span>
                        </div>
                        <div>
                            <h3 class="question-category-title">${categoryName}</h3>
                            <p class="question-category-count">${questionCount} ${questionCount === 1 ? 'question' : 'questions'}</p>
                        </div>
                    </div>
                </div>
                <div class="question-category-body">
                    ${this.buildQuestionsList(questions)}
                </div>
            </div>
        `;
    }

    /**
     * Build questions list HTML for a category
     */
    buildQuestionsList(questions) {
        if (questions.length === 0) {
            return '<div class="question-list-empty"><p>No questions available</p></div>';
        }

        const questionsList = questions.map(q => {
            const questionText = escapeHtml(q.text);
            const difficulty = escapeHtml(q.difficulty);
            const marks = escapeHtml(q.marks);
            const questionId = escapeHtml(q.id);

            return `
                <div class="question-item" data-question-id="${questionId}">
                    <div class="question-item-content">
                        <div class="question-text">${questionText}</div>
                        <div class="question-meta">
                            <span class="meta-badge difficulty-${difficulty}">${difficulty}</span>
                            <span class="meta-badge marks">${marks} marks</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        return `<div class="question-snippet-list">${questionsList}</div>`;
    }

    /**
     * Attach event listeners for accordion toggle
     */
    attachAccordionEventListeners() {
        this.refs.categoryCardsContainer?.querySelectorAll('[data-accordion-toggle]').forEach(toggle => {
            toggle.addEventListener('click', (e) => this.handleAccordionToggle(e));
            toggle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.handleAccordionToggle(e);
                }
            });
        });
    }

    /**
     * Handle accordion toggle click/keydown
     */
    handleAccordionToggle(event) {
        const toggle = event.currentTarget;
        const card = toggle.closest('.question-category-card');
        const categoryId = card?.getAttribute('data-category-id');

        if (!categoryId) return;

        const isCurrentlyExpanded = this.state.expandedCategories.has(categoryId);

        if (isCurrentlyExpanded) {
            this.collapseCategory(categoryId);
        } else {
            this.expandCategory(categoryId);
        }

        this.config.onCategoryToggle(categoryId, !isCurrentlyExpanded);
    }

    /**
     * Expand a category with animation
     */
    expandCategory(categoryId) {
        this.state.expandedCategories.add(categoryId);
        this.updateCategoryUI(categoryId, true);
    }

    /**
     * Collapse a category with animation
     */
    collapseCategory(categoryId) {
        this.state.expandedCategories.delete(categoryId);
        this.updateCategoryUI(categoryId, false);
    }

    /**
     * Update category UI state with animation
     */
    updateCategoryUI(categoryId, isExpanded) {
        const card = this.refs.categoryCardsContainer?.querySelector(`[data-category-id="${escapeHtml(categoryId)}"]`);
        if (!card) return;

        const toggle = card.querySelector('[data-accordion-toggle]');

        if (isExpanded) {
            card.classList.add('is-expanded');
            toggle?.setAttribute('aria-expanded', 'true');
        } else {
            card.classList.remove('is-expanded');
            toggle?.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Toggle all categories
     */
    toggleAll(expand = true) {
        this.state.expandedCategories.clear();

        if (expand) {
            this.state.selectedCategories.forEach(catId => {
                this.state.expandedCategories.add(catId);
            });
        }

        const leafCategories = this.getLeafCategories().filter(cat =>
            this.state.selectedCategories.has(cat.id)
        );

        leafCategories.forEach(cat => {
            this.updateCategoryUI(cat.id, expand);
        });
    }

    /**
     * Search/filter questions by keyword
     */
    searchQuestions(keyword) {
        if (!keyword.trim()) {
            this.renderAccordion();
            return;
        }

        const searchTerm = keyword.toLowerCase();
        const originalQuestions = this.state.filteredQuestions;

        // Filter questions by search term
        const searchedQuestions = originalQuestions.filter(q =>
            q.text.toLowerCase().includes(searchTerm)
        );

        // Temporarily replace filtered questions for rendering
        const tempQuestions = this.state.filteredQuestions;
        this.state.filteredQuestions = searchedQuestions;

        this.renderAccordion();

        // Restore original filtered questions
        this.state.filteredQuestions = tempQuestions;
    }

    /**
     * Show feedback message
     */
    showFeedback(message, type = 'info') {
        if (!this.refs.feedbackElement) return;

        if (!message) {
            this.refs.feedbackElement.textContent = '';
            this.refs.feedbackElement.className = 'question-bank-feedback';
            return;
        }

        this.refs.feedbackElement.textContent = message;
        this.refs.feedbackElement.className = `question-bank-feedback feedback-${type}`;
    }

    /**
     * Bind global event listeners
     */
    bindEvents() {
        if (this.refs.searchInput) {
            this.refs.searchInput.addEventListener('input', (e) => {
                this.searchQuestions(e.target.value);
            });
        }
    }

    /**
     * Get current state
     */
    getState() {
        return {
            selectedCategories: Array.from(this.state.selectedCategories),
            expandedCategories: Array.from(this.state.expandedCategories),
            questionCount: this.state.filteredQuestions.length
        };
    }

    /**
     * Export data for form submission
     */
    exportData() {
        return {
            selectedCategories: Array.from(this.state.selectedCategories),
            questions: this.state.filteredQuestions.map(q => ({
                id: q.id,
                categoryId: q.categoryId,
                marks: q.marks
            }))
        };
    }
}

// Utility function for HTML escaping (reuse from exam-create.js)
function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

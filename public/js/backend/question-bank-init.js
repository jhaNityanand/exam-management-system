/**
 * Question Bank Accordion Integration
 * Connects the QuestionBankAccordion component with the exam creation form
 * Handles category selection changes and updates the accordion display
 */

document.addEventListener('DOMContentLoaded', () => {
    // Wait for exam-create.js to initialize
    const checkExamCreateReady = setInterval(() => {
        if (window.examCreateConfig && document.getElementById('question-bank-container')) {
            clearInterval(checkExamCreateReady);
            initializeQuestionBankAccordion();
        }
    }, 100);
});

async function initializeQuestionBankAccordion() {
    const container = document.getElementById('question-bank-container');
    if (!container) return;

    // Create the accordion instance
    const accordion = new QuestionBankAccordion(container, {
        questionBankEndpoint: window.examCreateConfig.endpoints.questionBank,
        categoriesEndpoint: window.examCreateConfig.endpoints.categories,
        animationDuration: 300,
        onCategoryToggle: (categoryId, isExpanded) => {
            console.log(`Category ${categoryId} ${isExpanded ? 'expanded' : 'collapsed'}`);
        }
    });

    // Store accordion instance on window for later access
    window.questionBankAccordion = accordion;

    // Setup refresh button listener
    const refreshBtn = document.getElementById('refresh-question-bank');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async () => {
            const svg = refreshBtn.querySelector('svg');
            if (svg) svg.classList.add('animate-spin');
            refreshBtn.disabled = true;
            try {
                await accordion.reloadData();
                
                // Also reload exam-create.js's state.questionBank if it exists!
                if (window.examCreateConfig && window.examCreateConfig.endpoints) {
                    const response = await fetch(window.examCreateConfig.endpoints.questionBank, {
                        headers: { Accept: 'application/json' }
                    });
                    if (response.ok) {
                        const newQuestions = await response.json();
                        if (window.examCreateState) {
                            window.examCreateState.questionBank = newQuestions;
                            if (typeof window.examCreateUpdateAll === 'function') {
                                window.examCreateUpdateAll();
                            }
                        }
                    }
                }
            } catch (err) {
                console.error('Failed to reload question bank:', err);
            } finally {
                if (svg) svg.classList.remove('animate-spin');
                refreshBtn.disabled = false;
            }
        });
    }

    // Listen for category selection changes
    setupCategorySelectionListener(accordion);

    // Initial render based on selected categories
    updateAccordionWithSelectedCategories(accordion);
}

/**
 * Setup listener for category selection changes
 */
function setupCategorySelectionListener(accordion) {
    const categorySelect = document.getElementById('selected_categories_select');
    if (!categorySelect) return;

    // Use MutationObserver to detect changes since tom-select might update the DOM
    const observer = new MutationObserver(() => {
        updateAccordionWithSelectedCategories(accordion);
    });

    observer.observe(categorySelect, {
        attributes: true,
        attributeFilter: ['value'],
        subtree: true,
        childList: true
    });

    // Also listen for custom events if tom-select emits them
    if (window.EmsSelect) {
        // Check if we can hook into tom-select's onChange
        const originalChange = categorySelect.onchange;
        categorySelect.addEventListener('tom-select-update', () => {
            updateAccordionWithSelectedCategories(accordion);
        });

        // Fallback: Listen for input changes
        categorySelect.addEventListener('input', () => {
            updateAccordionWithSelectedCategories(accordion);
        });
    }
}

/**
 * Update accordion based on currently selected categories
 */
function updateAccordionWithSelectedCategories(accordion) {
    // Get selected categories from the hidden input field
    const selectedCategoriesInput = document.getElementById('selected_categories');
    if (!selectedCategoriesInput) return;

    try {
        const selectedCategories = JSON.parse(selectedCategoriesInput.value || '[]');
        accordion.setSelectedCategories(selectedCategories);

        // Update form tracking
        const state = accordion.getState();
        console.log('Question Bank Updated:', state);
    } catch (error) {
        console.warn('Could not parse selected categories:', error);
    }
}

/**
 * Export accordion state when form submits
 */
function setupFormSubmissionHandling() {
    const form = document.getElementById('exam-create-form');
    if (!form || !window.questionBankAccordion) return;

    form.addEventListener('submit', (e) => {
        // Optional: validate that questions are selected if needed
        const accordion = window.questionBankAccordion;
        const state = accordion.getState();

        if (state.selectedCategories.length === 0) {
            console.warn('No categories selected for question bank');
        }

        console.log('Form submission with questions:', state);
    });
}

// Setup form submission handling when page is ready
document.addEventListener('DOMContentLoaded', setupFormSubmissionHandling);

/**
 * Public API for manual accordion control
 */
window.QuestionBankAPI = {
    /**
     * Expand all categories
     */
    expandAll() {
        if (window.questionBankAccordion) {
            window.questionBankAccordion.toggleAll(true);
        }
    },

    /**
     * Collapse all categories
     */
    collapseAll() {
        if (window.questionBankAccordion) {
            window.questionBankAccordion.toggleAll(false);
        }
    },

    /**
     * Get current state
     */
    getState() {
        if (window.questionBankAccordion) {
            return window.questionBankAccordion.getState();
        }
        return null;
    },

    /**
     * Search questions
     */
    search(keyword) {
        if (window.questionBankAccordion) {
            window.questionBankAccordion.searchQuestions(keyword);
        }
    },

    /**
     * Set selected categories
     */
    setCategories(categoryIds) {
        if (window.questionBankAccordion) {
            window.questionBankAccordion.setSelectedCategories(categoryIds);
        }
    },

    /**
     * Export data for submission
     */
    exportData() {
        if (window.questionBankAccordion) {
            return window.questionBankAccordion.exportData();
        }
        return null;
    }
};

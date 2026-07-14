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

    const refreshBtn = document.getElementById('refresh-question-bank');
    if (!refreshBtn || refreshBtn.dataset.refreshBound === '1') {
        return;
    }

    refreshBtn.dataset.refreshBound = '1';
    refreshBtn.addEventListener('click', async () => {
        if (refreshBtn.classList.contains('is-loading') || refreshBtn.disabled) {
            return;
        }

        const startedAt = Date.now();
        const minSpinMs = 700;

        refreshBtn.disabled = true;
        refreshBtn.classList.add('is-loading');
        refreshBtn.setAttribute('aria-busy', 'true');

        try {
            if (typeof window.syncQuestionBankFromServer === 'function') {
                await window.syncQuestionBankFromServer();
            }
        } catch (err) {
            console.error('Failed to reload question bank:', err);
        } finally {
            const remaining = minSpinMs - (Date.now() - startedAt);
            if (remaining > 0) {
                await new Promise((resolve) => window.setTimeout(resolve, remaining));
            }
            refreshBtn.classList.remove('is-loading');
            refreshBtn.disabled = false;
            refreshBtn.removeAttribute('aria-busy');
        }
    });
}

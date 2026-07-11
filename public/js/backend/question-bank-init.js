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

    // Setup refresh button listener
    const refreshBtn = document.getElementById('refresh-question-bank');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async () => {
            const svg = refreshBtn.querySelector('svg');
            if (svg) svg.classList.add('animate-spin');
            refreshBtn.disabled = true;
            try {
                if (typeof window.syncQuestionBankFromServer === 'function') {
                    await window.syncQuestionBankFromServer();
                }
            } catch (err) {
                console.error('Failed to reload question bank:', err);
            } finally {
                if (svg) svg.classList.remove('animate-spin');
                refreshBtn.disabled = false;
            }
        });
    }
}

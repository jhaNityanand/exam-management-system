/**
 * Question Bank Accordion Integration
 * Per-part question banks are managed by exam-create.js.
 * This file only wires legacy global refresh if those DOM nodes exist.
 */

document.addEventListener('DOMContentLoaded', () => {
    const checkExamCreateReady = setInterval(() => {
        if (window.examCreateConfig && typeof window.syncQuestionBankFromServer === 'function') {
            clearInterval(checkExamCreateReady);
            initializeQuestionBankAccordion();
        }
    }, 100);
});

async function initializeQuestionBankAccordion() {
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
            await window.syncQuestionBankFromServer();
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

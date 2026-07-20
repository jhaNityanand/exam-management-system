import { initExamRunner } from './exam-runner';

let activeDestroy = null;

export function mountExamRunner(root) {
    if (!root) return null;
    if (typeof activeDestroy === 'function') {
        try { activeDestroy(); } catch (e) {}
        activeDestroy = null;
    }
    activeDestroy = initExamRunner(root);
    return activeDestroy;
}

window.__cxMountExam = mountExamRunner;
window.__cxDestroyExam = () => {
    if (typeof activeDestroy === 'function') {
        try { activeDestroy(); } catch (e) {}
        activeDestroy = null;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // Prepare uses public/js/candidate/prepare-boot.js.
    // Only auto-mount a statically rendered runner (started / show pages).
    const existing = document.getElementById('cx-exam');
    if (existing && !existing.closest('#cx-runner-host')) {
        mountExamRunner(existing);
    }
});

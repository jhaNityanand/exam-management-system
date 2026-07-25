import { isExamUiTarget } from '../helpers';

/**
 * Restricts pointer interactions outside the exam shell.
 * Candidates can still interact with #cx-exam controls.
 */
export function createMouseLockRule({ policy, send, examRoot }) {
    if (!policy.lock_mouse || !examRoot) return null;

    examRoot.classList.add('is-mouse-locked');
    document.body.classList.add('cx-mouse-lock');

    let lastSent = 0;
    const report = (reason) => {
        const now = Date.now();
        if (now - lastSent < 2500) return;
        lastSent = now;
        send('mouse_lock_bypass', { reason });
    };

    const guard = (e) => {
        const target = e.target;
        if (isExamUiTarget(target, examRoot)) return;
        // Allow browser chrome / extension UI clicks by only blocking body-level outside exam.
        if (!(target instanceof Element)) return;
        if (!document.body.contains(target)) return;
        e.preventDefault();
        e.stopPropagation();
        report(e.type);
    };

    ['mousedown', 'mouseup', 'click', 'dblclick', 'auxclick'].forEach((type) => {
        document.addEventListener(type, guard, true);
    });

    return () => {
        ['mousedown', 'mouseup', 'click', 'dblclick', 'auxclick'].forEach((type) => {
            document.removeEventListener(type, guard, true);
        });
        examRoot.classList.remove('is-mouse-locked');
        document.body.classList.remove('cx-mouse-lock');
    };
}

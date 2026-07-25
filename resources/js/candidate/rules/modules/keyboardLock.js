import { isEditableTarget } from '../helpers';

/**
 * Blocks common OS/browser shortcuts while allowing typing in answer fields
 * and exam UI controls (Enter on buttons is still fine).
 */
export function createKeyboardLockRule({ policy, send, examRoot }) {
    if (!policy.lock_keyboard) return null;

    if (examRoot) examRoot.classList.add('is-keyboard-locked');

    const onKeyDown = (e) => {
        if (isEditableTarget(e.target)) {
            // Allow typing; still block paste shortcuts if copy-paste rule also on (handled elsewhere).
            // Block meta/ctrl shortcuts that don't help answering.
            const key = e.key?.toLowerCase?.() || '';
            const blockedWhileTyping = (e.ctrlKey || e.metaKey) && ['p', 's', 'o', 'n', 'w', 't'].includes(key);
            if (!blockedWhileTyping) return;
            e.preventDefault();
            send('keyboard_lock_bypass', { key, whileTyping: true });
            return;
        }

        const key = e.key?.toLowerCase?.() || '';
        const isNav = ['tab', 'escape'].includes(key);
        const isModifierOnly = ['shift', 'control', 'alt', 'meta'].includes(key);
        if (isNav || isModifierOnly) return;

        // Allow Enter/Space on buttons/links inside exam UI.
        const el = e.target instanceof Element ? e.target : null;
        if (el && (el.closest('button, a, [role="button"]') || el.tagName === 'BUTTON')) {
            return;
        }

        // Block most shortcuts and non-answer key usage outside fields.
        if (e.ctrlKey || e.metaKey || e.altKey || key === 'f5' || key.startsWith('f')) {
            e.preventDefault();
            send('keyboard_lock_bypass', { key });
            return;
        }

        // Block printable keys outside inputs when lock is on.
        if (key.length === 1 || ['backspace', 'delete'].includes(key)) {
            e.preventDefault();
            send('keyboard_lock_bypass', { key });
        }
    };

    document.addEventListener('keydown', onKeyDown, true);
    return () => {
        document.removeEventListener('keydown', onKeyDown, true);
        if (examRoot) examRoot.classList.remove('is-keyboard-locked');
    };
}

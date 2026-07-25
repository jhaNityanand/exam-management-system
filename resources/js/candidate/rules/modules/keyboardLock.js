import { isEditableTarget } from '../helpers';

/**
 * Blocks common OS/browser shortcuts while allowing typing in answer fields
 * and exam UI controls (Enter on buttons is still fine).
 *
 * Note: OS-reserved chords like Alt+Tab / Win key are often intercepted by the
 * operating system before the page sees them. Where Chromium allows it
 * (Fullscreen + Keyboard Lock API), we request capture of those keys.
 * Leaving the exam is still primarily enforced by detect_tab_switch.
 */
export function createKeyboardLockRule({ policy, send, examRoot }) {
    if (!policy.lock_keyboard) return null;

    if (examRoot) examRoot.classList.add('is-keyboard-locked');

    const LOCK_KEYS = [
        'Tab',
        'Escape',
        'AltLeft',
        'AltRight',
        'MetaLeft',
        'MetaRight',
        'OSLeft',
        'OSRight',
        'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'F12',
    ];

    let keyboardLockRequested = false;

    async function requestKeyboardLock() {
        if (keyboardLockRequested) return;
        if (!navigator.keyboard?.lock) return;
        // Chromium only honors Keyboard Lock while fullscreen (or as a transient activation).
        if (!document.fullscreenElement && !policy.require_fullscreen) {
            // Still try — some Chromium builds allow partial lock without FS.
        }
        try {
            await navigator.keyboard.lock(LOCK_KEYS);
            keyboardLockRequested = true;
        } catch (e) {
            keyboardLockRequested = false;
        }
    }

    function releaseKeyboardLock() {
        if (!navigator.keyboard?.unlock) return;
        try { navigator.keyboard.unlock(); } catch (e) {}
        keyboardLockRequested = false;
    }

    const onFullscreen = () => {
        if (document.fullscreenElement) {
            requestKeyboardLock();
        } else {
            releaseKeyboardLock();
        }
    };

    const onKeyDown = (e) => {
        const key = e.key?.toLowerCase?.() || '';
        const code = e.code || '';

        // Always attempt to stop window/tab switching chords when the page receives them.
        const isSwitchChord =
            (e.altKey && (key === 'tab' || key === 'escape' || code === 'Tab' || code === 'F4'))
            || ((e.ctrlKey || e.metaKey) && key === 'tab')
            || ((e.ctrlKey || e.metaKey) && e.shiftKey && key === 'tab')
            || ((e.altKey || e.metaKey) && key === ' '); // Alt+Space / system menu on some OS

        if (isSwitchChord) {
            e.preventDefault();
            e.stopPropagation();
            send('keyboard_lock_bypass', { key, code, chord: 'window_switch' });
            return;
        }

        if (isEditableTarget(e.target)) {
            // Allow typing; still block paste shortcuts if copy-paste rule also on (handled elsewhere).
            // Block meta/ctrl shortcuts that don't help answering.
            const blockedWhileTyping = (e.ctrlKey || e.metaKey) && ['p', 's', 'o', 'n', 'w', 't'].includes(key);
            if (!blockedWhileTyping) return;
            e.preventDefault();
            send('keyboard_lock_bypass', { key, whileTyping: true });
            return;
        }

        const isModifierOnly = ['shift', 'control', 'alt', 'meta'].includes(key);
        if (isModifierOnly) return;

        // Allow plain Tab / Escape only for in-page focus movement — not with modifiers.
        if ((key === 'tab' || key === 'escape') && !e.altKey && !e.ctrlKey && !e.metaKey) {
            // Still block Escape when fullscreen is required (exit FS).
            if (key === 'escape' && policy.require_fullscreen && document.fullscreenElement) {
                e.preventDefault();
                send('keyboard_lock_bypass', { key });
            }
            return;
        }

        // Allow Enter/Space on buttons/links inside exam UI.
        const el = e.target instanceof Element ? e.target : null;
        if (el && (el.closest('button, a, [role="button"]') || el.tagName === 'BUTTON')) {
            if (key === 'enter' || key === ' ' || key === 'spacebar') return;
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
    document.addEventListener('fullscreenchange', onFullscreen);
    // Best-effort request (works reliably once fullscreen is entered).
    requestKeyboardLock();

    return () => {
        document.removeEventListener('keydown', onKeyDown, true);
        document.removeEventListener('fullscreenchange', onFullscreen);
        releaseKeyboardLock();
        if (examRoot) examRoot.classList.remove('is-keyboard-locked');
    };
}

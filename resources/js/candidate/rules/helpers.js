/**
 * Shared helpers for exam rule modules.
 */

export function isEditableTarget(target) {
    if (!target || !(target instanceof Element)) return false;
    const tag = target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return true;
    if (target.isContentEditable) return true;
    return !!target.closest('input, textarea, select, [contenteditable="true"]');
}

export function isExamUiTarget(target, examRoot) {
    if (!target || !examRoot) return false;
    return examRoot.contains(target instanceof Element ? target : target.parentElement);
}

export function createEventSender({ eventsUrl, api, allowUnloadRef, onAutoSubmit, onWarning, limit }) {
    return function send(event, payload = {}) {
        if (allowUnloadRef.current) return Promise.resolve(null);
        return api(eventsUrl, {
            method: 'POST',
            body: { event, payload },
        }).then((data) => {
            if (data?.auto_submitted) {
                onAutoSubmit?.({
                    reason: data.submission_reason || null,
                    message: data.submission_message
                        || 'Your exam has been automatically submitted because you exceeded the maximum number of allowed rule violations.',
                    violationCount: data.violation_count,
                    limit,
                });
                return data;
            }
            if (data?.action === 'warn' || data?.action === 'flag') {
                onWarning?.({
                    title: titleForEvent(event),
                    message: messageForEvent(event, data?.violation_count, limit),
                    event,
                    count: data?.violation_count || null,
                    limit,
                    action: data.action,
                }, data);
            }
            return data;
        }).catch(() => null);
    };
}

export function titleForEvent(event) {
    const map = {
        tab_switch: 'Tab switch detected',
        window_blur: 'Window focus lost',
        fullscreen_exit: 'Fullscreen exited',
        copy_attempt: 'Copy blocked',
        paste_attempt: 'Paste blocked',
        cut_attempt: 'Cut blocked',
        drag_attempt: 'Drag and drop blocked',
        right_click: 'Right-click blocked',
        detools_open: 'Developer tools detected',
        devtools_open: 'Developer tools detected',
        page_refresh: 'Page refresh blocked',
        navigation_back: 'Back navigation blocked',
        media_lost: 'Camera or microphone lost',
        media_grace_expired: 'Media not restored',
        keyboard_lock_bypass: 'Keyboard shortcut blocked',
        mouse_lock_bypass: 'Mouse action blocked',
        session_warning: 'Session warning',
    };
    return map[event] || 'Rule warning';
}

export function messageForEvent(event, count, limit) {
    const base = {
        tab_switch: 'Stay on the exam tab. Leaving or switching tabs is monitored.',
        window_blur: 'Keep this exam window focused until you finish.',
        fullscreen_exit: 'Fullscreen is required. Re-enter fullscreen to continue.',
        copy_attempt: 'Copying content is not allowed during this exam.',
        paste_attempt: 'Pasting content is not allowed during this exam.',
        cut_attempt: 'Cut is not allowed during this exam.',
        drag_attempt: 'Dragging content is not allowed during this exam.',
        right_click: 'Right-click is disabled during the exam.',
        detools_open: 'Please close developer tools to continue the exam.',
        devtools_open: 'Please close developer tools to continue the exam.',
        page_refresh: 'Refreshing or reloading the page is blocked during this exam.',
        navigation_back: 'Leaving this page with the back button is not allowed.',
        media_lost: 'Camera or microphone connection was lost. Please restore access.',
        media_grace_expired: 'Required camera/microphone was not restored in time.',
        keyboard_lock_bypass: 'That keyboard shortcut is locked during this exam.',
        mouse_lock_bypass: 'Mouse actions outside the exam area are restricted.',
    }[event] || 'A monitoring event was recorded.';

    if (count && !['right_click', 'media_lost'].includes(event) && limit != null && Number.isFinite(Number(limit))) {
        const max = Math.max(0, Number(limit));
        if (max === 0) {
            return `${base} No warnings are allowed for this exam. This may auto-submit your attempt.`;
        }
        return `${base} Warning ${Math.max(1, count)} of ${max}. Repeated violations may auto-submit your exam.`;
    }
    return base;
}

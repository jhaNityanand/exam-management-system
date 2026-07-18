const prefix = 'ems_attempt_';

export function storageKey(attemptId, userId = 'u') {
    return `${prefix}${userId}_${attemptId}`;
}

export function loadLocal(attemptId, userId = 'u') {
    try {
        const raw = localStorage.getItem(storageKey(attemptId, userId));
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
}

export function saveLocal(attemptId, payload, userId = 'u') {
    try {
        localStorage.setItem(storageKey(attemptId, userId), JSON.stringify(payload));
    } catch (e) {
        // Ignore quota / private mode failures.
    }
}

export function clearLocal(attemptId, userId = 'u') {
    try {
        localStorage.removeItem(storageKey(attemptId, userId));
    } catch (e) {
        // no-op
    }
}

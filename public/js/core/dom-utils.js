/**
 * Shared DOM string helpers for backend list/create scripts.
 */
window.EmsDom = window.EmsDom || {
    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    stripHtml(value) {
        const tmp = document.createElement('div');
        tmp.innerHTML = String(value ?? '');
        return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
    },

    truncate(text, max = 140) {
        const value = String(text ?? '');
        if (!value) return '';
        return value.length > max ? `${value.slice(0, max - 1)}…` : value;
    },
};

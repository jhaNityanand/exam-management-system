function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function firstErrorMessage(data) {
    if (!data || typeof data !== 'object') return null;
    if (typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
    }
    if (data.errors && typeof data.errors === 'object') {
        for (const value of Object.values(data.errors)) {
            if (Array.isArray(value) && value[0]) return String(value[0]);
            if (typeof value === 'string') return value;
        }
    }
    return null;
}

export async function api(url, { method = 'GET', body, headers = {}, timeoutMs = 60000 } = {}) {
    const options = {
        method,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...headers,
        },
        credentials: 'same-origin',
    };

    if (body instanceof FormData) {
        if (!body.has('_token') && csrfToken()) {
            body.append('_token', csrfToken());
        }
        options.body = body;
    } else if (body !== undefined) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), timeoutMs);
    options.signal = controller.signal;

    let response;
    try {
        response = await fetch(url, options);
    } catch (e) {
        window.clearTimeout(timer);
        if (e?.name === 'AbortError') {
            throw new Error('Request timed out. Please try again.');
        }
        throw new Error(e?.message || 'Network error. Please try again.');
    } finally {
        window.clearTimeout(timer);
    }

    const contentType = response.headers.get('content-type') || '';
    let data = null;
    if (contentType.includes('application/json')) {
        try {
            data = await response.json();
        } catch (e) {
            data = null;
        }
    } else {
        // Avoid treating HTML login/error pages as success.
        try {
            await response.text();
        } catch (e) {
            // ignore
        }
    }

    if (!response.ok) {
        const message = firstErrorMessage(data) || `Request failed (${response.status})`;
        const error = new Error(message);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    if (data === null) {
        throw new Error('Unexpected server response. Please refresh and try again.');
    }

    return data;
}

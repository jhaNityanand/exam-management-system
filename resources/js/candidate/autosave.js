import { api } from './api';

function isTerminalError(error) {
    const status = Number(error?.status || 0);
    return status === 401 || status === 403 || status === 404 || status === 419 || status === 422;
}

export function createAutosave({ attemptId, url, userId, onState, onRevision }) {
    let queue = new Map();
    let revision = 0;
    let timer = null;
    let inflight = null;
    let failCount = 0;
    let lastError = '';
    let stopped = false;

    function setState(state, detail = '') {
        onState?.(state, detail);
    }

    function enqueue(item, debounceMs = 500) {
        if (stopped) return;
        queue.set(item.exam_attempt_question_id, item);
        setState('pending');
        schedule(typeof debounceMs === 'number' ? debounceMs : 500);
    }

    function schedule(delay = 500) {
        if (timer) window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            flush().catch(() => {});
        }, Math.max(0, delay));
    }

    async function waitForInflight() {
        if (!inflight) return true;
        try {
            return await inflight;
        } catch (e) {
            return false;
        }
    }

    async function sendOnce() {
        if (queue.size === 0) {
            setState('saved');
            return true;
        }
        if (!navigator.onLine) {
            setState('offline');
            return false;
        }

        setState('saving');
        const snapshot = Array.from(queue.values());
        const answers = snapshot.map(({ _current, ...rest }) => ({
            exam_attempt_question_id: rest.exam_attempt_question_id,
            answer_value: rest.answer_value,
            is_marked_for_review: !!rest.is_marked_for_review,
            is_visited: rest.is_visited !== false,
        }));

        try {
            const data = await api(url, {
                method: 'POST',
                body: { revision, answers },
                timeoutMs: 20000,
            });

            revision = data.revision ?? revision + 1;
            onRevision?.(revision);

            snapshot.forEach((item) => {
                const current = queue.get(item.exam_attempt_question_id);
                if (current === item) {
                    queue.delete(item.exam_attempt_question_id);
                }
            });

            // If backend skipped invalid IDs, keep a soft warning without blocking UX.
            if (typeof data.saved === 'number' && data.saved < answers.length) {
                lastError = 'Some answers could not be confirmed. Retrying…';
                setState('error', lastError);
            } else {
                failCount = 0;
                lastError = '';
                setState(queue.size ? 'pending' : 'saved');
            }

            if (queue.size) schedule(250);
            return queue.size === 0;
        } catch (e) {
            failCount += 1;
            lastError = e?.message || 'Save failed';
            setState('error', lastError);

            if (isTerminalError(e)) {
                stopped = true;
                return false;
            }

            const delay = Math.min(8000, 1000 * failCount);
            schedule(delay);
            return false;
        }
    }

    async function flush(options = {}) {
        const waitForActive = options.waitForInflight !== false;
        const requireEmpty = !!options.requireEmpty;

        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }

        if (waitForActive) {
            await waitForInflight();
        }

        if (inflight) {
            const ok = await waitForInflight();
            if (requireEmpty && queue.size > 0) {
                return flush({ waitForInflight: true, requireEmpty: true });
            }
            return ok && (!requireEmpty || queue.size === 0);
        }

        if (queue.size === 0) {
            setState('saved');
            return true;
        }

        inflight = sendOnce().finally(() => {
            inflight = null;
        });

        const ok = await inflight;
        if (requireEmpty && ok && queue.size > 0) {
            return flush({ waitForInflight: true, requireEmpty: true });
        }
        return requireEmpty ? (ok && queue.size === 0) : ok;
    }

    window.addEventListener('online', () => {
        if (stopped) return;
        setState('pending');
        flush().catch(() => {});
    });
    window.addEventListener('offline', () => setState('offline'));

    return {
        enqueue,
        flush,
        setRevision(value) {
            revision = value || 0;
        },
        getRevision: () => revision,
        pendingCount: () => queue.size,
        lastError: () => lastError,
        isStopped: () => stopped,
    };
}

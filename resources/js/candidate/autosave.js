import { api } from './api';

export function createAutosave({ attemptId, url, userId, onState }) {
    let queue = new Map();
    let revision = 0;
    let timer = null;
    let inflight = false;
    let failCount = 0;
    let lastError = '';

    function setState(state, detail = '') {
        onState?.(state, detail);
    }

    function enqueue(item) {
        queue.set(item.exam_attempt_question_id, item);
        setState('pending');
        schedule();
    }

    function schedule(delay = 500) {
        if (timer) window.clearTimeout(timer);
        timer = window.setTimeout(flush, delay);
    }

    async function flush() {
        if (inflight || queue.size === 0) {
            if (!inflight && queue.size === 0) setState('saved');
            return true;
        }
        if (!navigator.onLine) {
            setState('offline');
            return false;
        }

        inflight = true;
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
            snapshot.forEach((item) => {
                const current = queue.get(item.exam_attempt_question_id);
                if (current === item) {
                    queue.delete(item.exam_attempt_question_id);
                }
            });
            failCount = 0;
            lastError = '';
            setState(queue.size ? 'pending' : 'saved');
            if (queue.size) schedule(250);
            return true;
        } catch (e) {
            failCount += 1;
            lastError = e?.message || 'Save failed';
            setState('error', lastError);
            // Retry with backoff; stop hammering after several hard failures.
            const delay = Math.min(8000, 1000 * failCount);
            schedule(delay);
            return false;
        } finally {
            inflight = false;
        }
    }

    window.addEventListener('online', () => {
        setState('pending');
        flush();
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
    };
}

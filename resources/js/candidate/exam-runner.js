import { api } from './api';
import { createAutosave } from './autosave';
import { bindProctoring, startWebcamMonitor } from './proctoring';
import { clearLocal, loadLocal, saveLocal } from './store';
import { createTimer } from './timer';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function optionLabel(raw) {
    if (raw == null) return '';
    if (typeof raw === 'string' || typeof raw === 'number') return String(raw);
    if (typeof raw === 'object') {
        return String(raw.text ?? raw.label ?? raw.value ?? raw.option ?? '');
    }
    return '';
}

function letterForIndex(index) {
    return String.fromCharCode(65 + (index % 26));
}

function orderedOptions(question) {
    const options = question.question?.options || {};
    const isArray = Array.isArray(options);
    const order = question.option_order?.length
        ? question.option_order
        : (isArray ? options.map((_, i) => i) : Object.keys(options));

    return order.map((key, index) => {
        const raw = isArray ? options[Number(key)] : options[key];
        const label = optionLabel(raw) || String(key);
        return { key: String(key), label, letter: letterForIndex(index) };
    }).filter((opt) => opt.label !== '');
}

function isAnsweredValue(value) {
    if (value === null || value === undefined || value === '') return false;
    if (Array.isArray(value)) return value.length > 0;
    return true;
}

function emptyAnswerFor(question) {
    const type = question.question?.type || 'mcq';
    const allowsMultiple = !!question.question?.allows_multiple || type === 'multi_select';
    if (type === 'fill_blank' || type === 'short_answer' || type === 'long_answer' || type === 'written') {
        return '';
    }
    return allowsMultiple ? [] : null;
}

function resolveTheme(prefs = {}) {
    const stored = (() => {
        try {
            return localStorage.getItem('examtube-theme');
        } catch (e) {
            return null;
        }
    })();

    let theme = prefs.theme || stored || window.__examtubeTheme || 'light';
    if (theme === 'system' || !theme) {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    theme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.dataset.theme = theme;
    document.body.dataset.theme = theme;
    return theme;
}

function choiceHtml(inputType, name, value, letter, text) {
    return [
        '<label class="cx-choice">',
        '<input type="' + escapeHtml(inputType) + '" name="' + escapeHtml(name) + '" value="' + escapeHtml(value) + '">',
        '<span class="cx-choice__key">' + escapeHtml(letter) + '</span>',
        '<span class="cx-choice__text">' + escapeHtml(text) + '</span>',
        '</label>',
    ].join('');
}

function renderQuestion(container, question) {
    const type = question.question?.type || 'mcq';
    const allowsMultiple = !!question.question?.allows_multiple || type === 'multi_select';
    const body = question.question?.body || '';
    const name = 'q' + question.id;
    let html = '<div class="cx-question">' + body + '</div><div class="cx-answer">';

    if (type === 'true_false') {
        ['True', 'False'].forEach((value, index) => {
            html += choiceHtml('radio', name, value, letterForIndex(index), value);
        });
    } else if (type === 'fill_blank') {
        html += '<input class="cx-field" type="text" name="' + escapeHtml(name) + '" autocomplete="off" placeholder="Type your answer">';
    } else if (type === 'short_answer') {
        html += '<textarea class="cx-field" name="' + escapeHtml(name) + '" rows="4" placeholder="Write your answer"></textarea>';
    } else if (type === 'long_answer' || type === 'written') {
        html += '<textarea class="cx-field" name="' + escapeHtml(name) + '" rows="8" placeholder="Write your answer"></textarea>';
    } else {
        if (allowsMultiple) {
            html += '<p class="cx-hint">Select all that apply.</p>';
        }
        orderedOptions(question).forEach((opt) => {
            html += choiceHtml(allowsMultiple ? 'checkbox' : 'radio', name, opt.key, opt.letter, opt.label);
        });
    }

    html += '</div>';
    container.innerHTML = html;
}

function readAnswer(container, question) {
    const type = question.question?.type || 'mcq';
    const allowsMultiple = !!question.question?.allows_multiple || type === 'multi_select';
    const name = 'q' + question.id;

    if (type === 'fill_blank' || type === 'short_answer' || type === 'long_answer' || type === 'written') {
        const el = container.querySelector('[name="' + name + '"]');
        return el ? el.value : '';
    }

    if (allowsMultiple) {
        return Array.from(container.querySelectorAll('[name="' + name + '"]:checked')).map((el) => el.value);
    }

    const selected = container.querySelector('[name="' + name + '"]:checked');
    return selected ? selected.value : null;
}

function applyAnswer(container, question, value) {
    if (value === null || value === undefined || value === '') return;
    const type = question.question?.type || 'mcq';
    const allowsMultiple = !!question.question?.allows_multiple || type === 'multi_select';
    const name = 'q' + question.id;

    if (type === 'fill_blank' || type === 'short_answer' || type === 'long_answer' || type === 'written') {
        const el = container.querySelector('[name="' + name + '"]');
        if (el) {
            el.value = typeof value === 'object' && !Array.isArray(value)
                ? (value.text || '')
                : (Array.isArray(value) ? (value[0] || '') : value);
        }
        return;
    }

    const values = Array.isArray(value) ? value.map(String) : [String(value)];
    container.querySelectorAll('[name="' + name + '"]').forEach((el) => {
        el.checked = values.includes(el.value);
        el.closest('.cx-choice')?.classList.toggle('is-selected', el.checked);
    });
}

function bindOptionHighlight(container) {
    container.querySelectorAll('.cx-choice input').forEach((input) => {
        input.addEventListener('change', () => {
            const name = input.name;
            container.querySelectorAll('input[name="' + name + '"]').forEach((el) => {
                el.closest('.cx-choice')?.classList.toggle('is-selected', el.checked);
            });
        });
    });
}

export function initExamRunner(root) {
    if (!root || root.dataset.cxReady === '1') return null;
    root.dataset.cxReady = '1';

    let payload = {};
    let urls = {};
    try {
        payload = JSON.parse(root.dataset.payload || '{}');
        urls = JSON.parse(root.dataset.urls || '{}');
    } catch (e) {
        root.dataset.cxReady = '0';
        throw new Error('Unable to load exam data.');
    }

    const userId = root.dataset.userId || 'u';
    const questions = payload.questions || [];
    const policy = payload.policy || {};
    const requireWebcam = root.dataset.requireWebcam === '1' || !!policy.require_webcam;
    const singleAttempt = !!policy.single_attempt_per_question;

    const state = {
        index: 0,
        answers: {},
        review: {},
        visited: {},
        leftAnswered: {},
        submitting: false,
        reviewSweep: false,
        toastTimer: null,
        drawerOpen: false,
        webcamStop: null,
        proctorCleanup: null,
        heartbeatTimer: null,
        timerApi: null,
        destroyed: false,
    };

    const cleanups = [];

    questions.forEach((q) => {
        state.answers[q.id] = q.answer ?? emptyAnswerFor(q);
        state.review[q.id] = !!q.is_marked_for_review;
        state.visited[q.id] = !!q.is_visited;
        if (singleAttempt && isAnsweredValue(state.answers[q.id])) {
            state.leftAnswered[q.id] = true;
        }
    });

    const serverRevision = Number(payload.attempt?.revision || 0);
    const local = loadLocal(payload.attempt.id, userId);
    if (local?.answers) {
        const localRevision = Number(local.revision || 0);
        if (localRevision >= serverRevision) {
            Object.entries(local.answers).forEach(([id, item]) => {
                state.answers[id] = item.answer_value;
                state.review[id] = !!item.is_marked_for_review;
                state.visited[id] = !!item.is_visited;
                if (singleAttempt && isAnsweredValue(item.answer_value)) {
                    state.leftAnswered[id] = true;
                }
            });
            if (local.current != null) {
                state.index = Math.min(questions.length - 1, Math.max(0, Number(local.current) || 0));
            }
        }
    }

    resolveTheme(payload.attempt?.preferences || {});
    document.body.dataset.font = payload.attempt?.preferences?.font_size || 'md';

    const questionEl = root.querySelector('#cx-question');
    const paletteEl = root.querySelector('#cx-palette');
    const timerEls = [
        root.querySelector('#cx-timer'),
        root.querySelector('#cx-rail-timer'),
    ].filter(Boolean);
    const saveStateEl = root.querySelector('#cx-save-state');
    const progressEl = root.querySelector('#cx-progress-label');
    const paletteSummaryEl = root.querySelector('#cx-palette-summary');
    const toastEl = root.querySelector('#cx-toast');
    const rail = root.querySelector('#cx-rail');
    const backdrop = root.querySelector('#cx-drawer-backdrop');
    const drawerToggle = root.querySelector('#cx-drawer-toggle');
    const drawerClose = root.querySelector('#cx-drawer-close');
    const modal = root.querySelector('#cx-submit-modal');
    const statsEl = root.querySelector('#cx-submit-stats');

    const autosave = createAutosave({
        attemptId: payload.attempt.id,
        url: urls.answers,
        userId,
        onState: (label, detail = '') => {
            if (!saveStateEl) return;
            const map = {
                saved: 'Saved',
                pending: 'Syncing…',
                saving: 'Saving…',
                offline: 'Offline',
                error: 'Save issue',
            };
            saveStateEl.textContent = map[label] || label;
            saveStateEl.dataset.state = label;
            saveStateEl.title = detail || 'Answers sync automatically in the background';
            if (label === 'offline') notify('You are offline. Answers are kept on this device.', 'warn');
            else if (label === 'error' && detail) notify(detail, 'error');
        },
        onRevision: (revision) => syncLocal(revision),
    });
    autosave.setRevision(serverRevision);

    function notify(message, tone = 'info', ttl = 4500) {
        if (!toastEl || !message || state.destroyed) return;
        toastEl.hidden = false;
        toastEl.removeAttribute('hidden');
        toastEl.dataset.tone = tone;
        toastEl.textContent = message;
        if (state.toastTimer) window.clearTimeout(state.toastTimer);
        state.toastTimer = window.setTimeout(() => {
            toastEl.hidden = true;
            toastEl.setAttribute('hidden', 'hidden');
            toastEl.textContent = '';
        }, ttl);
    }

    function syncLocal(revision = autosave.getRevision()) {
        saveLocal(payload.attempt.id, {
            current: state.index,
            revision,
            answers: Object.fromEntries(questions.map((item) => [item.id, {
                exam_attempt_question_id: item.id,
                answer_value: state.answers[item.id],
                is_marked_for_review: !!state.review[item.id],
                is_visited: !!state.visited[item.id],
            }])),
            updated_at: Date.now(),
        }, userId);
    }

    function persistCurrent({ debounceMs } = {}) {
        const q = questions[state.index];
        if (!q || !questionEl) return;
        const value = readAnswer(questionEl, q);
        state.answers[q.id] = value;
        state.visited[q.id] = true;
        autosave.enqueue({
            exam_attempt_question_id: q.id,
            answer_value: value,
            is_marked_for_review: !!state.review[q.id],
            is_visited: true,
            _current: state.index,
        }, debounceMs);
        syncLocal();
        paintPalette();
        updateProgress();
    }

    function setDrawer(open) {
        state.drawerOpen = !!open;
        root.classList.toggle('is-drawer-open', state.drawerOpen);
        if (rail) rail.classList.toggle('is-open', state.drawerOpen);
        if (backdrop) {
            backdrop.hidden = !state.drawerOpen;
            if (state.drawerOpen) backdrop.removeAttribute('hidden');
            else backdrop.setAttribute('hidden', 'hidden');
        }
        if (drawerToggle) drawerToggle.setAttribute('aria-expanded', state.drawerOpen ? 'true' : 'false');
        document.body.classList.toggle('cx-drawer-lock', state.drawerOpen);
    }

    function isLockedIndex(idx) {
        if (!singleAttempt) return false;
        const q = questions[idx];
        if (!q) return false;
        return !!state.leftAnswered[q.id] && idx !== state.index;
    }

    function paintPalette() {
        if (!paletteEl) return;
        paletteEl.innerHTML = '';
        questions.forEach((q, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = String(idx + 1);
            btn.setAttribute('role', 'listitem');
            btn.setAttribute('aria-label', 'Question ' + (idx + 1));
            if (idx === state.index) {
                btn.classList.add('is-current');
                btn.setAttribute('aria-current', 'true');
            }
            if (state.review[q.id]) btn.classList.add('is-review');
            else if (isAnsweredValue(state.answers[q.id])) btn.classList.add('is-answered');
            else if (state.visited[q.id]) btn.classList.add('is-visited');

            if (isLockedIndex(idx)) {
                btn.classList.add('is-locked');
                btn.disabled = true;
                btn.title = 'This question is locked after answering.';
            } else {
                btn.addEventListener('click', () => {
                    persistCurrent({ debounceMs: 0 });
                    if (!goToIndex(idx)) return;
                    if (window.matchMedia('(max-width: 960px)').matches) setDrawer(false);
                });
            }
            paletteEl.appendChild(btn);
        });
    }

    function updateProgress() {
        const answered = questions.filter((q) => isAnsweredValue(state.answers[q.id])).length;
        if (progressEl) progressEl.textContent = answered + ' / ' + questions.length + ' answered';
        if (paletteSummaryEl) paletteSummaryEl.textContent = answered + ' answered';
    }

    function showQuestion() {
        const q = questions[state.index];
        if (!q || !questionEl) return;
        renderQuestion(questionEl, q);
        applyAnswer(questionEl, q, state.answers[q.id]);
        bindOptionHighlight(questionEl);
        state.visited[q.id] = true;

        const type = q.question?.type || 'mcq';
        const isText = ['fill_blank', 'short_answer', 'long_answer', 'written'].includes(type);
        questionEl.onchange = () => persistCurrent();
        questionEl.oninput = () => persistCurrent({ debounceMs: isText ? 800 : 500 });

        paintPalette();
        updateProgress();
        syncLocal();

        const qno = root.querySelector('#cx-qno');
        const kicker = root.querySelector('#cx-question-kicker');
        const marksEl = root.querySelector('#cx-question-marks');
        const label = 'Question ' + (state.index + 1) + ' of ' + questions.length;
        if (qno) qno.textContent = label;
        if (kicker) kicker.textContent = label;
        if (marksEl) {
            const marks = q.marks ?? q.question?.marks;
            marksEl.textContent = marks != null ? (marks + ' mark' + (Number(marks) === 1 ? '' : 's')) : '';
        }
    }

    function canNavigateTo(nextIndex) {
        if (!singleAttempt) return true;
        if (isLockedIndex(nextIndex)) {
            notify('This exam does not allow returning to answered questions.', 'warn');
            return false;
        }
        return true;
    }

    function leaveCurrentIfAnswered() {
        if (!singleAttempt) return;
        const current = questions[state.index];
        if (current && isAnsweredValue(state.answers[current.id])) {
            state.leftAnswered[current.id] = true;
        }
    }

    function goToIndex(idx) {
        const next = Math.max(0, Math.min(questions.length - 1, idx));
        if (!canNavigateTo(next)) return false;
        if (next !== state.index) leaveCurrentIfAnswered();
        state.index = next;
        showQuestion();
        return true;
    }

    function nextIncompleteIndex(fromIndex = state.index) {
        for (let i = fromIndex + 1; i < questions.length; i += 1) {
            if (!isAnsweredValue(state.answers[questions[i].id]) && !isLockedIndex(i)) return i;
        }
        for (let i = 0; i <= fromIndex; i += 1) {
            if (!isAnsweredValue(state.answers[questions[i].id]) && !isLockedIndex(i)) return i;
        }
        return -1;
    }

    function nextReviewIndex(fromIndex = state.index) {
        for (let i = fromIndex + 1; i < questions.length; i += 1) {
            if (state.review[questions[i].id] && !isLockedIndex(i)) return i;
        }
        for (let i = 0; i <= fromIndex; i += 1) {
            if (state.review[questions[i].id] && !isLockedIndex(i)) return i;
        }
        return -1;
    }

    function summary() {
        const unanswered = questions.filter((q) => !isAnsweredValue(state.answers[q.id]));
        const reviewed = questions.filter((q) => state.review[q.id]);
        return {
            total: questions.length,
            answered: questions.length - unanswered.length,
            unanswered,
            reviewed,
        };
    }

    function openModal() {
        const s = summary();
        if (statsEl) {
            statsEl.innerHTML = [
                '<li><strong>' + s.total + '</strong> total questions</li>',
                '<li><strong>' + s.answered + '</strong> answered</li>',
                '<li><strong>' + s.unanswered.length + '</strong> unanswered</li>',
                '<li><strong>' + s.reviewed.length + '</strong> marked for review</li>',
            ].join('');
        }
        if (modal) {
            modal.hidden = false;
            modal.removeAttribute('hidden');
            modal.setAttribute('aria-hidden', 'false');
            root.querySelector('#cx-confirm-submit')?.focus();
        }
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
            modal.setAttribute('hidden', 'hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    async function finalizeSubmit() {
        if (state.submitting) return;
        state.submitting = true;
        notify('Submitting exam…', 'info', 8000);
        try {
            persistCurrent({ debounceMs: 0 });
            const ok = await autosave.flush({ waitForInflight: true, requireEmpty: true });
            if (!ok) throw new Error(autosave.lastError() || 'Unable to sync answers. Please try again.');
            const data = await api(urls.submit, { method: 'POST', body: {} });
            state.webcamStop?.();
            clearLocal(payload.attempt.id, userId);
            window.location.href = data.redirect || urls.result;
        } catch (e) {
            state.submitting = false;
            notify(e.message || 'Submit failed', 'error', 7000);
        }
    }

    async function advanceAfterSave() {
        const unansweredLeft = questions.some((q) => !isAnsweredValue(state.answers[q.id]));
        const reviewLeft = questions.some((q) => state.review[q.id]);
        const atLast = state.index >= questions.length - 1;

        if (!unansweredLeft && !reviewLeft) {
            openModal();
            return;
        }

        if (state.reviewSweep || (atLast && !unansweredLeft && reviewLeft)) {
            state.reviewSweep = true;
            const reviewIdx = nextReviewIndex(state.index);
            if (reviewIdx === -1 || reviewIdx === state.index) {
                state.reviewSweep = false;
                openModal();
                return;
            }
            goToIndex(reviewIdx);
            return;
        }

        if (!atLast) {
            goToIndex(state.index + 1);
            return;
        }

        const incomplete = nextIncompleteIndex(state.index);
        if (incomplete !== -1) {
            goToIndex(incomplete);
            return;
        }

        if (reviewLeft) {
            state.reviewSweep = true;
            const reviewIdx = nextReviewIndex(state.index);
            goToIndex(reviewIdx === -1 ? state.index : reviewIdx);
            return;
        }

        openModal();
    }

    async function handleSubmitClick() {
        persistCurrent({ debounceMs: 0 });
        await autosave.flush({ waitForInflight: true });
        await advanceAfterSave();
    }

    async function handleMarkReviewNext() {
        const q = questions[state.index];
        if (!q) return;
        state.review[q.id] = true;
        persistCurrent({ debounceMs: 0 });
        await autosave.flush({ waitForInflight: true });
        await advanceAfterSave();
    }

    function skipCurrent() {
        persistCurrent({ debounceMs: 0 });

        if (state.index < questions.length - 1) {
            goToIndex(state.index + 1);
            return;
        }

        const incomplete = questions.findIndex((q, idx) => (
            idx !== state.index && !isAnsweredValue(state.answers[q.id]) && !isLockedIndex(idx)
        ));
        if (incomplete !== -1) {
            goToIndex(incomplete);
            return;
        }

        const reviewIdx = questions.findIndex((q, idx) => (
            idx !== state.index && state.review[q.id] && !isLockedIndex(idx)
        ));
        if (reviewIdx !== -1) {
            goToIndex(reviewIdx);
            return;
        }

        openModal();
    }

    async function openFinalReview() {
        persistCurrent({ debounceMs: 0 });
        await autosave.flush({ waitForInflight: true });
        openModal();
        setDrawer(false);
    }

    function on(el, event, handler) {
        if (!el) return;
        el.addEventListener(event, handler);
        cleanups.push(() => el.removeEventListener(event, handler));
    }

    on(root.querySelector('#cx-skip'), 'click', skipCurrent);
    on(root.querySelector('#cx-submit'), 'click', () => {
        handleSubmitClick().catch((e) => notify(e.message || 'Unable to continue', 'error'));
    });
    on(root.querySelector('#cx-mark-review-next'), 'click', () => {
        handleMarkReviewNext().catch((e) => notify(e.message || 'Unable to continue', 'error'));
    });
    on(root.querySelector('#cx-final-submit'), 'click', () => {
        openFinalReview().catch((e) => notify(e.message || 'Unable to prepare final submission', 'error'));
    });
    on(drawerToggle, 'click', () => setDrawer(!state.drawerOpen));
    on(drawerClose, 'click', () => setDrawer(false));
    on(backdrop, 'click', () => setDrawer(false));
    on(root.querySelector('#cx-confirm-submit'), 'click', () => {
        closeModal();
        finalizeSubmit();
    });
    modal?.querySelectorAll('[data-close-modal]').forEach((el) => {
        on(el, 'click', closeModal);
    });

    const onKeyDown = (event) => {
        if (state.destroyed) return;
        if (event.key === 'Escape') {
            if (state.drawerOpen) {
                setDrawer(false);
                return;
            }
            if (modal && !modal.hidden) closeModal();
            return;
        }

        if (event.key !== 'Enter' || event.repeat || (modal && !modal.hidden)) return;
        const target = event.target;
        if (target instanceof HTMLTextAreaElement || target instanceof HTMLButtonElement) return;
        if (target instanceof HTMLSelectElement) return;
        if (target instanceof HTMLInputElement && target.type === 'text') return;

        event.preventDefault();
        const q = questions[state.index];
        const value = q ? readAnswer(questionEl, q) : null;
        if (isAnsweredValue(value)) {
            handleSubmitClick().catch((e) => notify(e.message || 'Unable to save and continue', 'error'));
        } else {
            skipCurrent();
        }
    };
    document.addEventListener('keydown', onKeyDown);
    cleanups.push(() => document.removeEventListener('keydown', onKeyDown));

    if (payload.exam?.enable_exam_timer && payload.attempt?.expires_at) {
        const totalSeconds = Math.max(
            1,
            Math.floor((new Date(payload.attempt.expires_at).getTime() - new Date(payload.server_now).getTime()) / 1000),
        );
        state.timerApi = createTimer({
            expiresAt: payload.attempt.expires_at,
            serverNow: payload.server_now,
            onTick: ({ label, stage }) => {
                timerEls.forEach((el) => {
                    el.textContent = label;
                    el.className = ['cx-timer', el.classList.contains('cx-timer--rail') ? 'cx-timer--rail' : '', el.classList.contains('cx-timer--top') ? 'cx-timer--top' : '', 'is-' + stage]
                        .filter(Boolean)
                        .join(' ');
                });
            },
            onExpire: () => {
                notify('Time is up. Submitting your exam…', 'warn', 8000);
                if (payload.exam.auto_submit_on_timer_end) {
                    finalizeSubmit().catch(() => {
                        window.location.href = urls.result;
                    });
                }
            },
        });
        state.timerApi.start(totalSeconds);
    } else {
        timerEls.forEach((el) => {
            el.textContent = 'No timer';
            el.classList.add('is-green');
        });
    }

    state.proctorCleanup = bindProctoring({
        eventsUrl: urls.events,
        policy,
        onAutoSubmit: () => {
            notify('Exam submitted due to a rule violation.', 'error', 6000);
            state.webcamStop?.();
            window.setTimeout(() => {
                window.location.href = urls.result;
            }, 800);
        },
        onWarning: (message) => {
            if (message) notify(message, 'warn', 6000);
        },
        onFullscreenExit: () => {
            if (!policy.require_fullscreen) return;
            let overlay = document.getElementById('cx-fs-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'cx-fs-overlay';
                overlay.className = 'cx-loading';
                overlay.innerHTML = [
                    '<div class="cx-loading__card">',
                    '<h2>Fullscreen required</h2>',
                    '<p>Return to fullscreen to continue the exam.</p>',
                    '<button type="button" class="et-btn et-btn--primary" id="cx-fs-reenter">Re-enter fullscreen</button>',
                    '</div>',
                ].join('');
                document.body.appendChild(overlay);
                overlay.querySelector('#cx-fs-reenter')?.addEventListener('click', async () => {
                    try {
                        await document.documentElement.requestFullscreen();
                        overlay.hidden = true;
                        overlay.setAttribute('hidden', 'hidden');
                    } catch (e) {
                        notify('Unable to enter fullscreen. Allow it when prompted.', 'error');
                    }
                });
            }
            overlay.hidden = false;
            overlay.removeAttribute('hidden');
            notify('Fullscreen is required for this exam.', 'warn');
        },
    });

    if (requireWebcam) {
        state.webcamStop = startWebcamMonitor({
            videoEl: root.querySelector('#cx-webcam-preview'),
            statusEl: root.querySelector('#cx-webcam-status'),
            eventsUrl: urls.events,
            onStatus: (message, tone = 'warn') => notify(message, tone),
            onAutoSubmit: () => {
                state.webcamStop?.();
                window.location.href = urls.result;
            },
        });
    }

    if (policy.require_fullscreen && !document.fullscreenElement) {
        document.documentElement.requestFullscreen?.().catch(() => {
            notify('Please enter fullscreen to continue.', 'warn');
        });
    }

    state.heartbeatTimer = window.setInterval(() => {
        api(urls.heartbeat, { method: 'POST', body: {} })
            .then((data) => {
                if (data?.status && data.status !== 'in_progress' && data.status !== 'active') {
                    notify('This attempt has ended.', 'warn');
                    window.setTimeout(() => {
                        window.location.href = urls.result;
                    }, 1200);
                }
            })
            .catch(() => {});
    }, 30000);

    const onBeforeUnload = () => {
        state.webcamStop?.();
    };
    window.addEventListener('beforeunload', onBeforeUnload);
    cleanups.push(() => window.removeEventListener('beforeunload', onBeforeUnload));

    showQuestion();

    return function destroy() {
        if (state.destroyed) return;
        state.destroyed = true;
        root.dataset.cxReady = '0';
        if (state.toastTimer) window.clearTimeout(state.toastTimer);
        if (state.heartbeatTimer) window.clearInterval(state.heartbeatTimer);
        state.timerApi?.stop?.();
        state.webcamStop?.();
        state.proctorCleanup?.();
        setDrawer(false);
        cleanups.forEach((fn) => {
            try { fn(); } catch (e) {}
        });
    };
}

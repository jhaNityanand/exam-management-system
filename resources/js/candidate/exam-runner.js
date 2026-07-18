import { api } from './api';
import { createAutosave } from './autosave';
import { bindProctoring } from './proctoring';
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

function orderedOptions(question) {
    const options = question.question?.options || {};
    const isArray = Array.isArray(options);
    const order = question.option_order?.length
        ? question.option_order
        : (isArray ? options.map((_, i) => i) : Object.keys(options));

    return order.map((key) => {
        const raw = isArray ? options[Number(key)] : options[key];
        const label = optionLabel(raw) || String(key);
        return { key: String(key), label };
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

function renderQuestion(container, question) {
    const type = question.question?.type || 'mcq';
    const allowsMultiple = !!question.question?.allows_multiple || type === 'multi_select';
    const body = question.question?.body || '';
    let html = `<div class="cx-question">${body}</div><div class="cx-answer">`;

    if (type === 'true_false') {
        ['True', 'False'].forEach((value) => {
            html += `<label class="cx-option"><input type="radio" name="q${question.id}" value="${escapeHtml(value)}"> <span>${escapeHtml(value)}</span></label>`;
        });
    } else if (type === 'fill_blank') {
        html += `<input type="text" name="q${question.id}" placeholder="Type your answer">`;
    } else if (type === 'short_answer' || type === 'long_answer' || type === 'written') {
        html += `<textarea name="q${question.id}" rows="6" placeholder="Write your answer"></textarea>`;
    } else {
        if (allowsMultiple) {
            html += `<p class="cx-hint">Multiple answers may be correct.</p>`;
        }
        orderedOptions(question).forEach((opt) => {
            const inputType = allowsMultiple ? 'checkbox' : 'radio';
            html += `<label class="cx-option"><input type="${inputType}" name="q${question.id}" value="${escapeHtml(opt.key)}"> <span>${escapeHtml(opt.label)}</span></label>`;
        });
    }

    html += '</div>';
    container.innerHTML = html;
}

function readAnswer(container, question) {
    const type = question.question?.type || 'mcq';
    const allowsMultiple = !!question.question?.allows_multiple || type === 'multi_select';

    if (type === 'fill_blank' || type === 'short_answer' || type === 'long_answer' || type === 'written') {
        const el = container.querySelector(`[name="q${question.id}"]`);
        return el ? el.value : '';
    }

    if (allowsMultiple) {
        return Array.from(container.querySelectorAll(`[name="q${question.id}"]:checked`)).map((el) => el.value);
    }

    const selected = container.querySelector(`[name="q${question.id}"]:checked`);
    return selected ? selected.value : null;
}

function applyAnswer(container, question, value) {
    if (value === null || value === undefined || value === '') return;
    const type = question.question?.type || 'mcq';
    const allowsMultiple = !!question.question?.allows_multiple || type === 'multi_select';

    if (type === 'fill_blank' || type === 'short_answer' || type === 'long_answer' || type === 'written') {
        const el = container.querySelector(`[name="q${question.id}"]`);
        if (el) el.value = typeof value === 'object' && !Array.isArray(value) ? (value.text || '') : (Array.isArray(value) ? (value[0] || '') : value);
        return;
    }

    const values = Array.isArray(value) ? value.map(String) : [String(value)];
    container.querySelectorAll(`[name="q${question.id}"]`).forEach((el) => {
        el.checked = values.includes(el.value);
        el.closest('.cx-option')?.classList.toggle('is-selected', el.checked);
    });
}

function bindOptionHighlight(container) {
    container.querySelectorAll('.cx-option input').forEach((input) => {
        input.addEventListener('change', () => {
            const name = input.name;
            container.querySelectorAll(`input[name="${name}"]`).forEach((el) => {
                el.closest('.cx-option')?.classList.toggle('is-selected', el.checked);
            });
        });
    });
}

export function initExamRunner(root) {
    if (!root) return;

    const payload = JSON.parse(root.dataset.payload || '{}');
    const urls = JSON.parse(root.dataset.urls || '{}');
    const userId = root.dataset.userId || 'u';
    const questions = payload.questions || [];
    const state = {
        index: 0,
        answers: {},
        review: {},
        visited: {},
        submitting: false,
        reviewSweep: false,
    };

    questions.forEach((q) => {
        state.answers[q.id] = q.answer ?? emptyAnswerFor(q);
        state.review[q.id] = !!q.is_marked_for_review;
        state.visited[q.id] = !!q.is_visited;
    });

    const local = loadLocal(payload.attempt.id, userId);
    if (local?.answers) {
        Object.entries(local.answers).forEach(([id, item]) => {
            state.answers[id] = item.answer_value;
            state.review[id] = !!item.is_marked_for_review;
            state.visited[id] = !!item.is_visited;
        });
        if (local.current != null) state.index = Math.min(questions.length - 1, Math.max(0, Number(local.current) || 0));
    }

    const prefs = payload.attempt?.preferences || {};
    document.body.dataset.theme = prefs.theme === 'system'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : (prefs.theme || 'light');
    document.body.dataset.font = prefs.font_size || 'md';
    if (prefs.palette_position === 'left') root.classList.add('palette-left');

    const questionEl = root.querySelector('#cx-question');
    const paletteEl = root.querySelector('#cx-palette');
    const timerEl = root.querySelector('#cx-timer');
    const mobileTimerEl = root.querySelector('#cx-mobile-timer');
    const saveStateEl = root.querySelector('#cx-save-state');
    const progressEl = root.querySelector('#cx-progress-label');
    const hintEl = root.querySelector('#cx-nav-hint');
    const modal = document.getElementById('cx-submit-modal');
    const statsEl = document.getElementById('cx-submit-stats');

    const autosave = createAutosave({
        attemptId: payload.attempt.id,
        url: urls.answers,
        userId,
        onState: (label, detail = '') => {
            if (!saveStateEl) return;
            const map = {
                saved: 'Saved',
                pending: 'Pending sync',
                saving: 'Saving…',
                offline: 'Offline — answers kept locally',
                error: detail ? `Save issue: ${detail}` : 'Save failed — retrying…',
            };
            saveStateEl.textContent = map[label] || label;
            saveStateEl.dataset.state = label;
            saveStateEl.title = detail || '';
        },
    });
    autosave.setRevision(payload.attempt.revision || 0);

    function syncLocal() {
        saveLocal(payload.attempt.id, {
            current: state.index,
            answers: Object.fromEntries(questions.map((item) => [item.id, {
                exam_attempt_question_id: item.id,
                answer_value: state.answers[item.id],
                is_marked_for_review: !!state.review[item.id],
                is_visited: !!state.visited[item.id],
            }])),
            updated_at: Date.now(),
        }, userId);
    }

    function showHint(message) {
        if (!hintEl) return;
        if (!message) {
            hintEl.hidden = true;
            hintEl.textContent = '';
            return;
        }
        hintEl.hidden = false;
        hintEl.textContent = message;
    }

    function persistCurrent() {
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
        });
        syncLocal();
        paintPalette();
        updateProgress();
    }

    function paintPalette() {
        if (!paletteEl) return;
        paletteEl.innerHTML = '';
        questions.forEach((q, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = String(idx + 1);
            if (idx === state.index) btn.classList.add('is-current');
            if (state.review[q.id]) btn.classList.add('is-review');
            else if (isAnsweredValue(state.answers[q.id])) btn.classList.add('is-answered');
            else if (state.visited[q.id]) btn.classList.add('is-visited');
            btn.addEventListener('click', () => {
                persistCurrent();
                state.index = idx;
                showQuestion();
            });
            paletteEl.appendChild(btn);
        });
    }

    function updateProgress() {
        const answered = questions.filter((q) => isAnsweredValue(state.answers[q.id])).length;
        if (progressEl) progressEl.textContent = `${answered} / ${questions.length} answered`;
    }

    function showQuestion() {
        const q = questions[state.index];
        if (!q || !questionEl) return;
        renderQuestion(questionEl, q);
        applyAnswer(questionEl, q, state.answers[q.id]);
        bindOptionHighlight(questionEl);
        state.visited[q.id] = true;
        questionEl.onchange = persistCurrent;
        questionEl.oninput = persistCurrent;
        paintPalette();
        updateProgress();
        syncLocal();
        const qno = root.querySelector('#cx-qno');
        if (qno) qno.textContent = `Question ${state.index + 1} of ${questions.length}`;
        const mark = root.querySelector('#cx-mark-review');
        if (mark) mark.checked = !!state.review[q.id];
    }

    function goToIndex(idx, hint = '') {
        state.index = Math.max(0, Math.min(questions.length - 1, idx));
        showHint(hint);
        showQuestion();
    }

    function nextIncompleteIndex(fromIndex = state.index) {
        for (let i = fromIndex + 1; i < questions.length; i += 1) {
            if (!isAnsweredValue(state.answers[questions[i].id])) return i;
        }
        for (let i = 0; i <= fromIndex; i += 1) {
            if (!isAnsweredValue(state.answers[questions[i].id])) return i;
        }
        return -1;
    }

    function nextReviewIndex(fromIndex = state.index) {
        for (let i = fromIndex + 1; i < questions.length; i += 1) {
            if (state.review[questions[i].id]) return i;
        }
        for (let i = 0; i <= fromIndex; i += 1) {
            if (state.review[questions[i].id]) return i;
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
            statsEl.innerHTML = `
                <li><strong>${s.total}</strong> total questions</li>
                <li><strong>${s.answered}</strong> answered</li>
                <li><strong>${s.unanswered.length}</strong> unanswered</li>
                <li><strong>${s.reviewed.length}</strong> marked for review</li>
            `;
        }
        if (modal) {
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
        }
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    async function finalizeSubmit() {
        if (state.submitting) return;
        state.submitting = true;
        showHint('Submitting exam…');
        try {
            persistCurrent();
            const ok = await autosave.flush();
            if (!ok && autosave.pendingCount() > 0) {
                throw new Error(autosave.lastError() || 'Unable to sync answers. Please try again.');
            }
            const data = await api(urls.submit, { method: 'POST', body: {} });
            clearLocal(payload.attempt.id, userId);
            window.location.href = data.redirect || urls.result;
        } catch (e) {
            state.submitting = false;
            showHint(e.message || 'Submit failed');
            alert(e.message || 'Submit failed');
        }
    }

    /**
     * Submit = save current answer, then:
     * - go to next question
     * - on last question, jump to unanswered / marked-for-review
     * - while sweeping review items, jump review-to-review
     * - when everything is done, open final confirmation
     */
    async function handleSubmitClick() {
        persistCurrent();
        await autosave.flush();

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
            if (reviewIdx === -1) {
                state.reviewSweep = false;
                openModal();
                return;
            }
            if (reviewIdx === state.index) {
                // Only one review item left (current). Offer final submit.
                openModal();
                return;
            }
            goToIndex(reviewIdx, 'Opening a question marked for review.');
            return;
        }

        if (!atLast) {
            goToIndex(state.index + 1, 'Answer saved. Moved to the next question.');
            return;
        }

        const incomplete = nextIncompleteIndex(state.index);
        if (incomplete !== -1) {
            goToIndex(incomplete, 'Last question saved. Opening an unanswered question.');
            return;
        }

        if (reviewLeft) {
            state.reviewSweep = true;
            const reviewIdx = nextReviewIndex(state.index);
            goToIndex(reviewIdx === -1 ? state.index : reviewIdx, 'All answered. Opening a marked-for-review question.');
            return;
        }

        openModal();
    }

    function skipCurrent() {
        persistCurrent();

        if (state.index < questions.length - 1) {
            goToIndex(state.index + 1, 'Question skipped. Moved to the next question.');
            return;
        }

        const incomplete = questions.findIndex((q, idx) => (
            idx !== state.index && !isAnsweredValue(state.answers[q.id])
        ));
        if (incomplete !== -1) {
            goToIndex(incomplete, 'Question skipped. Opening another unanswered question.');
            return;
        }

        const reviewIdx = questions.findIndex((q, idx) => (
            idx !== state.index && state.review[q.id]
        ));
        if (reviewIdx !== -1) {
            goToIndex(reviewIdx, 'Question skipped. Opening a question marked for review.');
            return;
        }

        openModal();
    }

    async function openFinalReview() {
        persistCurrent();
        await autosave.flush();
        openModal();
    }

    root.querySelector('#cx-prev')?.addEventListener('click', () => {
        persistCurrent();
        if (state.index > 0) {
            goToIndex(state.index - 1);
        }
    });

    root.querySelector('#cx-next')?.addEventListener('click', () => {
        persistCurrent();
        if (state.index < questions.length - 1) {
            goToIndex(state.index + 1);
        } else {
            // Last question: jump to review queue if any, else unanswered, else confirmation.
            const reviewIdx = nextReviewIndex(state.index);
            if (reviewIdx !== -1) {
                goToIndex(reviewIdx, 'Last question reached. Opening a marked-for-review question.');
                return;
            }
            const incomplete = nextIncompleteIndex(state.index);
            if (incomplete !== -1) {
                goToIndex(incomplete, 'Last question reached. Opening an unanswered question.');
                return;
            }
            openModal();
        }
    });

    root.querySelector('#cx-skip')?.addEventListener('click', skipCurrent);

    root.querySelector('#cx-clear')?.addEventListener('click', () => {
        const q = questions[state.index];
        if (!q || !questionEl) return;
        state.answers[q.id] = emptyAnswerFor(q);
        renderQuestion(questionEl, q);
        bindOptionHighlight(questionEl);
        questionEl.onchange = persistCurrent;
        questionEl.oninput = persistCurrent;
        persistCurrent();
        showHint('Selection cleared for this question.');
    });

    root.querySelector('#cx-mark-review')?.addEventListener('change', (e) => {
        const q = questions[state.index];
        state.review[q.id] = !!e.target.checked;
        persistCurrent();
    });

    root.querySelector('#cx-submit')?.addEventListener('click', () => {
        handleSubmitClick().catch((e) => {
            showHint(e.message || 'Unable to continue');
        });
    });

    root.querySelector('#cx-final-submit')?.addEventListener('click', () => {
        openFinalReview().catch((e) => {
            showHint(e.message || 'Unable to prepare final submission');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.repeat || (modal && !modal.hidden)) return;

        const target = event.target;
        if (target instanceof HTMLTextAreaElement || target instanceof HTMLButtonElement) return;
        if (target instanceof HTMLSelectElement) return;

        event.preventDefault();
        const q = questions[state.index];
        const value = q ? readAnswer(questionEl, q) : null;

        if (isAnsweredValue(value)) {
            handleSubmitClick().catch((e) => {
                showHint(e.message || 'Unable to save and continue');
            });
        } else {
            skipCurrent();
        }
    });

    document.getElementById('cx-confirm-submit')?.addEventListener('click', () => {
        closeModal();
        finalizeSubmit();
    });

    modal?.querySelectorAll('[data-close-modal]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    const totalSeconds = Math.max(1, Math.floor((new Date(payload.attempt.expires_at).getTime() - new Date(payload.server_now).getTime()) / 1000));
    if (payload.exam?.enable_exam_timer && payload.attempt?.expires_at) {
        const timer = createTimer({
            expiresAt: payload.attempt.expires_at,
            serverNow: payload.server_now,
            onTick: ({ label, stage }) => {
                [timerEl, mobileTimerEl].forEach((el) => {
                    if (!el) return;
                    el.textContent = label;
                    el.className = `cx-timer is-${stage === 'green' ? 'green' : stage}`;
                });
            },
            onExpire: () => {
                if (payload.exam.auto_submit_on_timer_end) {
                    finalizeSubmit().catch(() => {
                        window.location.href = urls.result;
                    });
                }
            },
        });
        timer.start(totalSeconds);
    } else if (timerEl) {
        timerEl.textContent = 'No timer';
    }

    bindProctoring({
        eventsUrl: urls.events,
        policy: payload.policy || {},
        onAutoSubmit: () => {
            window.location.href = urls.result;
        },
    });

    window.setInterval(() => {
        api(urls.heartbeat, { method: 'POST', body: {} }).catch(() => {});
    }, 30000);

    showQuestion();
}

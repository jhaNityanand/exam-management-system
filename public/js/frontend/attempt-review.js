/**
 * Attempt review page: skeleton → AJAX fetch → summary + cards.
 */
(function () {
    'use strict';

    var TEXT_TYPES = {
        fill_blank: true,
        short_answer: true,
        long_answer: true,
        written: true,
    };

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function iconCheck() {
        return '<svg class="rv-option__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    function iconCross() {
        return '<svg class="rv-option__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';
    }

    function iconChevron() {
        return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    function statusLabel(status) {
        if (status === 'correct') return 'Correct';
        if (status === 'incorrect') return 'Incorrect';
        if (status === 'pending') return 'Pending review';
        return 'Unanswered';
    }

    function formatNumber(value, digits) {
        var n = Number(value || 0);
        return n.toLocaleString(undefined, {
            minimumFractionDigits: digits,
            maximumFractionDigits: digits,
        });
    }

    function hideAndRemove(el) {
        if (!el) return;
        el.hidden = true;
        el.setAttribute('aria-hidden', 'true');
        el.classList.add('rv-is-gone');
        if (el.parentNode) {
            el.parentNode.removeChild(el);
        }
    }

    function isMulti(question) {
        return !!(question.allows_multiple || question.type === 'multi_select');
    }

    function isTextType(question) {
        return !!TEXT_TYPES[question.type];
    }

    function textValue(labels, keys) {
        if (labels && labels.length) return labels.join(', ');
        if (keys && keys.length) return keys.join(', ');
        return '';
    }

    function optionState(option, question) {
        var key = String(option.key);
        var candidate = (question.candidate_keys || []).map(String);
        var correct = (question.correct_keys || []).map(String);
        var selected = candidate.indexOf(key) !== -1;
        var isCorrectKey = correct.indexOf(key) !== -1;
        var cls = '';
        var icon = '';
        var checked = selected;

        if (selected && isCorrectKey) {
            cls = 'is-correct';
            icon = iconCheck();
        } else if (selected && !isCorrectKey) {
            cls = 'is-wrong';
            icon = iconCross();
        } else if (!selected && isCorrectKey) {
            // Always reveal correct answer (including unanswered).
            cls = question.status === 'correct' ? 'is-correct' : 'is-missed';
            icon = iconCheck();
            checked = true;
        }

        return { cls: cls, icon: icon, checked: checked, selected: selected, isCorrectKey: isCorrectKey };
    }

    function renderSummary(summary) {
        var passed = !!summary.passed;
        var pct = Math.max(0, Math.min(100, Number(summary.percentage || 0)));
        return [
            '<div class="rv-summary__banner ' + (passed ? 'is-pass' : 'is-fail') + '">',
            '  <div class="rv-summary__score">',
            '    <strong>' + formatNumber(summary.score, 2) + ' / ' + formatNumber(summary.total_marks, 2) + '</strong>',
            '    <span>Marks obtained · Passing marks ' + formatNumber(summary.passing_marks, 2) + '</span>',
            '  </div>',
            '  <span class="rv-status-pill ' + (passed ? 'is-pass' : 'is-fail') + '">' + (passed ? 'Pass' : 'Fail') + ' · ' + formatNumber(summary.percentage, 1) + '%</span>',
            '</div>',
            '<div class="rv-summary__grid">',
            '  <div class="rv-stat"><span class="rv-stat__label">Total questions</span><span class="rv-stat__value">' + summary.total_questions + '</span></div>',
            '  <div class="rv-stat"><span class="rv-stat__label">Attempted</span><span class="rv-stat__value">' + summary.attempted + '</span></div>',
            '  <div class="rv-stat is-correct"><span class="rv-stat__label">Correct</span><span class="rv-stat__value">' + summary.correct + '</span></div>',
            '  <div class="rv-stat is-incorrect"><span class="rv-stat__label">Incorrect</span><span class="rv-stat__value">' + summary.incorrect + '</span></div>',
            '  <div class="rv-stat is-muted"><span class="rv-stat__label">Unanswered</span><span class="rv-stat__value">' + summary.unanswered + '</span></div>',
            '  <div class="rv-stat"><span class="rv-stat__label">Score</span><span class="rv-stat__value">' + formatNumber(summary.score, 2) + '</span></div>',
            '  <div class="rv-stat"><span class="rv-stat__label">Passing marks</span><span class="rv-stat__value">' + formatNumber(summary.passing_marks, 2) + '</span></div>',
            '  <div class="rv-stat"><span class="rv-stat__label">Percentage</span><span class="rv-stat__value">' + formatNumber(summary.percentage, 1) + '%</span></div>',
            '</div>',
            '<div class="rv-progress">',
            '  <div class="rv-progress__meta"><span>Overall score</span><span>' + formatNumber(summary.percentage, 1) + '%</span></div>',
            '  <div class="rv-progress__track"><div class="rv-progress__fill ' + (passed ? '' : 'is-fail') + '" data-width="' + pct + '%" style="width:0%"></div></div>',
            '</div>',
        ].join('');
    }

    function renderChoiceOptions(question) {
        var options = question.options || [];
        var inputType = isMulti(question) ? 'checkbox' : 'radio';
        var name = 'rv-q-' + question.id;

        if (!options.length && question.type === 'true_false') {
            options = [
                { key: 'True', letter: 'A', text: 'True' },
                { key: 'False', letter: 'B', text: 'False' },
            ];
        }

        if (!options.length) {
            return renderTextAnswer(question);
        }

        return '<div class="rv-options" role="group">' + options.map(function (option) {
            var state = optionState(option, question);
            // Unique names so wrong + correct can both appear checked in review.
            var inputName = name + '-' + option.key;
            return [
                '<label class="rv-option ' + state.cls + '">',
                '  <span class="rv-option__control">',
                '    <input type="' + inputType + '" name="' + escapeHtml(inputName) + '" value="' + escapeHtml(option.key) + '"' + (state.checked ? ' checked' : '') + ' disabled tabindex="-1">',
                '    <span class="rv-option__fake" aria-hidden="true"></span>',
                '  </span>',
                '  <span class="rv-option__key">' + escapeHtml(option.letter) + '</span>',
                '  <span class="rv-option__text">' + escapeHtml(option.text) + '</span>',
                state.icon,
                '</label>',
            ].join('');
        }).join('') + '</div>';
    }

    function renderTextAnswer(question) {
        var yours = textValue(question.candidate_labels, question.candidate_keys);
        var correct = textValue(question.correct_labels, question.correct_keys);
        var yoursClass = 'is-empty';
        if (question.status === 'correct') yoursClass = 'is-yours-correct';
        else if (question.status === 'incorrect') yoursClass = 'is-yours-wrong';
        else if (!yours) yoursClass = 'is-empty';

        var field;
        if (question.type === 'fill_blank') {
            field = '<input class="rv-field" type="text" value="' + escapeHtml(yours) + '" readonly disabled placeholder="No answer">';
        } else {
            var rows = question.type === 'long_answer' || question.type === 'written' ? 5 : 3;
            field = '<textarea class="rv-field" rows="' + rows + '" readonly disabled placeholder="No answer">' + escapeHtml(yours) + '</textarea>';
        }

        return [
            '<div class="rv-answer-block">',
            '  <div class="rv-answer-row ' + yoursClass + '">',
            '    <strong>Your answer</strong>',
            field,
            '  </div>',
            '  <div class="rv-answer-row is-correct">',
            '    <strong>Correct answer</strong>',
            '    <div class="rv-correct-text">' + (correct ? escapeHtml(correct) : '—') + '</div>',
            '  </div>',
            '</div>',
        ].join('');
    }

    function renderAnswers(question) {
        if (isTextType(question)) {
            return renderTextAnswer(question);
        }
        return renderChoiceOptions(question);
    }

    function renderQuestion(question, index) {
        var status = question.status || 'unanswered';
        var delay = Math.min(index * 30, 220);
        var explanation = question.explanation
            ? [
                '<div class="rv-explain">',
                '  <button type="button" class="rv-explain__toggle" aria-expanded="false">',
                '    <span>View explanation</span>',
                iconChevron(),
                '  </button>',
                '  <div class="rv-explain__panel"><div class="rv-explain__inner"><div class="rv-explain__content">' + question.explanation + '</div></div></div>',
                '</div>',
            ].join('')
            : '';

        return [
            '<article class="rv-card" style="animation-delay:' + delay + 'ms" data-status="' + escapeHtml(status) + '">',
            '  <div class="rv-card__head">',
            '    <div class="rv-card__identity">',
            '      <span class="rv-qno">Q' + question.position + '</span>',
            '      <span class="rv-badge is-' + escapeHtml(status) + '">' + escapeHtml(statusLabel(status)) + '</span>',
            '    </div>',
            '    <span class="rv-marks is-' + escapeHtml(status) + '">' + formatNumber(question.awarded_marks, 2) + ' / ' + formatNumber(question.marks, 2) + '</span>',
            '  </div>',
            '  <div class="rv-body et-prose">' + (question.body || '') + '</div>',
            renderAnswers(question),
            explanation,
            '</article>',
        ].join('');
    }

    function bindExplanations(root) {
        root.querySelectorAll('.rv-explain').forEach(function (block) {
            var toggle = block.querySelector('.rv-explain__toggle');
            if (!toggle) return;
            toggle.addEventListener('click', function () {
                var open = block.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.querySelector('span').textContent = open ? 'Hide explanation' : 'View explanation';
            });
        });
    }

    async function loadReview(page) {
        var url = page.getAttribute('data-url');
        var errorEl = document.getElementById('rv-error');
        var summaryEl = document.getElementById('rv-summary');
        var summarySkel = document.getElementById('rv-summary-skeleton');
        var listEl = document.getElementById('rv-questions');
        var listSkel = document.getElementById('rv-questions-skeleton');
        var listMeta = document.getElementById('rv-list-meta');

        try {
            var res = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            var data = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                throw new Error(data.message || 'Unable to load review.');
            }

            hideAndRemove(summarySkel);
            hideAndRemove(listSkel);

            if (summaryEl) {
                summaryEl.hidden = false;
                summaryEl.removeAttribute('hidden');
                summaryEl.innerHTML = renderSummary(data.summary || {});
                requestAnimationFrame(function () {
                    var fill = summaryEl.querySelector('.rv-progress__fill');
                    if (fill) {
                        fill.style.width = fill.getAttribute('data-width') || '0%';
                    }
                });
            }

            var questions = data.questions || [];
            if (listMeta) {
                listMeta.textContent = questions.length
                    ? questions.length + ' question' + (questions.length === 1 ? '' : 's')
                    : 'No questions in this attempt';
            }

            if (listEl) {
                listEl.hidden = false;
                listEl.removeAttribute('hidden');
                if (!questions.length) {
                    listEl.innerHTML = '<div class="rv-empty">No reviewable questions were found for this attempt.</div>';
                } else {
                    listEl.innerHTML = questions.map(renderQuestion).join('');
                    bindExplanations(listEl);
                }
            }
        } catch (e) {
            hideAndRemove(summarySkel);
            hideAndRemove(listSkel);
            if (listMeta) listMeta.textContent = 'Unable to load questions';
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.removeAttribute('hidden');
                errorEl.textContent = (e && e.message) || 'Unable to load the review right now.';
            }
        }
    }

    onReady(function () {
        var page = document.getElementById('rv-page');
        if (!page) return;
        loadReview(page);
    });
})();

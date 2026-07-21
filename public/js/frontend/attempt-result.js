/**
 * Attempt result page: skeleton → AJAX summary render.
 */
(function () {
    'use strict';

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

    function formatNumber(value, digits) {
        var n = Number(value || 0);
        return n.toLocaleString(undefined, {
            minimumFractionDigits: digits,
            maximumFractionDigits: digits,
        });
    }

    function formatDuration(seconds) {
        var total = Math.max(0, Math.floor(Number(seconds) || 0));
        var h = Math.floor(total / 3600);
        var m = Math.floor((total % 3600) / 60);
        var s = total % 60;
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function hideAndRemove(el) {
        if (!el) return;
        el.hidden = true;
        el.classList.add('rs-is-gone');
        if (el.parentNode) el.parentNode.removeChild(el);
    }

    function iconPass() {
        return '<svg class="rs-outcome__glyph" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="m8.5 12.2 2.4 2.4 4.6-4.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    function iconFail() {
        return '<svg class="rs-outcome__glyph" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="m9 9 6 6M15 9l-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
    }

    function renderResult(data) {
        var summary = data.summary || {};
        var passed = !!summary.passed;
        var pct = Math.max(0, Math.min(100, Number(summary.percentage || 0)));
        var reviewUrl = data.review_url || '#';
        var examUrl = data.exam_url || '#';
        var resultsUrl = data.results_url || '#';

        return [
            '<section class="rs-panel">',
            '  <div class="rs-outcome ' + (passed ? 'is-pass' : 'is-fail') + '">',
            '    <div class="rs-outcome__main">',
            passed ? iconPass() : iconFail(),
            '      <div class="rs-outcome__copy">',
            '        <p class="rs-outcome__label">' + (passed ? 'Congratulations' : 'Keep practicing') + '</p>',
            '        <h2>' + (passed ? 'You passed' : 'You did not pass') + '</h2>',
            '        <p class="rs-outcome__meta">' + escapeHtml(summary.exam_title || 'Exam') + '</p>',
            '      </div>',
            '    </div>',
            '    <div class="rs-outcome__score">',
            '      <strong>' + formatNumber(summary.score, 2) + '<span> / ' + formatNumber(summary.total_marks, 2) + '</span></strong>',
            '      <em>' + formatNumber(summary.percentage, 1) + '%</em>',
            '      <span class="rs-status-pill ' + (passed ? 'is-pass' : 'is-fail') + '">' + (passed ? 'Pass' : 'Fail') + '</span>',
            '    </div>',
            '  </div>',

            '  <div class="rs-progress">',
            '    <div class="rs-progress__meta"><span>Overall score</span><span>' + formatNumber(summary.percentage, 1) + '%</span></div>',
            '    <div class="rs-progress__track"><div class="rs-progress__fill ' + (passed ? '' : 'is-fail') + '" data-width="' + pct + '%" style="width:0%"></div></div>',
            '    <div class="rs-progress__hint">Passing marks ' + formatNumber(summary.passing_marks, 2) + '</div>',
            '  </div>',

            '  <div class="rs-stats">',
            '    <div class="rs-stat"><span class="rs-stat__label">Score</span><span class="rs-stat__value">' + formatNumber(summary.score, 2) + '</span></div>',
            '    <div class="rs-stat"><span class="rs-stat__label">Percentage</span><span class="rs-stat__value">' + formatNumber(summary.percentage, 1) + '%</span></div>',
            '    <div class="rs-stat ' + (passed ? 'is-correct' : 'is-incorrect') + '"><span class="rs-stat__label">Status</span><span class="rs-stat__value">' + (passed ? 'Pass' : 'Fail') + '</span></div>',
            '    <div class="rs-stat"><span class="rs-stat__label">Time spent</span><span class="rs-stat__value">' + formatDuration(summary.time_spent_seconds) + '</span></div>',
            '    <div class="rs-stat"><span class="rs-stat__label">Total questions</span><span class="rs-stat__value">' + (summary.total_questions || 0) + '</span></div>',
            '    <div class="rs-stat is-correct"><span class="rs-stat__label">Correct</span><span class="rs-stat__value">' + (summary.correct || 0) + '</span></div>',
            '    <div class="rs-stat is-incorrect"><span class="rs-stat__label">Incorrect</span><span class="rs-stat__value">' + (summary.incorrect || 0) + '</span></div>',
            '    <div class="rs-stat is-muted"><span class="rs-stat__label">Unanswered</span><span class="rs-stat__value">' + (summary.unanswered || 0) + '</span></div>',
            '  </div>',

            '  <div class="rs-breakdown">',
            '    <div class="rs-breakdown__item"><span>Attempted</span><strong>' + (summary.attempted || 0) + '</strong></div>',
            '    <div class="rs-breakdown__item"><span>Passing marks</span><strong>' + formatNumber(summary.passing_marks, 2) + '</strong></div>',
            '    <div class="rs-breakdown__item"><span>Submission</span><strong>' + escapeHtml(summary.submission_label || summary.submission_reason || '—') + '</strong></div>',
            '  </div>',

            '  <div class="rs-actions">',
            '    <a href="' + escapeHtml(reviewUrl) + '" class="et-btn et-btn--primary">Question review</a>',
            '    <a href="' + escapeHtml(examUrl) + '" class="et-btn et-btn--ghost">Back to exam</a>',
            '    <a href="' + escapeHtml(resultsUrl) + '" class="et-btn et-btn--ghost">All results</a>',
            '  </div>',
            '</section>',
        ].join('');
    }

    async function loadResult(page) {
        var url = page.getAttribute('data-url');
        var errorEl = document.getElementById('rs-error');
        var skeleton = document.getElementById('rs-skeleton');
        var content = document.getElementById('rs-content');

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
                throw new Error(data.message || 'Unable to load result.');
            }

            hideAndRemove(skeleton);

            if (!data.visible) {
                if (content) {
                    content.hidden = false;
                    content.removeAttribute('hidden');
                    content.innerHTML = [
                        '<section class="rs-locked">',
                        '  <h2>Results are not available yet</h2>',
                        '  <p>Your attempt has been submitted. The institution will release results according to the exam policy.</p>',
                        '  <div class="rs-actions">',
                        '    <a href="' + escapeHtml(data.results_url || '#') + '" class="et-btn et-btn--primary">Back to my results</a>',
                        '  </div>',
                        '</section>',
                    ].join('');
                }
                return;
            }

            if (content) {
                content.hidden = false;
                content.removeAttribute('hidden');
                content.innerHTML = renderResult(data);
                requestAnimationFrame(function () {
                    var fill = content.querySelector('.rs-progress__fill');
                    if (fill) fill.style.width = fill.getAttribute('data-width') || '0%';
                });
            }
        } catch (e) {
            hideAndRemove(skeleton);
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.removeAttribute('hidden');
                errorEl.textContent = (e && e.message) || 'Unable to load the result right now.';
            }
        }
    }

    onReady(function () {
        var page = document.getElementById('rs-page');
        if (!page || page.getAttribute('data-visible') !== '1') return;
        loadResult(page);
    });
})();

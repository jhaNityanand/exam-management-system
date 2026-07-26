/**
 * Exam Attempts page — AjaxTable list with expandable per-candidate attempts.
 * Depends on: window.examAttemptsPage, AjaxTable, EmsListUi (optional)
 */
document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.examAttemptsPage || {};
    if (!cfg.apiUrl) return;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[ch]);

    const dash = (v) => (v === null || v === undefined || v === '' ? '—' : escapeHtml(v));

    const badgeClass = (kind) => {
        const map = {
            success: 'ea-page-badge--success',
            danger: 'ea-page-badge--danger',
            warning: 'ea-page-badge--warning',
            info: 'ea-page-badge--info',
            muted: 'ea-page-badge--muted',
        };
        return map[kind] || map.muted;
    };

    const statusBadge = (attempt) => {
        if (!attempt?.status_label) return '<span class="text-slate-400">—</span>';
        return `<span class="ea-page-badge ${badgeClass(attempt.status_badge)}">${escapeHtml(attempt.status_label)}</span>`;
    };

    const resultBadge = (attempt) => {
        if (attempt?.passed === true || attempt?.result_label === 'Pass') {
            return '<span class="ea-page-badge ea-page-badge--success">Pass</span>';
        }
        if (attempt?.passed === false || attempt?.result_label === 'Fail') {
            return '<span class="ea-page-badge ea-page-badge--danger">Fail</span>';
        }
        return '<span class="text-slate-400">—</span>';
    };

    const pctLabel = (attempt) => (
        attempt?.percentage != null ? `${escapeHtml(attempt.percentage)}%` : '—'
    );

    const verificationThumbs = (attempt, candidateName) => {
        const items = Array.isArray(attempt?.verification) ? attempt.verification : [];
        if (!items.length) return '<span class="text-slate-400 dark:text-slate-500">—</span>';

        const shown = items.slice(0, 4).map((snap) => `
            <button type="button"
                    class="cand-verify-thumb js-ea-preview"
                    title="${escapeHtml(snap.type_label || 'Verification')}"
                    aria-label="View ${escapeHtml(snap.type_label || 'verification')}"
                    data-preview-url="${escapeHtml(snap.url)}"
                    data-preview-label="${escapeHtml(snap.type_label || 'Verification')} — ${escapeHtml(candidateName || '')}">
                <img src="${escapeHtml(snap.url)}" alt="" loading="lazy">
            </button>
        `).join('');

        const more = items.length > 4
            ? `<span class="cand-verify-more">+${items.length - 4}</span>`
            : '';

        return `<div class="cand-verify-thumbs">${shown}${more}</div>`;
    };

    const attemptCells = (attempt, candidateName) => `
        <td class="px-3 py-2.5 align-middle">${statusBadge(attempt)}</td>
        <td class="px-3 py-2.5 align-middle text-sm tabular-nums">${dash(attempt?.total_questions)}</td>
        <td class="px-3 py-2.5 align-middle text-sm tabular-nums">${dash(attempt?.attempted)}</td>
        <td class="px-3 py-2.5 align-middle text-sm tabular-nums text-emerald-600 dark:text-emerald-400">${dash(attempt?.right)}</td>
        <td class="px-3 py-2.5 align-middle text-sm tabular-nums text-rose-600 dark:text-rose-400">${dash(attempt?.wrong)}</td>
        <td class="px-3 py-2.5 align-middle text-sm tabular-nums">${dash(attempt?.unanswered)}</td>
        <td class="px-3 py-2.5 align-middle text-sm tabular-nums">${dash(attempt?.total_marks)}</td>
        <td class="px-3 py-2.5 align-middle text-sm">${dash(attempt?.neg_marks_label)}</td>
        <td class="px-3 py-2.5 align-middle text-sm font-semibold tabular-nums">${dash(attempt?.score)}</td>
        <td class="px-3 py-2.5 align-middle text-sm tabular-nums">${pctLabel(attempt)}</td>
        <td class="px-3 py-2.5 align-middle">${resultBadge(attempt)}</td>
        <td class="px-3 py-2.5 align-middle text-xs text-slate-600 dark:text-slate-300 whitespace-nowrap">${dash(attempt?.started_at_label)}</td>
        <td class="px-3 py-2.5 align-middle text-xs text-slate-600 dark:text-slate-300 whitespace-nowrap">${dash(attempt?.ended_at_label)}</td>
        <td class="px-3 py-2.5 align-middle text-sm whitespace-nowrap">${dash(attempt?.time_taken)}</td>
        <td class="px-3 py-2.5 align-middle text-sm whitespace-nowrap">${dash(attempt?.exam_duration_label)}</td>
        <td class="px-3 py-2.5 align-middle cand-td-verify">${verificationThumbs(attempt, candidateName)}</td>
    `;

    const avatarHtml = (row) => {
        if (row.avatar_url) {
            return `<div class="cand-list-avatar"><img src="${escapeHtml(row.avatar_url)}" alt="" class="cand-list-avatar__img"></div>`;
        }
        const color = escapeHtml(row.avatar_color || '#4f46e5');
        const initials = escapeHtml(row.initials || 'U');
        return `<div class="cand-list-avatar" style="background:${color}"><span class="cand-list-avatar__initials">${initials}</span></div>`;
    };

    const attemptsTable = new AjaxTable({
        containerSelector: '#ajax-table-container',
        apiUrl: cfg.apiUrl,
        tableBodySelector: '#attempts-table-body',
        paginationSelector: '#attempts-pagination',
        searchSelector: '#attempts-search',
        perPageSelector: '#attempts-per-page',
        filterDrawerSelector: '#filter-drawer',
        filterToggleSelector: '#btn-toggle-filters',
        filterDrawerFormSelector: '#filter-drawer-form',
        loadingSelector: '#attempts-loading',
        emptySelector: '#attempts-empty',
        totalCountSelector: '#attempts-total-count',
        defaultSort: 'last_attempt_at',
        defaultDirection: 'desc',
        skeletonColumns: 19,
        skeletonRows: 6,
        onFetchSuccess: (response) => {
            const total = Number(response?.meta?.total || 0);
            const countEl = document.getElementById('attempts-total-count');
            if (countEl) {
                countEl.textContent = total === 1 ? '1 candidate' : `${total} candidates`;
            }
        },
        rowTemplate: (row) => {
            const latest = row.latest_attempt || {};
            const attempts = Array.isArray(row.attempts) ? row.attempts : [];
            const canExpand = attempts.length > 0;
            const profileUrl = escapeHtml(row.profile_url || '#');
            const name = escapeHtml(row.name || '—');
            const email = escapeHtml(row.email || '—');
            const attemptsCount = Number(row.attempts_count || attempts.length || 0);

            const expandBtn = canExpand
                ? `<button type="button"
                           class="ea-expand-btn"
                           data-ea-expand="${escapeHtml(row.id)}"
                           aria-expanded="false"
                           aria-controls="ea-child-${escapeHtml(row.id)}"
                           title="Show all attempts">
                        <svg class="ea-expand-btn__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                        </svg>
                   </button>`
                : '<span class="ea-expand-placeholder"></span>';

            const childRows = attempts.map((attempt) => `
                <tr class="ea-child-attempt-row">
                    <td class="px-3 py-2 align-middle text-xs font-semibold text-indigo-600 dark:text-indigo-300 whitespace-nowrap">
                        #${escapeHtml(attempt.attempt_no)}
                    </td>
                    ${attemptCells(attempt, row.name)}
                </tr>
            `).join('');

            return `
                <tr class="list-row ea-main-row group" data-user-id="${escapeHtml(row.id)}">
                    <td class="px-3 py-2.5 align-middle w-10">${expandBtn}</td>
                    <td class="px-4 py-2.5 align-middle ea-td-candidate">
                        <div class="cand-list-user">
                            ${avatarHtml(row)}
                            <div class="min-w-0">
                                <a href="${profileUrl}" class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate block" title="${name}">
                                    ${name}
                                </a>
                                <div class="text-xs text-slate-500 dark:text-slate-400 truncate" title="${email}">${email}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-2.5 align-middle">
                        <span class="ea-attempts-pill">${escapeHtml(attemptsCount)}</span>
                        ${latest.attempt_no != null ? `<div class="text-[11px] text-slate-400 mt-0.5">#${escapeHtml(latest.attempt_no)} latest</div>` : ''}
                    </td>
                    ${attemptCells(latest, row.name)}
                </tr>
                <tr class="ea-expand-row" id="ea-child-${escapeHtml(row.id)}" hidden>
                    <td colspan="19" class="ea-expand-cell">
                        <div class="ea-expand-panel">
                            <div class="ea-expand-panel__head">
                                <p class="ea-expand-panel__title">All attempts · ${name}</p>
                                <p class="ea-expand-panel__meta">${escapeHtml(attempts.length)} attempt${attempts.length === 1 ? '' : 's'} · ordered by start time</p>
                            </div>
                            <div class="ea-expand-scroll">
                                <table class="ea-nested-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Status</th>
                                            <th>Total Qs</th>
                                            <th>Attempted</th>
                                            <th>Right</th>
                                            <th>Wrong</th>
                                            <th>Unanswered</th>
                                            <th>Total Marks</th>
                                            <th>Neg. Marks</th>
                                            <th>Scored</th>
                                            <th>%</th>
                                            <th>Result</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Duration</th>
                                            <th>Exam Duration</th>
                                            <th>Verification</th>
                                        </tr>
                                    </thead>
                                    <tbody>${childRows || `<tr><td colspan="17" class="px-4 py-6 text-center text-sm text-slate-500">No attempts</td></tr>`}</tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        },
    });

    // Expand / collapse
    document.getElementById('attempts-table-body')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-ea-expand]');
        if (!btn) return;

        const userId = btn.getAttribute('data-ea-expand');
        const child = document.getElementById(`ea-child-${userId}`);
        if (!child) return;

        const open = child.hasAttribute('hidden');
        if (open) {
            child.removeAttribute('hidden');
            btn.setAttribute('aria-expanded', 'true');
            btn.classList.add('is-open');
            btn.closest('tr')?.classList.add('is-expanded');
        } else {
            child.setAttribute('hidden', '');
            btn.setAttribute('aria-expanded', 'false');
            btn.classList.remove('is-open');
            btn.closest('tr')?.classList.remove('is-expanded');
        }
    });

    // Verification preview
    const previewModal = document.getElementById('ea-verify-preview-modal');
    const previewImg = document.getElementById('ea-preview-image');
    const previewTitle = document.getElementById('ea-preview-title');

    const closePreview = () => {
        if (!previewModal) return;
        previewModal.hidden = true;
        document.body.classList.remove('cand-modal-open');
        if (previewImg) previewImg.src = '';
    };

    const openPreview = (url, label) => {
        if (!previewModal || !previewImg) return;
        previewImg.src = url;
        previewImg.alt = label || 'Verification';
        if (previewTitle) previewTitle.textContent = label || 'Preview';
        previewModal.hidden = false;
        document.body.classList.add('cand-modal-open');
    };

    document.getElementById('attempts-table-body')?.addEventListener('click', (e) => {
        const thumb = e.target.closest('.js-ea-preview');
        if (!thumb) return;
        e.preventDefault();
        openPreview(thumb.getAttribute('data-preview-url'), thumb.getAttribute('data-preview-label'));
    });

    previewModal?.querySelectorAll('[data-ea-preview-close]').forEach((el) => {
        el.addEventListener('click', closePreview);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && previewModal && !previewModal.hidden) closePreview();
    });

    // Refresh
    document.getElementById('btn-refresh-attempts')?.addEventListener('click', () => {
        attemptsTable.fetch();
    });

    window.examAttemptsTable = attemptsTable;
});

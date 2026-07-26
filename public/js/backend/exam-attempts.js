/**
 * Exam Attempts modal — list + details pages.
 * Depends on: window.examAttemptsConfig
 */
(function () {
    'use strict';

    const cfg = window.examAttemptsConfig || {};
    if (!cfg.baseUrlTemplate) {
        return;
    }

    const els = {
        attemptsModal: document.getElementById('exam-attempts-modal'),
        historyModal: document.getElementById('exam-attempt-history-modal'),
        verifyModal: document.getElementById('exam-attempt-verification-modal'),
        title: document.getElementById('ea-attempts-title'),
        subtitle: document.getElementById('ea-attempts-subtitle'),
        search: document.getElementById('ea-search'),
        status: document.getElementById('ea-filter-status'),
        result: document.getElementById('ea-filter-result'),
        verified: document.getElementById('ea-filter-verified'),
        sort: document.getElementById('ea-sort'),
        perPage: document.getElementById('ea-per-page'),
        skeleton: document.getElementById('ea-attempts-skeleton'),
        empty: document.getElementById('ea-attempts-empty'),
        list: document.getElementById('ea-attempts-list'),
        tableWrap: document.getElementById('ea-attempts-table-wrap'),
        meta: document.getElementById('ea-attempts-meta'),
        pagination: document.getElementById('ea-attempts-pagination'),
        historyTitle: document.getElementById('ea-history-title'),
        historySubtitle: document.getElementById('ea-history-subtitle'),
        historySkeleton: document.getElementById('ea-history-skeleton'),
        historyEmpty: document.getElementById('ea-history-empty'),
        historyList: document.getElementById('ea-history-list'),
        verifyTitle: document.getElementById('ea-verify-title'),
        verifySubtitle: document.getElementById('ea-verify-subtitle'),
        verifySkeleton: document.getElementById('ea-verify-skeleton'),
        verifyFlags: document.getElementById('ea-verify-flags'),
        verifyEmpty: document.getElementById('ea-verify-empty'),
        verifyGrid: document.getElementById('ea-verify-grid'),
    };

    if (!els.attemptsModal) {
        return;
    }

    const state = {
        examId: null,
        examTitle: '',
        page: 1,
        perPage: 15,
        search: '',
        sort: 'last_attempt_at',
        direction: 'desc',
        filters: {},
        requestSeq: 0,
        debounceTimer: null,
        openMenus: new Set(),
        activeActionsMenu: null,
        activeActionsBtn: null,
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function urlFor(examId, suffix = '') {
        return String(cfg.baseUrlTemplate).replace('__EXAM__', String(examId)) + suffix;
    }

    function badgeClass(kind) {
        const map = {
            success: 'ea-badge--success',
            danger: 'ea-badge--danger',
            warning: 'ea-badge--warning',
            info: 'ea-badge--info',
            muted: 'ea-badge--muted',
        };
        return map[kind] || map.muted;
    }

    function setModalOpen(modal, open) {
        if (!modal) return;
        modal.hidden = !open;
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('ea-modal-open', open || !els.attemptsModal.hidden);
        if (!open && els.attemptsModal.hidden && els.historyModal?.hidden && els.verifyModal?.hidden) {
            document.body.classList.remove('ea-modal-open');
        } else if (open || !els.attemptsModal.hidden) {
            document.body.classList.add('ea-modal-open');
        }
    }

    function renderSkeleton(target, count = 4) {
        if (!target) return;
        target.innerHTML = Array.from({ length: count }, () => '<div class="ea-skel" aria-hidden="true"></div>').join('');
        target.hidden = false;
        target.setAttribute('aria-hidden', 'false');
    }

    function hideSkeleton(target) {
        if (!target) return;
        target.hidden = true;
        target.setAttribute('aria-hidden', 'true');
        target.innerHTML = '';
    }

    function avatarHtml(row) {
        if (row.avatar_url) {
            return `<div class="ea-avatar"><img src="${escapeHtml(row.avatar_url)}" alt=""></div>`;
        }
        const color = escapeHtml(row.avatar_color || '#4f46e5');
        const initials = escapeHtml(row.initials || 'U');
        return `<div class="ea-avatar" style="background:${color}">${initials}</div>`;
    }

    function candidateCard(row) {
        const latest = row.latest_attempt || {};
        const statusBadge = latest.status_label
            ? `<span class="ea-badge ${badgeClass(latest.status_badge)}">${escapeHtml(latest.status_label)}</span>`
            : '<span class="ea-muted">—</span>';
        const resultBadge = latest.passed === true
            ? '<span class="ea-badge ea-badge--success">Pass</span>'
            : (latest.passed === false ? '<span class="ea-badge ea-badge--danger">Fail</span>' : '');

        const score = latest.score != null ? escapeHtml(latest.score) : '—';
        const pct = latest.percentage != null ? `${escapeHtml(latest.percentage)}%` : null;
        const phone = row.phone ? escapeHtml(row.phone) : null;

        const verifyCells = [
            { ok: !!row.email_verified, label: row.email_verified ? 'Email verified' : 'Email unverified', short: 'E' },
            { ok: !!row.mobile_provided, label: row.mobile_provided ? 'Mobile on file' : 'No mobile', short: 'M' },
            { ok: !!row.identity_verified, label: row.identity_verified ? 'ID on file' : 'No ID docs', short: 'ID' },
        ].map((item) => `
            <span class="ea-verify-dot ${item.ok ? 'is-ok' : 'is-off'}" title="${escapeHtml(item.label)}">${escapeHtml(item.short)}</span>
        `).join('');

        return `
            <tr class="ea-row" data-user-id="${escapeHtml(row.id)}">
                <td class="ea-td ea-td--candidate">
                    <div class="ea-person">
                        ${avatarHtml(row)}
                        <div class="min-w-0">
                            <p class="ea-person__name">${escapeHtml(row.name)}</p>
                            <p class="ea-person__meta">
                                ${escapeHtml(row.email || '—')}
                                ${phone ? `<span class="ea-dot">·</span>${phone}` : ''}
                                <span class="ea-dot">·</span>#${escapeHtml(row.candidate_id)}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="ea-td ea-td--num">
                    <span class="ea-cell-strong">${escapeHtml(row.attempts_count)}</span>
                    <span class="ea-cell-sub">#${escapeHtml(latest.attempt_no ?? '—')} latest</span>
                </td>
                <td class="ea-td ea-td--score">
                    <span class="ea-cell-strong">${score}</span>
                    <span class="ea-cell-sub">${pct ? escapeHtml(pct) : '—'} · ${escapeHtml(latest.time_taken || '—')}</span>
                </td>
                <td class="ea-td ea-td--status">
                    <div class="ea-badges ea-badges--inline">${statusBadge}${resultBadge}</div>
                </td>
                <td class="ea-td ea-td--date">
                    <span class="ea-cell-strong">${escapeHtml(latest.last_attempt_at_label || '—')}</span>
                    ${latest.started_at_label && latest.ended_at_label
                        ? `<span class="ea-cell-sub">${escapeHtml(latest.started_at_label)} → ${escapeHtml(latest.ended_at_label)}</span>`
                        : ''}
                </td>
                <td class="ea-td ea-td--verify">
                    <div class="ea-verify-dots" aria-label="Verification status">${verifyCells}</div>
                </td>
                <td class="ea-td ea-td--actions">
                    <div class="ea-actions">
                        <button type="button" class="ea-actions__btn js-ea-actions-toggle" aria-expanded="false" aria-haspopup="true">
                            Actions
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="ea-actions__menu" hidden>
                            <a class="ea-actions__item" href="${escapeHtml(row.profile_url)}" target="_blank" rel="noopener">View Candidate Profile</a>
                            <button type="button" class="ea-actions__item js-ea-history" data-user-id="${escapeHtml(row.id)}" data-user-name="${escapeHtml(row.name)}">View Attempt History</button>
                            <button type="button" class="ea-actions__item js-ea-latest" data-user-id="${escapeHtml(row.id)}" data-user-name="${escapeHtml(row.name)}">View Latest Attempt</button>
                            <button type="button" class="ea-actions__item js-ea-result" data-user-id="${escapeHtml(row.id)}" data-user-name="${escapeHtml(row.name)}" ${latest.id ? '' : 'disabled'}>View Result</button>
                            <button type="button" class="ea-actions__item js-ea-violations" data-user-id="${escapeHtml(row.id)}" data-user-name="${escapeHtml(row.name)}" ${(row.violation_count || 0) > 0 ? '' : 'disabled'}>View Rule Violations${row.violation_count ? ` (${escapeHtml(row.violation_count)})` : ''}</button>
                            <button type="button" class="ea-actions__item js-ea-verify" data-user-id="${escapeHtml(row.id)}" data-user-name="${escapeHtml(row.name)}" ${row.has_verification_docs ? '' : 'disabled'}>View Verification</button>
                            <button type="button" class="ea-actions__item" disabled title="Coming soon">Download Result</button>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderPagination(meta) {
        if (!els.pagination || !meta) return;
        const last = Number(meta.last_page || 1);
        const current = Number(meta.current_page || 1);
        if (last <= 1) {
            els.pagination.innerHTML = '';
            return;
        }

        const buttons = [];
        buttons.push(`<button type="button" class="ea-page-btn" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>Prev</button>`);
        const windowSize = 5;
        let start = Math.max(1, current - Math.floor(windowSize / 2));
        let end = Math.min(last, start + windowSize - 1);
        start = Math.max(1, end - windowSize + 1);
        for (let p = start; p <= end; p += 1) {
            buttons.push(`<button type="button" class="ea-page-btn ${p === current ? 'is-active' : ''}" data-page="${p}">${p}</button>`);
        }
        buttons.push(`<button type="button" class="ea-page-btn" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}>Next</button>`);
        els.pagination.innerHTML = buttons.join('');
    }

    async function fetchAttempters() {
        if (!state.examId) return;
        const seq = ++state.requestSeq;

        els.list.innerHTML = '';
        els.empty.hidden = true;
        if (els.tableWrap) els.tableWrap.hidden = true;
        renderSkeleton(els.skeleton, 8);
        els.subtitle.textContent = 'Loading candidates…';

        const params = new URLSearchParams();
        params.set('page', String(state.page));
        params.set('per_page', String(state.perPage));
        params.set('search', state.search);
        params.set('sort', state.sort);
        params.set('direction', state.direction);
        Object.entries(state.filters).forEach(([key, value]) => {
            if (value !== '' && value != null) {
                params.set(`filters[${key}]`, String(value));
            }
        });

        try {
            const res = await fetch(`${urlFor(state.examId)}?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (seq !== state.requestSeq) return;
            if (!res.ok) throw new Error('Failed to load attempters');
            const json = await res.json();
            if (seq !== state.requestSeq) return;
            const rows = Array.isArray(json.data) ? json.data : [];
            const meta = json.meta || {};

            hideSkeleton(els.skeleton);
            if (json.exam?.title) {
                state.examTitle = json.exam.title;
                els.title.textContent = json.exam.title;
            }

            if (rows.length === 0) {
                if (els.tableWrap) els.tableWrap.hidden = true;
                els.empty.hidden = false;
                const titleEl = els.empty.querySelector('.ea-empty__title');
                const textEl = els.empty.querySelector('.ea-empty__text');
                if (titleEl) titleEl.textContent = 'No candidates yet';
                if (textEl) textEl.textContent = 'No one has attempted this exam, or no results match your filters.';
                els.meta.textContent = '0 candidates';
                els.subtitle.textContent = 'No matching candidates';
            } else {
                els.empty.hidden = true;
                if (els.tableWrap) els.tableWrap.hidden = false;
                els.list.innerHTML = rows.map(candidateCard).join('');
                const from = meta.from ?? 1;
                const to = meta.to ?? rows.length;
                const total = meta.total ?? rows.length;
                els.meta.textContent = `Showing ${from}–${to} of ${total}`;
                els.subtitle.textContent = `${total} candidate${total === 1 ? '' : 's'} attempted this exam`;
            }
            renderPagination(meta);
        } catch (err) {
            if (seq !== state.requestSeq) return;
            hideSkeleton(els.skeleton);
            els.list.innerHTML = '';
            if (els.tableWrap) els.tableWrap.hidden = true;
            els.empty.hidden = false;
            els.empty.querySelector('.ea-empty__title').textContent = 'Unable to load attempts';
            els.empty.querySelector('.ea-empty__text').textContent = err?.message || 'Please try again.';
            els.meta.textContent = '';
            els.pagination.innerHTML = '';
        }
    }

    function groupViolations(violations) {
        const map = new Map();
        (violations || []).forEach((v) => {
            const title = String(v.title || v.type || 'Violation').trim() || 'Violation';
            const key = title.toLowerCase();
            const existing = map.get(key);
            if (existing) {
                existing.count += 1;
            } else {
                map.set(key, { title, count: 1 });
            }
        });
        return Array.from(map.values());
    }

    function attemptCardHtml(attempt, { focusViolations = false } = {}) {
        const violations = Array.isArray(attempt.violations) ? attempt.violations : [];
        const grouped = groupViolations(violations);
        const totalViolations = violations.length;
        const violationsHtml = grouped.length
            ? `<div class="ea-violations">
                <div class="ea-violations__head">
                    <span class="ea-violations__title">Rule violations</span>
                    <span class="ea-badge ea-badge--danger">${escapeHtml(totalViolations)}</span>
                </div>
                <div class="ea-violations__chips">
                    ${grouped.map((v) => `
                        <span class="ea-violation-chip" title="${escapeHtml(v.title)}">
                            ${escapeHtml(v.title)}
                            ${v.count > 1 ? `<span class="ea-violation-chip__count">×${escapeHtml(v.count)}</span>` : ''}
                        </span>
                    `).join('')}
                </div>
               </div>`
            : (focusViolations ? '<p class="ea-attempt-card__reason">No rule violations recorded for this attempt.</p>' : '');

        return `
            <article class="ea-attempt-card" data-attempt-id="${escapeHtml(attempt.id)}">
                <div class="ea-attempt-card__top">
                    <div class="ea-attempt-card__heading">
                        <span class="ea-attempt-card__no">Attempt #${escapeHtml(attempt.attempt_no)}</span>
                        ${attempt.submission_reason_label && attempt.submission_reason_label !== '—'
                            ? `<span class="ea-attempt-card__reason">${escapeHtml(attempt.submission_reason_label)}</span>`
                            : ''}
                    </div>
                    <div class="ea-badges">
                        <span class="ea-badge ${badgeClass(attempt.status_badge)}">${escapeHtml(attempt.status_label)}</span>
                        ${attempt.passed === true ? '<span class="ea-badge ea-badge--success">Pass</span>' : ''}
                        ${attempt.passed === false ? '<span class="ea-badge ea-badge--danger">Fail</span>' : ''}
                        ${attempt.submission_type && attempt.submission_type !== '—'
                            ? `<span class="ea-badge ea-badge--muted">${escapeHtml(attempt.submission_type)}</span>`
                            : ''}
                    </div>
                </div>
                <div class="ea-stats ea-stats--compact">
                    <div class="ea-stat"><span class="ea-stat__label">Score</span><span class="ea-stat__value">${attempt.score != null ? escapeHtml(attempt.score) : '—'}</span></div>
                    <div class="ea-stat"><span class="ea-stat__label">%</span><span class="ea-stat__value">${attempt.percentage != null ? `${escapeHtml(attempt.percentage)}%` : '—'}</span></div>
                    <div class="ea-stat"><span class="ea-stat__label">Started</span><span class="ea-stat__value">${escapeHtml(attempt.started_at_label || '—')}</span></div>
                    <div class="ea-stat"><span class="ea-stat__label">Ended</span><span class="ea-stat__value">${escapeHtml(attempt.ended_at_label || '—')}</span></div>
                    <div class="ea-stat"><span class="ea-stat__label">Duration</span><span class="ea-stat__value">${escapeHtml(attempt.time_taken || '—')}</span></div>
                </div>
                ${violationsHtml}
            </article>
        `;
    }

    async function openHistory(userId, userName, { focusLatest = false, focusViolations = false, focusResult = false } = {}) {
        setModalOpen(els.historyModal, true);
        els.historyTitle.textContent = userName || 'Candidate';
        els.historySubtitle.textContent = focusViolations
            ? 'Rule violations across attempts'
            : (focusResult ? 'Latest result' : 'All attempts for this exam');
        els.historyList.innerHTML = '';
        els.historyEmpty.hidden = true;
        renderSkeleton(els.historySkeleton, 3);

        try {
            const res = await fetch(urlFor(state.examId, `/${userId}/attempts`), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Failed to load attempt history');
            const json = await res.json();
            let rows = Array.isArray(json.data) ? json.data : [];
            hideSkeleton(els.historySkeleton);

            if (focusLatest || focusResult) {
                rows = rows.slice(0, 1);
            }
            if (focusViolations) {
                rows = rows.filter((r) => Array.isArray(r.violations) && r.violations.length > 0);
            }

            if (rows.length === 0) {
                els.historyEmpty.hidden = false;
                els.historyEmpty.querySelector('.ea-empty__title').textContent = focusViolations
                    ? 'No rule violations'
                    : 'No attempts found';
                return;
            }

            els.historyEmpty.hidden = true;
            els.historyList.innerHTML = rows.map((a) => attemptCardHtml(a, { focusViolations })).join('');
        } catch (err) {
            hideSkeleton(els.historySkeleton);
            els.historyEmpty.hidden = false;
            els.historyEmpty.querySelector('.ea-empty__title').textContent = err?.message || 'Unable to load history';
        }
    }

    async function openVerification(userId, userName) {
        setModalOpen(els.verifyModal, true);
        els.verifyTitle.textContent = userName || 'Candidate';
        els.verifySubtitle.textContent = 'Verification status & documents';
        els.verifyGrid.innerHTML = '';
        els.verifyFlags.innerHTML = '';
        els.verifyEmpty.hidden = true;
        renderSkeleton(els.verifySkeleton, 2);

        try {
            const res = await fetch(urlFor(state.examId, `/${userId}/verification`), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Failed to load verification');
            const json = await res.json();
            const user = json.user || {};
            const docs = Array.isArray(json.documents) ? json.documents : [];
            hideSkeleton(els.verifySkeleton);

            els.verifyFlags.innerHTML = [
                user.email_verified
                    ? '<span class="ea-badge ea-badge--success">Email verified</span>'
                    : '<span class="ea-badge ea-badge--muted">Email unverified</span>',
                user.mobile_provided
                    ? `<span class="ea-badge ea-badge--info">Mobile: ${escapeHtml(user.phone || 'on file')}</span>`
                    : '<span class="ea-badge ea-badge--muted">Mobile not provided</span>',
                user.identity_on_file
                    ? '<span class="ea-badge ea-badge--success">Identity docs on file</span>'
                    : '<span class="ea-badge ea-badge--muted">No identity docs</span>',
            ].join('');

            if (docs.length === 0) {
                els.verifyEmpty.hidden = false;
                return;
            }

            els.verifyGrid.innerHTML = docs.map((doc) => `
                <a class="ea-verify-card" href="${escapeHtml(doc.url)}" target="_blank" rel="noopener" title="${escapeHtml(doc.label)}">
                    <div class="ea-verify-card__media">
                        <img src="${escapeHtml(doc.url)}" alt="${escapeHtml(doc.label)}" loading="lazy">
                    </div>
                    <div class="ea-verify-card__body">
                        <p class="ea-verify-card__label">${escapeHtml(doc.label)}</p>
                        <p class="ea-verify-card__meta">${escapeHtml(doc.status || '')}${doc.meta?.captured_at ? ` · ${escapeHtml(doc.meta.captured_at)}` : ''}</p>
                    </div>
                </a>
            `).join('');
        } catch (err) {
            hideSkeleton(els.verifySkeleton);
            els.verifyEmpty.hidden = false;
            els.verifyEmpty.querySelector('.ea-empty__title').textContent = err?.message || 'Unable to load verification';
        }
    }

    function closeMenus() {
        document.querySelectorAll('.ea-actions__menu').forEach((menu) => {
            if (menu.hidden && !menu.classList.contains('is-open')) return;
            menu.hidden = true;
            menu.classList.remove('is-open');
            menu.style.top = '';
            menu.style.bottom = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.position = '';
            menu.style.zIndex = '';
            menu.style.minWidth = '';
            const btn = menu.parentElement?.querySelector('.js-ea-actions-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
        state.activeActionsMenu = null;
        state.activeActionsBtn = null;
    }

    function positionActionsMenu(menu, toggle) {
        if (!menu || !toggle) return;
        const rect = toggle.getBoundingClientRect();
        const menuWidth = Math.max(220, Math.ceil(rect.width));
        menu.style.position = 'fixed';
        menu.style.zIndex = '120';
        menu.style.minWidth = `${menuWidth}px`;
        menu.style.right = 'auto';

        // Measure after making visible
        const menuHeight = menu.offsetHeight || 260;
        const spaceBelow = window.innerHeight - rect.bottom - 10;
        const openUp = spaceBelow < menuHeight && rect.top > spaceBelow;
        const left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8));

        menu.style.left = `${left}px`;
        if (openUp) {
            menu.style.top = 'auto';
            menu.style.bottom = `${Math.max(8, window.innerHeight - rect.top + 6)}px`;
        } else {
            menu.style.bottom = 'auto';
            menu.style.top = `${rect.bottom + 6}px`;
        }
    }

    function openActionsMenu(toggle) {
        const menu = toggle.parentElement?.querySelector('.ea-actions__menu');
        if (!menu) return;
        closeMenus();
        menu.hidden = false;
        menu.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        positionActionsMenu(menu, toggle);
        state.activeActionsMenu = menu;
        state.activeActionsBtn = toggle;
    }

    function openAttemptsModal(examId, examTitle) {
        state.examId = Number(examId);
        state.examTitle = examTitle || `Exam #${examId}`;
        state.page = 1;
        state.search = '';
        state.filters = {};
        if (els.search) els.search.value = '';
        if (els.status) els.status.value = '';
        if (els.result) els.result.value = '';
        if (els.verified) els.verified.value = '';
        if (els.sort) els.sort.value = 'last_attempt_at:desc';
        if (els.perPage) els.perPage.value = String(state.perPage);
        state.sort = 'last_attempt_at';
        state.direction = 'desc';

        els.title.textContent = state.examTitle;
        setModalOpen(els.attemptsModal, true);
        fetchAttempters();
    }

    function syncFiltersFromUi() {
        state.filters = {
            status: els.status?.value || '',
            result: els.result?.value || '',
            email_verified: els.verified?.value || '',
        };
        const sortVal = els.sort?.value || 'last_attempt_at:desc';
        const [sort, direction] = sortVal.split(':');
        state.sort = sort || 'last_attempt_at';
        state.direction = direction === 'asc' ? 'asc' : 'desc';
        state.perPage = Number(els.perPage?.value || 15);
        state.page = 1;
        fetchAttempters();
    }

    function resetFilters() {
        if (els.search) els.search.value = '';
        if (els.status) els.status.value = '';
        if (els.result) els.result.value = '';
        if (els.verified) els.verified.value = '';
        if (els.sort) els.sort.value = 'last_attempt_at:desc';
        if (els.perPage) els.perPage.value = '15';
        state.search = '';
        state.filters = {};
        state.sort = 'last_attempt_at';
        state.direction = 'desc';
        state.perPage = 15;
        state.page = 1;
        fetchAttempters();
    }

    // Open triggers (list + show)
    document.addEventListener('click', (e) => {
        const openBtn = e.target.closest('[data-exam-attempts]');
        if (openBtn) {
            e.preventDefault();
            openAttemptsModal(openBtn.getAttribute('data-exam-attempts'), openBtn.getAttribute('data-exam-title') || '');
            return;
        }

        const closeTarget = e.target.closest('[data-ea-close]');
        if (closeTarget) {
            const which = closeTarget.getAttribute('data-ea-close');
            if (which === 'attempts') setModalOpen(els.attemptsModal, false);
            if (which === 'history') setModalOpen(els.historyModal, false);
            if (which === 'verification') setModalOpen(els.verifyModal, false);
            closeMenus();
            return;
        }

        const toggle = e.target.closest('.js-ea-actions-toggle');
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            const menu = toggle.parentElement?.querySelector('.ea-actions__menu');
            const isOpen = menu && !menu.hidden;
            if (isOpen) {
                closeMenus();
            } else {
                openActionsMenu(toggle);
            }
            return;
        }

        const historyBtn = e.target.closest('.js-ea-history');
        if (historyBtn) {
            closeMenus();
            openHistory(historyBtn.dataset.userId, historyBtn.dataset.userName);
            return;
        }
        const latestBtn = e.target.closest('.js-ea-latest');
        if (latestBtn) {
            closeMenus();
            openHistory(latestBtn.dataset.userId, latestBtn.dataset.userName, { focusLatest: true });
            return;
        }
        const resultBtn = e.target.closest('.js-ea-result');
        if (resultBtn && !resultBtn.disabled) {
            closeMenus();
            openHistory(resultBtn.dataset.userId, resultBtn.dataset.userName, { focusResult: true });
            return;
        }
        const violBtn = e.target.closest('.js-ea-violations');
        if (violBtn && !violBtn.disabled) {
            closeMenus();
            openHistory(violBtn.dataset.userId, violBtn.dataset.userName, { focusViolations: true });
            return;
        }
        const verifyBtn = e.target.closest('.js-ea-verify');
        if (verifyBtn && !verifyBtn.disabled) {
            closeMenus();
            openVerification(verifyBtn.dataset.userId, verifyBtn.dataset.userName);
            return;
        }

        const pageBtn = e.target.closest('#ea-attempts-pagination .ea-page-btn');
        if (pageBtn && !pageBtn.disabled) {
            state.page = Number(pageBtn.getAttribute('data-page') || 1);
            fetchAttempters();
            return;
        }

        if (!e.target.closest('.ea-actions') && !e.target.closest('.ea-actions__menu')) {
            closeMenus();
        }
    });

    // Keep fixed menu aligned while scrolling the modal body
    els.attemptsModal?.querySelector('.ea-modal__body')?.addEventListener('scroll', () => {
        if (state.activeActionsMenu && state.activeActionsBtn) {
            positionActionsMenu(state.activeActionsMenu, state.activeActionsBtn);
        }
    }, { passive: true });

    window.addEventListener('resize', () => {
        if (state.activeActionsMenu && state.activeActionsBtn) {
            positionActionsMenu(state.activeActionsMenu, state.activeActionsBtn);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!els.verifyModal?.hidden) {
            setModalOpen(els.verifyModal, false);
            return;
        }
        if (!els.historyModal?.hidden) {
            setModalOpen(els.historyModal, false);
            return;
        }
        if (!els.attemptsModal.hidden) {
            setModalOpen(els.attemptsModal, false);
        }
    });

    els.search?.addEventListener('input', () => {
        clearTimeout(state.debounceTimer);
        state.debounceTimer = setTimeout(() => {
            state.search = els.search.value.trim();
            state.page = 1;
            fetchAttempters();
        }, 350);
    });

    [els.status, els.result, els.verified, els.sort, els.perPage].forEach((el) => {
        el?.addEventListener('change', syncFiltersFromUi);
    });

    document.getElementById('ea-filters-reset')?.addEventListener('click', resetFilters);

    window.EmsExamAttempts = { open: openAttemptsModal };
})();

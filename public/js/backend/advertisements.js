/**
 * Advertisement module — placement preview, ads CRUD, custom code.
 */
(function () {
    'use strict';

    const cfg = window.adsModuleConfig || null;
    if (!cfg || !cfg.routes) return;

    const state = {
        pageKey: cfg.pageKey || 'home',
        placements: Array.isArray(cfg.placements) ? cfg.placements.slice() : [],
        ads: Array.isArray(cfg.ads) ? cfg.ads.slice() : [],
        googleAds: Array.isArray(cfg.googleAds) ? cfg.googleAds.slice() : [],
        pendingPosition: null,
        pendingSource: null,
        activePlacementId: null,
        replaceMode: false,
        pickMode: 'custom',
    };

    const els = {
        pageSelect: document.querySelector('[data-ads-page-select]'),
        pageDescription: document.querySelector('[data-ads-page-description]'),
        preview: document.querySelector('[data-ads-preview]'),
        previewTitle: document.querySelector('[data-ads-preview-title]'),
        adsBody: document.querySelector('[data-ads-table-body]'),
        adsEmpty: document.querySelector('[data-ads-empty]'),
        googleBody: document.querySelector('[data-google-ads-table-body]'),
        googleEmpty: document.querySelector('[data-google-ads-empty]'),
        filterSearch: document.querySelector('[data-ads-filter-search]'),
        filterType: document.querySelector('[data-ads-filter-type]'),
        filterStatus: document.querySelector('[data-ads-filter-status]'),
        filterClear: document.querySelector('[data-ads-filter-clear]'),
        customCodeForm: document.getElementById('ads-custom-code-form'),
        customCodeSave: document.getElementById('ads-custom-code-save'),
        adForm: document.getElementById('ad-form'),
        googleForm: document.getElementById('google-ad-form'),
        pickList: document.querySelector('[data-ads-pick-list]'),
        pickEmpty: document.querySelector('[data-ads-pick-empty]'),
        pickSearch: document.querySelector('[data-ads-pick-search]'),
        pickSubtitle: document.querySelector('[data-ads-pick-subtitle]'),
        sourceSubtitle: document.querySelector('[data-ads-source-subtitle]'),
        actionsSubtitle: document.querySelector('[data-ads-actions-subtitle]'),
        bannerNote: document.querySelector('[data-ads-banner-note]'),
        bannerSize: document.querySelector('[data-ads-banner-size]'),
    };

    const modals = {
        source: document.getElementById('ads-source-modal'),
        pick: document.getElementById('ads-pick-modal'),
        actions: document.getElementById('ads-placement-actions-modal'),
        form: document.getElementById('ads-form-modal'),
        google: document.getElementById('ads-google-form-modal'),
        help: document.getElementById('ads-help-modal'),
    };

    const toast = (message, type = 'success') => {
        if (window.EmsToast?.show) {
            window.EmsToast.show({ type, message });
            return;
        }
        if (window.Swal) {
            window.Swal.fire({ toast: true, position: 'top-end', icon: type === 'success' ? 'success' : 'error', title: message, showConfirmButton: false, timer: 2600 });
            return;
        }
        // eslint-disable-next-line no-alert
        alert(message);
    };

    const csrfHeaders = () => ({
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': cfg.csrf,
        'X-Requested-With': 'XMLHttpRequest',
    });

    const openModal = (key) => {
        const modal = modals[key];
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('overflow-hidden');
    };

    const closeModal = (key) => {
        const modal = modals[key];
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        const anyOpen = Object.values(modals).some((m) => m && !m.classList.contains('hidden'));
        if (!anyOpen) document.documentElement.classList.remove('overflow-hidden');
    };

    const clearFormErrors = (form) => {
        if (!form) return;
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.hidden = true;
            el.textContent = '';
            el.classList.remove('is-visible');
        });
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    };

    const showFormErrors = (form, errors) => {
        Object.entries(errors || {}).forEach(([field, messages]) => {
            const msg = Array.isArray(messages) ? messages[0] : String(messages);
            const fieldEl = form.querySelector(`[name="${field}"]`);
            const errorEl = form.querySelector(`[data-error-for="${field}"]`);
            if (fieldEl) fieldEl.classList.add('is-invalid');
            if (errorEl) {
                errorEl.textContent = msg;
                errorEl.hidden = false;
                errorEl.classList.add('is-visible');
            }
        });
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, options);
        const data = await response.json().catch(() => ({}));
        return { response, data };
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const pageMeta = () => cfg.pages[state.pageKey] || cfg.pages.home;
    const positionMeta = (key) => cfg.positions[key] || { label: key, note: '', multi: false };
    const placementsFor = (positionKey) => state.placements.filter((p) => p.position_key === positionKey);

    const insertHtml = (positionKey, options = {}) => {
        const meta = positionMeta(positionKey);
        const items = placementsFor(positionKey);
        const filled = items.length > 0;
        const side = options.side === true;
        const itemsHtml = items.map((item) => `
            <button type="button" class="ads-slot-item ${item.is_enabled ? '' : 'is-disabled'}" data-ads-placement-id="${item.id}">
                <span>
                    <span class="ads-slot-item__name">${escapeHtml(item.name)}</span>
                    <span class="ads-slot-item__meta">${escapeHtml(item.preview_label || item.source_type)}${item.is_enabled ? '' : ' · Disabled'}</span>
                </span>
                <span class="ads-slot-item__meta">Manage</span>
            </button>
        `).join('');

        const body = `
            ${filled ? `<div class="ads-insert__box" aria-label="Placed advertisements"><div class="ads-insert__ads">${itemsHtml}</div></div>` : ''}
            <div class="ads-insert__rule">
                <span class="ads-insert__rule-line" aria-hidden="true"></span>
                <button type="button" class="ads-insert__plus" data-ads-add="${escapeHtml(positionKey)}" title="Add advertisement — ${escapeHtml(meta.label)}" aria-label="Add advertisement — ${escapeHtml(meta.label)}">+</button>
                <span class="ads-insert__rule-line" aria-hidden="true"></span>
            </div>
            <p class="ads-insert__hint">${escapeHtml(meta.label)}</p>
        `;

        if (side) {
            return `
                <div class="ads-insert ads-insert--side" data-ads-slot="${escapeHtml(positionKey)}">
                    ${body}
                </div>
            `;
        }

        return `
            <div class="ads-insert" data-ads-slot="${escapeHtml(positionKey)}">
                <div class="hp-skel__container">
                    ${body}
                </div>
            </div>
        `;
    };

    const pageSkeleton = (block) => {
        const label = escapeHtml(block.label || block.id);
        switch (block.skeleton) {
            case 'header':
                return `
                    <div class="hp-skel hp-skel--header" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container hp-skel__container--header">
                            <div class="hp-skel__logo"></div>
                            <div class="hp-skel__nav">
                                <span></span><span></span><span></span><span></span><span></span>
                            </div>
                            <div class="hp-skel__actions"><span></span><span></span></div>
                        </div>
                    </div>`;
            case 'hero':
                return `
                    <div class="hp-skel hp-skel--hero" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container hp-skel__container--hero">
                            <div class="hp-skel--hero__copy">
                                <span class="hp-skel__badge"></span>
                                <span class="hp-skel__h1"></span>
                                <span class="hp-skel__h1 hp-skel__h1--short"></span>
                                <span class="hp-skel__text"></span>
                                <span class="hp-skel__text hp-skel__text--short"></span>
                                <span class="hp-skel__btn"></span>
                            </div>
                            <div class="hp-skel--hero__art"></div>
                        </div>
                    </div>`;
            case 'page_hero':
                return `
                    <div class="hp-skel hp-skel--page-hero" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__crumbs"><span></span><span></span><span></span></div>
                            <span class="hp-skel__h1"></span>
                            <span class="hp-skel__text"></span>
                            <span class="hp-skel__text hp-skel__text--short"></span>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'article_title':
                return `
                    <div class="hp-skel hp-skel--article-title" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__crumbs"><span></span><span></span></div>
                            <span class="hp-skel__badge"></span>
                            <span class="hp-skel__h1"></span>
                            <span class="hp-skel__h1 hp-skel__h1--short"></span>
                            <span class="hp-skel__text"></span>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'heading_h2':
                return `
                    <div class="hp-skel hp-skel--heading-h2" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <span class="hp-skel__h2 hp-skel__h2--wide"></span>
                            <span class="hp-skel__text hp-skel__text--short"></span>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'banner':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__banner"></div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'prose':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__prose">
                                <span></span><span></span><span></span><span></span>
                                <span class="hp-skel__prose-short"></span>
                                <span></span><span></span><span class="hp-skel__prose-short"></span>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'filters':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__filters">
                                <span></span><span></span><span></span><span></span>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'load_more':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container" style="display:flex;justify-content:center;">
                            <span class="hp-skel__btn"></span>
                        </div>
                    </div>`;
            case 'section':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__lines"><span></span><span></span><span></span></div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'author_hero':
                return `
                    <div class="hp-skel hp-skel--author-hero" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container hp-skel__container--author">
                            <div class="hp-skel__avatar"></div>
                            <div class="hp-skel--hero__copy">
                                <span class="hp-skel__h1"></span>
                                <span class="hp-skel__text"></span>
                                <span class="hp-skel__text hp-skel__text--short"></span>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'exam_topbar':
                return `
                    <div class="hp-skel hp-skel--exam-topbar" data-section="${escapeHtml(block.id)}">
                        <span class="hp-skel__badge"></span>
                        <span class="hp-skel__h2"></span>
                        <span class="hp-skel__progress"></span>
                    </div>`;
            case 'exam_question':
                return `
                    <div class="hp-skel hp-skel--exam-question" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__heading"><span class="hp-skel__h2"></span></div>
                        <div class="hp-skel__prose"><span></span><span></span><span></span></div>
                        <div class="hp-skel__faq">
                            <div class="hp-skel__faq-row"></div>
                            <div class="hp-skel__faq-row"></div>
                            <div class="hp-skel__faq-row"></div>
                            <div class="hp-skel__faq-row"></div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'error':
                return `
                    <div class="hp-skel hp-skel--error" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container" style="text-align:center;">
                            <div class="hp-skel__error-art"></div>
                            <span class="hp-skel__badge" style="margin:0.75rem auto;"></span>
                            <span class="hp-skel__h1" style="margin:0.5rem auto;"></span>
                            <span class="hp-skel__text" style="margin:0.35rem auto;"></span>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'stats':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__stats">
                                <div class="hp-skel__stat"></div>
                                <div class="hp-skel__stat"></div>
                                <div class="hp-skel__stat"></div>
                                <div class="hp-skel__stat"></div>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'cards4':
                return `
                    <div class="hp-skel hp-skel--section hp-skel--alt" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__grid hp-skel__grid--4">
                                <div class="hp-skel__card"></div><div class="hp-skel__card"></div>
                                <div class="hp-skel__card"></div><div class="hp-skel__card"></div>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'cards3':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__grid hp-skel__grid--3">
                                <div class="hp-skel__card"></div><div class="hp-skel__card"></div><div class="hp-skel__card"></div>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'chips':
                return `
                    <div class="hp-skel hp-skel--section hp-skel--alt" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__chips">
                                <span></span><span></span><span></span><span></span><span></span><span></span>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'quotes':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__grid hp-skel__grid--3">
                                <div class="hp-skel__quote"></div><div class="hp-skel__quote"></div><div class="hp-skel__quote"></div>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'faq':
                return `
                    <div class="hp-skel hp-skel--section hp-skel--alt" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__faq">
                                <div class="hp-skel__faq-row"></div>
                                <div class="hp-skel__faq-row"></div>
                                <div class="hp-skel__faq-row"></div>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'newsletter':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel--newsletter">
                                <div class="hp-skel--newsletter__copy">
                                    <span class="hp-skel__badge"></span>
                                    <span class="hp-skel__h2"></span>
                                    <span class="hp-skel__text"></span>
                                    <span class="hp-skel__input"></span>
                                </div>
                                <div class="hp-skel--newsletter__art"></div>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'cta':
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel--cta">
                                <span class="hp-skel__h2"></span>
                                <span class="hp-skel__text"></span>
                                <div class="hp-skel--cta__btns"><span></span><span></span></div>
                            </div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
            case 'footer':
                return `
                    <div class="hp-skel hp-skel--footer" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel--footer__grid">
                                <div class="hp-skel--footer__col hp-skel--footer__col--wide"></div>
                                <div class="hp-skel--footer__col"></div>
                                <div class="hp-skel--footer__col"></div>
                                <div class="hp-skel--footer__col"></div>
                            </div>
                            <div class="hp-skel--footer__bottom"></div>
                        </div>
                    </div>`;
            default:
                return `
                    <div class="hp-skel hp-skel--section" data-section="${escapeHtml(block.id)}">
                        <div class="hp-skel__container">
                            <div class="hp-skel__heading"><span class="hp-skel__h2"></span><span class="hp-skel__sub"></span></div>
                            <div class="hp-skel__lines"><span></span><span></span><span></span></div>
                        </div>
                        <span class="hp-skel__section-label">${label}</span>
                    </div>`;
        }
    };

    const sidebarContextHtml = (side = 'right') => {
        const prefix = side === 'left' ? 'left_' : 'right_';
        const blocks = (Array.isArray(pageMeta().sidebar_blocks) ? pageMeta().sidebar_blocks : [])
            .filter((block) => String(block.after || '').startsWith(prefix));
        if (blocks.length === 0) {
            return '';
        }

        return `
            <div class="hp-sidebar-context" aria-label="${escapeHtml(side)} sidebar preview">
                ${blocks.map((block) => `
                    <div class="hp-sidebar-section" data-sidebar-section="${escapeHtml(block.id)}">
                        <div class="hp-context-card">
                            <span class="hp-context-card__title">${escapeHtml(block.label)}</span>
                            <span class="hp-context-card__line"></span>
                            <span class="hp-context-card__line hp-context-card__line--short"></span>
                        </div>
                        ${insertHtml(block.after, { side: true })}
                    </div>
                `).join('')}
            </div>
        `;
    };

    const sideColumnHtml = (side) => {
        const html = sidebarContextHtml(side);
        if (!html) {
            return '';
        }

        return `
            <aside class="hp-side hp-side--${escapeHtml(side)}" aria-label="${escapeHtml(side)} sidebar">
                ${html}
            </aside>
        `;
    };

    const renderBlockFlow = (blocks) => {
        let html = '';
        blocks.forEach((block) => {
            if (block.before) {
                html += insertHtml(block.before);
            }
            html += pageSkeleton(block);
            if (block.after) {
                html += insertHtml(block.after);
            }
        });
        return html;
    };

    const renderStructuredPreview = (page) => {
        const blocks = Array.isArray(page.layout_blocks) ? page.layout_blocks : [];
        const sidebars = Array.isArray(page.sidebars) ? page.sidebars : [];
        const hasLeft = sidebars.includes('left');
        const hasRight = sidebars.includes('right');
        const isExamAttempt = page.layout === 'exam_attempt';

        const chromeBlocks = blocks.filter((block) => block.chrome);
        const middleBlocks = blocks.filter((block) => !block.chrome);
        const header = chromeBlocks.find((block) => block.skeleton === 'header' || block.id === 'header');
        const footer = chromeBlocks.find((block) => block.skeleton === 'footer' || block.id === 'footer');

        let headerHtml = '';
        if (header) {
            headerHtml = pageSkeleton(header);
        }

        let leadingHtml = '';
        if (header?.after) {
            leadingHtml += insertHtml(header.after);
        }

        // Live page heroes/titles span the centered container; columns start below.
        const leadingIds = ['hero', 'title', 'top', 'toolbar', 'error'];
        const firstMiddle = middleBlocks[0];
        const hasLeadingBlock = firstMiddle && leadingIds.includes(firstMiddle.id);
        const leadingBlocks = hasLeadingBlock ? [firstMiddle] : [];
        const contentBlocks = hasLeadingBlock ? middleBlocks.slice(1) : middleBlocks;
        const visibleLeadingBlocks = leadingBlocks.map((block) => (
            header?.after && block.before === 'above_title'
                ? { ...block, before: null }
                : block
        ));
        leadingHtml += renderBlockFlow(visibleLeadingBlocks);
        const mainHtml = renderBlockFlow(contentBlocks);

        let footerHtml = '';
        if (footer) {
            if (footer.before) {
                footerHtml += `<div class="hp-container-flow hp-pre-footer">${insertHtml(footer.before)}</div>`;
            }
            footerHtml += pageSkeleton(footer);
        }

        const leftHtml = hasLeft ? sideColumnHtml('left') : '';
        const rightHtml = hasRight ? sideColumnHtml('right') : '';
        const shellClass = [
            'hp-shell',
            !hasLeft && !hasRight ? 'hp-shell--single' : '',
            hasLeft && hasRight ? 'hp-shell--both' : '',
            hasLeft && !hasRight ? 'hp-shell--left-only' : '',
            !hasLeft && hasRight ? 'hp-shell--right-only' : '',
            isExamAttempt ? 'hp-shell--exam' : '',
        ].filter(Boolean).join(' ');

        const bodyHtml = `
            <div class="hp-container-flow">
                ${leadingHtml}
                <div class="${shellClass}">
                    ${leftHtml}
                    <div class="hp-main ${isExamAttempt ? 'hp-main--exam' : ''}">${mainHtml}</div>
                    ${rightHtml}
                </div>
            </div>
        `;

        // Exam attempt: no site header/footer; topbar spans the centered container.
        if (isExamAttempt) {
            const topbar = middleBlocks[0];
            const rest = middleBlocks.slice(1);
            let topHtml = '';
            if (topbar) {
                if (topbar.before) topHtml += insertHtml(topbar.before);
                topHtml += pageSkeleton(topbar);
                if (topbar.after) topHtml += insertHtml(topbar.after);
            }

            els.preview.innerHTML = `
                <div class="ads-preview__frame ads-preview__frame--page">
                    <div class="hp-page hp-page--exam">
                        <div class="hp-container-flow">
                            ${topHtml}
                            <div class="${shellClass}">
                                ${leftHtml}
                                <div class="hp-main hp-main--exam">
                                    ${renderBlockFlow(rest)}
                                </div>
                                ${rightHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        els.preview.innerHTML = `
            <div class="ads-preview__frame ads-preview__frame--page">
                <div class="hp-page">
                    ${headerHtml}
                    ${bodyHtml}
                    ${footerHtml}
                </div>
            </div>
        `;
    };

    const renderPreview = () => {
        if (!els.preview) return;
        const page = pageMeta();

        if (els.previewTitle) {
            els.previewTitle.textContent = `${page.label || 'Page'} preview`;
        }
        if (els.pageDescription) {
            els.pageDescription.textContent = page.description || '';
        }

        renderStructuredPreview(page);
    };
    const loadPlacements = async (pageKey) => {
        const url = `${cfg.routes.placementsIndex}?page=${encodeURIComponent(pageKey)}`;
        const { response, data } = await request(url, { headers: csrfHeaders() });
        if (!response.ok) {
            toast(data.message || 'Failed to load placements.', 'error');
            return;
        }
        state.pageKey = data.page_key;
        state.placements = data.placements || [];
        if (els.pageSelect) els.pageSelect.value = state.pageKey;
        renderPreview();
    };

    const statusPill = (status) => `<span class="ads-status-pill ads-status-pill--${status === 'active' ? 'active' : 'inactive'}">${escapeHtml(status)}</span>`;

    const syncAdsFilterUi = () => {
        const search = (els.filterSearch?.value || '').trim();
        const type = els.filterType?.value || '';
        const status = els.filterStatus?.value || '';
        const active = Boolean(search || type || status);

        els.filterType?.classList.toggle('is-filtered', Boolean(type));
        els.filterStatus?.classList.toggle('is-filtered', Boolean(status));
        els.filterSearch?.classList.toggle('is-filtered', Boolean(search));

        if (els.filterClear) {
            els.filterClear.hidden = !active;
        }
    };

    const renderAdsTable = () => {
        if (!els.adsBody) return;
        const search = (els.filterSearch?.value || '').trim().toLowerCase();
        const type = els.filterType?.value || '';
        const status = els.filterStatus?.value || '';
        const rows = state.ads.filter((ad) => {
            if (type && ad.type !== type) return false;
            if (status && ad.status !== status) return false;
            if (search && !(`${ad.name} ${ad.title || ''}`.toLowerCase().includes(search))) return false;
            return true;
        });

        syncAdsFilterUi();

        els.adsBody.innerHTML = rows.map((ad) => `
            <tr>
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">${escapeHtml(ad.name)}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">${escapeHtml(ad.type_label)}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">${escapeHtml(ad.preview_label || '—')}</td>
                <td class="px-4 py-3">${statusPill(ad.status)}</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <button type="button" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline" data-ads-edit="${ad.id}">Edit</button>
                    <button type="button" class="text-red-600 dark:text-red-400 font-medium hover:underline" data-ads-delete="${ad.id}">Delete</button>
                </td>
            </tr>
        `).join('');

        if (els.adsEmpty) {
            const noAdsAtAll = state.ads.length === 0;
            els.adsEmpty.textContent = noAdsAtAll
                ? 'No custom advertisements yet. Create a banner, iframe, or HTML ad to get started.'
                : 'No advertisements match your filters.';
            els.adsEmpty.classList.toggle('hidden', rows.length > 0);
            els.adsBody.parentElement?.classList.toggle('hidden', rows.length === 0);
        }
    };

    const renderGoogleTable = () => {
        if (!els.googleBody) return;
        const rows = state.googleAds;
        els.googleBody.innerHTML = rows.map((ad) => `
            <tr>
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">${escapeHtml(ad.name)}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">${escapeHtml([ad.ad_client, ad.ad_slot].filter(Boolean).join(' / ') || '—')}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">${escapeHtml(ad.ad_format || '—')}</td>
                <td class="px-4 py-3">${statusPill(ad.status)}</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <button type="button" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline" data-google-edit="${ad.id}">Edit</button>
                    <button type="button" class="text-red-600 dark:text-red-400 font-medium hover:underline" data-google-delete="${ad.id}">Delete</button>
                </td>
            </tr>
        `).join('');

        if (els.googleEmpty) {
            els.googleEmpty.classList.toggle('hidden', rows.length > 0);
            els.googleBody.parentElement?.classList.toggle('hidden', rows.length === 0);
        }
    };

    const syncTypePanels = () => {
        const type = els.adForm?.querySelector('input[name="type"]:checked')?.value || 'banner';
        els.adForm?.querySelectorAll('[data-ads-type-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel.getAttribute('data-ads-type-panel') !== type);
        });
    };

    const resetAdForm = () => {
        if (!els.adForm) return;
        clearFormErrors(els.adForm);
        els.adForm.reset();
        els.adForm.querySelector('#ad_id').value = '';
        const bannerRadio = els.adForm.querySelector('input[name="type"][value="banner"]');
        if (bannerRadio) bannerRadio.checked = true;
        const openTab = els.adForm.querySelector('#ad_open_in_new_tab');
        if (openTab) openTab.checked = true;
        const responsive = els.adForm.querySelector('#ad_is_responsive');
        if (responsive) responsive.checked = true;
        const imageInput = els.adForm.querySelector('#ad_image_id');
        if (imageInput) imageInput.value = '';
        const preview = els.adForm.querySelector('#ad_image_preview');
        if (preview) preview.innerHTML = '';
        document.getElementById('ads-form-title').textContent = 'Create advertisement';
        syncTypePanels();
        updateBannerNote();
    };

    const fillAdForm = (ad) => {
        resetAdForm();
        els.adForm.querySelector('#ad_id').value = ad.id;
        els.adForm.querySelector('#ad_name').value = ad.name || '';
        els.adForm.querySelector('#ad_title').value = ad.title || '';
        els.adForm.querySelector('#ad_status').value = ad.status || 'active';
        const typeRadio = els.adForm.querySelector(`input[name="type"][value="${ad.type}"]`);
        if (typeRadio) typeRadio.checked = true;
        els.adForm.querySelector('#ad_banner_size').value = ad.banner_size || '';
        els.adForm.querySelector('#ad_target_url').value = ad.target_url || '';
        els.adForm.querySelector('#ad_open_in_new_tab').checked = !!ad.open_in_new_tab;
        els.adForm.querySelector('#ad_iframe_url').value = ad.iframe_url || '';
        els.adForm.querySelector('#ad_width').value = ad.width || '';
        els.adForm.querySelector('#ad_height').value = ad.height || '';
        els.adForm.querySelector('#ad_is_responsive').checked = ad.is_responsive !== false;
        els.adForm.querySelector('#ad_html_code').value = ad.html_code || '';
        els.adForm.querySelector('#ad_css_code').value = ad.css_code || '';
        els.adForm.querySelector('#ad_js_code').value = ad.js_code || '';
        els.adForm.querySelector('#ad_notes').value = ad.notes || '';
        const imageInput = els.adForm.querySelector('#ad_image_id');
        if (imageInput) imageInput.value = ad.image_id || '';
        const preview = els.adForm.querySelector('#ad_image_preview');
        if (preview && ad.image_url) {
            preview.innerHTML = `<img src="${escapeHtml(ad.image_url)}" alt="" class="max-h-28 rounded-lg border border-slate-200 dark:border-slate-700">`;
        }
        document.getElementById('ads-form-title').textContent = 'Edit advertisement';
        syncTypePanels();
        updateBannerNote();
    };

    const resetGoogleForm = () => {
        if (!els.googleForm) return;
        clearFormErrors(els.googleForm);
        els.googleForm.reset();
        els.googleForm.querySelector('#google_ad_id').value = '';
        document.getElementById('ads-google-form-title').textContent = 'Google Ad configuration';
    };

    const fillGoogleForm = (ad) => {
        resetGoogleForm();
        els.googleForm.querySelector('#google_ad_id').value = ad.id;
        els.googleForm.querySelector('#google_ad_name').value = ad.name || '';
        els.googleForm.querySelector('#google_ad_client').value = ad.ad_client || '';
        els.googleForm.querySelector('#google_ad_slot').value = ad.ad_slot || '';
        els.googleForm.querySelector('#google_ad_format').value = ad.ad_format || '';
        els.googleForm.querySelector('#google_ad_code').value = ad.code || '';
        els.googleForm.querySelector('#google_ad_status').value = ad.status || 'active';
        els.googleForm.querySelector('#google_ad_notes').value = ad.notes || '';
        document.getElementById('ads-google-form-title').textContent = 'Edit Google Ad configuration';
    };

    const updateBannerNote = () => {
        if (!els.bannerNote || !els.bannerSize) return;
        const option = els.bannerSize.selectedOptions?.[0];
        els.bannerNote.textContent = option?.dataset?.note || 'Select a size to see placement recommendations.';

        const picker = els.adForm?.querySelector('[data-gallery-picker][data-name="image_id"]');
        const hint = els.adForm?.querySelector('[data-ads-image-size-hint]');
        const width = Number(option?.dataset?.width || 0);
        const height = Number(option?.dataset?.height || 0);
        const label = option?.dataset?.label || option?.textContent?.trim() || '';
        if (picker && width > 0 && height > 0) {
            picker.dataset.recommendWidth = String(width);
            picker.dataset.recommendHeight = String(height);
            picker.dataset.recommendLabel = `${width} × ${height} px`;
            const sizeHint = picker.querySelector('.gallery-picker-size-hint, .gallery-picker-dropzone__size');
            const text = `Recommended size: ${width} × ${height} px${label ? ` (${label})` : ''}.`;
            if (sizeHint) sizeHint.textContent = text;
            const modalRec = document.getElementById(picker.dataset.modalId)?.querySelector('[data-modal-recommend]');
            if (modalRec) {
                modalRec.innerHTML = `Recommended for this field: <strong>${width} × ${height} px</strong> — matching images are highlighted.`;
            }
            if (hint) hint.textContent = `${text} Matching gallery images are highlighted when you choose from gallery.`;
        }
    };

    const openSourceChooser = (positionKey) => {
        state.pendingPosition = positionKey;
        state.replaceMode = false;
        state.activePlacementId = null;
        const meta = positionMeta(positionKey);
        if (els.sourceSubtitle) {
            els.sourceSubtitle.textContent = `Place an ad in “${meta.label}”.`;
        }
        openModal('source');
    };

    const openPickList = (source) => {
        state.pendingSource = source;
        state.pickMode = source;
        if (els.pickSubtitle) {
            els.pickSubtitle.textContent = source === 'google'
                ? 'Select a Google Ad configuration.'
                : 'Select a custom advertisement.';
        }
        if (els.pickSearch) els.pickSearch.value = '';
        renderPickList();
        closeModal('source');
        openModal('pick');
    };

    const renderPickList = () => {
        if (!els.pickList) return;
        const q = (els.pickSearch?.value || '').trim().toLowerCase();
        const items = state.pickMode === 'google' ? state.googleAds : state.ads;
        const filtered = items.filter((item) => {
            if (item.status === 'inactive') return true; // still selectable, labeled
            return true;
        }).filter((item) => !q || String(item.name || '').toLowerCase().includes(q));

        els.pickList.innerHTML = filtered.map((item) => `
            <button type="button" class="ads-pick-item" data-ads-pick-id="${item.id}">
                <span>
                    <span class="ads-pick-item__name">${escapeHtml(item.name)}</span>
                    <span class="ads-pick-item__meta">${escapeHtml(item.preview_label || '')}${item.status === 'inactive' ? ' · Inactive' : ''}</span>
                </span>
                <span class="ads-pick-item__meta">Select</span>
            </button>
        `).join('');

        if (els.pickEmpty) els.pickEmpty.classList.toggle('hidden', filtered.length > 0);
    };

    const assignPlacement = async (id) => {
        if (state.replaceMode && state.activePlacementId) {
            const payload = {
                source_type: state.pendingSource,
                advertisement_id: state.pendingSource === 'custom' ? id : null,
                google_advertisement_id: state.pendingSource === 'google' ? id : null,
            };
            const { response, data } = await request(`${cfg.routes.placementsUpdate}/${state.activePlacementId}`, {
                method: 'PUT',
                headers: csrfHeaders(),
                body: JSON.stringify(payload),
            });
            if (!response.ok) {
                toast(data.message || Object.values(data.errors || {})[0]?.[0] || 'Failed to replace placement.', 'error');
                return;
            }
            toast(data.message || 'Placement updated.');
            closeModal('pick');
            await loadPlacements(state.pageKey);
            return;
        }

        const payload = {
            page_key: state.pageKey,
            position_key: state.pendingPosition,
            source_type: state.pendingSource,
            advertisement_id: state.pendingSource === 'custom' ? id : null,
            google_advertisement_id: state.pendingSource === 'google' ? id : null,
            is_enabled: true,
        };

        const { response, data } = await request(cfg.routes.placementsStore, {
            method: 'POST',
            headers: csrfHeaders(),
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            toast(firstError || data.message || 'Failed to place advertisement.', 'error');
            return;
        }

        toast(data.message || 'Advertisement placed.');
        closeModal('pick');
        await loadPlacements(state.pageKey);
    };

    const openPlacementActions = (placementId) => {
        const placement = state.placements.find((p) => Number(p.id) === Number(placementId));
        if (!placement) return;
        state.activePlacementId = placement.id;
        if (els.actionsSubtitle) {
            els.actionsSubtitle.textContent = `${placement.name} · ${placement.position_label}`;
        }
        const toggleBtn = document.querySelector('[data-ads-action="toggle"]');
        if (toggleBtn) {
            toggleBtn.textContent = placement.is_enabled ? 'Disable placement' : 'Enable placement';
        }
        openModal('actions');
    };

    const collectAdPayload = () => {
        const form = els.adForm;
        const type = form.querySelector('input[name="type"]:checked')?.value || 'banner';
        const payload = {
            name: form.querySelector('#ad_name').value.trim(),
            title: form.querySelector('#ad_title').value.trim(),
            type,
            status: form.querySelector('#ad_status').value,
            notes: form.querySelector('#ad_notes').value.trim(),
            open_in_new_tab: !!form.querySelector('#ad_open_in_new_tab')?.checked,
            is_responsive: !!form.querySelector('#ad_is_responsive')?.checked,
        };

        if (type === 'banner') {
            payload.banner_size = form.querySelector('#ad_banner_size').value || null;
            payload.image_id = form.querySelector('#ad_image_id')?.value || null;
            payload.target_url = form.querySelector('#ad_target_url').value.trim() || null;
        } else if (type === 'iframe') {
            payload.iframe_url = form.querySelector('#ad_iframe_url').value.trim();
            payload.width = form.querySelector('#ad_width').value || null;
            payload.height = form.querySelector('#ad_height').value || null;
        } else if (type === 'html') {
            payload.html_code = form.querySelector('#ad_html_code').value;
            payload.css_code = form.querySelector('#ad_css_code').value;
            payload.js_code = form.querySelector('#ad_js_code').value;
        }

        return payload;
    };

    // Events
    document.querySelector('[data-ads-help-open]')?.addEventListener('click', () => openModal('help'));
    document.querySelector('[data-ads-create]')?.addEventListener('click', () => {
        resetAdForm();
        openModal('form');
    });
    document.querySelector('[data-ads-google-create]')?.addEventListener('click', () => {
        resetGoogleForm();
        openModal('google');
    });

    document.querySelectorAll('[data-ads-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => closeModal(btn.getAttribute('data-ads-modal-close')));
    });

    document.querySelectorAll('[data-ads-choose-source]').forEach((btn) => {
        btn.addEventListener('click', () => openPickList(btn.getAttribute('data-ads-choose-source')));
    });

    document.querySelector('[data-ads-pick-create]')?.addEventListener('click', () => {
        closeModal('pick');
        if (state.pickMode === 'google') {
            resetGoogleForm();
            openModal('google');
        } else {
            resetAdForm();
            openModal('form');
        }
    });

    els.pickSearch?.addEventListener('input', renderPickList);
    els.pickList?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-ads-pick-id]');
        if (!btn) return;
        assignPlacement(Number(btn.getAttribute('data-ads-pick-id')));
    });

    els.preview?.addEventListener('click', (event) => {
        const addBtn = event.target.closest('[data-ads-add]');
        if (addBtn) {
            openSourceChooser(addBtn.getAttribute('data-ads-add'));
            return;
        }
        const itemBtn = event.target.closest('[data-ads-placement-id]');
        if (itemBtn) {
            openPlacementActions(itemBtn.getAttribute('data-ads-placement-id'));
        }
    });

    document.querySelectorAll('[data-ads-action]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const action = btn.getAttribute('data-ads-action');
            const placement = state.placements.find((p) => Number(p.id) === Number(state.activePlacementId));
            if (!placement) return;

            if (action === 'replace') {
                state.replaceMode = true;
                state.pendingPosition = placement.position_key;
                closeModal('actions');
                openModal('source');
                return;
            }

            if (action === 'toggle') {
                const { response, data } = await request(`${cfg.routes.placementsUpdate}/${placement.id}`, {
                    method: 'PUT',
                    headers: csrfHeaders(),
                    body: JSON.stringify({ is_enabled: !placement.is_enabled }),
                });
                if (!response.ok) {
                    toast(data.message || 'Failed to update placement.', 'error');
                    return;
                }
                toast(data.message || 'Placement updated.');
                closeModal('actions');
                await loadPlacements(state.pageKey);
                return;
            }

            if (action === 'remove') {
                const confirmed = window.Swal
                    ? (await window.Swal.fire({
                        title: 'Remove placement?',
                        text: 'This removes the ad from this slot. The advertisement itself is kept.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Remove',
                    })).isConfirmed
                    : window.confirm('Remove this placement?');
                if (!confirmed) return;

                const { response, data } = await request(`${cfg.routes.placementsDestroy}/${placement.id}`, {
                    method: 'DELETE',
                    headers: csrfHeaders(),
                });
                if (!response.ok) {
                    toast(data.message || 'Failed to remove placement.', 'error');
                    return;
                }
                toast(data.message || 'Placement removed.');
                closeModal('actions');
                await loadPlacements(state.pageKey);
            }
        });
    });

    els.pageSelect?.addEventListener('change', async () => {
        const next = els.pageSelect.value;
        await loadPlacements(next);
        const url = new URL(window.location.href);
        url.searchParams.set('page', next);
        window.history.replaceState({}, '', url);
    });

    els.adForm?.querySelectorAll('[data-ads-type-radio]').forEach((radio) => {
        radio.addEventListener('change', syncTypePanels);
    });
    els.bannerSize?.addEventListener('change', updateBannerNote);

    els.adForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors(els.adForm);
        const id = els.adForm.querySelector('#ad_id').value;
        const payload = collectAdPayload();
        const url = id ? `${cfg.routes.adsUpdate}/${id}` : cfg.routes.adsStore;
        const method = id ? 'PUT' : 'POST';
        const submitBtn = els.adForm.querySelector('[data-ads-form-submit]');
        const original = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving…';
        }
        try {
            const { response, data } = await request(url, {
                method,
                headers: csrfHeaders(),
                body: JSON.stringify(payload),
            });
            if (!response.ok) {
                if (response.status === 422) showFormErrors(els.adForm, data.errors || {});
                toast(data.message || 'Unable to save advertisement.', 'error');
                return;
            }
            const ad = data.ad;
            if (id) {
                state.ads = state.ads.map((row) => (Number(row.id) === Number(ad.id) ? ad : row));
            } else {
                state.ads.unshift(ad);
            }
            renderAdsTable();
            toast(data.message || 'Advertisement saved.');
            closeModal('form');

            // If opened from pick flow, auto-select the new ad.
            if (state.pendingPosition || state.replaceMode) {
                state.pendingSource = 'custom';
                await assignPlacement(ad.id);
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = original || 'Save advertisement';
            }
        }
    });

    els.googleForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors(els.googleForm);
        const id = els.googleForm.querySelector('#google_ad_id').value;
        const payload = {
            name: els.googleForm.querySelector('#google_ad_name').value.trim(),
            ad_client: els.googleForm.querySelector('#google_ad_client').value.trim() || null,
            ad_slot: els.googleForm.querySelector('#google_ad_slot').value.trim() || null,
            ad_format: els.googleForm.querySelector('#google_ad_format').value.trim() || null,
            code: els.googleForm.querySelector('#google_ad_code').value,
            status: els.googleForm.querySelector('#google_ad_status').value,
            notes: els.googleForm.querySelector('#google_ad_notes').value.trim() || null,
        };
        const url = id ? `${cfg.routes.googleUpdate}/${id}` : cfg.routes.googleStore;
        const method = id ? 'PUT' : 'POST';
        const { response, data } = await request(url, {
            method,
            headers: csrfHeaders(),
            body: JSON.stringify(payload),
        });
        if (!response.ok) {
            if (response.status === 422) showFormErrors(els.googleForm, data.errors || {});
            toast(data.message || 'Unable to save Google Ad.', 'error');
            return;
        }
        const ad = data.google_ad;
        if (id) {
            state.googleAds = state.googleAds.map((row) => (Number(row.id) === Number(ad.id) ? ad : row));
        } else {
            state.googleAds.unshift(ad);
        }
        renderGoogleTable();
        toast(data.message || 'Google Ad saved.');
        closeModal('google');

        if (state.pendingPosition || state.replaceMode) {
            state.pendingSource = 'google';
            await assignPlacement(ad.id);
        }
    });

    els.adsBody?.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('[data-ads-edit]');
        if (editBtn) {
            const ad = state.ads.find((row) => Number(row.id) === Number(editBtn.getAttribute('data-ads-edit')));
            if (ad) {
                fillAdForm(ad);
                openModal('form');
            }
            return;
        }
        const deleteBtn = event.target.closest('[data-ads-delete]');
        if (deleteBtn) {
            const id = deleteBtn.getAttribute('data-ads-delete');
            const confirmed = window.Swal
                ? (await window.Swal.fire({
                    title: 'Delete advertisement?',
                    text: 'Placements using this ad will also be removed.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                })).isConfirmed
                : window.confirm('Delete this advertisement?');
            if (!confirmed) return;
            const { response, data } = await request(`${cfg.routes.adsDestroy}/${id}`, {
                method: 'DELETE',
                headers: csrfHeaders(),
            });
            if (!response.ok) {
                toast(data.message || 'Failed to delete advertisement.', 'error');
                return;
            }
            state.ads = state.ads.filter((row) => Number(row.id) !== Number(id));
            renderAdsTable();
            await loadPlacements(state.pageKey);
            toast(data.message || 'Advertisement deleted.');
        }
    });

    els.googleBody?.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('[data-google-edit]');
        if (editBtn) {
            const ad = state.googleAds.find((row) => Number(row.id) === Number(editBtn.getAttribute('data-google-edit')));
            if (ad) {
                fillGoogleForm(ad);
                openModal('google');
            }
            return;
        }
        const deleteBtn = event.target.closest('[data-google-delete]');
        if (deleteBtn) {
            const id = deleteBtn.getAttribute('data-google-delete');
            const confirmed = window.Swal
                ? (await window.Swal.fire({
                    title: 'Delete Google Ad?',
                    text: 'Placements using this configuration will also be removed.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                })).isConfirmed
                : window.confirm('Delete this Google Ad configuration?');
            if (!confirmed) return;
            const { response, data } = await request(`${cfg.routes.googleDestroy}/${id}`, {
                method: 'DELETE',
                headers: csrfHeaders(),
            });
            if (!response.ok) {
                toast(data.message || 'Failed to delete Google Ad.', 'error');
                return;
            }
            state.googleAds = state.googleAds.filter((row) => Number(row.id) !== Number(id));
            renderGoogleTable();
            await loadPlacements(state.pageKey);
            toast(data.message || 'Google Ad deleted.');
        }
    });

    ['input', 'change'].forEach((evt) => {
        els.filterSearch?.addEventListener(evt, renderAdsTable);
        els.filterType?.addEventListener(evt, renderAdsTable);
        els.filterStatus?.addEventListener(evt, renderAdsTable);
    });

    els.filterClear?.addEventListener('click', () => {
        if (els.filterSearch) els.filterSearch.value = '';
        if (els.filterType) els.filterType.value = '';
        if (els.filterStatus) els.filterStatus.value = '';
        renderAdsTable();
        els.filterSearch?.focus();
    });

    els.customCodeForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFormErrors(els.customCodeForm);
        const payload = {
            header_code: els.customCodeForm.querySelector('#ads_header_code').value,
            footer_code: els.customCodeForm.querySelector('#ads_footer_code').value,
        };
        const original = els.customCodeSave?.textContent;
        if (els.customCodeSave) {
            els.customCodeSave.disabled = true;
            els.customCodeSave.textContent = 'Saving…';
        }
        try {
            const { response, data } = await request(cfg.routes.customCode, {
                method: 'PUT',
                headers: csrfHeaders(),
                body: JSON.stringify(payload),
            });
            if (!response.ok) {
                if (response.status === 422) showFormErrors(els.customCodeForm, data.errors || {});
                toast(data.message || 'Failed to save custom code.', 'error');
                return;
            }
            toast(data.message || 'Custom code saved.');
        } finally {
            if (els.customCodeSave) {
                els.customCodeSave.disabled = false;
                els.customCodeSave.textContent = original || 'Save custom code';
            }
        }
    });

    // Init gallery pickers (shared content form helper)
    if (window.EmsContentForm?.initGalleryPickers) {
        window.EmsContentForm.initGalleryPickers(window.contentFormConfig?.existingMedia || {});
    }

    renderPreview();
    renderAdsTable();
    renderGoogleTable();
    syncTypePanels();
    updateBannerNote();
})();

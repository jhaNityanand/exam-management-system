/**
 * Universal Bootstrap-Style Tooltip Engine
 * Automatically attaches floating tooltips to any button or element with title / data-bs-title / data-tooltip attributes.
 */
(function () {
    'use strict';

    let activeTooltipEl = null;
    let currentTarget = null;

    function createTooltipElement() {
        const el = document.createElement('div');
        el.className = 'ems-tooltip ems-tooltip--top';
        el.innerHTML = '<div class="ems-tooltip__inner"></div><div class="ems-tooltip__arrow"></div>';
        document.body.appendChild(el);
        return el;
    }

    function showTooltip(target) {
        if (!target) return;

        // Get title text
        let titleText = target.getAttribute('data-bs-title') || target.getAttribute('data-tooltip') || target.getAttribute('title');
        if (!titleText || !titleText.trim()) return;

        // Suppress native browser yellow tooltip
        if (target.hasAttribute('title')) {
            target.setAttribute('data-bs-title', titleText);
            target.removeAttribute('title');
        }

        if (!activeTooltipEl) {
            activeTooltipEl = createTooltipElement();
        }

        const inner = activeTooltipEl.querySelector('.ems-tooltip__inner');
        if (inner) inner.textContent = titleText.trim();

        currentTarget = target;

        // Position calculations
        const rect = target.getBoundingClientRect();
        activeTooltipEl.classList.remove('ems-tooltip--bottom', 'ems-tooltip--top');

        // Position above by default, or below if near top of window
        const spaceAbove = rect.top;
        const positionAbove = spaceAbove >= 40;

        if (positionAbove) {
            activeTooltipEl.classList.add('ems-tooltip--top');
        } else {
            activeTooltipEl.classList.add('ems-tooltip--bottom');
        }

        activeTooltipEl.style.display = 'block';

        // Measure tooltip size
        const tooltipRect = activeTooltipEl.getBoundingClientRect();
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        let top = positionAbove 
            ? (rect.top - tooltipRect.height - 7)
            : (rect.bottom + 7);

        // Clamp to viewport edges
        left = Math.max(8, Math.min(left, window.innerWidth - tooltipRect.width - 8));
        top = Math.max(8, Math.min(top, window.innerHeight - tooltipRect.height - 8));

        activeTooltipEl.style.left = `${left}px`;
        activeTooltipEl.style.top = `${top}px`;

        requestAnimationFrame(() => {
            if (activeTooltipEl && currentTarget === target) {
                activeTooltipEl.classList.add('is-active');
            }
        });
    }

    function hideTooltip() {
        if (activeTooltipEl) {
            activeTooltipEl.classList.remove('is-active');
            setTimeout(() => {
                if (activeTooltipEl && !activeTooltipEl.classList.contains('is-active')) {
                    activeTooltipEl.style.display = 'none';
                }
            }, 150);
        }
        currentTarget = null;
    }

    // Global Event Delegation for mouseover / mouseout / click
    document.addEventListener('mouseover', (e) => {
        const target = e.target.closest('button, a, [title], [data-bs-title], [data-tooltip]');
        if (target && (target.hasAttribute('title') || target.hasAttribute('data-bs-title') || target.hasAttribute('data-tooltip'))) {
            if (target !== currentTarget) {
                showTooltip(target);
            }
        }
    }, true);

    document.addEventListener('mouseout', (e) => {
        const target = e.target.closest('button, a, [data-bs-title], [data-tooltip]');
        if (target && target === currentTarget) {
            hideTooltip();
        }
    }, true);

    document.addEventListener('click', () => {
        hideTooltip();
    }, true);

    window.addEventListener('scroll', () => {
        hideTooltip();
    }, { passive: true });
})();

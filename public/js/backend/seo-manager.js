/**
 * seo-manager.js
 *
 * Shared JavaScript library to manage the SEO & Metadata sections:
 *   - Accordion expansion & collapse
 *   - AI Toggle visibility logic ("Create with AI" ON -> hide manual fields and Improve with AI toggle)
 *   - Dynamic character counters
 *   - Auto-expand if server-side validation errors are present
 */
document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------------------------------------------ */
    /*  Accordion Logic                                                   */
    /* ------------------------------------------------------------------ */
    const accordionToggle  = document.getElementById('meta-accordion-toggle');
    const accordionBody    = document.getElementById('meta-accordion-body');
    const accordionChevron = accordionToggle?.querySelector('.qcat-meta-chevron');

    const openAccordion = () => {
        if (!accordionBody) return;
        accordionBody.classList.remove('hidden');
        accordionToggle?.setAttribute('aria-expanded', 'true');
        if (accordionChevron) accordionChevron.style.transform = 'rotate(180deg)';
    };

    const closeAccordion = () => {
        if (!accordionBody) return;
        accordionBody.classList.add('hidden');
        accordionToggle?.setAttribute('aria-expanded', 'false');
        if (accordionChevron) accordionChevron.style.transform = '';
    };

    if (accordionToggle && accordionBody) {
        // Toggle on click
        accordionToggle.addEventListener('click', () => {
            accordionBody.classList.contains('hidden') ? openAccordion() : closeAccordion();
        });

        // Toggle on Enter/Space
        accordionToggle.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                accordionBody.classList.contains('hidden') ? openAccordion() : closeAccordion();
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /*  AI Toggle Logic                                                   */
    /* ------------------------------------------------------------------ */
    const aiCreateCheckbox = document.getElementById('toggle-ai-create');
    const aiImproveWrapper = document.getElementById('improve-with-ai-wrapper');
    const aiImproveCheckbox = document.getElementById('toggle-ai-improve');
    const manualFieldsWrapper = document.getElementById('manual-seo-fields-wrapper');

    const applyAiCreateState = (enabled) => {
        if (enabled) {
            if (manualFieldsWrapper) manualFieldsWrapper.classList.add('hidden');
            if (aiImproveWrapper) aiImproveWrapper.classList.add('hidden');
            if (aiImproveCheckbox) aiImproveCheckbox.checked = false;
        } else {
            if (manualFieldsWrapper) manualFieldsWrapper.classList.remove('hidden');
            if (aiImproveWrapper) aiImproveWrapper.classList.remove('hidden');
        }
    };

    if (aiCreateCheckbox) {
        aiCreateCheckbox.addEventListener('change', () => {
            applyAiCreateState(aiCreateCheckbox.checked);
        });
        // Initial state sync
        applyAiCreateState(aiCreateCheckbox.checked);
    }

    /* ------------------------------------------------------------------ */
    /*  Character Counters                                                */
    /* ------------------------------------------------------------------ */
    document.querySelectorAll('.qcat-meta-count[data-max]').forEach((counter) => {
        const field = counter.previousElementSibling;
        if (!field) return;

        const max = parseInt(counter.dataset.max, 10);
        const update = () => {
            const len = field.value.length;
            counter.textContent = `${len} / ${max}`;
            counter.classList.toggle('qcat-meta-count--warn', len > max * 0.85);
            counter.classList.toggle('qcat-meta-count--over', len > max);
        };

        field.addEventListener('input', update);
        update(); // Run once on load
    });

    /* ------------------------------------------------------------------ */
    /*  Live Search Preview                                               */
    /* ------------------------------------------------------------------ */
    const previewWrap = document.querySelector('[data-seo-preview]');
    if (previewWrap) {
        const baseUrl = (previewWrap.dataset.baseUrl || window.location.origin).replace(/\/+$/, '');
        const metaTitle = document.getElementById('meta-title');
        const pageTitle = document.getElementById('title');
        const metaDesc = document.getElementById('meta-desc');
        const slugField = document.getElementById('meta-slug') || document.getElementById('slug');
        const nameField = document.getElementById('name');
        const previewTitle = document.getElementById('seo-preview-title');
        const previewUrl = document.getElementById('seo-preview-url');
        const previewDesc = document.getElementById('seo-preview-desc');

        const slugify = (text) => String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');

        const render = () => {
            const title = metaTitle?.value?.trim() || pageTitle?.value?.trim() || nameField?.value?.trim() || 'Page title preview';
            const desc = metaDesc?.value?.trim() || 'Meta description preview will appear here.';
            const slug = slugField?.value?.trim() || slugify(pageTitle?.value || nameField?.value) || 'example-slug';
            if (previewTitle) previewTitle.textContent = title;
            if (previewDesc) previewDesc.textContent = desc;
            if (previewUrl) previewUrl.textContent = `${baseUrl}/${slug}`;
        };

        [metaTitle, pageTitle, metaDesc, slugField, nameField].forEach((el) => {
            el?.addEventListener('input', render);
        });
        render();
    }

    /* ------------------------------------------------------------------ */
    /*  Auto-Expand on Validation Errors                                  */
    /* ------------------------------------------------------------------ */
    if (accordionBody) {
        const hasErrors = accordionBody.querySelectorAll('.is-invalid, .qcat-field-error.is-visible').length > 0;
        if (hasErrors) {
            openAccordion();
        } else {
            closeAccordion(); // Ensure it starts collapsed by default
        }
    }
});

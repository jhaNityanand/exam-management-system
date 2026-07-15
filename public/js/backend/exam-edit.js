/**
 * Exam edit page bootstrap: rich text, Tom Select, instruction templates, SEO helpers.
 */
document.addEventListener('DOMContentLoaded', async () => {
    if (window.EmsRichTextEditor?.initAll) {
        await window.EmsRichTextEditor.initAll(document);
    }

    if (window.EmsSelect) {
        window.EmsSelect.initAll(
            document,
            'select.panel-input:not(#edit_category_id)'
        );
    }

    const categorySelect = window.EmsTomSelectHierarchy?.create('#edit_category_id', {
        placeholder: 'Search for a category...',
    }) || (window.TomSelect
        ? new TomSelect('#edit_category_id', {
            create: false,
            placeholder: 'Search for a category...',
            closeAfterSelect: true,
        })
        : null);

    if (categorySelect && !window.EmsTomSelectHierarchy) {
        window.EmsTomSelectBlur?.attach(categorySelect);
    }
    window.EmsTomSelectBlur?.blurNativeSelects(document.querySelector('form') || document);

    const applyBtn = document.getElementById('edit-apply-instruction-template');
    const templateSelect = document.getElementById('edit_instruction_template');
    applyBtn?.addEventListener('click', () => {
        if (!templateSelect) return;
        const templateId = templateSelect.value;
        const content = window.examEditInstructionTemplates?.[templateId] || '';
        if (!content) return;

        const adapter = window.EmsRichTextEditor?.get('edit_instructions');
        if (adapter) {
            adapter.setData(content);
            return;
        }

        const field = document.getElementById('edit_instructions');
        if (field) {
            field.value = content;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    document.querySelector('form')?.addEventListener('submit', () => {
        window.EmsRichTextEditor?.syncAll();
    });
});

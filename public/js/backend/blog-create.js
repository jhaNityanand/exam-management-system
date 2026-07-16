document.addEventListener('DOMContentLoaded', () => {
    const config = window.contentFormConfig || {};
    if (!config.formId || !window.EmsContentForm) return;

    window.EmsDateTimePicker?.initAll?.(document).then?.(() => {
        window.EmsContentForm.initContentForm(config);
    }) || window.EmsContentForm.initContentForm(config);
});

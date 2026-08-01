/**
 * Global searchable-select configuration.
 * Change values here to adjust dropdown behavior app-wide.
 */
window.EmsSelectConfig = Object.assign({
    /** Show the Tom Select search input only when option count >= this value. */
    searchMinOptions: 8,
    /** Default placeholder when a select has no data-placeholder attribute. */
    placeholder: 'Select an option',
}, window.EmsSelectConfig || {});

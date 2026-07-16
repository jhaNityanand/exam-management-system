(function (global) {
    'use strict';

    function syncSortButtons(table, selector = '.list-sort-btn') {
        document.querySelectorAll(selector).forEach((button) => {
            const active = button.dataset.sortKey === table.sort;
            button.classList.toggle('is-active', active);
            button.classList.toggle('is-asc', active && table.direction === 'asc');
            button.classList.toggle('is-desc', active && table.direction === 'desc');
            button.setAttribute('aria-sort', active ? (table.direction === 'asc' ? 'ascending' : 'descending') : 'none');
        });
    }

    function bindSortButtons(table, selector = '.list-sort-btn') {
        document.querySelectorAll(selector).forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.dataset.sortKey;
                if (!key) return;
                if (table.sort === key) {
                    table.direction = table.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    table.sort = key;
                    table.direction = 'asc';
                }
                table.page = 1;
                syncSortButtons(table, selector);
                table.fetch();
            });
        });
        syncSortButtons(table, selector);
    }

    class ListSelection {
        constructor(options = {}) {
            this.body = document.querySelector(options.bodySelector);
            this.selectAll = document.querySelector(options.selectAllSelector);
            this.bulkBar = document.querySelector(options.bulkBarSelector);
            this.count = document.querySelector(options.countSelector);
            this.checkboxSelector = options.checkboxSelector || '.list-row-check';
            this.rowSelector = options.rowSelector || 'tr';
            this.activeActions = document.querySelector(options.activeActionsSelector);
            this.binActions = document.querySelector(options.binActionsSelector);
            this.ids = new Set();
            this.mode = 'active';
            this.bind();
        }

        bind() {
            this.body?.addEventListener('change', (event) => {
                const checkbox = event.target.closest(this.checkboxSelector);
                if (!checkbox) return;
                const id = String(checkbox.dataset.id || checkbox.value);
                if (checkbox.checked) this.ids.add(id);
                else this.ids.delete(id);
                checkbox.closest(this.rowSelector)?.classList.toggle('is-selected', checkbox.checked);
                this.render();
            });

            this.selectAll?.addEventListener('change', () => {
                this.body?.querySelectorAll(this.checkboxSelector).forEach((checkbox) => {
                    checkbox.checked = this.selectAll.checked;
                    const id = String(checkbox.dataset.id || checkbox.value);
                    if (checkbox.checked) this.ids.add(id);
                    else this.ids.delete(id);
                    checkbox.closest(this.rowSelector)?.classList.toggle('is-selected', checkbox.checked);
                });
                this.render();
            });
        }

        setMode(mode) {
            this.mode = mode === 'bin' ? 'bin' : 'active';
            if (this.activeActions) this.activeActions.hidden = this.mode === 'bin';
            if (this.binActions) this.binActions.hidden = this.mode !== 'bin';
            this.clear();
        }

        clear() {
            this.ids.clear();
            this.body?.querySelectorAll(this.checkboxSelector).forEach((checkbox) => {
                checkbox.checked = false;
                checkbox.closest(this.rowSelector)?.classList.remove('is-selected');
            });
            this.render();
        }

        render() {
            const checkboxes = [...(this.body?.querySelectorAll(this.checkboxSelector) || [])];
            if (this.bulkBar) this.bulkBar.hidden = this.ids.size === 0;
            if (this.count) this.count.textContent = String(this.ids.size);
            if (this.selectAll) {
                this.selectAll.checked = checkboxes.length > 0 && this.ids.size === checkboxes.length;
                this.selectAll.indeterminate = this.ids.size > 0 && this.ids.size < checkboxes.length;
            }
        }

        appendToForm(form) {
            if (!form || this.ids.size === 0) return false;
            form.querySelectorAll('input[name="ids[]"]').forEach((input) => input.remove());
            this.ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            return true;
        }

        submit(formOrSelector) {
            const form = typeof formOrSelector === 'string'
                ? document.querySelector(formOrSelector)
                : formOrSelector;
            if (!this.appendToForm(form)) return false;
            form.submit();
            return true;
        }
    }

    global.EmsListUi = {
        ListSelection,
        bindSortButtons,
        syncSortButtons,
    };
}(window));

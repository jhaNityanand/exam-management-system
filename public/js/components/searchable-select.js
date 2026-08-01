/**
 * Searchable selects for frontend + backend.
 * Enhances native <select> with Tom Select (searchable dropdown).
 *
 * Skip with: data-no-search | multiple | size>1 | data-filter-multiple
 *            | data-option-style="hierarchy" | data-filter-hierarchy
 *            | data-ems-select="manual"
 */
(function () {
  'use strict';

  var TomSelect = window.TomSelect;
  if (!TomSelect) {
    console.warn('[searchable-select] Tom Select not loaded');
    return;
  }

  var DEFAULTS = {
    allowEmptyOption: true,
    create: false,
    maxOptions: null,
    hideSelected: false,
    closeAfterSelect: true,
    searchField: ['text'],
    placeholder: 'Select…',
    plugins: ['dropdown_input'],
    render: {
      no_results: function () {
        return '<div class="no-results">No results found</div>';
      },
    },
  };

  function shouldEnhance(select) {
    if (!select || select.tagName !== 'SELECT') return false;
    if (select.dataset.noSearch != null || select.hasAttribute('data-no-search')) return false;
    if (select.dataset.emsSelect === 'manual') return false;
    if (select.hasAttribute('data-filter-multiple')) return false;
    if (select.hasAttribute('data-filter-hierarchy')) return false;
    if (select.dataset.optionStyle === 'hierarchy') return false;
    if (select.multiple) return false;
    if (select.size && Number(select.size) > 1) return false;
    if (select.disabled && select.dataset.searchableForce == null) return false;
    if (select.tomselect) return false;
    if (select.classList.contains('tomselected')) return false;
    // Hidden selects used as Tom Select sources after init
    if (select.style && select.style.display === 'none' && select.classList.contains('is-searchable')) {
      return false;
    }
    return true;
  }

  function enhanceSelect(select) {
    if (!shouldEnhance(select)) return null;

    var opts = Object.assign({}, DEFAULTS);
    if (select.dataset.placeholder) {
      opts.placeholder = select.dataset.placeholder;
    }

    // Keep dropdown usable inside modals/overflow containers
    opts.dropdownParent = 'body';

    opts.onInitialize = function () {
      this.wrapper.classList.add('ems-select-wrapper');
      this.wrapper.classList.remove('panel-input');
      this.wrapper.classList.remove('org-contact-input', 'org-brand-input');
    };

    try {
      var instance = new TomSelect(select, opts);
      select.classList.add('is-searchable');
      return instance;
    } catch (err) {
      console.warn('[searchable-select] failed', err);
      return null;
    }
  }

  function initSearchableSelects(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var nodes = scope.querySelectorAll
      ? scope.querySelectorAll('select')
      : [];

    // If root itself is a select
    if (root && root.tagName === 'SELECT') {
      enhanceSelect(root);
    }

    Array.prototype.forEach.call(nodes, enhanceSelect);
  }

  function destroySearchableSelects(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var nodes = scope.querySelectorAll('select');
    Array.prototype.forEach.call(nodes, function (select) {
      if (select.tomselect) {
        select.tomselect.destroy();
      }
    });
  }

  window.initSearchableSelects = initSearchableSelects;
  window.destroySearchableSelects = destroySearchableSelects;

  document.addEventListener('DOMContentLoaded', function () {
    initSearchableSelects(document);

    // Re-init when new nodes are injected (AJAX partials, Alpine, etc.)
    if (window.MutationObserver) {
      var timer = null;
      var obs = new MutationObserver(function (mutations) {
        var needs = false;
        mutations.forEach(function (m) {
          m.addedNodes && m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) return;
            if (node.tagName === 'SELECT' || (node.querySelector && node.querySelector('select'))) {
              needs = true;
            }
          });
        });
        if (!needs) return;
        clearTimeout(timer);
        timer = setTimeout(function () {
          initSearchableSelects(document);
        }, 60);
      });
      obs.observe(document.body, { childList: true, subtree: true });
    }
  });

  // Helpful hook for modals that open after page load
  document.addEventListener('et:filters-open', function (event) {
    var root = event && event.detail && event.detail.root ? event.detail.root : document;
    initSearchableSelects(root);
  });
})();

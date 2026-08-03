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
    placeholder: 'Select an option',
    plugins: ['dropdown_input'],
    render: {
      no_results: function () {
        return '<div class="no-results">No results found</div>';
      },
    },
  };

  function searchMinOptions() {
    var cfg = window.EmsSelectConfig || {};
    var value = Number(cfg.searchMinOptions);
    return Number.isFinite(value) && value > 0 ? value : 8;
  }

  function defaultPlaceholder() {
    var cfg = window.EmsSelectConfig || {};
    return cfg.placeholder || 'Select an option';
  }

  function resolvePlaceholder(select) {
    if (select.dataset.placeholder) return select.dataset.placeholder;
    if (select.getAttribute('placeholder')) return select.getAttribute('placeholder');

    var first = select.options && select.options[0];
    if (first && first.value === '' && first.textContent.trim()) {
      var text = first.textContent.trim().replace(/[.…]+$/, '').trim();
      if (first.disabled || /^(select|choose|pick|search|type)\b/i.test(text)) {
        return text;
      }
    }

    var aria = select.getAttribute('aria-label');
    if (aria && aria.trim()) {
      var label = aria.trim();
      if (/^(select|choose|pick|search)\b/i.test(label)) return label;
      return 'Select ' + label.charAt(0).toLowerCase() + label.slice(1);
    }

    return defaultPlaceholder();
  }

  function scrubSwalSelects(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var nodes = [];
    if (scope.querySelectorAll) {
      nodes = scope.querySelectorAll('#swal2-select, select.swal2-select, select.flatpickr-monthDropdown-months, .flatpickr-calendar select');
    }
    Array.prototype.forEach.call(nodes, function (select) {
      if (select.tomselect) {
        try {
          select.tomselect.destroy();
        } catch (err) {
          /* ignore */
        }
      }
      select.classList.remove('tomselected', 'is-searchable', 'ts-hidden-accessible');
    });
    if (scope.querySelectorAll) {
      Array.prototype.forEach.call(
        scope.querySelectorAll(
          '.swal2-container .ts-wrapper.swal2-select, .swal2-popup .ts-wrapper.swal2-select, .flatpickr-calendar .ts-wrapper'
        ),
        function (el) {
          el.remove();
        }
      );
    }
  }

  function countableOptions(select) {
    return Array.prototype.filter.call(select.options || [], function (option) {
      if (option.disabled && option.value === '') return false;
      return true;
    }).length;
  }

  function shouldIncludeSearch(select) {
    if (select.dataset.forceSearch != null || select.hasAttribute('data-force-search')) {
      return true;
    }
    if (select.dataset.disableSearch != null || select.hasAttribute('data-disable-search')) {
      return false;
    }
    // Listing filter modals: keep dropdown search available for category-style lists.
    if (select.closest('[data-filter-modal]')) {
      return countableOptions(select) >= 3;
    }
    return countableOptions(select) >= searchMinOptions();
  }

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
    // SweetAlert2 keeps hidden input widgets in every popup; never enhance them.
    if (select.id === 'swal2-select' || select.classList.contains('swal2-select')) return false;
    if (select.closest('.swal2-container, .swal2-popup')) return false;
    // Flatpickr month/year controls — must stay native for theme + option visibility.
    if (select.classList.contains('flatpickr-monthDropdown-months')) return false;
    if (select.closest('.flatpickr-calendar')) return false;
    // Hidden selects used as Tom Select sources after init
    if (select.style && select.style.display === 'none' && select.classList.contains('is-searchable')) {
      return false;
    }
    // Skip empty selects — JS often populates options later. Early Tom Select init
    // captures empty HTML; destroy() would restore that and wipe later options.
    if (countableOptions(select) === 0 && select.dataset.searchableForce == null
        && !select.hasAttribute('data-searchable-force')) {
      return false;
    }
    return true;
  }

  /**
   * Flip Tom Select menus above the control when space below is tight
   * (filter drawers, near viewport bottom, overflow panels).
   * Scroll only .ts-dropdown-content to avoid duplicate scrollbars.
   */
  function positionDropdown(instance) {
    if (!instance || !instance.dropdown || !instance.control) return;

    // Prefer the shared helper when the filter drawer script is present.
    if (window.EmsFilterDrawer && typeof window.EmsFilterDrawer.positionTomSelectDropdown === 'function') {
      window.EmsFilterDrawer.positionTomSelectDropdown(instance);
      return;
    }

    var dropdown = instance.dropdown;
    var content = instance.dropdown_content
      || dropdown.querySelector('.ts-dropdown-content');

    dropdown.classList.remove('ts-dropdown--up');
    dropdown.style.maxHeight = '';
    dropdown.style.overflow = 'hidden';
    if (content) {
      content.style.maxHeight = '';
    }

    window.requestAnimationFrame(function () {
      var controlRect = instance.control.getBoundingClientRect();
      var viewportH = window.innerHeight || document.documentElement.clientHeight;
      var spaceBelow = Math.max(0, viewportH - controlRect.bottom - 8);
      var spaceAbove = Math.max(0, controlRect.top - 8);
      var contentNatural = content
        ? Math.max(content.scrollHeight || 0, 120)
        : Math.max(dropdown.scrollHeight || 200, 160);
      var chrome = Math.max(0, (dropdown.offsetHeight || 0) - (content ? content.offsetHeight || contentNatural : contentNatural));
      var naturalHeight = Math.min(320, contentNatural + chrome);
      var openUp = spaceBelow < Math.min(naturalHeight, 240) && spaceAbove > spaceBelow;
      var shellMax = Math.max(120, Math.min(naturalHeight, openUp ? spaceAbove : spaceBelow));
      var contentMax = Math.max(96, shellMax - chrome);

      if (content) {
        content.style.maxHeight = contentMax + 'px';
        content.style.overflowX = 'hidden';
        content.style.overflowY = 'auto';
      } else {
        dropdown.style.maxHeight = shellMax + 'px';
      }

      dropdown.classList.toggle('ts-dropdown--up', openUp);

      // Fixed coords track the control even when an ancestor panel scrolls.
      dropdown.style.position = 'fixed';
      dropdown.style.left = Math.round(controlRect.left) + 'px';
      dropdown.style.width = Math.max(160, Math.round(controlRect.width)) + 'px';
      dropdown.style.minWidth = Math.max(160, Math.round(controlRect.width)) + 'px';
      dropdown.style.right = 'auto';
      dropdown.style.bottom = 'auto';
      dropdown.style.marginTop = '0';
      dropdown.style.marginBottom = '0';
      dropdown.style.transform = 'none';
      dropdown.style.setProperty('z-index', '12000', 'important');

      if (openUp) {
        var height = Math.min(Math.max(dropdown.offsetHeight || 0, contentNatural + chrome, 120), shellMax);
        dropdown.style.top = Math.max(8, Math.round(controlRect.top - height - 6)) + 'px';
      } else {
        dropdown.style.top = Math.round(controlRect.bottom + 6) + 'px';
      }
    });
  }

  function enhanceSelect(select) {
    if (!shouldEnhance(select)) return null;

    var opts = Object.assign({}, DEFAULTS, {
      placeholder: resolvePlaceholder(select),
    });

    // Keep dropdown usable inside modals/overflow containers
    opts.dropdownParent = 'body';

    if (!shouldIncludeSearch(select)) {
      opts.plugins = (opts.plugins || []).filter(function (plugin) {
        return plugin !== 'dropdown_input';
      });
      // Keep searchField valid for Tom Select internals; disable filtering via score.
      opts.searchField = ['text'];
      opts.score = function () {
        return function () {
          return 1;
        };
      };
    }

    opts.onInitialize = function () {
      this.wrapper.classList.add('ems-select-wrapper');
      this.wrapper.classList.remove('panel-input');
      this.wrapper.classList.remove(
        'org-contact-input',
        'org-brand-input',
        'faq-field__select',
        'faq-toolbar__select',
        'org-members-toolbar__select',
        'qcat-meta-input'
      );
      this.dropdown.classList.add('ems-select-dropdown');
      if (!shouldIncludeSearch(select)) {
        this.wrapper.classList.add('ems-select-wrapper--no-search');
      }
    };

    opts.onDropdownOpen = function () {
      var self = this;
      if (shouldIncludeSearch(select)) {
        // Clear any stale query that can hide all options (common with dropdown_input).
        if (typeof self.setTextboxValue === 'function') {
          self.setTextboxValue('');
        }
        self.lastQuery = '';
        setTimeout(function () {
          if (!self.isOpen) return;
          try {
            self.refreshOptions(false);
          } catch (err) {
            /* ignore mid-open race */
          }
        }, 0);
      }
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () {
          if (!self.isOpen) return;
          positionDropdown(self);
        });
      });
    };

    opts.onDropdownClose = function () {
      if (!this.dropdown) return;
      this.dropdown.classList.remove('ts-dropdown--up');
      this.dropdown.style.top = '';
      this.dropdown.style.bottom = '';
      this.dropdown.style.maxHeight = '';
      var content = this.dropdown_content
        || this.dropdown.querySelector('.ts-dropdown-content');
      if (content) {
        content.style.maxHeight = '';
      }
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
  window.EmsSearchableSelect = {
    init: initSearchableSelects,
    destroy: destroySearchableSelects,
    positionDropdown: positionDropdown,
  };

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
          scrubSwalSelects(document);
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

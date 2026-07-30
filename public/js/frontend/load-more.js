(function () {
  'use strict';

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.from((root || document).querySelectorAll(sel));
  }

  function buildUrl(endpoint, params) {
    try {
      var url = new URL(endpoint, window.location.origin);
      // Clear existing query first so reset truly cleans URL
      url.search = '';
      Object.keys(params || {}).forEach(function (key) {
        var value = params[key];
        if (value == null || value === '') return;
        url.searchParams.set(key, String(value));
      });
      return url.toString();
    } catch (e) {
      return endpoint;
    }
  }

  function formToParams(form) {
    var data = new FormData(form);
    var params = {};
    data.forEach(function (value, key) {
      if (String(value).trim() !== '') {
        params[key] = String(value);
      }
    });
    return params;
  }

  function countActiveFilters(params, defaultSort) {
    var count = 0;
    var skipSort = defaultSort || 'latest';
    Object.keys(params || {}).forEach(function (key) {
      if (key === 'page') return;
      if (key === 'sort' && params[key] === skipSort) return;
      if (params[key] != null && String(params[key]).trim() !== '') count += 1;
    });
    return count;
  }

  function updateHistory(url) {
    try {
      window.history.replaceState({}, '', url);
    } catch (e) { /* ignore */ }
  }

  function animateNodes(nodes) {
    nodes.forEach(function (node, index) {
      node.classList.add('et-card--enter');
      node.style.animationDelay = (index * 40) + 'ms';
      node.addEventListener('animationend', function () {
        node.classList.remove('et-card--enter');
        node.style.animationDelay = '';
      }, { once: true });
    });
  }

  function fetchPage(url) {
    return fetch(url, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    }).then(function (res) {
      if (!res.ok) throw new Error('Failed to load listing');
      return res.json();
    });
  }

  function setLoading(btn, spinner, label, loading, loadingText) {
    if (!btn) return;
    btn.disabled = !!loading;
    if (label) label.textContent = loading ? (loadingText || 'Loading…') : 'Load more';
    if (spinner) spinner.hidden = !loading;
  }

  function syncLoadMore(root, meta, endpoint) {
    if (!root) return;
    var btn = qs('[data-load-more-btn]', root);

    root.setAttribute('data-endpoint', endpoint);
    root.setAttribute('data-page', String(meta.current_page || 1));
    root.setAttribute('data-last-page', String(meta.last_page || 1));
    root.setAttribute('data-total', String(meta.total || 0));
    root.hidden = !(meta.has_more && meta.next_page);

    if (btn) {
      if (meta.has_more && meta.next_page) {
        btn.hidden = false;
        btn.disabled = false;
        btn.setAttribute('data-next-page', String(meta.next_page));
      } else {
        btn.hidden = true;
        btn.removeAttribute('data-next-page');
      }
    }
  }

  function ensureLoadMore(slot, endpoint) {
    var existing = qs('[data-load-more]', slot);
    if (existing) return existing;

    var wrap = document.createElement('div');
    wrap.className = 'et-load-more';
    wrap.setAttribute('data-load-more', '');
    wrap.setAttribute('data-endpoint', endpoint);
    wrap.hidden = true;
    wrap.innerHTML =
      '<button type="button" class="et-btn et-btn--primary" data-load-more-btn hidden>' +
      '<span data-load-more-label>Load more</span>' +
      '<span class="et-spinner et-spinner--sm" data-load-more-spinner hidden aria-hidden="true"></span>' +
      '</button>';
    slot.appendChild(wrap);
    return wrap;
  }

  function initListing(listing) {
    var form = qs('[data-load-more-filters]', listing) || qs('[data-load-more-filters]');
    var list = qs('[data-load-more-list]', listing);
    var slot = qs('[data-load-more-slot]', listing) || listing;
    var empty = qs('[data-listing-empty]', listing);
    var skeleton = qs('[data-listing-skeleton]', listing);
    var modal = qs('[data-filter-modal]', listing);
    var openBtn = qs('[data-filter-open]', listing);
    var countEl = qs('[data-filter-count]', listing);
    var resetBtn = qs('[data-filters-reset]', listing);
    var endpoint = listing.getAttribute('data-endpoint') || (form && form.getAttribute('action')) || window.location.pathname;
    var defaultSort = (form && form.getAttribute('data-default-sort')) || 'latest';
    var loading = false;

    function syncFilterChrome(params) {
      var count = countActiveFilters(params, defaultSort);
      if (countEl) {
        countEl.textContent = String(count);
        countEl.hidden = count < 1;
      }
      if (resetBtn) {
        resetBtn.hidden = count < 1;
      }
    }

    function openModal() {
      if (!modal) return;
      modal.hidden = false;
      modal.classList.add('is-open');
      document.documentElement.classList.add('et-filter-modal-open');
      document.dispatchEvent(new CustomEvent('et:filters-open', { detail: { root: modal } }));
      if (typeof window.initSearchableSelects === 'function') {
        window.initSearchableSelects(modal);
      }
      var first = qs('input, select, button', form);
      if (first && typeof first.focus === 'function') {
        setTimeout(function () { first.focus(); }, 30);
      }
    }

    function closeModal() {
      if (!modal) return;
      modal.classList.remove('is-open');
      modal.hidden = true;
      document.documentElement.classList.remove('et-filter-modal-open');
    }

    function showSkeleton(show) {
      if (skeleton) skeleton.hidden = !show;
      if (list && show) list.hidden = true;
      if (empty && show) empty.hidden = true;
    }

    function applyResults(payload, requestUrl, replace) {
      var html = payload.html || '';
      var meta = payload.meta || {};
      var tmp = document.createElement('div');
      tmp.innerHTML = html;
      var nodes = Array.from(tmp.children);

      if (replace) {
        list.innerHTML = '';
      }

      nodes.forEach(function (node) {
        list.appendChild(node);
      });
      animateNodes(nodes);

      var hasItems = list.children.length > 0;
      list.hidden = !hasItems;
      if (empty) empty.hidden = hasItems;

      var loadEndpoint = endpoint;
      try {
        var u = new URL(requestUrl, window.location.origin);
        u.searchParams.delete('page');
        loadEndpoint = u.pathname + u.search;
      } catch (e) { /* keep endpoint */ }

      var loadMore = ensureLoadMore(slot, loadEndpoint);
      syncLoadMore(loadMore, meta, loadEndpoint);
      bindLoadMore(loadMore);
    }

    function bindLoadMore(root) {
      if (!root || root.getAttribute('data-bound') === '1') return;
      root.setAttribute('data-bound', '1');
      var btn = qs('[data-load-more-btn]', root);
      var spinner = qs('[data-load-more-spinner]', btn);
      var label = qs('[data-load-more-label]', btn);
      if (!btn) return;

      btn.addEventListener('click', function () {
        if (loading) return;
        var nextPage = Number(btn.getAttribute('data-next-page') || 0);
        var lastPage = Number(root.getAttribute('data-last-page') || 1);
        var loadEndpoint = root.getAttribute('data-endpoint') || endpoint;
        if (!nextPage || nextPage > lastPage) return;

        loading = true;
        setLoading(btn, spinner, label, true);

        var url = buildUrl(loadEndpoint, Object.assign(
          Object.fromEntries(new URL(loadEndpoint, window.location.origin).searchParams.entries()),
          { page: nextPage }
        ));

        fetchPage(url)
          .then(function (payload) {
            applyResults(payload, loadEndpoint, false);
            try {
              var historyUrl = new URL(window.location.href);
              historyUrl.searchParams.set('page', String(payload.meta && payload.meta.current_page ? payload.meta.current_page : nextPage));
              updateHistory(historyUrl.toString());
            } catch (e) { /* ignore */ }
          })
          .catch(function () { /* keep current list */ })
          .finally(function () {
            loading = false;
            setLoading(btn, spinner, label, false);
          });
      });
    }

    function runFilter(event) {
      if (event) event.preventDefault();
      if (!form || loading) return;

      loading = true;
      showSkeleton(true);
      closeModal();

      var params = formToParams(form);
      // Keep default sort out of URL
      if (params.sort === defaultSort) delete params.sort;
      params.page = 1;
      // Don't keep page=1 in URL
      var urlParams = Object.assign({}, params);
      delete urlParams.page;
      var url = buildUrl(endpoint, urlParams);
      // Fetch with page=1 explicitly
      var fetchUrl = buildUrl(endpoint, params);

      fetchPage(fetchUrl)
        .then(function (payload) {
          applyResults(payload, url, true);
          updateHistory(url);
          syncFilterChrome(urlParams);
        })
        .catch(function () {
          if (empty) empty.hidden = false;
        })
        .finally(function () {
          loading = false;
          showSkeleton(false);
          if (list && list.children.length > 0) list.hidden = false;
        });
    }

    function resetFilters(event) {
      if (event) event.preventDefault();
      if (!form) return;
      qsa('input, select', form).forEach(function (el) {
        if (el.type === 'checkbox' || el.type === 'radio') {
          el.checked = false;
        } else if (el.tagName === 'SELECT') {
          el.selectedIndex = 0;
          if (el.tomselect) {
            try {
              el.tomselect.setValue(el.value, true);
            } catch (e) { /* ignore */ }
          }
        } else {
          el.value = '';
        }
      });
      runFilter();
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    qsa('[data-filter-close]', listing).forEach(function (el) {
      el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
    });

    if (form) {
      form.addEventListener('submit', runFilter);
    }
    if (resetBtn) {
      resetBtn.addEventListener('click', resetFilters);
    }

    // Sync chrome from current URL on boot
    try {
      var bootParams = Object.fromEntries(new URL(window.location.href).searchParams.entries());
      delete bootParams.page;
      syncFilterChrome(bootParams);
    } catch (e) { /* ignore */ }

    qsa('[data-load-more]', listing).forEach(bindLoadMore);
  }

  function initLegacyLoadMore(root) {
    if (root.closest('[data-listing]')) return;
    var btn = qs('[data-load-more-btn]', root);
    var list = qs('[data-load-more-list]') || qs('[data-load-more-list]', root.parentElement);
    var label = qs('[data-load-more-label]', btn);
    var spinner = qs('[data-load-more-spinner]', btn);
    if (!btn || !list) return;

    var loading = false;

    btn.addEventListener('click', function () {
      if (loading) return;
      var endpoint = root.getAttribute('data-endpoint');
      var nextPage = Number(btn.getAttribute('data-next-page') || 0);
      var lastPage = Number(root.getAttribute('data-last-page') || 1);
      if (!endpoint || !nextPage || nextPage > lastPage) return;

      loading = true;
      setLoading(btn, spinner, label, true);

      var params = {};
      try {
        params = Object.fromEntries(new URL(endpoint, window.location.origin).searchParams.entries());
      } catch (e) { /* ignore */ }
      params.page = nextPage;

      fetchPage(buildUrl(endpoint.split('?')[0], params))
        .then(function (payload) {
          var html = payload.html || '';
          if (html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = html;
            var nodes = Array.from(tmp.children);
            nodes.forEach(function (node) {
              list.appendChild(node);
            });
            animateNodes(nodes);
          }

          var meta = payload.meta || {};
          root.setAttribute('data-page', String(meta.current_page || nextPage));
          if (meta.last_page) root.setAttribute('data-last-page', String(meta.last_page));
          if (meta.total != null) root.setAttribute('data-total', String(meta.total));
          try {
            var historyUrl = new URL(window.location.href);
            historyUrl.searchParams.set('page', String(meta.current_page || nextPage));
            updateHistory(historyUrl.toString());
          } catch (e) { /* ignore */ }

          if (meta.has_more && meta.next_page) {
            btn.setAttribute('data-next-page', String(meta.next_page));
            root.hidden = false;
            btn.hidden = false;
          } else {
            btn.hidden = true;
            root.hidden = true;
          }
        })
        .catch(function () { /* keep current list */ })
        .finally(function () {
          loading = false;
          setLoading(btn, spinner, label, false);
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    qsa('[data-listing]').forEach(initListing);
    qsa('[data-load-more]').forEach(initLegacyLoadMore);
  });
})();

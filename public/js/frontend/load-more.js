(function () {
  'use strict';

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.from((root || document).querySelectorAll(sel));
  }

  function buildUrl(endpoint, page) {
    try {
      var url = new URL(endpoint, window.location.origin);
      url.searchParams.set('page', String(page));
      return url.toString();
    } catch (e) {
      var joiner = endpoint.indexOf('?') === -1 ? '?' : '&';
      return endpoint + joiner + 'page=' + encodeURIComponent(page);
    }
  }

  function updateHistory(page) {
    try {
      var url = new URL(window.location.href);
      url.searchParams.set('page', String(page));
      window.history.replaceState({}, '', url.toString());
    } catch (e) { /* ignore */ }
  }

  function initLoadMore(root) {
    var btn = qs('[data-load-more-btn]', root);
    var list = qs('[data-load-more-list]') || qs('[data-load-more-list]', root.parentElement);
    var status = qs('[data-load-more-status]', root);
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
      btn.disabled = true;
      if (label) label.textContent = 'Loading…';
      if (spinner) spinner.hidden = false;

      fetch(buildUrl(endpoint, nextPage), {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      })
        .then(function (res) {
          if (!res.ok) throw new Error('Failed to load more');
          return res.json();
        })
        .then(function (payload) {
          var html = payload.html || '';
          if (html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = html;
            var nodes = Array.from(tmp.children);
            nodes.forEach(function (node) {
              list.appendChild(node);
            });
            if (nodes[0] && typeof nodes[0].focus === 'function') {
              nodes[0].setAttribute('tabindex', '-1');
              nodes[0].focus({ preventScroll: true });
            }
          }

          var meta = payload.meta || {};
          root.setAttribute('data-page', String(meta.current_page || nextPage));
          if (meta.last_page) root.setAttribute('data-last-page', String(meta.last_page));
          if (meta.total != null) root.setAttribute('data-total', String(meta.total));

          if (status) {
            var to = meta.to || list.children.length;
            var total = meta.total || root.getAttribute('data-total') || '';
            status.textContent = total ? ('Showing ' + to + ' of ' + total) : ('Showing ' + to + ' results');
          }

          updateHistory(meta.current_page || nextPage);

          if (meta.has_more && meta.next_page) {
            btn.setAttribute('data-next-page', String(meta.next_page));
          } else {
            btn.hidden = true;
            if (status && meta.total) status.textContent = 'Showing all ' + meta.total + ' results';
          }
        })
        .catch(function () {
          if (status) status.textContent = 'Unable to load more. Please try again.';
        })
        .finally(function () {
          loading = false;
          btn.disabled = false;
          if (label) label.textContent = 'Load more';
          if (spinner) spinner.hidden = true;
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    qsa('[data-load-more]').forEach(initLoadMore);
  });
})();

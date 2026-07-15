(function () {
  'use strict';

  const doc = document;
  const html = doc.documentElement;
  const THEME_KEY = 'examtube-theme';
  const ANNOUNCE_KEY = 'examtube-announce-dismissed';

  function qs(sel, root) {
    return (root || doc).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.from((root || doc).querySelectorAll(sel));
  }

  function debounce(fn, wait) {
    let t;
    return function debounced() {
      const ctx = this;
      const args = arguments;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  /* Theme */
  function initTheme() {
    const stored = localStorage.getItem(THEME_KEY);
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = stored || (prefersDark ? 'dark' : 'light');
    applyTheme(theme);

    qsa('[data-theme-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const next = html.classList.contains('dark') ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem(THEME_KEY, next);
      });
    });
  }

  function applyTheme(theme) {
    html.classList.toggle('dark', theme === 'dark');
    qsa('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    });
  }

  /* Mobile nav */
  function initMobileNav() {
    const toggle = qs('[data-mobile-nav-toggle]');
    const panel = qs('[data-mobile-nav]');
    if (!toggle || !panel) return;

    toggle.addEventListener('click', function () {
      const open = panel.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* Profile dropdown */
  function initProfileMenu() {
    const wrap = qs('[data-profile-menu]');
    if (!wrap) return;
    const btn = qs('[data-profile-toggle]', wrap);
    if (!btn) return;

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      wrap.classList.toggle('is-open');
    });

    doc.addEventListener('click', function () {
      wrap.classList.remove('is-open');
    });
  }

  /* Announcement dismiss */
  function initAnnouncements() {
    qsa('[data-announce]').forEach(function (bar) {
      const id = bar.getAttribute('data-announce-id');
      if (!id) return;
      try {
        const raw = localStorage.getItem(ANNOUNCE_KEY);
        const dismissed = raw ? JSON.parse(raw) : [];
        if (Array.isArray(dismissed) && dismissed.indexOf(String(id)) !== -1) {
          bar.hidden = true;
          return;
        }
      } catch (e) { /* ignore */ }

      const btn = qs('[data-announce-dismiss]', bar);
      if (!btn) return;
      btn.addEventListener('click', function () {
        bar.hidden = true;
        try {
          const raw = localStorage.getItem(ANNOUNCE_KEY);
          const dismissed = raw ? JSON.parse(raw) : [];
          const list = Array.isArray(dismissed) ? dismissed : [];
          if (list.indexOf(String(id)) === -1) list.push(String(id));
          localStorage.setItem(ANNOUNCE_KEY, JSON.stringify(list));
        } catch (e) { /* ignore */ }
      });
    });
  }

  /* Hero slider */
  function initHeroSlider() {
    const root = qs('[data-hero-slider]');
    if (!root) return;
    const slides = qsa('[data-hero-slide]', root);
    const dots = qsa('[data-hero-dot]', root);
    if (slides.length < 2) return;

    let index = 0;
    let timer;

    function go(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach(function (slide, n) {
        slide.classList.toggle('is-active', n === index);
      });
      dots.forEach(function (dot, n) {
        dot.classList.toggle('is-active', n === index);
        dot.setAttribute('aria-selected', n === index ? 'true' : 'false');
      });
    }

    function next() {
      go(index + 1);
    }

    function start() {
      stop();
      timer = setInterval(next, 6500);
    }

    function stop() {
      if (timer) clearInterval(timer);
    }

    dots.forEach(function (dot, n) {
      dot.addEventListener('click', function () {
        go(n);
        start();
      });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    start();
  }

  /* FAQ accordion */
  function initFaq() {
    qsa('[data-faq]').forEach(function (list) {
      qsa('[data-faq-item]', list).forEach(function (item) {
        const trigger = qs('[data-faq-trigger]', item);
        if (!trigger) return;
        trigger.addEventListener('click', function () {
          const open = item.classList.contains('is-open');
          qsa('[data-faq-item]', list).forEach(function (other) {
            other.classList.remove('is-open');
            const t = qs('[data-faq-trigger]', other);
            if (t) t.setAttribute('aria-expanded', 'false');
          });
          if (!open) {
            item.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
          }
        });
      });
    });
  }

  /* Search overlay + suggest */
  function initSearch() {
    const overlay = qs('[data-search-overlay]');
    const openers = qsa('[data-search-open]');
    const input = qs('[data-search-input]');
    const suggestBox = qs('[data-search-suggest]');
    const suggestUrl = overlay && overlay.getAttribute('data-suggest-url');
    if (!overlay) return;

    function open() {
      overlay.classList.add('is-open');
      setTimeout(function () {
        if (input) input.focus();
      }, 30);
    }

    function close() {
      overlay.classList.remove('is-open');
    }

    openers.forEach(function (btn) {
      btn.addEventListener('click', open);
    });

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });

    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        open();
      }
    });

    const closer = qs('[data-search-close]', overlay);
    if (closer) closer.addEventListener('click', close);

    if (!input || !suggestBox || !suggestUrl) return;

    const runSuggest = debounce(function () {
      const q = input.value.trim();
      if (q.length < 2) {
        suggestBox.innerHTML = '<div class="et-search-suggest__empty">Type at least 2 characters</div>';
        return;
      }

      suggestBox.innerHTML = '<div class="et-search-suggest__empty">Searching…</div>';
      fetch(suggestUrl + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (payload) {
          const data = payload.data || payload;
          const groups = [
            { key: 'exams', label: 'Exams' },
            { key: 'blogs', label: 'Blogs' },
            { key: 'news', label: 'News' },
            { key: 'categories', label: 'Categories' },
          ];
          let htmlOut = '';
          let count = 0;
          groups.forEach(function (g) {
            const items = data[g.key] || [];
            if (!items.length) return;
            count += items.length;
            htmlOut += '<div class="et-search-suggest__group"><div class="et-search-suggest__label">' + g.label + '</div>';
            items.forEach(function (item) {
              const href = item.url || item.href || '#';
              const title = item.title || item.name || 'Result';
              htmlOut += '<a href="' + href + '">' + escapeHtml(title) + '</a>';
            });
            htmlOut += '</div>';
          });
          suggestBox.innerHTML = count
            ? htmlOut
            : '<div class="et-search-suggest__empty">No matches found</div>';
        })
        .catch(function () {
          suggestBox.innerHTML = '<div class="et-search-suggest__empty">Unable to load suggestions</div>';
        });
    }, 280);

    input.addEventListener('input', runSuggest);
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* Newsletter AJAX */
  function initNewsletter() {
    qsa('[data-newsletter-form]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const action = form.getAttribute('action');
        const msg = qs('[data-newsletter-msg]', form);
        const btn = qs('button[type="submit"]', form);
        if (!action) return;

        const fd = new FormData(form);
        if (btn) btn.disabled = true;
        if (msg) {
          msg.textContent = '';
          msg.className = 'et-newsletter-form__msg';
        }

        fetch(action, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': fd.get('_token') || (qs('meta[name="csrf-token"]') || {}).content || '',
          },
          body: fd,
        })
          .then(function (res) {
            return res.json().then(function (json) {
              return { ok: res.ok, json: json };
            });
          })
          .then(function (result) {
            if (!msg) return;
            if (result.ok) {
              msg.textContent = (result.json && result.json.message) || 'Subscribed successfully.';
              msg.classList.add('is-ok');
              form.reset();
            } else {
              const errors = result.json && result.json.errors;
              const first = errors ? Object.values(errors)[0] : null;
              msg.textContent =
                (first && first[0]) ||
                (result.json && result.json.message) ||
                'Subscription failed. Please try again.';
              msg.classList.add('is-error');
            }
          })
          .catch(function () {
            if (msg) {
              msg.textContent = 'Network error. Please try again.';
              msg.classList.add('is-error');
            }
          })
          .finally(function () {
            if (btn) btn.disabled = false;
          });
      });
    });
  }

  doc.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initMobileNav();
    initProfileMenu();
    initAnnouncements();
    initHeroSlider();
    initFaq();
    initSearch();
    initNewsletter();
  });
})();

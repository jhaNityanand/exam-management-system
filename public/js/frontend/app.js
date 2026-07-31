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
    var actual = theme === 'dark' ? 'dark' : 'light';
    html.classList.toggle('dark', actual === 'dark');
    html.dataset.theme = actual;
    html.dataset.themeActual = actual;
    html.style.colorScheme = actual;
    html.style.backgroundColor = '';
    html.classList.add('ems-theme-ready');
    try {
      localStorage.setItem(THEME_KEY, actual);
      localStorage.setItem('ems.theme', actual);
    } catch (e) {}
    if (doc.body && doc.body.classList.contains('cx-body')) {
      doc.body.setAttribute('data-theme', actual);
    }
    qsa('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-label', actual === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('aria-pressed', actual === 'dark' ? 'true' : 'false');
    });
  }

  /* Mobile drawer */
  function initMobileNav() {
    const toggle = qs('[data-mobile-nav-toggle]');
    const drawer = qs('[data-mobile-nav]');
    const backdrop = qs('[data-mobile-nav-backdrop]');
    const closer = qs('[data-mobile-nav-close]');
    if (!toggle || !drawer) return;

    let lastFocus = null;

    function focusables() {
      return qsa('a, button, input, [tabindex]:not([tabindex="-1"])', drawer)
        .filter(function (el) { return !el.hasAttribute('disabled') && !el.hasAttribute('hidden'); });
    }

    function open() {
      lastFocus = doc.activeElement;
      drawer.hidden = false;
      if (backdrop) backdrop.hidden = false;
      requestAnimationFrame(function () {
        drawer.classList.add('is-open');
        if (backdrop) backdrop.classList.add('is-open');
      });
      toggle.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'Close menu');
      doc.body.classList.add('et-drawer-open');
      const items = focusables();
      if (items[0]) items[0].focus();
    }

    function close() {
      drawer.classList.remove('is-open');
      if (backdrop) backdrop.classList.remove('is-open');
      toggle.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open menu');
      doc.body.classList.remove('et-drawer-open');
      setTimeout(function () {
        if (!drawer.classList.contains('is-open')) {
          drawer.hidden = true;
          if (backdrop) backdrop.hidden = true;
        }
      }, 260);
      if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    function isOpen() {
      return drawer.classList.contains('is-open');
    }

    toggle.addEventListener('click', function () {
      if (isOpen()) close();
      else open();
    });
    if (closer) closer.addEventListener('click', close);
    if (backdrop) backdrop.addEventListener('click', close);

    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen()) {
        e.preventDefault();
        close();
      }
      if (e.key === 'Tab' && isOpen()) {
        const items = focusables();
        if (!items.length) return;
        const first = items[0];
        const last = items[items.length - 1];
        if (e.shiftKey && doc.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && doc.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    });

    qsa('[data-drawer-accordion]').forEach(function (group) {
      const trigger = qs('[data-drawer-accordion-trigger]', group);
      const panel = qs('[data-drawer-accordion-panel]', group);
      if (!trigger || !panel) return;
      trigger.addEventListener('click', function () {
        const openAcc = group.classList.toggle('is-open');
        panel.hidden = !openAcc;
        trigger.setAttribute('aria-expanded', openAcc ? 'true' : 'false');
      });
    });
  }

  /* Desktop nav dropdowns — hover + click, delayed close so submenu stays clickable */
  function initNavDropdown() {
    qsa('[data-nav-dropdown]').forEach(function (wrap) {
      const trigger = qs('[data-nav-dropdown-trigger]', wrap);
      const panel = qs('[data-nav-dropdown-panel]', wrap);
      if (!trigger || !panel) return;

      let closeTimer = null;

      function open() {
        if (closeTimer) {
          clearTimeout(closeTimer);
          closeTimer = null;
        }
        // Close other open dropdowns
        qsa('[data-nav-dropdown].is-open').forEach(function (other) {
          if (other !== wrap) {
            other.classList.remove('is-open');
            const otherPanel = qs('[data-nav-dropdown-panel]', other);
            const otherTrigger = qs('[data-nav-dropdown-trigger]', other);
            if (otherPanel) otherPanel.hidden = true;
            if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
          }
        });
        wrap.classList.add('is-open');
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
      }

      function close() {
        wrap.classList.remove('is-open');
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
      }

      function scheduleClose() {
        if (closeTimer) clearTimeout(closeTimer);
        closeTimer = setTimeout(close, 180);
      }

      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (wrap.classList.contains('is-open')) close();
        else open();
      });

      trigger.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          open();
          const first = qs('[role="menuitem"]', panel);
          if (first) first.focus();
        }
        if (e.key === 'Escape') close();
      });

      panel.addEventListener('keydown', function (e) {
        const items = qsa('[role="menuitem"]', panel);
        const idx = items.indexOf(doc.activeElement);
        if (e.key === 'Escape') {
          e.preventDefault();
          close();
          trigger.focus();
        }
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          const next = items[(idx + 1) % items.length];
          if (next) next.focus();
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          const prev = items[(idx - 1 + items.length) % items.length];
          if (prev) prev.focus();
        }
      });

      // Keep panel open while pointer is over trigger or panel
      wrap.addEventListener('mouseenter', open);
      wrap.addEventListener('mouseleave', scheduleClose);
      panel.addEventListener('mouseenter', open);
      panel.addEventListener('mouseleave', scheduleClose);

      // Allow clicking links without the panel vanishing mid-click
      panel.addEventListener('mousedown', function (e) {
        e.stopPropagation();
      });

      doc.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) close();
      });
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
    const prevBtn = qs('[data-hero-prev]', root);
    const nextBtn = qs('[data-hero-next]', root);
    if (!slides.length) return;

    let index = 0;
    let timer;
    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const intervalMs = 3000;

    function go(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach(function (slide, n) {
        const active = n === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        if (active) {
          slide.removeAttribute('inert');
        } else {
          slide.setAttribute('inert', '');
        }
      });
      dots.forEach(function (dot, n) {
        const active = n === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      root.setAttribute('data-active-theme', slides[index].getAttribute('data-theme') || '');
    }

    function next() {
      go(index + 1);
    }

    function start() {
      if (reduceMotion || slides.length < 2) return;
      stop();
      timer = setInterval(next, intervalMs);
    }

    function stop() {
      if (timer) clearInterval(timer);
      timer = null;
    }

    function restart() {
      stop();
      start();
    }

    dots.forEach(function (dot, n) {
      dot.addEventListener('click', function () {
        go(n);
        restart();
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        go(index - 1);
        restart();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        go(index + 1);
        restart();
      });
    }

    root.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        go(index - 1);
        restart();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        go(index + 1);
        restart();
      }
    });
    if (!root.hasAttribute('tabindex')) {
      root.setAttribute('tabindex', '0');
    }

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', function (event) {
      if (!root.contains(event.relatedTarget)) start();
    });

    let touchX = null;
    root.addEventListener('touchstart', function (event) {
      touchX = event.changedTouches[0].clientX;
      stop();
    }, { passive: true });
    root.addEventListener('touchend', function (event) {
      if (touchX == null) return;
      const dx = event.changedTouches[0].clientX - touchX;
      touchX = null;
      if (Math.abs(dx) > 40) {
        go(index + (dx < 0 ? 1 : -1));
      }
      restart();
    }, { passive: true });

    go(0);
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

    const root = qs('[data-faq-root]');
    const search = qs('[data-faq-search]');
    const empty = qs('[data-faq-empty]');
    if (!root || !search) return;

    search.addEventListener('input', function () {
      const term = String(search.value || '').trim().toLowerCase();
      let visible = 0;
      qsa('[data-faq-group]', root).forEach(function (group) {
        let groupVisible = 0;
        qsa('[data-faq-item]', group).forEach(function (item) {
          const text = item.getAttribute('data-faq-text') || '';
          const show = !term || text.indexOf(term) !== -1;
          item.hidden = !show;
          if (show) groupVisible += 1;
        });
        group.hidden = groupVisible === 0;
        visible += groupVisible;
      });
      if (empty) empty.hidden = visible > 0;
    });
  }

  /* Search overlay + suggest */
  function initSearch() {
    const overlay = qs('[data-search-overlay]');
    const openers = qsa('[data-search-open]');
    const input = qs('[data-search-input]');
    const suggestBox = qs('[data-search-suggest]');
    const suggestUrl = overlay && overlay.getAttribute('data-suggest-url');
    const panel = qs('.et-search-panel', overlay);
    if (!overlay) return;
    let lastOpener = null;

    function open(fromEl) {
      lastOpener = fromEl || document.activeElement;
      overlay.classList.add('is-open');
      openers.forEach(function (btn) {
        btn.setAttribute('aria-expanded', 'true');
      });
      setTimeout(function () {
        if (input) input.focus();
      }, 30);
    }

    function close() {
      overlay.classList.remove('is-open');
      openers.forEach(function (btn) {
        btn.setAttribute('aria-expanded', 'false');
      });
      if (lastOpener && typeof lastOpener.focus === 'function') {
        lastOpener.focus();
      }
    }

    openers.forEach(function (btn) {
      btn.addEventListener('click', function () {
        open(btn);
      });
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
      if (e.key === 'Tab' && overlay.classList.contains('is-open') && panel) {
        const focusable = qsa('a, button, input, [tabindex]:not([tabindex="-1"])', panel)
          .filter(function (el) { return !el.hasAttribute('disabled') && el.offsetParent !== null; });
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && doc.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && doc.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
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

      suggestBox.innerHTML = `
        <div class="et-search-suggest__skeleton" aria-busy="true" aria-live="polite">
          <div class="et-search-suggest__skel-line et-search-suggest__skel-line--lg"></div>
          <div class="et-search-suggest__skel-line"></div>
          <div class="et-search-suggest__skel-line et-search-suggest__skel-line--md"></div>
        </div>
      `;
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
            { key: 'questions', label: 'Questions' },
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

        const run = function () {
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
              if (!msg) return;
              msg.textContent = 'Subscription failed. Please try again.';
              msg.classList.add('is-error');
            })
            .finally(function () {
              if (btn) btn.disabled = false;
            });
        };

        const cfg = window.ExamtubeRecaptcha;
        const tokenInput = qs('[data-et-recaptcha-token]', form);
        if (cfg && cfg.enabled && cfg.version === 'v3' && tokenInput && window.grecaptcha) {
          window.grecaptcha.ready(function () {
            window.grecaptcha
              .execute(cfg.site_key, { action: 'newsletter' })
              .then(function (token) {
                tokenInput.value = token;
                run();
              })
              .catch(function () {
                if (msg) {
                  msg.textContent = 'reCAPTCHA failed. Please try again.';
                  msg.classList.add('is-error');
                }
              });
          });
          return;
        }

        run();
      });
    });
  }

  /* Category group slider — one full group at a time, pause, then slide */
  function initCatSlider() {
    qsa('[data-cat-slider]').forEach(function (root) {
      const track = qs('[data-cat-slider-track]', root);
      const groups = qsa('[data-cat-slider-group]', root);
      const dots = qsa('[data-cat-slider-dot]', root);
      const prevBtn = qs('[data-cat-slider-prev]', root);
      const nextBtn = qs('[data-cat-slider-next]', root);
      if (!track || groups.length < 2) return;

      const pauseMs = Number(root.getAttribute('data-pause-ms') || 2000);
      const slideMs = Number(root.getAttribute('data-slide-ms') || 650);
      let index = 0;
      let timer = null;
      let locked = false;
      const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      track.style.transition = 'transform ' + slideMs + 'ms cubic-bezier(0.22, 1, 0.36, 1)';

      function render() {
        track.style.transform = 'translateX(-' + index * 100 + '%)';
        groups.forEach(function (group, i) {
          group.classList.toggle('is-active', i === index);
        });
        dots.forEach(function (dot, i) {
          const active = i === index;
          dot.classList.toggle('is-active', active);
          dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
      }

      function go(next) {
        if (locked) return;
        locked = true;
        index = (next + groups.length) % groups.length;
        render();
        setTimeout(function () {
          locked = false;
        }, slideMs + 40);
      }

      function stop() {
        if (timer) {
          clearTimeout(timer);
          timer = null;
        }
      }

      function schedule() {
        stop();
        if (reduceMotion) return;
        timer = setTimeout(function () {
          go(index + 1);
          schedule();
        }, pauseMs + slideMs);
      }

      if (prevBtn) {
        prevBtn.addEventListener('click', function () {
          go(index - 1);
          schedule();
        });
      }
      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          go(index + 1);
          schedule();
        });
      }
      dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () {
          go(i);
          schedule();
        });
      });

      root.addEventListener('mouseenter', stop);
      root.addEventListener('mouseleave', schedule);
      root.addEventListener('focusin', stop);
      root.addEventListener('focusout', function (e) {
        if (!root.contains(e.relatedTarget)) schedule();
      });

      render();
      schedule();
    });
  }

  /* Sticky header shadow */
  function initStickyHeader() {
    const header = qs('[data-sticky-header]');
    if (!header) return;
    function onScroll() {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* Back to top */
  function initBackToTop() {
    const btn = qs('[data-back-top]');
    if (!btn) return;

    function sync() {
      const show = window.scrollY > 320;
      btn.classList.toggle('is-visible', show);
      btn.hidden = !show;
    }

    btn.addEventListener('click', function () {
      const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });

    sync();
    window.addEventListener('scroll', sync, { passive: true });
  }

  /* Legal accordion (Privacy / Terms) + TOC sync */
  function initLegalAccordion() {
    const layouts = qsa('.et-legal-layout');
    if (!layouts.length) return;

    function headerOffset() {
      const header = qs('.et-header') || qs('header');
      return (header ? header.getBoundingClientRect().height : 72) + 16;
    }

    function setOpen(root, item, shouldOpen) {
      const trigger = qs('[data-legal-trigger]', item);
      const panel = qs('.et-legal-card__panel', item);
      if (!trigger || !panel) return;

      if (shouldOpen) {
        item.classList.add('is-open');
        item.setAttribute('data-open', '');
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
      } else {
        item.classList.remove('is-open');
        item.removeAttribute('data-open');
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
      }
    }

    function closeAll(root) {
      qsa('[data-legal-item]', root).forEach(function (item) {
        setOpen(root, item, false);
      });
    }

    function syncToc(layout, activeId) {
      qsa('[data-legal-nav]', layout).forEach(function (link) {
        const target = link.getAttribute('href') || '';
        const id = target.charAt(0) === '#' ? target.slice(1) : '';
        const isActive = id && id === activeId;
        link.classList.toggle('is-active', isActive);
        if (isActive) link.setAttribute('aria-current', 'true');
        else link.removeAttribute('aria-current');
      });
    }

    function activateItem(layout, root, item, opts) {
      opts = opts || {};
      if (!item) return;
      closeAll(root);
      setOpen(root, item, true);
      qsa('[data-legal-item]', root).forEach(function (card) {
        card.classList.remove('is-highlighted');
      });
      item.classList.add('is-highlighted');
      syncToc(layout, item.id || '');

      if (opts.updateHash !== false && item.id) {
        if (history.replaceState) {
          history.replaceState(null, '', '#' + item.id);
        } else {
          location.hash = item.id;
        }
      }

      if (opts.scroll !== false) {
        window.requestAnimationFrame(function () {
          const top = item.getBoundingClientRect().top + window.pageYOffset - headerOffset();
          window.scrollTo({ top: Math.max(0, top), behavior: opts.smooth === false ? 'auto' : 'smooth' });
        });
      }
    }

    layouts.forEach(function (layout) {
      const root = qs('[data-legal-accordion]', layout);
      if (!root) return;

      // Initial open state from markup
      qsa('[data-legal-item]', root).forEach(function (item) {
        if (item.hasAttribute('data-open')) {
          setOpen(root, item, true);
          item.classList.add('is-highlighted');
          syncToc(layout, item.id || '');
        }
      });

      // Accordion triggers
      qsa('[data-legal-item]', root).forEach(function (item) {
        const trigger = qs('[data-legal-trigger]', item);
        if (!trigger) return;
        trigger.addEventListener('click', function () {
          const alreadyOpen = item.classList.contains('is-open');
          if (alreadyOpen) {
            setOpen(root, item, false);
            item.classList.remove('is-highlighted');
            syncToc(layout, '');
            return;
          }
          activateItem(layout, root, item, { scroll: false, updateHash: true });
        });
      });

      // Sidebar TOC links
      qsa('[data-legal-nav]', layout).forEach(function (link) {
        link.addEventListener('click', function (event) {
          const href = link.getAttribute('href') || '';
          if (href.charAt(0) !== '#') return;
          const id = href.slice(1);
          const item = id ? doc.getElementById(id) : null;
          if (!item || !root.contains(item)) return;
          event.preventDefault();
          activateItem(layout, root, item, { scroll: true, updateHash: true, smooth: true });
        });
      });

      // Deep-link / hash on load
      const hashId = (location.hash || '').replace(/^#/, '');
      if (hashId) {
        const hashItem = doc.getElementById(hashId);
        if (hashItem && root.contains(hashItem)) {
          activateItem(layout, root, hashItem, { scroll: true, updateHash: false, smooth: false });
        }
      }
    });
  }

  function initContactForm() {
    qsa('[data-contact-form]').forEach(function (form) {
      const fields = qsa('[data-validate]', form);

      function clearFieldError(field) {
        field.classList.remove('is-invalid');
        const err = qs('[data-error-for="' + field.name + '"]', form);
        if (err) {
          err.hidden = true;
          err.textContent = '';
        }
      }

      function setFieldError(field, message) {
        field.classList.add('is-invalid');
        const err = qs('[data-error-for="' + field.name + '"]', form);
        if (err) {
          err.hidden = false;
          err.textContent = message;
        }
      }

      function validateField(field) {
        const rules = (field.getAttribute('data-validate') || '').split('|').filter(Boolean);
        const value = (field.value || '').trim();
        let max = null;

        for (let i = 0; i < rules.length; i += 1) {
          const rule = rules[i];
          if (rule === 'required' && value === '') {
            return field.name === 'email'
              ? 'Please enter your email address.'
              : field.name === 'message'
                ? 'Please enter your message.'
                : 'Please enter your name.';
          }
          if (rule === 'email' && value !== '') {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
              return 'Please enter a valid email address.';
            }
          }
          if (rule.indexOf('max:') === 0) {
            max = parseInt(rule.slice(4), 10);
            if (!Number.isNaN(max) && value.length > max) {
              return 'This field may not be greater than ' + max + ' characters.';
            }
          }
        }
        return null;
      }

      fields.forEach(function (field) {
        field.addEventListener('input', function () {
          clearFieldError(field);
        });
        field.addEventListener('blur', function () {
          const message = validateField(field);
          if (message) setFieldError(field, message);
          else clearFieldError(field);
        });
      });

      form.addEventListener('submit', function (event) {
        let firstInvalid = null;
        fields.forEach(function (field) {
          const message = validateField(field);
          if (message) {
            setFieldError(field, message);
            if (!firstInvalid) firstInvalid = field;
          } else {
            clearFieldError(field);
          }
        });
        if (firstInvalid) {
          event.preventDefault();
          firstInvalid.focus();
        }
      });
    });
  }

  doc.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initMobileNav();
    initNavDropdown();
    initStickyHeader();
    initBackToTop();
    initAnnouncements();
    initHeroSlider();
    initFaq();
    initLegalAccordion();
    initContactForm();
    initSearch();
    initNewsletter();
    initCatSlider();
  });
})();

/**
 * Exam detail page: return-url storage, attempt accordion, feedback reload.
 */
(function () {
  'use strict';

  function initReturnLinks() {
    var store = (window.EmsFrontend && window.EmsFrontend.storeReturnUrl) || function (url) {
      if (!url) return;
      try {
        localStorage.setItem('ems_exam_return_url', url);
      } catch (e) {}
      document.cookie =
        'ems_exam_return_url=' + encodeURIComponent(url) + '; path=/; max-age=7200; SameSite=Lax';
    };

    document.querySelectorAll('.js-store-return').forEach(function (el) {
      el.addEventListener('click', function () {
        store(el.getAttribute('data-return-url'));
      });
    });
  }

  function initAttemptToggles() {
    document.querySelectorAll('[data-pa-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var card = btn.closest('[data-pa-card]');
        if (!card) return;
        var panelId = btn.getAttribute('aria-controls');
        var panel = panelId
          ? document.getElementById(panelId)
          : card.querySelector('.pa-card__body');
        var label = btn.querySelector('[data-pa-toggle-label]');
        var open = card.classList.contains('is-collapsed');

        card.classList.toggle('is-collapsed', !open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (panel) {
          if (open) panel.removeAttribute('hidden');
          else panel.setAttribute('hidden', 'hidden');
        }
        if (label) label.textContent = open ? 'Hide details' : 'Show details';
      });
    });
  }

  function initFeedbackReload() {
    var feedback = document.getElementById('exam-feedback');
    if (!feedback) return;
    feedback.addEventListener('feedback:submitted', function () {
      window.setTimeout(function () {
        window.location.reload();
      }, 800);
    });
  }

  function init() {
    initReturnLinks();
    initAttemptToggles();
    initFeedbackReload();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

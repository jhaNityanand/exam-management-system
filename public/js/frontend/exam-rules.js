/**
 * Exam rules page: live clock + agree checkbox gating for payment / continue.
 */
(function () {
  'use strict';

  function initClock() {
    var el = document.getElementById('cx-current-time');
    if (!el) return;
    window.setInterval(function () {
      el.textContent = new Date().toLocaleString();
    }, 1000);
  }

  function initAgreeGate() {
    var wrap = document.getElementById('cx-rules-actions');
    var checkbox = document.getElementById('cx-rules-agree');
    if (!wrap || !checkbox) return;

    var gated = Array.prototype.slice.call(wrap.querySelectorAll('[data-cx-rules-gate]'));
    if (!gated.length) return;

    var agreeUrl = wrap.getAttribute('data-agree-url') || '';
    var csrf =
      (window.EmsFrontend && window.EmsFrontend.csrfToken && window.EmsFrontend.csrfToken()) ||
      ((document.querySelector('meta[name="csrf-token"]') || {}).content || '');

    function setEnabled(enabled) {
      gated.forEach(function (el) {
        if (el.tagName === 'BUTTON') {
          el.disabled = !enabled;
        }
        if (enabled) {
          el.removeAttribute('aria-disabled');
          el.removeAttribute('tabindex');
          el.classList.remove('is-disabled');
        } else {
          el.setAttribute('aria-disabled', 'true');
          if (el.tagName === 'A') el.setAttribute('tabindex', '-1');
          el.classList.add('is-disabled');
        }
      });
    }

    setEnabled(checkbox.checked);

    gated.forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!checkbox.checked) {
          e.preventDefault();
          e.stopPropagation();
          checkbox.focus();
        }
      });
    });

    checkbox.addEventListener('change', function () {
      var agreed = checkbox.checked;
      setEnabled(agreed);
      if (!agreeUrl) return;

      var post =
        (window.EmsFrontend && window.EmsFrontend.postJson) ||
        function (url, body) {
          return fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              Accept: 'application/json',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(body || {}),
          });
        };

      post(agreeUrl, { agreed: agreed }).catch(function () {});
    });
  }

  function init() {
    initClock();
    initAgreeGate();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

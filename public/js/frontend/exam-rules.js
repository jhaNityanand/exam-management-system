/**
 * Exam rules page: live clock + agree checkbox gating.
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
    var continueBtn = document.getElementById('cx-rules-continue');
    if (!wrap || !checkbox || !continueBtn) return;

    var agreeUrl = wrap.getAttribute('data-agree-url') || '';
    var csrf =
      (window.EmsFrontend && window.EmsFrontend.csrfToken && window.EmsFrontend.csrfToken()) ||
      ((document.querySelector('meta[name="csrf-token"]') || {}).content || '');

    function setContinueEnabled(enabled) {
      if (enabled) {
        continueBtn.removeAttribute('aria-disabled');
        continueBtn.removeAttribute('tabindex');
        continueBtn.classList.remove('is-disabled');
      } else {
        continueBtn.setAttribute('aria-disabled', 'true');
        continueBtn.setAttribute('tabindex', '-1');
        continueBtn.classList.add('is-disabled');
      }
    }

    setContinueEnabled(checkbox.checked);

    continueBtn.addEventListener('click', function (e) {
      if (!checkbox.checked) {
        e.preventDefault();
        checkbox.focus();
      }
    });

    checkbox.addEventListener('change', function () {
      var agreed = checkbox.checked;
      setContinueEnabled(agreed);
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

/**
 * Placeholder exam purchase button handler.
 * Expects button#purchase-exam-btn or [data-exam-purchase] with data-url,
 * optional data-redirect / data-reload="1".
 */
(function () {
  'use strict';

  function bind(btn) {
    if (!btn || btn.dataset.emsPurchaseBound === '1') return;
    btn.dataset.emsPurchaseBound = '1';

    btn.addEventListener('click', function () {
      var utils = window.EmsFrontend || {};
      var url = btn.getAttribute('data-url');
      if (!url) return;

      var confirmFn = utils.confirmAction || function () {
        return Promise.resolve(window.confirm('Proceed with placeholder payment for this exam?'));
      };

      confirmFn({
        title: 'Complete payment?',
        text: 'Proceed with placeholder payment for this exam?',
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel',
        fallbackConfirm: 'Proceed with placeholder payment for this exam?',
      }).then(function (confirmed) {
        if (!confirmed) return;

        var post = utils.postJson || function (u) {
          return fetch(u, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
              Accept: 'application/json',
              'Content-Type': 'application/json',
            },
            body: '{}',
          });
        };

        return post(url, {}).then(function (res) {
          if (res.ok) {
            if (btn.getAttribute('data-reload') === '1') {
              window.location.reload();
              return;
            }
            var redirect = btn.getAttribute('data-redirect');
            if (redirect) {
              window.location.href = redirect;
              return;
            }
            window.location.reload();
            return;
          }

          var err = utils.showError || function (msg) {
            window.alert(msg);
          };
          err('Unable to complete placeholder payment.', 'Payment failed');
        }).catch(function () {
          var err = utils.showError || function (msg) {
            window.alert(msg);
          };
          err('Unable to complete placeholder payment.', 'Payment failed');
        });
      });
    });
  }

  function init() {
    document.querySelectorAll('#purchase-exam-btn, #rules-purchase-btn, [data-exam-purchase]').forEach(bind);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

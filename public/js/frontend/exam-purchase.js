/**
 * Placeholder exam purchase button handler.
 *
 * Modular demo checkout: confirmation modal → POST to purchase URL → entitlement.
 * Replace the POST target / service later with a real payment gateway without
 * changing button markup (data-exam-purchase + data-url).
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
        return Promise.resolve(window.confirm(
          'Payment gateway integration is currently under development. For the demo version, click OK to simulate a successful payment.'
        ));
      };

      confirmFn({
        title: 'Simulate payment?',
        text: 'Payment gateway integration is currently under development. For the demo version, click the button below to simulate a successful payment.',
        confirmButtonText: 'Simulate Payment Success',
        cancelButtonText: 'Cancel',
        fallbackConfirm: 'Payment gateway integration is currently under development. Simulate a successful payment?',
        icon: 'info',
      }).then(function (confirmed) {
        if (!confirmed) return;

        if (btn.disabled) return;
        btn.disabled = true;

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
            var ok = utils.showSuccess || function (msg) { window.alert(msg); };
            return Promise.resolve(ok('Payment simulated successfully. You can now attempt the exam.', 'Payment recorded')).then(function () {
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
            });
          }

          btn.disabled = false;
          var err = utils.showError || function (msg) { window.alert(msg); };
          err('Unable to simulate payment. Please try again.', 'Payment failed');
        }).catch(function () {
          btn.disabled = false;
          var err = utils.showError || function (msg) { window.alert(msg); };
          err('Unable to simulate payment. Please try again.', 'Payment failed');
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

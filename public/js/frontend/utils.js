/**
 * Shared frontend helpers for Examtube public pages.
 */
(function (window) {
  'use strict';

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function showError(message, title) {
    title = title || 'Something went wrong';
    if (window.EmsToast && typeof window.EmsToast.error === 'function') {
      window.EmsToast.error(message);
      return;
    }
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({ icon: 'error', title: title, text: message });
      return;
    }
    window.alert(message);
  }

  function showSuccess(message, title) {
    title = title || 'Success';
    if (window.EmsToast && typeof window.EmsToast.success === 'function') {
      window.EmsToast.success(message);
      return Promise.resolve();
    }
    if (window.Swal && typeof window.Swal.fire === 'function') {
      return window.Swal.fire({ icon: 'success', title: title, text: message, timer: 2200, showConfirmButton: true });
    }
    window.alert(message);
    return Promise.resolve();
  }

  function confirmAction(options) {
    options = options || {};
    var title = options.title || 'Are you sure?';
    var text = options.text || '';
    var confirmButtonText = options.confirmButtonText || 'Continue';
    var cancelButtonText = options.cancelButtonText || 'Cancel';
    var fallback = options.fallbackConfirm || text || title;

    if (window.Swal && typeof window.Swal.fire === 'function') {
      return window.Swal.fire({
        title: title,
        text: text,
        icon: options.icon || 'question',
        showCancelButton: true,
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText,
        reverseButtons: true,
      }).then(function (result) {
        return !!(result && result.isConfirmed);
      });
    }

    return Promise.resolve(window.confirm(fallback));
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body || {}),
    });
  }

  function storeReturnUrl(url) {
    if (!url) return;
    try {
      localStorage.setItem('ems_exam_return_url', url);
    } catch (e) {}
    document.cookie =
      'ems_exam_return_url=' +
      encodeURIComponent(url) +
      '; path=/; max-age=7200; SameSite=Lax';
  }

  window.EmsFrontend = Object.assign({}, window.EmsFrontend || {}, {
    csrfToken: csrfToken,
    showError: showError,
    showSuccess: showSuccess,
    confirmAction: confirmAction,
    postJson: postJson,
    storeReturnUrl: storeReturnUrl,
  });
})(window);

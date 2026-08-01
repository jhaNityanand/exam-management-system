/**
 * Mark document theme-ready after load (avoids FOUC flash).
 * Optional: sync body[data-theme] for candidate exam shell (data-sync-body="1").
 */
(function () {
  'use strict';

  var script = document.currentScript;
  var syncBody = script && script.getAttribute('data-sync-body') === '1';

  if (syncBody) {
    var actual =
      (document.documentElement.dataset.themeActual ||
        document.documentElement.dataset.theme ||
        'light') === 'dark'
        ? 'dark'
        : 'light';
    if (document.body) {
      document.body.setAttribute('data-theme', actual);
    }
    document.documentElement.classList.toggle('dark', actual === 'dark');
    document.documentElement.dataset.theme = actual;
    document.documentElement.dataset.themeActual = actual;
    document.documentElement.style.colorScheme = actual;
  }

  function markThemeReady() {
    document.documentElement.classList.add('ems-theme-ready');
    document.documentElement.style.backgroundColor = '';
  }

  if (document.readyState === 'complete') markThemeReady();
  else window.addEventListener('load', markThemeReady);
})();

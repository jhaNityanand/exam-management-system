/**
 * Standalone theme toggle for shells that do not load app.js (e.g. guest/auth).
 */
(function () {
  'use strict';

  var THEME_KEY = 'examtube-theme';
  var html = document.documentElement;

  function applyTheme(theme) {
    var actual = theme === 'dark' ? 'dark' : 'light';
    html.classList.toggle('dark', actual === 'dark');
    html.dataset.theme = actual;
    html.dataset.themeActual = actual;
    html.style.colorScheme = actual;
    html.classList.add('ems-theme-ready');
    try {
      localStorage.setItem(THEME_KEY, actual);
      localStorage.setItem('ems.theme', actual);
    } catch (e) {}
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-label', actual === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('aria-pressed', actual === 'dark' ? 'true' : 'false');
    });
  }

  function init() {
    var stored = null;
    try {
      stored = localStorage.getItem(THEME_KEY) || localStorage.getItem('ems.theme');
    } catch (e) {}
    applyTheme(stored || 'light');

    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      if (btn.dataset.themeBound === '1') return;
      btn.dataset.themeBound = '1';
      btn.addEventListener('click', function () {
        applyTheme(html.classList.contains('dark') ? 'light' : 'dark');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

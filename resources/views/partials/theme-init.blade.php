{{-- Blocking script: apply theme before paint to prevent FOUC --}}
<script>
(function () {
    var KEY = 'ems.theme';
    var stored = null;
    try { stored = localStorage.getItem(KEY); } catch (e) {}
    var fallback = document.documentElement.dataset.themeDefault || 'system';
    var theme = stored || fallback;
    var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    var root = document.documentElement;
    root.classList.toggle('dark', dark);
    root.dataset.theme = theme;
    root.style.colorScheme = dark ? 'dark' : 'light';
})();
</script>

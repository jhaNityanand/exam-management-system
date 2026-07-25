{{-- Blocking: apply theme before CSS/paint to prevent FOIT/FOUC on frontend + backend.
     @param string $themeStorageKey  primary localStorage key (ems.theme | examtube-theme)
     @param string $themeResolveMode preference = keep system|light|dark on dataset;
                                     resolved  = dataset is always light|dark
--}}
@php
    $themeStorageKey = $themeStorageKey ?? 'ems.theme';
    $themeResolveMode = $themeResolveMode ?? 'preference';
@endphp
<script>
(function () {
    var KEY = @json($themeStorageKey);
    var ALT = KEY === 'ems.theme' ? 'examtube-theme' : 'ems.theme';
    var MODE = @json($themeResolveMode);
    var stored = null;
    try { stored = localStorage.getItem(KEY); } catch (e) {}
    if (!stored) {
        try { stored = localStorage.getItem(ALT); } catch (e) {}
    }

    var preference = stored || document.documentElement.dataset.themeDefault || 'system';
    if (preference !== 'light' && preference !== 'dark' && preference !== 'system') {
        preference = 'system';
    }

    var prefersDark = false;
    try {
        prefersDark = !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    } catch (e) {}

    var actual = preference === 'system' ? (prefersDark ? 'dark' : 'light') : preference;
    var root = document.documentElement;

    root.classList.toggle('dark', actual === 'dark');
    root.dataset.theme = MODE === 'resolved' ? actual : preference;
    root.dataset.themeActual = actual;
    root.style.colorScheme = actual;
    root.style.backgroundColor = actual === 'dark' ? '#0b1220' : '#f8fafc';

    try {
        if (!localStorage.getItem(KEY)) localStorage.setItem(KEY, MODE === 'resolved' ? actual : preference);
        if (!localStorage.getItem(ALT)) localStorage.setItem(ALT, actual);
    } catch (e) {}

    window.__emsTheme = { key: KEY, preference: preference, actual: actual };
    window.__examtubeTheme = actual;
})();
</script>
<style id="ems-theme-critical">
    html { margin: 0; }
    html.dark {
        background-color: #0b1220;
        color-scheme: dark;
    }
    html:not(.dark) {
        background-color: #f8fafc;
        color-scheme: light;
    }
    html:not(.ems-theme-ready).dark body {
        background-color: #0b1220 !important;
        color: #e2e8f0;
    }
    html:not(.ems-theme-ready):not(.dark) body {
        background-color: #f8fafc !important;
    }
    /* Full-page / overlay loaders before main CSS */
    .exam-page-loader {
        background: rgba(248, 250, 252, 0.94) !important;
        color: #0f172a;
    }
    html.dark .exam-page-loader {
        background: rgba(15, 23, 42, 0.94) !important;
        color: #e2e8f0;
    }
    .cx-page-boot {
        background: #f8fafc !important;
    }
    html.dark .cx-page-boot {
        background: #0b1220 !important;
    }
    .table-loading-overlay {
        background-color: rgba(255, 255, 255, 0.65);
    }
    html.dark .table-loading-overlay {
        background-color: rgba(15, 23, 42, 0.65);
    }
</style>

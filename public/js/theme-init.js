// Run before first paint; an external script works with the production CSP.
(function () {
    var stored = null;
    try {
        stored = localStorage.getItem('theme');
    } catch (_) {
        // Storage may be unavailable in restricted browser contexts.
    }
    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var isDark = stored === 'dark' || (stored !== 'light' && prefersDark);
    document.documentElement.classList.toggle('dark', isDark);
})();

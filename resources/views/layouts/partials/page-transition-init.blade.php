<script>
    (function () {
        try {
            var key = 'app_nav_progressive_pending';
            var tsKey = key + '_ts';
            var isPending = sessionStorage.getItem(key) === '1';
            var ts = Number(sessionStorage.getItem(tsKey) || 0);
            var isFresh = Number.isFinite(ts) && ts > 0 && (Date.now() - ts) < 20000;

            if (isPending && isFresh) {
                document.documentElement.classList.add('page-transition-pending');
                document.documentElement.setAttribute('data-page-transition', 'pending');
            }
        } catch (_) {
            // Ignore storage restriction in private mode.
        }
    })();
</script>

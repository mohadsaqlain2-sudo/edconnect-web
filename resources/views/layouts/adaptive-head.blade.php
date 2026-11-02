{{-- Phase 3F: capability attributes before the first paint.
     resources/js/adaptive.js keeps these up to date; this tiny script only
     avoids a one-frame flash of desktop chrome on a phone / slow connection. --}}
<script>
    (function () {
        try {
            var d = document.documentElement;
            var w = window.innerWidth || d.clientWidth;
            var ua = navigator.userAgent || '';
            var touch = window.matchMedia('(pointer: coarse)').matches
                || (navigator.maxTouchPoints > 0 && !window.matchMedia('(hover: hover)').matches);
            var tv = window.matchMedia('(min-width: 2200px)').matches
                || /smart-?tv|smarttv|googletv|appletv|hbbtv|netcast|viera|bravia|tizen|webos|crkey|roku|firetv|android tv/i.test(ua);

            var device = tv
                ? 'tv'
                : (w <= 575.98
                    ? 'mobile'
                    : (w <= 1199.98 && (touch || /ipad|tablet|playbook|silk|kindle/i.test(ua))
                        ? 'tablet'
                        : 'desktop'));

            var c = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            var slow = !!c && (!!c.saveData
                || (typeof c.downlink === 'number' && c.downlink > 0 && c.downlink < 1.5)
                || /(^|-)2g$|^3g$|slow-2g/i.test(String(c.effectiveType || '')));

            d.dataset.device = device;
            d.dataset.input = touch ? 'touch' : 'mouse';
            d.dataset.orientation = window.innerHeight >= window.innerWidth ? 'portrait' : 'landscape';
            d.dataset.network = navigator.onLine && slow ? 'slow' : 'fast';
            d.dataset.online = navigator.onLine ? 'true' : 'false';
            d.dataset.saveData = c && c.saveData ? 'true' : 'false';
            d.dataset.standalone = (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone)
                ? 'true'
                : 'false';
            d.dataset.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'true' : 'false';

            var storedTheme = null;
            try {
                storedTheme = window.localStorage.getItem('educonnect.theme');
            } catch (error) {
                storedTheme = null;
            }

            d.dataset.theme = storedTheme === 'dark' || storedTheme === 'light'
                ? storedTheme
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            try {
                if (window.localStorage.getItem('educonnect.largeText') === '1') {
                    d.classList.add('edu-large-text');
                }
            } catch (error) {
                // Private mode: default text size.
            }
        } catch (error) {
            // The document still works with CSS media queries alone.
        }
    })();
</script>

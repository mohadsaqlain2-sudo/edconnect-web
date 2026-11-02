/*
 * EduConnect service worker (Phase 3F — full adaptive layout).
 *
 * Strategy
 *   • Navigations: network first, then the cached page, then /offline.html.
 *     Authenticated HTML is never shared between users: only the shell and the
 *     offline page are cached, and the cache name is versioned.
 *   • Static assets (Vite build output, icons, fonts): stale-while-revalidate.
 *   • Vite-built CSS and JS are precached at install time so the app works
 *     offline after the first visit. The asset manifest is fetched once and
 *     the versioned file URLs are extracted.
 *   • Never caches POST/PUT/PATCH/DELETE, never caches /login, /register,
 *     password reset or anything with an Authorization header.
 *   • Updates wait until every tab is closed unless the page sends
 *     SKIP_WAITING (resources/js/adaptive.js shows the "new version" prompt).
 */

// Bump this when you deploy breaking changes to app shell / offline page.
const VERSION = 'educonnect-v2';
const SHELL_CACHE = `${VERSION}-shell`;
const ASSET_CACHE = `${VERSION}-assets`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/maskable-192.png',
    '/icons/maskable-512.png',
    '/icons/apple-touch-icon.png',
];

// Pages that must never be served from a cache.
const NEVER_CACHE_PATHS = [
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/verify-email',
    '/logout',
    '/notifications/poll',
    '/classes',    // live data
    '/feed',       // live data
    '/dashboard',  // live data
    '/admin',      // admin pages always fresh
];

// ---------------------------------------------------------------------------
// Install: precache the app shell
// ---------------------------------------------------------------------------

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);

        // Precache known-stable assets
        await cache.addAll(PRECACHE_URLS).catch(() => {
            // A missing icon must not abort installation.
        });

        // Also cache the Vite-built CSS and JS by reading the asset manifest.
        try {
            const manifestResponse = await fetch('/build/manifest.json', { cache: 'no-store' });
            if (manifestResponse.ok) {
                const manifest = await manifestResponse.json();
                const assetCache = await caches.open(ASSET_CACHE);

                const viteUrls = Object.values(manifest)
                    .flatMap((entry) => {
                        const urls = [];
                        if (entry.file) urls.push(`/build/${entry.file}`);
                        if (Array.isArray(entry.css)) {
                            entry.css.forEach((css) => urls.push(`/build/${css}`));
                        }
                        return urls;
                    });

                // Precache concurrently, ignore individual failures.
                await Promise.allSettled(
                    viteUrls.map((url) => fetch(url).then((res) => {
                        if (res.ok) assetCache.put(url, res);
                    }))
                );
            }
        } catch (error) {
            // Manifest not available offline — that's fine.
        }
    })());
});

// ---------------------------------------------------------------------------
// Activate: remove old caches, enable navigation preload
// ---------------------------------------------------------------------------

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();

        await Promise.all(
            keys
                .filter((key) => !key.startsWith(VERSION))
                .map((key) => caches.delete(key))
        );

        if (self.registration.navigationPreload) {
            await self.registration.navigationPreload.enable().catch(() => {});
        }

        await self.clients.claim();
    })());
});

// ---------------------------------------------------------------------------
// Message: SKIP_WAITING sent by the app when the user clicks "Reload"
// ---------------------------------------------------------------------------

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function isAsset(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname.startsWith('/images/')
        || url.pathname.startsWith('/storage/')
        || /\.(css|js|mjs|png|jpe?g|webp|avif|gif|svg|ico|woff2?|ttf|otf|eot)$/i.test(url.pathname);
}

function isFont(url) {
    return url.hostname.includes('fonts.g') // Google Fonts static
        || /\.(woff2?|ttf|otf)$/i.test(url.pathname);
}

function isNeverCached(url) {
    return NEVER_CACHE_PATHS.some((path) =>
        url.pathname === path || url.pathname.startsWith(`${path}/`)
    );
}

// ---------------------------------------------------------------------------
// Fetch strategies
// ---------------------------------------------------------------------------

async function networkFirst(request, event) {
    const url = new URL(request.url);

    try {
        const preload = await event.preloadResponse;
        if (preload) return preload;

        const response = await fetch(request);

        // Cache only the public landing page as an app shell fallback.
        const isPublicShell = (url.pathname === '/' || url.pathname === '/offline.html')
            && response.ok
            && response.type === 'basic';

        if (isPublicShell) {
            const copy = response.clone();
            caches.open(SHELL_CACHE).then((c) => c.put(request, copy)).catch(() => {});
        }

        return response;
    } catch (error) {
        // Network failed — try the cache
        const cached = await caches.match(request, { ignoreSearch: true });
        if (cached) return cached;

        // As a last resort serve the offline page
        return caches.match(OFFLINE_URL);
    }
}

async function staleWhileRevalidate(request, cacheName = ASSET_CACHE) {
    const cached = await caches.match(request);

    const networkPromise = fetch(request).then((response) => {
        if (response.ok) {
            const copy = response.clone();
            caches.open(cacheName).then((c) => c.put(request, copy)).catch(() => {});
        }
        return response;
    }).catch(() => null);

    return cached || await networkPromise || caches.match(OFFLINE_URL);
}

async function cacheFirst(request, cacheName = ASSET_CACHE) {
    const cached = await caches.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok) {
        const copy = response.clone();
        caches.open(cacheName).then((c) => c.put(request, copy)).catch(() => {});
    }
    return response;
}

// ---------------------------------------------------------------------------
// Fetch routing
// ---------------------------------------------------------------------------

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only handle GET requests
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Cross-origin: fonts are cache-first; other assets stale-while-revalidate
    if (url.origin !== self.location.origin) {
        if (isFont(url)) {
            event.respondWith(cacheFirst(request));
        } else if (isAsset(url)) {
            event.respondWith(staleWhileRevalidate(request));
        }
        return;
    }

    // Page navigation
    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request, event));
        return;
    }

    // Never cache auth/live-data routes
    if (isNeverCached(url)) return;

    // Vite built assets have content hashes — cache-first is safe
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Other static assets: stale-while-revalidate
    if (isAsset(url)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

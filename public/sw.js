/*
 * Coaching Institute ERP - service worker (Phase 0 skeleton).
 * Provides a minimal offline shell. Phase 10 extends this with the portal's
 * last-loaded summary cache and a clear stale-data notice.
 */
const CACHE = 'coaching-erp-shell-v1';
const SHELL = ['/offline.html', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    // Network-first for navigations, falling back to the offline shell.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );
        return;
    }
});

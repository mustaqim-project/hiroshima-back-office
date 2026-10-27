// Service Worker for Hiroshima Back Office PWA
// Cache Versioning
const CACHE_NAME = 'hiroshima-pwa-v1.0.0';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/favicon.png',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/icon-maskable-512x512.png',
    '/icons/apple-touch-icon.png'
];

// Install: Cache critical assets and activate immediately
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activate: Clean up old caches & take control of clients
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Strategy depending on request type
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // 1. Only handle GET requests (never cache POST/PUT/DELETE/mutations)
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // 2. Only handle same-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // 3. Skip API calls, live exports, and auth endpoints from caching
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.includes('/export') ||
        url.pathname.includes('/excel') ||
        url.pathname.includes('/download') ||
        url.pathname.includes('/logout')
    ) {
        return;
    }

    // 4. HTML Navigation Requests -> Network-First with Offline Fallback
    const isNavigation = request.mode === 'navigate' ||
        (request.headers.get('accept') && request.headers.get('accept').includes('text/html'));

    if (isNavigation) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    // Try to serve cached HTML page first
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Otherwise serve the branded offline fallback page
                    const offlinePage = await caches.match(OFFLINE_URL);
                    return offlinePage || new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
                })
        );
        return;
    }

    // 5. Static Assets (Images, Icons, CSS, JS, Fonts) -> Stale-While-Revalidate
    const isStaticAsset = url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|gif|webp|woff|woff2|ttf|ico|webmanifest)$/i);

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // Network error for static asset, ignore if already cached
                });

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }
});

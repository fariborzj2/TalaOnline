const CACHE_NAME = 'tala-online-v3';
const OFFLINE_URL = '/offline.php';

const ASSETS_TO_CACHE = [
    OFFLINE_URL,
    '/',
    '/assets/images/logo.svg',
    '/assets/images/favicon/favicon-32x32.png',
    '/assets/images/favicon/android-icon-192x192.png'
];

// Install event - cache core assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // For HTML requests, try network first, fallback to cache/offline page
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match(event.request).then((response) => {
                    return response || caches.match(OFFLINE_URL);
                });
            })
        );
        return;
    }

    // For other assets, try cache first, then network
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request).then((networkResponse) => {
                // Don't cache everything, just specific assets if needed
                // For now, we just return the network response
                return networkResponse;
            });
        })
    );
});

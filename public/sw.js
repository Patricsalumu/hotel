const CACHE_NAME = 'hotel-pwa-v2';
const ASSETS_TO_CACHE = [
    '/',
    '/manifest.webmanifest',
    '/pwa-h-192.png',
    '/pwa-h-512.png',
    '/apple-touch-icon.png',
    '/apple-splash-2048x2732.png',
    '/pwa-h-icon.svg',
    '/favicon.ico',
];

const STATIC_EXTENSIONS = [
    '.js',
    '.css',
    '.png',
    '.jpg',
    '.jpeg',
    '.svg',
    '.webp',
    '.ico',
    '.woff',
    '.woff2',
    '.ttf',
    '.map',
    '.json',
    '.webmanifest',
];

const isStaticAsset = (requestUrl) => STATIC_EXTENSIONS.some((ext) => requestUrl.pathname.endsWith(ext));

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => response)
                .catch(() => caches.match('/'))
        );

        return;
    }

    if (!isStaticAsset(requestUrl)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            const networkFetch = fetch(event.request)
                .then((response) => {
                    if (response && response.status === 200 && response.type === 'basic') {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
                    }

                    return response;
                });

            return cached || networkFetch;
        }).catch(() => caches.match(event.request))
    );
});

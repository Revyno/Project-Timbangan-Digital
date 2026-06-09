const CACHE_NAME = 'timbangan-pwa-v4';
const STATIC_ASSETS = [
  '/manifest.json',
  '/images/logo.webp'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;

  // We only intercept GET requests
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  const acceptHeader = request.headers.get('accept') || '';

  // Network-First for HTML/Document navigation, SPA requests, or dynamic routes
  if (
    request.mode === 'navigate' || 
    url.pathname === '/' || 
    url.pathname.startsWith('/dashboard') || 
    acceptHeader.includes('text/html') ||
    request.headers.get('x-inertia')
  ) {
    event.respondWith(
      fetch(request)
        .then(response => {
          // If successful response, save it in the cache for offline fallback
          if (response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // If network fails, serve from cache
          return caches.match(request);
        })
    );
    return;
  }

  // Cache-First for static assets (images, manifest, fonts) and Vite compiled assets (build/assets/)
  if (
    STATIC_ASSETS.includes(url.pathname) || 
    url.pathname.includes('/build/assets/') || 
    url.pathname.includes('/images/') ||
    request.destination === 'image' ||
    request.destination === 'font' ||
    request.destination === 'style' ||
    request.destination === 'script'
  ) {
    event.respondWith(
      caches.match(request)
        .then(response => {
          if (response && !response.redirected && response.type !== 'opaqueredirect') {
            return response;
          }
          return fetch(request).then(networkResponse => {
            if (networkResponse.status === 200) {
              const responseClone = networkResponse.clone();
              caches.open(CACHE_NAME).then(cache => {
                cache.put(request, responseClone);
              });
            }
            return networkResponse;
          });
        })
    );
    return;
  }

  // Fallback default: Go straight to network
  event.respondWith(fetch(request));
});

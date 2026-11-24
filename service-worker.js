const CACHE_NAME = 'freshguardai-cache-v1';
const urlsToCache = [
    './index.php',
    './assets/css/style.css',
    './assets/img/lays.png',
    './assets/img/milk.png',
    './assets/img/bread.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request).then(response => response || fetch(event.request))
    );
});

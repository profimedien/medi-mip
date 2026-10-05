// Service Worker medi Interaktionsportal: Offline-Hinweis und Push-Mitteilungen
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open('mip-v1').then((cache) => cache.addAll([OFFLINE_URL, '/images/icon-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

// Nur Seitenaufrufe abfangen: ohne Netz die Offline-Seite zeigen. Keine Inhalte cachen (Datenschutz).
self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;
    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL)));
});

self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    event.waitUntil(
        self.registration.showNotification(data.title || 'medi Interaktionsportal', {
            body: data.body || '',
            icon: '/images/icon-192.png',
            badge: '/images/icon-192.png',
            data: { url: (data.data && data.data.url) || data.url || '/' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if ('focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            return self.clients.openWindow(url);
        }),
    );
});

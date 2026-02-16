const CACHE_NAME = 'bhc-blood-sos-v1';
const ASSETS_TO_CACHE = [
    '/community/index.php',
    '/community/login.php',
    '/community/manifest.json',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://code.jquery.com/jquery-3.6.0.min.js'
];

// Offline caching for hospital data and assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

self.addEventListener('fetch', (event) => {
    // Cache-first strategy for assets, Network-first for dynamic content (hospitals)
    event.respondWith(
        fetch(event.request).then((response) => {
            // If request is successful, clone and store in cache
            if (response && response.status === 200) {
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseClone);
                });
            }
            return response;
        }).catch(() => {
            // If network fails, serve from cache
            return caches.match(event.request);
        })
    );
});

importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

// REPLACE THE BELOW CONFIG WITH YOUR OWN FIREBASE CONFIG FROM THE CONSOLE
const firebaseConfig = {
    apiKey: "AIzaSyCccrVRro89pfJKDTVSxwy6MjlhCJ4bZdA",
    authDomain: "bhc-blood-finder.firebaseapp.com",
    projectId: "bhc-blood-finder",
    storageBucket: "bhc-blood-finder.firebasestorage.app",
    messagingSenderId: "163130966032",
    appId: "1:163130966032:web:9bce71cc4a3461913cca95"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: payload.notification.icon
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

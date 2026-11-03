/**
 * Service Worker - Portail Voyageurs Carré d'Hôtes
 * Version: 1.0.0
 * Stratégies de cache :
 * - App Shell (HTML, CSS, JS, Icônes) : Stale-While-Revalidate & Pre-caching
 * - API (api.php) : Network-First avec fallback Cache (consultation hors-ligne des guides)
 */

const CACHE_VERSION = 'cdh-portail-v3.0.4';
const CACHE_NAME_STATIC = `${CACHE_VERSION}-static`;
const CACHE_NAME_API = `${CACHE_VERSION}-api`;

// Détection dynamique du périmètre (/portail/ ou /portail/dev/)
const SCOPE_PATH = new URL(self.registration.scope).pathname.replace(/\/?$/, '/');

// Ressources fondamentales du noyau PWA (App Shell & Modules)
// Note : Les manifestes dynamiques (manifest.php) ne sont pas pré-cachés pour préserver le ciblage par logement
const PRECACHE_ASSETS = [
    SCOPE_PATH,
    SCOPE_PATH + 'index.html',
    SCOPE_PATH + 'assets/css/custom.css?v=10.4',
    SCOPE_PATH + 'assets/js/app.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/state.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/utils.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/i18n.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/theme.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/scroll.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/router.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/api.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/gestures.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/pdf-viewer.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/modals.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/renderer.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/pwa.js?v=10.4',
    SCOPE_PATH + 'assets/js/modules/app-controller.js?v=10.4',
    SCOPE_PATH + 'assets/images/antho.jpg',
    SCOPE_PATH + 'assets/icons/icon-192.png',
    SCOPE_PATH + 'assets/icons/icon-512.png',
    SCOPE_PATH + 'assets/icons/apple-touch-icon.png',
    SCOPE_PATH + 'assets/icons/icon-maskable-192.png',
    SCOPE_PATH + 'assets/icons/icon-maskable-512.png',
    SCOPE_PATH + 'assets/images/picto-light.png',
    SCOPE_PATH + 'assets/images/picto-dark.png',
    SCOPE_PATH + 'assets/images/logo.svg',
    SCOPE_PATH + 'assets/images/favicon-32x32.png',
    SCOPE_PATH + 'assets/images/favicon-16x16.png',
    SCOPE_PATH + 'favicon.ico',
    'https://cdn.tailwindcss.com',
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'
];

// -----------------------------------------------------------------------------
// 1. INSTALLATION : Pré-chargement des ressources essentielles
// -----------------------------------------------------------------------------
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME_STATIC).then((cache) => {
            // Utilise addAll en tolérant les erreurs ponctuelles sur assets secondaires
            return Promise.allSettled(
                PRECACHE_ASSETS.map((url) => {
                    return cache.add(new Request(url, { cache: 'reload' })).catch((err) => {
                        console.warn('[SW] Pré-cache facultatif non disponible :', url, err.message);
                    });
                })
            );
        }).then(() => {
            return self.skipWaiting();
        })
    );
});

// -----------------------------------------------------------------------------
// 2. ACTIVATION : Nettoyage automatique des anciens caches
// -----------------------------------------------------------------------------
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME_STATIC && cacheName !== CACHE_NAME_API) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// -----------------------------------------------------------------------------
// 3. INTERCEPTION DES REQUÊTES (FETCH)
// -----------------------------------------------------------------------------
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Ne traiter que les requêtes GET HTTP/HTTPS
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Ne pas intercepter les requêtes administratives ou passerelle directe
    if (url.pathname.includes('/passerelle-cdh-8d3f7a/') || url.pathname.includes('/tools/')) {
        return;
    }

    // A. REQUÊTES API (api.php) : Network-First avec fallback Cache
    if (url.pathname.endsWith('/api.php') || url.pathname.includes('/api.php')) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const contentType = networkResponse.headers.get('content-type') || '';
                        if (contentType.includes('application/json') || contentType.includes('application/pdf')) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME_API).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                    }
                    return networkResponse;
                })
                .catch(() => {
                    // Fallback vers le cache existant pour consultation hors-ligne
                    return caches.match(request).then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // Réponse JSON hors-ligne par défaut
                        return new Response(JSON.stringify({
                            success: false,
                            error: 'Mode hors-ligne : vous n\'avez pas de connexion internet.',
                            offline: true
                        }), {
                            status: 503,
                            headers: { 'Content-Type': 'application/json; charset=utf-8' }
                        });
                    });
                })
        );
        return;
    }

    // B. MANIFESTES PWA : Toujours requêtés en direct sans cache pour garantir le slug exact du logement
    if (url.pathname.includes('/manifest.php') || url.pathname.includes('/manifest.webmanifest')) {
        event.respondWith(fetch(request));
        return;
    }

    // C. NAVIGATION SPA (Pages HTML) : Network-First avec fallback vers index.html
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(SCOPE_PATH + 'index.html').then((cachedIndex) => {
                    return cachedIndex || caches.match(SCOPE_PATH);
                });
            })
        );
        return;
    }

    // C. ASSETS STATIQUES (CSS, JS, Images, Polices) : Stale-While-Revalidate
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const fetchPromise = fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME_STATIC).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Échec réseau toléré si présent dans le cache
                return null;
            });

            return cachedResponse || fetchPromise.then((networkRes) => {
                return networkRes || new Response('', { status: 408, statusText: 'Offline asset unavailable' });
            });
        })
    );
});

// -----------------------------------------------------------------------------
// 4. MESSAGERIE : Mise à jour immédiate à la demande
// -----------------------------------------------------------------------------
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

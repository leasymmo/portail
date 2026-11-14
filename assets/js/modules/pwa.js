/**
 * ==============================================================================
 * MODULE PROGRESSIVE WEB APP (SERVICE WORKER, INSTALL PROMPT & OFFLINE)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=11.1';
import { BASE_PATH } from './router.js?v=11.1';

export const PwaManager = {
    deferredPrompt: null,
    isPromptDismissed: false,
    _registration: null,

    init() {
        if (window.__cdhDeferredPrompt) {
            this.deferredPrompt = window.__cdhDeferredPrompt;
        }
        this.registerServiceWorker();
        this.initInstallPrompt();
        this.initNetworkStatus();
    },

    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                const swUrl = BASE_PATH + 'sw.js';
                navigator.serviceWorker.register(swUrl, { scope: BASE_PATH })
                    .then((reg) => {
                        this._registration = reg;

                        reg.addEventListener('updatefound', () => {
                            const newWorker = reg.installing;
                            if (newWorker) {
                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        this.promptUpdate(newWorker);
                                    }
                                });
                            }
                        });
                    })
                    .catch((err) => {
                        console.warn('[PWA] Service Worker registration skipped :', err.message);
                    });
            });

            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
        }
    },

    promptUpdate(worker) {
        if (confirm("Une mise à jour de l'application est disponible. Voulez-vous la charger ?")) {
            worker.postMessage({ type: 'SKIP_WAITING' });
        }
    },

    initInstallPrompt() {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (isStandalone) {
            return;
        }

        const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            window.__cdhDeferredPrompt = e;
            this.showInstallBanner('native');
        });

        window.addEventListener('appinstalled', () => {
            this.deferredPrompt = null;
            window.__cdhDeferredPrompt = null;
            this.dismissPrompt();
        });

        if (this.deferredPrompt) {
            this.showInstallBanner('native');
        } else if (isIos && isSafari) {
            const hasSeenIosPrompt = localStorage.getItem('cdh_ios_prompt_dismissed');
            if (!hasSeenIosPrompt) {
                setTimeout(() => {
                    this.showInstallBanner('ios');
                }, 4000);
            }
        }
    },

    showInstallBanner(type) {
        if (this.isPromptDismissed) return;
        const banner = document.getElementById('pwa-install-banner');
        const nativeArea = document.getElementById('pwa-prompt-native');
        const iosArea = document.getElementById('pwa-prompt-ios');
        const androidManualArea = document.getElementById('pwa-prompt-android-manual');
        if (!banner) return;

        if (type === 'ios') {
            if (nativeArea) nativeArea.classList.add('hidden');
            if (androidManualArea) androidManualArea.classList.add('hidden');
            if (iosArea) iosArea.classList.remove('hidden');
        } else if (type === 'android-manual') {
            if (nativeArea) nativeArea.classList.add('hidden');
            if (iosArea) iosArea.classList.add('hidden');
            if (androidManualArea) androidManualArea.classList.remove('hidden');
        } else {
            if (nativeArea) nativeArea.classList.remove('hidden');
            if (iosArea) iosArea.classList.add('hidden');
            if (androidManualArea) androidManualArea.classList.add('hidden');
        }

        banner.classList.remove('hidden');
        requestAnimationFrame(() => {
            banner.classList.add('show');
        });
    },

    async installApp() {
        if (this.deferredPrompt) {
            try {
                this.deferredPrompt.prompt();
                const choiceResult = await this.deferredPrompt.userChoice;
                if (choiceResult && choiceResult.outcome === 'accepted') {
                    this.dismissPrompt();
                }
            } catch (err) {
                console.warn('[PWA] Erreur lors du prompt d’installation :', err);
            } finally {
                this.deferredPrompt = null;
                window.__cdhDeferredPrompt = null;
            }
        } else {
            // Mode repli Android si l'invite automatique n'est pas disponible
            const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
            if (isIos) {
                this.showInstallBanner('ios');
            } else {
                this.showInstallBanner('android-manual');
            }
        }
    },

    triggerInstall() {
        return this.installApp();
    },

    dismissPrompt() {
        this.isPromptDismissed = true;
        const banner = document.getElementById('pwa-install-banner');
        if (banner) {
            banner.classList.remove('show');
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 350);
        }
        try {
            localStorage.setItem('cdh_ios_prompt_dismissed', 'true');
        } catch (e) {}
    },

    initNetworkStatus() {
        const offlineToast = document.getElementById('offline-toast');

        const updateOnlineStatus = () => {
            AppState.isOffline = !navigator.onLine;
            if (offlineToast) {
                if (AppState.isOffline) {
                    offlineToast.classList.remove('hidden');
                    requestAnimationFrame(() => offlineToast.classList.add('show'));
                } else {
                    offlineToast.classList.remove('show');
                    setTimeout(() => offlineToast.classList.add('hidden'), 300);
                }
            }
        };

        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        if (!navigator.onLine) {
            updateOnlineStatus();
        }
    }
};

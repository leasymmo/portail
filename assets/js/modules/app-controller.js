/**
 * ==============================================================================
 * MODULE CONTRÔLEUR PRINCIPAL (CYCLE DE VIE, ÉVÉNEMENTS & AFFICHAGE)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=10.4';
import { Utils } from './utils.js?v=10.4';
import { I18n } from './i18n.js?v=10.4';
import { Router } from './router.js?v=10.4';
import { ApiService } from './api.js?v=10.4';
import { PropertyRenderer, ListRenderer } from './renderer.js?v=10.4';
import { ModalManager } from './modals.js?v=10.4';
import { ScrollManager } from './scroll.js?v=10.4';
import { PdfViewer } from './pdf-viewer.js?v=10.4';
import { SheetGestureController, TabGestureController } from './gestures.js?v=10.4';
import { PwaManager } from './pwa.js?v=10.4';

export const AppController = {
    async load() {
        const route = Router.parseRoute();
        if (AppState.currentSlug && AppState.currentSlug !== route.slug) {
            PdfViewer.destroy();
        }
        AppState.currentSlug = route.slug;

        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (isStandalone && !AppState.currentSlug) {
            try {
                const savedSlug = localStorage.getItem('cdh_saved_property');
                if (savedSlug && !savedSlug.includes('.')) {
                    AppState.currentSlug = savedSlug;
                    route.slug = savedSlug;
                    Router.setUrl(savedSlug, 'guides');
                }
            } catch(e) {}
        }

        try {
            const response = await ApiService.fetchRouteData(AppState.currentSlug);
            this.hideAllViews();

            if (!response.ok) {
                if (response.status === 404) {
                    this.renderNotFound(AppState.currentSlug);
                } else {
                    this.renderError(response.data.error || response.data.details);
                }
                return;
            }

            const result = response.data;
            if (result.type === 'property') {
                const videoList = result.data.videos || result.data.tutorials || [];
                PropertyRenderer.render(result.data.property, videoList, route);
            } else if (result.type === 'list') {
                ScrollManager.unlock();
                AppState.allProperties = result.data.properties || [];
                ListRenderer.render(AppState.allProperties);
            }

            if (route.section === 'reservation') {
                ModalManager.openBooking(false);
            }
        } catch (err) {
            this.hideAllViews();
            this.renderError(err.message);
        }
    },

    hideAllViews() {
        const ids = ['view-loading', 'view-property', 'view-list', 'view-not-found', 'view-error'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
    },

    renderNotFound(slug) {
        const msgEl = document.getElementById('not-found-msg');
        if (msgEl) {
            msgEl.innerHTML = `Aucun logement ne correspond à l'identifiant <strong>"${Utils.escapeHtml(slug)}"</strong>.`;
        }
        const notFoundView = document.getElementById('view-not-found');
        if (notFoundView) notFoundView.classList.remove('hidden');
    },

    renderError(msg) {
        const detailsEl = document.getElementById('error-details');
        if (detailsEl) {
            detailsEl.textContent = msg || 'Erreur de connexion.';
        }
        const errorView = document.getElementById('view-error');
        if (errorView) errorView.classList.remove('hidden');
    },

    showToast(message) {
        const toast = document.getElementById('copy-toast');
        if (toast) {
            const span = document.getElementById('copy-toast-msg');
            if (span && message) span.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }
    },

    copyText(text, btnElement, toastMsg) {
        if (!text) return;
        const triggerFeedback = () => {
            if (btnElement) {
                btnElement.classList.add('copied');
                setTimeout(() => {
                    btnElement.classList.remove('copied');
                }, 2000);
            }
            this.showToast(toastMsg || I18n.t('wifi_copied_toast'));
            Utils.announceA11y(toastMsg || I18n.t('a11y_wifi_copied'));
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(triggerFeedback).catch(() => {
                this.fallbackCopyText(text);
                triggerFeedback();
            });
        } else {
            this.fallbackCopyText(text);
            triggerFeedback();
        }
    },

    copyNetwork(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        if (!AppState.currentProperty || !AppState.currentProperty.wifi_ssid) return;
        const btn = document.getElementById('wifi-copy-ssid-btn');
        this.copyText(AppState.currentProperty.wifi_ssid, btn, I18n.t('wifi_ssid_copied_toast'));
    },

    copyWifi(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        if (!AppState.currentProperty || !AppState.currentProperty.wifi_password) return;
        const btn = document.getElementById('wifi-copy-pwd-btn');
        this.copyText(AppState.currentProperty.wifi_password, btn, I18n.t('wifi_copied_toast'));
    },

    copyWifiFromCard(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        if (!AppState.currentProperty || !AppState.currentProperty.wifi_password) return;
        const btn = document.getElementById('btn-wifi-card-quick');
        const label = btn ? btn.querySelector('.wifi-card-copy-label') : null;
        const originalText = label ? label.textContent : '';

        this.copyText(AppState.currentProperty.wifi_password, btn, I18n.t('wifi_copied_toast'));

        if (label) {
            label.textContent = I18n.t('wifi_quick_copied');
            setTimeout(() => {
                label.textContent = originalText || I18n.t('wifi_quick_copy');
            }, 2000);
        }
    },

    fallbackCopyText(text) {
        const temp = document.createElement('textarea');
        temp.value = text;
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.focus();
        temp.select();
        try {
            document.execCommand('copy');
        } catch (e) {}
        document.body.removeChild(temp);
    },

    initEvents() {
        SheetGestureController.init();
        TabGestureController.init();

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (AppState.isPdfModalOpen) ModalManager.closePdf();
                if (AppState.isVideoModalOpen) ModalManager.closeVideo();
                if (AppState.isBookingModalOpen) ModalManager.closeBooking();
                if (AppState.isWifiModalOpen) ModalManager.closeWifi();
                if (AppState.isReviewModalOpen) ModalManager.closeReview();
                if (AppState.isContactModalOpen) ModalManager.closeContact();
                const pwaBanner = document.getElementById('pwa-install-banner');
                if (pwaBanner && pwaBanner.classList.contains('show')) {
                    PwaManager.dismissPrompt();
                }
            }
        });

        window.addEventListener('popstate', () => {
            if (AppState.isPdfModalOpen) {
                ModalManager.closePdf(true);
                return;
            }
            if (AppState.isVideoModalOpen) {
                ModalManager.closeVideo(true);
                return;
            }
            if (AppState.isBookingModalOpen) {
                ModalManager.closeBooking(true);
                return;
            }
            if (AppState.isWifiModalOpen) {
                ModalManager.closeWifi(true);
                return;
            }
            if (AppState.isReviewModalOpen) {
                ModalManager.closeReview(true);
                return;
            }
            if (AppState.isContactModalOpen) {
                ModalManager.closeContact(true);
                return;
            }
            this.load();
        });

        window.addEventListener('resize', () => {
            if (AppState.isVideoModalOpen) {
                ModalManager.adjustVideoDimensions();
            }
        });

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('touchmove', (e) => {
                if (e.target === overlay || e.target.closest('.modal-header-fixed') || e.target.closest('.sheet-drag-handle')) {
                    e.preventDefault();
                }
            }, { passive: false });
        });

        const preventGlobalGesture = (e) => {
            if (e.cancelable) e.preventDefault();
        };
        document.addEventListener('gesturestart', preventGlobalGesture, { passive: false });
        document.addEventListener('gesturechange', preventGlobalGesture, { passive: false });
        document.addEventListener('gestureend', preventGlobalGesture, { passive: false });
        document.addEventListener('touchmove', (e) => {
            if (e.touches && e.touches.length > 1) {
                if (!e.target || !e.target.closest || !e.target.closest('#pdf-viewport')) {
                    if (e.cancelable) e.preventDefault();
                }
            }
        }, { passive: false });
        window.addEventListener('wheel', (e) => {
            if (e.ctrlKey || e.metaKey) {
                if (!e.target || !e.target.closest || !e.target.closest('#pdf-viewport')) {
                    if (e.cancelable) e.preventDefault();
                }
            }
        }, { passive: false });
    }
};

/**
 * ==============================================================================
 * MODULE GESTIONNAIRE DE MODALES (PDF, VIDÉOS, BOOKING, WIFI, REVIEWS, CONTACT)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=10.3';
import { Utils } from './utils.js?v=10.3';
import { I18n } from './i18n.js?v=10.3';
import { ScrollManager } from './scroll.js?v=10.3';
import { Router } from './router.js?v=10.3';
import { PdfViewer } from './pdf-viewer.js?v=10.3';

export const ModalManager = {
    // --- MODALE PDF ---
    openPdf(lang = null, updateUrl = true) {
        if (!AppState.currentProperty) return;
        const activeLang = lang || I18n.currentLang;
        let pdfUrl = activeLang === 'en' ? AppState.currentProperty.guide_en_url : AppState.currentProperty.guide_fr_url;
        if (!pdfUrl) {
            pdfUrl = activeLang === 'en' ? AppState.currentProperty.guide_fr_url : AppState.currentProperty.guide_en_url;
        }
        if (!pdfUrl) return;

        const modal = document.getElementById('modal-pdf');
        if (!modal) return;
        const titleEl = document.getElementById('pdf-modal-title');
        const descEl = document.getElementById('pdf-modal-status-label');
        const extLink = document.getElementById('pdf-modal-external-link');

        if (titleEl) titleEl.textContent = I18n.t('booklet_title');
        if (descEl) descEl.textContent = I18n.t('booklet_desc');
        extLink.href = Utils.safeUrl(pdfUrl);

        // Réinitialise tout style restant d'un glissement gestuel
        modal.classList.remove('is-closing');
        const sheet = modal.querySelector('.modal-sheet');
        if (sheet) {
            sheet.style.transform = '';
            sheet.style.transition = '';
        }
        modal.style.opacity = '';
        modal.style.backgroundColor = '';

        // Étape 1 : active la modale dans le DOM (display: flex) à sa position fermée translateY(100%)
        modal.classList.add('is-active');
        void modal.offsetHeight; // Force le calcul du rendu initial

        // Étape 2 : déclenche l'animation d'ouverture fluide (montée depuis le bas)
        requestAnimationFrame(() => {
            modal.classList.add('open');
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'false');
        });

        AppState.isPdfModalOpen = true;
        ScrollManager.lock();

        // Réinitialise le défilement du conteneur en haut de page
        const container = document.getElementById('pdf-viewport');
        if (container) container.scrollTop = 0;

        // Rendu vectoriel via PDF.js (fit-to-width parfait & zoom de contenu)
        PdfViewer.load(AppState.currentSlug, activeLang, pdfUrl);

        if (updateUrl) {
            Router.setUrl(AppState.currentSlug, 'guides', `pdf-${activeLang}`);
        }
    },

    closePdf(immediate = false) {
        const modal = document.getElementById('modal-pdf');
        if (!modal) return;

        AppState.isPdfModalOpen = false;
        ScrollManager.unlock();

        if (immediate || !modal.classList.contains('open')) {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            PdfViewer.resetZoom();
            Router.setUrl(AppState.currentSlug, AppState.currentActiveTab);
            return;
        }

        // Animation de fermeture fluide : descente progressive jusqu'en bas
        modal.classList.remove('open');
        modal.classList.add('is-closing');
        modal.setAttribute('aria-hidden', 'true');

        setTimeout(() => {
            modal.classList.remove('is-closing', 'is-active');
            PdfViewer.resetZoom();
            Router.setUrl(AppState.currentSlug, AppState.currentActiveTab);
        }, 360);
    },

    onPdfLoaded() {
        const spinner = document.getElementById('pdf-loading-spinner');
        if (spinner) spinner.classList.add('hidden');
    },

    // --- MODALE VIDÉO (STANDARDISÉE EN PLEINE LARGEUR) ---
    openVideo(video, updateUrl = true) {
        const modal = document.getElementById('modal-video');
        if (!modal) return;
        const titleEl = document.getElementById('video-modal-title');
        const subtitleEl = document.getElementById('video-modal-subtitle');
        const iconEl = document.getElementById('video-modal-icon');
        const extLink = document.getElementById('video-external-btn');

        const isEn = I18n.currentLang === 'en';
        titleEl.textContent = isEn ? (video.title_en || video.title_fr) : video.title_fr;
        subtitleEl.textContent = isEn ? (video.title_en ? video.title_fr : '') : (video.title_en || '');
        iconEl.textContent = video.icon || '💡';
        extLink.href = Utils.safeUrl(video.video_url);

        const iframe = document.getElementById('video-iframe');
        const player = document.getElementById('custom-video-player');
        const source = document.getElementById('custom-video-source');

        // Réinitialise tout style restant
        modal.classList.remove('is-closing');
        const sheet = modal.querySelector('.modal-sheet');
        if (sheet) {
            sheet.style.transform = '';
            sheet.style.transition = '';
        }
        modal.style.opacity = '';
        modal.style.backgroundColor = '';

        // Étape 1 : active la modale dans le DOM (display: flex) à sa position fermée translateY(100%)
        modal.classList.add('is-active');
        void modal.offsetHeight; // Force le calcul du rendu initial

        // Étape 2 : déclenche l'animation d'ouverture fluide (montée depuis le bas)
        requestAnimationFrame(() => {
            modal.classList.add('open');
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'false');
        });

        AppState.isVideoModalOpen = true;
        ScrollManager.lock();

        if (Utils.isGoogleDriveUrl(video.video_url)) {
            const embedUrl = Utils.getGoogleDriveEmbedUrl(video.video_url);
            if (player) {
                player.pause();
                player.classList.add('hidden');
            }
            if (iframe) {
                iframe.classList.remove('hidden');
                requestAnimationFrame(() => {
                    iframe.src = Utils.safeUrl(embedUrl);
                });
            }
        } else {
            if (iframe) {
                iframe.src = '';
                iframe.classList.add('hidden');
            }
            if (player && source) {
                source.src = Utils.safeUrl(video.video_url);
                player.load();
                player.classList.remove('hidden');
                player.play().catch(() => {});
            }
        }

        if (updateUrl) {
            const videoSlug = Utils.getVideoSlug(video);
            Router.setUrl(AppState.currentSlug, 'videos', videoSlug);
        }
    },

    closeVideo(immediate = false) {
        const modal = document.getElementById('modal-video');
        if (!modal) return;

        AppState.isVideoModalOpen = false;
        ScrollManager.unlock();

        const player = document.getElementById('custom-video-player');
        if (player) {
            player.pause();
        }

        if (immediate || !modal.classList.contains('open')) {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            const iframe = document.getElementById('video-iframe');
            if (iframe) {
                iframe.src = '';
                iframe.classList.add('hidden');
            }
            if (player) {
                player.currentTime = 0;
                player.classList.add('hidden');
            }
            Router.setUrl(AppState.currentSlug, AppState.currentActiveTab);
            return;
        }

        // Animation de fermeture fluide : descente progressive jusqu'en bas
        modal.classList.remove('open');
        modal.classList.add('is-closing');
        modal.setAttribute('aria-hidden', 'true');

        setTimeout(() => {
            modal.classList.remove('is-closing', 'is-active');
            const iframe = document.getElementById('video-iframe');
            if (iframe) {
                iframe.src = '';
                iframe.classList.add('hidden');
            }
            if (player) {
                player.currentTime = 0;
                player.classList.add('hidden');
            }
            Router.setUrl(AppState.currentSlug, AppState.currentActiveTab);
        }, 360);
    },

    adjustVideoDimensions() {
        // Les modales (PDF, Vidéo, Réservation) sont désormais standardisées en pleine largeur (100%)
        // avec hauteur réhaussée calc(100dvh - 28px) et coins arrondis 26px.
    },

    // --- MODALE RÉSERVATION SUPERHOTE (RECHERCHE HÉBERGEMENTS) ---
    isBookingLoaded: Boolean(window.__bookingLoaded),
    _bookingSpinnerTimer: null,

    getBookingUrl(lang = null) {
        const activeLang = lang || I18n.currentLang;
        const baseUrl = "https://connect.superhote.com/integrations/iframes/4ecc4a3f-2e6d-4fc8-a03a-48f501f85694/rentals-search";
        return activeLang === 'en' ? `${baseUrl}?lang=en` : baseUrl;
    },

    updateBookingLanguage() {
        this.preloadBooking();
    },

    openBooking(updateUrl = true) {
        const modal = document.getElementById('modal-booking');
        const spinner = document.getElementById('booking-loading-spinner');
        const iframe = document.getElementById('booking-search-engine-rentals');
        if (!modal) return;

        const targetUrl = this.getBookingUrl();
        const link = document.getElementById('booking-modal-external-link');
        if (link) {
            link.href = targetUrl;
        }

        if (iframe) {
            iframe.setAttribute('data-src', targetUrl);
            const needsLoad = !iframe.src || !iframe.src.includes('superhote.com') || iframe.src !== targetUrl;

            if (needsLoad) {
                if (spinner) {
                    spinner.classList.remove('hidden');
                    spinner.style.opacity = '1';
                }
                this.isBookingLoaded = false;
                window.__bookingLoaded = false;
                clearTimeout(this._bookingSpinnerTimer);
                this._bookingSpinnerTimer = setTimeout(() => {
                    this.onBookingLoaded();
                }, 1500);
                iframe.addEventListener('load', () => this.onBookingLoaded(), { once: true });
                iframe.src = targetUrl;
            } else if (this.isBookingLoaded || window.__bookingLoaded) {
                if (spinner) spinner.classList.add('hidden');
            }
        }

        // Réinitialise tout style restant
        modal.classList.remove('is-closing');
        const sheet = modal.querySelector('.modal-sheet');
        if (sheet) {
            sheet.style.transform = '';
            sheet.style.transition = '';
        }
        modal.style.opacity = '';
        modal.style.backgroundColor = '';

        // Étape 1 : active la modale dans le DOM (display: flex) à sa position fermée translateY(100%)
        modal.classList.add('is-active');
        void modal.offsetHeight; // Force le calcul du rendu initial

        // Étape 2 : déclenche l'animation d'ouverture fluide (montée depuis le bas)
        requestAnimationFrame(() => {
            modal.classList.add('open');
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'false');
        });

        AppState.isBookingModalOpen = true;
        ScrollManager.lock();

        if (updateUrl) {
            Router.setUrl(AppState.currentSlug, 'reservation');
        }
    },

    closeBooking(immediate = false) {
        const modal = document.getElementById('modal-booking');
        if (!modal) return;

        AppState.isBookingModalOpen = false;
        ScrollManager.unlock();

        if (immediate || !modal.classList.contains('open')) {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            Router.setUrl(AppState.currentSlug, AppState.currentActiveTab || 'guides');
            return;
        }

        // Animation de fermeture fluide : descente progressive jusqu'en bas
        modal.classList.remove('open');
        modal.classList.add('is-closing');
        modal.setAttribute('aria-hidden', 'true');

        setTimeout(() => {
            modal.classList.remove('is-closing', 'is-active');
            Router.setUrl(AppState.currentSlug, AppState.currentActiveTab || 'guides');
        }, 360);
    },

    onBookingLoaded() {
        this.isBookingLoaded = true;
        window.__bookingLoaded = true;
        if (this._bookingSpinnerTimer) {
            clearTimeout(this._bookingSpinnerTimer);
            this._bookingSpinnerTimer = null;
        }
        const spinner = document.getElementById('booking-loading-spinner');
        if (spinner) spinner.classList.add('hidden');
    },

    preloadBooking() {
        const targetUrl = this.getBookingUrl();
        const link = document.getElementById('booking-modal-external-link');
        if (link) {
            link.href = targetUrl;
        }

        const iframe = document.getElementById('booking-search-engine-rentals');
        if (iframe) {
            iframe.setAttribute('data-src', targetUrl);
            const isDifferent = !iframe.src || !iframe.src.includes('superhote.com') || iframe.src !== targetUrl;
            if (isDifferent) {
                this.isBookingLoaded = false;
                window.__bookingLoaded = false;
                const spinner = document.getElementById('booking-loading-spinner');
                // N'affiche le spinner que si la modale de réservation est actuellement ouverte
                if (spinner && AppState.isBookingModalOpen) {
                    spinner.classList.remove('hidden');
                    spinner.style.opacity = '1';
                }
                clearTimeout(this._bookingSpinnerTimer);
                this._bookingSpinnerTimer = setTimeout(() => {
                    this.onBookingLoaded();
                }, 2000);
                iframe.addEventListener('load', () => this.onBookingLoaded(), { once: true });
                iframe.src = targetUrl;
            }
        }
    },

    // --- MODALE WI-FI COMPACTE ---
    openWifi() {
        if (!AppState.currentProperty || !AppState.currentProperty.wifi_ssid) return;
        const modal = document.getElementById('modal-wifi');
        if (!modal) return;

        // Réinitialise tout style restant
        modal.classList.remove('is-closing');
        const sheet = modal.querySelector('.modal-sheet-compact');
        if (sheet) {
            sheet.style.transform = '';
            sheet.style.transition = '';
        }
        modal.style.opacity = '';
        modal.style.backgroundColor = '';

        // Étape 1 : active la modale dans le DOM (display: flex) à sa position fermée translateY(100%)
        modal.classList.add('is-active');
        void modal.offsetHeight;

        // Étape 2 : déclenche l'animation d'ouverture fluide (montée depuis le bas)
        requestAnimationFrame(() => {
            modal.classList.add('open');
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'false');
        });

        AppState.isWifiModalOpen = true;
        ScrollManager.lock();
    },

    closeWifi(immediate = false) {
        const modal = document.getElementById('modal-wifi');
        if (!modal) return;

        AppState.isWifiModalOpen = false;
        ScrollManager.unlock();

        if (immediate || !modal.classList.contains('open')) {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            return;
        }

        modal.classList.add('is-closing');
        const sheet = modal.querySelector('.modal-sheet-compact');
        if (sheet) {
            sheet.style.transform = 'translateY(100%)';
        }
        modal.style.opacity = '0';

        setTimeout(() => {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            if (sheet) {
                sheet.style.transform = '';
                sheet.style.transition = '';
            }
            modal.style.opacity = '';
        }, 340);
    },

    // --- MODALE AVIS GOOGLE MY BUSINESS COMPACTE ---
    openReview() {
        const modal = document.getElementById('modal-review');
        if (!modal) return;

        // Réinitialise tout style restant
        modal.classList.remove('is-closing');
        const sheet = modal.querySelector('.modal-sheet-compact');
        if (sheet) {
            sheet.style.transform = '';
            sheet.style.transition = '';
        }
        modal.style.opacity = '';
        modal.style.backgroundColor = '';

        // Étape 1 : active la modale dans le DOM (display: flex) à sa position fermée translateY(100%)
        modal.classList.add('is-active');
        void modal.offsetHeight;

        // Étape 2 : déclenche l'animation d'ouverture fluide (montée depuis le bas)
        requestAnimationFrame(() => {
            modal.classList.add('open');
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'false');
        });

        AppState.isReviewModalOpen = true;
        ScrollManager.lock();
    },

    closeReview(immediate = false) {
        const modal = document.getElementById('modal-review');
        if (!modal) return;

        AppState.isReviewModalOpen = false;
        ScrollManager.unlock();

        if (immediate || !modal.classList.contains('open')) {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            const sheet = modal.querySelector('.modal-sheet-compact');
            if (sheet) {
                sheet.style.transform = '';
                sheet.style.transition = '';
            }
            modal.style.opacity = '';
            return;
        }

        modal.classList.add('is-closing');
        const sheet = modal.querySelector('.modal-sheet-compact');
        if (sheet) {
            sheet.style.transform = 'translateY(100%)';
        }
        modal.style.opacity = '0';

        setTimeout(() => {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            if (sheet) {
                sheet.style.transform = '';
                sheet.style.transition = '';
            }
            modal.style.opacity = '';
        }, 340);
    },

    // --- MODALE CONTACT ANTHONY (TRIAGE & WHATSAPP) ---
    openContact() {
        const modal = document.getElementById('modal-contact-anthony');
        if (!modal) return;

        // Met à jour l'affichage conditionnel des raccourcis selon le logement
        const prop = AppState.currentProperty;
        const triageWifi = document.getElementById('contact-triage-wifi');
        if (triageWifi) {
            triageWifi.classList.toggle('hidden', !(prop && prop.wifi_ssid));
        }
        const triageVideos = document.getElementById('contact-triage-videos');
        if (triageVideos) {
            triageVideos.classList.toggle('hidden', !(AppState.currentVideos && AppState.currentVideos.length > 0));
        }

        // Pré-configuration native du lien direct WhatsApp vers Anthony
        const btnWhatsApp = document.getElementById('btn-whatsapp-anthony');
        if (btnWhatsApp) {
            const propName = (prop && prop.name) ? prop.name : (I18n.currentLang === 'en' ? 'my accommodation' : 'mon hébergement');
            const msg = I18n.currentLang === 'en'
                ? `Hello Anthony, I am currently staying at "${propName}" and I have a question regarding my stay: `
                : `Bonjour Anthony, je séjourne actuellement au logement "${propName}" et j'ai une question concernant mon séjour : `;
            btnWhatsApp.href = `https://wa.me/33675883791?text=${encodeURIComponent(msg)}`;
        }

        // Réinitialise tout style restant
        modal.classList.remove('is-closing');
        const sheet = modal.querySelector('.modal-sheet-compact');
        if (sheet) {
            sheet.style.transform = '';
            sheet.style.transition = '';
        }
        modal.style.opacity = '';
        modal.style.backgroundColor = '';

        modal.classList.add('is-active');
        void modal.offsetHeight;

        requestAnimationFrame(() => {
            modal.classList.add('open');
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'false');
        });

        AppState.isContactModalOpen = true;
        ScrollManager.lock();
    },

    closeContact(immediate = false) {
        const modal = document.getElementById('modal-contact-anthony');
        if (!modal) return;

        AppState.isContactModalOpen = false;
        ScrollManager.unlock();

        if (immediate || !modal.classList.contains('open')) {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            const sheet = modal.querySelector('.modal-sheet-compact');
            if (sheet) {
                sheet.style.transform = '';
                sheet.style.transition = '';
            }
            modal.style.opacity = '';
            return;
        }

        modal.classList.add('is-closing');
        const sheet = modal.querySelector('.modal-sheet-compact');
        if (sheet) {
            sheet.style.transform = 'translateY(100%)';
        }
        modal.style.opacity = '0';

        setTimeout(() => {
            modal.classList.remove('open', 'is-active', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            if (sheet) {
                sheet.style.transform = '';
                sheet.style.transition = '';
            }
            modal.style.opacity = '';
        }, 340);
    },

    // --- TRANSITIONS ENTRE MODALES SANS CONFLIT NI BLOCAGE DU RENDU MOBILE ---
    switchFromReviewToContact(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        this.closeReview(true);
        setTimeout(() => {
            this.openContact();
        }, 80);
    },

    switchToWifiFromContact(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        this.closeContact(true);
        setTimeout(() => {
            this.openWifi();
        }, 80);
    },

    switchToVideosFromContact(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        this.closeContact(true);
        setTimeout(() => {
            PropertyRenderer.navigateTab('videos');
        }, 80);
    },

    switchToGuideFromContact(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        this.closeContact(true);
        setTimeout(() => {
            PropertyRenderer.openCurrentGuide();
        }, 80);
    },

    openWhatsAppAnthony(e) {
        if (e && e.stopPropagation) e.stopPropagation();

        const prop = AppState.currentProperty;
        const propName = (prop && prop.name)
            ? prop.name
            : (I18n.currentLang === 'en' ? 'my accommodation' : 'mon hébergement');

        const msg = I18n.currentLang === 'en'
            ? `Hello Anthony, I am currently staying at "${propName}" and I have a question regarding my stay: `
            : `Bonjour Anthony, je séjourne actuellement au logement "${propName}" et j'ai une question concernant mon séjour : `;

        const phone = '33675883791';
        const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;

        const btn = document.getElementById('btn-whatsapp-anthony');
        if (btn) btn.href = url;

        // Détection universelle smartphone / tablette / PWA standalone
        const isMobile = /iphone|ipad|ipod|android/i.test(navigator.userAgent.toLowerCase()) || 
                         (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        if (isMobile) {
            if (e && e.preventDefault) e.preventDefault();
            window.location.href = url;
        }
        // Sur desktop, le lien natif <a href="..." target="_blank"> ouvre WhatsApp Web dans un nouvel onglet
    },

    openGoogleReview(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        const url = "https://g.page/r/CZ8_-ySB8FV2ECE/review";
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        if (isStandalone) {
            if (e && e.preventDefault) e.preventDefault();
            window.open(url, '_blank');
        }
        // En mode web normal (mobile et desktop), le lien natif <a href="..." target="_blank" rel="noopener noreferrer">
        // gère l'ouverture sans risquer le blocage pop-up du navigateur.
    },

    onBackdropClick(event, type) {
        if (!event || !event.target || event.target.classList.contains('modal-overlay')) {
            if (type === 'pdf') this.closePdf();
            if (type === 'video') this.closeVideo();
            if (type === 'booking') this.closeBooking();
            if (type === 'wifi') this.closeWifi();
            if (type === 'review') this.closeReview();
            if (type === 'contact') this.closeContact();
        }
    }
};



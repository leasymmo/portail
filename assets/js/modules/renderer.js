/**
 * ==============================================================================
 * MODULE DE RENDU UI (LOGEMENTS, GUIDES, VIDÉOS, LISTE & RECHERCHE)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=10.6';
import { Utils } from './utils.js?v=10.6';
import { I18n } from './i18n.js?v=10.6';
import { ScrollManager } from './scroll.js?v=10.6';
import { Router, BASE_PATH } from './router.js?v=10.6';
import { ModalManager } from './modals.js?v=10.6';
import { PdfViewer } from './pdf-viewer.js?v=10.6';

// 8. RENDU DE L'INTERFACE UTILISATEUR (UI RENDERERS)
// -----------------------------------------------------------------------------
export const PropertyRenderer = {
    render(prop, videos, route) {
        AppState.currentProperty = prop;
        AppState.currentVideos = videos || [];

        document.title = `${prop.name} - Portail Voyageurs | Carré d'Hôtes`;

        // Mise à jour dynamique du manifeste PWA & des métadonnées pour ce logement spécifique
        const manifestLink = document.getElementById('app-manifest-link');
        if (manifestLink && prop.slug) {
            manifestLink.href = `${BASE_PATH}manifest.php?slug=${encodeURIComponent(prop.slug)}`;
        }
        const appleTitleMeta = document.querySelector('meta[name="apple-mobile-web-app-title"]');
        if (appleTitleMeta && prop.name) {
            appleTitleMeta.setAttribute('content', prop.name);
        }
        const canonicalLink = document.getElementById('app-canonical-link');
        if (canonicalLink && prop.slug) {
            canonicalLink.href = `${BASE_PATH}${encodeURIComponent(prop.slug)}`;
        }
        if (prop && prop.slug) {
            try { localStorage.setItem('cdh_saved_property', prop.slug); } catch(e) {}
        }

        // Textes principaux
        document.getElementById('prop-name').textContent = prop.name;
        const propSub = document.getElementById('prop-subtitle');
        if (propSub) propSub.textContent = I18n.t('hero_badge');

        // Bloc Wi-Fi & Animation en cascade décalée des cartes principales
        const guideCard = document.getElementById('card-guide-active');
        const parkingCard = document.getElementById('card-parking-active');
        const wifiCard = document.getElementById('prop-wifi-card');
        const bookingCard = document.getElementById('card-booking-active');
        const reviewCard = document.getElementById('card-review-active');
        const contactCard = document.getElementById('card-contact-active');
        const infoGrid = document.getElementById('info-cards-grid');

        if (guideCard) {
            guideCard.classList.add('cascade-item');
            guideCard.style.animationDelay = '0.04s';
        }

        const hasParking = prop.parking && (prop.parking.general || (prop.parking.locations && prop.parking.locations.all && prop.parking.locations.all.length > 0));
        if (hasParking) {
            if (parkingCard) {
                parkingCard.classList.remove('hidden');
                parkingCard.classList.add('cascade-item');
                parkingCard.style.animationDelay = '0.08s';
            }
            this.renderParkingContent(prop.parking);
        } else {
            if (parkingCard) {
                parkingCard.classList.add('hidden');
            }
        }

        if (prop.wifi_ssid) {
            const modalSsid = document.getElementById('wifi-modal-ssid');
            const modalPwd = document.getElementById('wifi-modal-password');
            if (modalSsid) modalSsid.textContent = prop.wifi_ssid;
            if (modalPwd) modalPwd.textContent = prop.wifi_password || '—';

            if (wifiCard) {
                wifiCard.classList.remove('hidden');
                wifiCard.classList.add('cascade-item');
                wifiCard.style.animationDelay = hasParking ? '0.12s' : '0.08s';
            }
        } else {
            if (wifiCard) {
                wifiCard.classList.add('hidden');
            }
        }

        if (bookingCard) {
            bookingCard.classList.add('cascade-item');
            bookingCard.style.animationDelay = '0.16s';
        }
        if (reviewCard) {
            reviewCard.classList.add('cascade-item');
            reviewCard.style.animationDelay = '0.20s';
        }
        if (contactCard) {
            contactCard.classList.add('cascade-item');
            contactCard.style.animationDelay = '0.24s';
        }

        if (infoGrid) {
            infoGrid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 max-w-3xl mx-auto';
        }

        // Mise à jour de la carte du livret et des éléments dépendant de la langue
        this.updateLanguageContent();

        // Onglets et Vidéos
        const tabsContainer = document.getElementById('property-tabs-container');
        if (AppState.currentVideos.length > 0) {
            tabsContainer.classList.remove('hidden');
            document.getElementById('videos-badge-count').textContent = AppState.currentVideos.length;
            this.renderVideos(AppState.currentVideos);

            const targetTab = route.section === 'videos' ? 'videos' : 'guides';
            this.switchTab(targetTab);
        } else {
            tabsContainer.classList.add('hidden');
            this.switchTab('guides');
        }

        document.getElementById('view-property').classList.remove('hidden');
        ScrollManager.updateTopbarHeight();
        ScrollManager.resetToTop();
        requestAnimationFrame(() => {
            ScrollManager.resetToTop();
            ScrollManager.updatePageScrollState();
        });
        setTimeout(() => {
            ScrollManager.resetToTop();
            ScrollManager.updatePageScrollState();
        }, 50);

        // Traitement des deep-links dans l'URL
        if (route.subSection === 'pdf-fr') {
            I18n.setLanguage('fr');
            ModalManager.openPdf('fr', false);
        } else if (route.subSection === 'pdf-en') {
            I18n.setLanguage('en');
            ModalManager.openPdf('en', false);
        } else if (route.section === 'videos' && route.subSection) {
            const targetSlug = route.subSection.toLowerCase();
            const foundVideo = AppState.currentVideos.find(v => {
                const vSlug = Utils.getVideoSlug(v).toLowerCase();
                return vSlug === targetSlug || String(v.id) === targetSlug.replace('video-', '');
            });
            if (foundVideo) {
                ModalManager.openVideo(foundVideo, false);
            }
        }

        // Préchargement échelonné en arrière-plan : d'abord le livret PDF dans le DOM, puis le moteur de réservation
        if (route.subSection !== 'pdf-fr' && route.subSection !== 'pdf-en') {
            setTimeout(() => {
                const targetLang = I18n.currentLang || 'fr';
                PdfViewer.preload(prop.slug, targetLang);
            }, 150);

            setTimeout(() => {
                ModalManager.preloadBooking();
            }, 600);
        } else {
            setTimeout(() => {
                ModalManager.preloadBooking();
            }, 800);
        }
    },

    updateLanguageContent() {
        if (!AppState.currentProperty) return;
        const prop = AppState.currentProperty;
        const isEn = I18n.currentLang === 'en';

        // 1. Titres de bienvenue
        const welcomeTitleEl = document.getElementById('prop-welcome-title');
        const welcomeSubtitleEl = document.getElementById('prop-welcome-subtitle');
        if (welcomeTitleEl) {
            welcomeTitleEl.textContent = I18n.t('welcome_title');
        }
        if (welcomeSubtitleEl) {
            welcomeSubtitleEl.textContent = I18n.t('welcome_subtitle');
        }

        // 2. Carte unique Guide de bienvenue (FR ou EN selon langue active)
        const cardActive = document.getElementById('card-guide-active');
        const titleEl = document.getElementById('guide-active-title');
        const descEl = document.getElementById('guide-active-desc');

        const hasGuide = Boolean(prop.guide_fr_url || prop.guide_en_url);
        if (cardActive) {
            cardActive.classList.toggle('hidden', !hasGuide);
            cardActive.classList.add('cascade-item');
            cardActive.style.animationDelay = '0.04s';
        }
        if (titleEl) {
            titleEl.textContent = I18n.t('booklet_title');
        }
        if (descEl) {
            descEl.textContent = I18n.t('booklet_desc');
        }

        // 2a. Carte Stationnement
        const cardParking = document.getElementById('card-parking-active');
        const parkingTitleEl = document.getElementById('parking-card-title');
        const parkingDescEl = document.getElementById('parking-card-desc');
        const parkingBadgeEl = document.getElementById('parking-card-badge');
        if (parkingTitleEl) parkingTitleEl.textContent = I18n.t('parking_card_title');
        if (parkingDescEl) parkingDescEl.textContent = I18n.t('parking_card_desc');
        if (parkingBadgeEl) parkingBadgeEl.textContent = I18n.t('parking_badge');
        const parkingModalTitleEl = document.getElementById('parking-modal-title');
        const parkingModalSubEl = document.getElementById('parking-modal-subtitle');
        if (parkingModalTitleEl) parkingModalTitleEl.textContent = I18n.t('parking_modal_title');
        if (parkingModalSubEl) parkingModalSubEl.textContent = I18n.t('parking_modal_subtitle');

        if (prop.parking) {
            this.renderParkingContent(prop.parking);
        }

        // 2b. Carte Réservez votre prochain séjour
        const cardBooking = document.getElementById('card-booking-active');
        if (cardBooking) {
            cardBooking.classList.add('cascade-item');
            cardBooking.style.animationDelay = prop.wifi_ssid ? '0.12s' : '0.08s';
        }

        // 2c. Carte Avis Google
        const cardReview = document.getElementById('card-review-active');
        if (cardReview) {
            cardReview.classList.add('cascade-item');
            cardReview.style.animationDelay = prop.wifi_ssid ? '0.16s' : '0.12s';
        }

        // 2d. Carte Contact Anthony
        const cardContact = document.getElementById('card-contact-active');
        if (cardContact) {
            cardContact.classList.add('cascade-item');
            cardContact.style.animationDelay = prop.wifi_ssid ? '0.20s' : '0.16s';
            if (prop.wifi_ssid) {
                cardContact.classList.add('sm:col-span-2');
            } else {
                cardContact.classList.remove('sm:col-span-2');
            }
        }

        // 3. Actualiser la grille des vidéos avec les titres traduits
        if (AppState.currentVideos && AppState.currentVideos.length > 0) {
            this.renderVideos(AppState.currentVideos);
        }

        // Configuration anticipée du lien direct WhatsApp
        const btnWhatsApp = document.getElementById('btn-whatsapp-anthony');
        if (btnWhatsApp) {
            const propName = (prop && prop.name) ? prop.name : (I18n.currentLang === 'en' ? 'my accommodation' : 'mon hébergement');
            const msg = I18n.currentLang === 'en'
                ? `Hello Anthony, I am currently staying at "${propName}" and I have a question regarding my stay: `
                : `Bonjour Anthony, je séjourne actuellement au logement "${propName}" et j'ai une question concernant mon séjour : `;
            btnWhatsApp.href = `https://wa.me/33675883791?text=${encodeURIComponent(msg)}`;
        }

        // Actualise la langue de l'iframe de réservation directe Superhote (?lang=en)
        ModalManager.updateBookingLanguage();

        // 4. Préchargement réactif du livret de bienvenue dans la nouvelle langue pour affichage 0ms
        let targetLang = isEn ? 'en' : 'fr';
        if (isEn && !prop.guide_en_url && prop.guide_fr_url) {
            targetLang = 'fr';
        } else if (!isEn && !prop.guide_fr_url && prop.guide_en_url) {
            targetLang = 'en';
        }
        PdfViewer.preload(prop.slug, targetLang);

        // 5. Si la modale PDF est actuellement ouverte, synchronise ses libellés, liens et URL de route
        if (AppState.isPdfModalOpen) {
            const pdfLink = document.getElementById('pdf-modal-external-link');
            if (pdfLink) {
                const pdfUrl = (targetLang === 'en')
                    ? (prop.guide_en_url || prop.guide_fr_url)
                    : (prop.guide_fr_url || prop.guide_en_url);
                if (pdfUrl) pdfLink.href = Utils.safeUrl(pdfUrl);
            }
            const pdfTitle = document.getElementById('pdf-modal-title');
            if (pdfTitle) pdfTitle.textContent = I18n.t('booklet_title');
            const pdfDesc = document.getElementById('pdf-modal-status-label');
            if (pdfDesc) pdfDesc.textContent = I18n.t('booklet_desc');
            Router.setUrl(prop.slug, 'guides', `pdf-${targetLang}`);
        }
    },

    openCurrentGuide() {
        if (!AppState.currentProperty) return;
        const prop = AppState.currentProperty;
        const isEn = I18n.currentLang === 'en';
        let targetLang = isEn ? 'en' : 'fr';
        if (isEn && !prop.guide_en_url && prop.guide_fr_url) {
            targetLang = 'fr';
        } else if (!isEn && !prop.guide_fr_url && prop.guide_en_url) {
            targetLang = 'en';
        }
        ModalManager.openPdf(targetLang);
    },

    toggleParkingSection(id) {
        const content = document.getElementById(`parking-acc-content-${id}`);
        const trigger = document.getElementById(`parking-acc-trigger-${id}`);
        if (!content || !trigger) return;
        const isHidden = content.classList.contains('hidden');
        content.classList.toggle('hidden', !isHidden);
        const chevron = trigger.querySelector('.acc-chevron');
        if (chevron) {
            chevron.classList.toggle('rotate-180', isHidden);
        }
    },

    renderParkingContent(parking) {
        const container = document.getElementById('parking-modal-body');
        if (!container || !parking) return;
        const isEn = I18n.currentLang === 'en';
        const gen = parking.general || {};
        const locs = parking.locations || {};
        const freeStreets = locs.free_street || [];
        const freeSpots = locs.free_spot || [];
        const privateParkings = locs.private_parking || [];
        const tclPrs = locs.tcl_pr || [];

        let html = '';

        // 1. Règle d'or / Avertissement primordial
        const ruleText = isEn ? (gen.important_rule_en || gen.important_rule_fr) : (gen.important_rule_fr || gen.important_rule_en);
        if (ruleText) {
            html += `
                <div class="p-3.5 sm:p-4 rounded-2xl bg-amber-500/10 dark:bg-amber-500/15 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-3">
                    <span class="text-base flex-shrink-0">⚠️</span>
                    <div class="flex-1 min-w-0">
                        <span class="font-bold uppercase tracking-wider text-[10px] block text-amber-800 dark:text-amber-300 mb-0.5">${I18n.t('parking_rule_label')}</span>
                        <p class="leading-relaxed font-medium">${Utils.escapeHtml(ruleText)}</p>
                    </div>
                </div>
            `;
        }

        // Introduction concise
        const introText = isEn ? (gen.intro_en || gen.intro_fr) : (gen.intro_fr || gen.intro_en);
        if (introText) {
            html += `
                <p class="text-xs text-ink-soft dark:text-gray-300 px-1 leading-relaxed">
                    ${Utils.escapeHtml(introText)}
                </p>
            `;
        }

        // 2. ACCORDÉON - SECTION 1 : Stationnement gratuit (free_street & free_spot)
        const totalFree = freeStreets.length + freeSpots.length;
        if (totalFree > 0) {
            let freeCards = '';

            // Arrêt minute gratuit (ex: Super U)
            freeSpots.forEach(spot => {
                const name = isEn ? (spot.name_en || spot.name_fr) : spot.name_fr;
                const walk = isEn ? (spot.walking_time_en || spot.walking_time_fr) : spot.walking_time_fr;
                const notes = isEn ? (spot.notes_en || spot.notes_fr) : spot.notes_fr;
                freeCards += `
                    <div class="p-3 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/30 space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-xs sm:text-sm text-emerald-800 dark:text-emerald-300">${Utils.escapeHtml(name)}</span>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-600 text-white flex-shrink-0">${I18n.t('parking_free_tag')}</span>
                        </div>
                        <p class="text-[11px] text-ink-soft dark:text-gray-300">📍 ${Utils.escapeHtml(spot.address || '')}</p>
                        ${walk ? `<p class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">🚶 ${Utils.escapeHtml(walk)}</p>` : ''}
                        ${notes ? `<p class="text-[11px] text-ink-faint dark:text-gray-400 italic">${Utils.escapeHtml(notes)}</p>` : ''}
                    </div>
                `;
            });

            // Rues gratuites
            if (freeStreets.length > 0) {
                freeCards += `<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">`;
                freeStreets.forEach(street => {
                    const name = isEn ? (street.name_en || street.name_fr) : street.name_fr;
                    const walk = isEn ? (street.walking_time_en || street.walking_time_fr) : street.walking_time_fr;
                    const notes = isEn ? (street.notes_en || street.notes_fr) : street.notes_fr;
                    const mapsQuery = encodeURIComponent((street.address || street.name_fr) + ' Lyon');
                    freeCards += `
                        <div class="p-2.5 rounded-xl bg-[#faf9f6] dark:bg-[#1e2739] border border-[#e2e4e6] dark:border-[#38455d] flex items-center justify-between gap-2">
                            <div class="min-w-0 pr-1">
                                <h5 class="text-xs font-bold text-ink dark:text-white truncate">${Utils.escapeHtml(name)}</h5>
                                ${walk ? `<p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold truncate mt-0.5">🚶 ${Utils.escapeHtml(walk)}</p>` : ''}
                                ${notes ? `<p class="text-[10px] text-ink-faint dark:text-gray-400 truncate">${Utils.escapeHtml(notes)}</p>` : ''}
                            </div>
                            <a href="https://www.google.com/maps/search/?api=1&query=${mapsQuery}" target="_blank" rel="noopener noreferrer"
                               class="w-7 h-7 rounded-lg bg-gold/10 hover:bg-gold hover:text-white text-gold flex items-center justify-center flex-shrink-0 transition text-xs"
                               title="${I18n.t('parking_maps_btn')}">
                                📍
                            </a>
                        </div>
                    `;
                });
                freeCards += `</div>`;
            }

            const freeNotes = isEn ? (gen.free_parking_notes_en || gen.free_parking_notes_fr) : gen.free_parking_notes_fr;
            if (freeNotes) {
                freeCards += `<p class="text-[10px] text-ink-faint dark:text-gray-400 italic px-1 pt-1">${Utils.escapeHtml(freeNotes)}</p>`;
            }

            html += `
                <div class="rounded-2xl border border-[#e2e4e6] dark:border-[#38455d] overflow-hidden bg-white dark:bg-[#243047] shadow-2xs">
                    <button type="button" id="parking-acc-trigger-free" onclick="toggleParkingSection('free')"
                            class="w-full flex items-center justify-between p-3.5 bg-[#faf9f6] dark:bg-[#1e2739] hover:bg-gold/5 transition text-left cursor-pointer group">
                        <div class="flex items-center space-x-3 min-w-0 pr-2">
                            <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 text-sm">
                                🟢
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-xs sm:text-sm font-bold text-ink dark:text-white group-hover:text-gold transition truncate">${I18n.t('parking_sec_free_title')}</h4>
                                    <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex-shrink-0">${totalFree} ${isEn ? 'spots' : 'options'}</span>
                                </div>
                                <p class="text-[11px] text-ink-soft dark:text-gray-400 truncate mt-0.5">${I18n.t('parking_sec_free_desc')}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-ink-faint dark:text-gray-400 transform transition-transform duration-200 acc-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="parking-acc-content-free" class="p-3.5 space-y-2.5 border-t border-[#e2e4e6] dark:border-[#38455d]">
                        ${freeCards}
                    </div>
                </div>
            `;
        }

        // 3. ACCORDÉON - SECTION 2 : Stationnement payant dans la rue (EasyPark)
        const streetSched = isEn ? (gen.street_parking_schedule_en || gen.street_parking_schedule_fr) : (gen.street_parking_schedule_fr || gen.street_parking_schedule_en);
        const streetRates = isEn ? (gen.street_parking_rates_en || gen.street_parking_rates_fr) : (gen.street_parking_rates_fr || gen.street_parking_rates_en);
        const streetInfo = isEn ? (gen.street_parking_info_en || gen.street_parking_info_fr) : (gen.street_parking_info_fr || gen.street_parking_info_en);

        if (streetSched || streetRates) {
            html += `
                <div class="rounded-2xl border border-[#e2e4e6] dark:border-[#38455d] overflow-hidden bg-white dark:bg-[#243047] shadow-2xs">
                    <button type="button" id="parking-acc-trigger-street" onclick="toggleParkingSection('street')"
                            class="w-full flex items-center justify-between p-3.5 bg-[#faf9f6] dark:bg-[#1e2739] hover:bg-gold/5 transition text-left cursor-pointer group">
                        <div class="flex items-center space-x-3 min-w-0 pr-2">
                            <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0 text-sm">
                                🅿️
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-xs sm:text-sm font-bold text-ink dark:text-white group-hover:text-gold transition truncate">${I18n.t('parking_sec_street_title')}</h4>
                                    <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex-shrink-0">EasyPark</span>
                                </div>
                                <p class="text-[11px] text-ink-soft dark:text-gray-400 truncate mt-0.5">${I18n.t('parking_sec_street_desc')}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-ink-faint dark:text-gray-400 transform transition-transform duration-200 acc-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="parking-acc-content-street" class="p-3.5 space-y-3 border-t border-[#e2e4e6] dark:border-[#38455d] hidden">
                        ${streetInfo ? `<p class="text-xs text-ink-soft dark:text-gray-300 leading-relaxed">${Utils.escapeHtml(streetInfo)}</p>` : ''}
                        
                        ${streetSched ? `
                            <div class="p-3 rounded-xl bg-[#faf9f6] dark:bg-[#1e2739] border border-[#e2e4e6] dark:border-[#38455d]">
                                <span class="text-[10px] font-bold text-gold uppercase tracking-wider block mb-1">🕒 ${I18n.t('parking_street_schedule_title')}</span>
                                <p class="text-xs font-medium text-ink dark:text-white leading-relaxed">${Utils.escapeHtml(streetSched)}</p>
                            </div>
                        ` : ''}

                        ${streetRates ? `
                            <div class="p-3 rounded-xl bg-[#faf9f6] dark:bg-[#1e2739] border border-[#e2e4e6] dark:border-[#38455d]">
                                <span class="text-[10px] font-bold text-gold uppercase tracking-wider block mb-1">💶 ${I18n.t('parking_street_rates_title')}</span>
                                <div class="text-xs text-ink dark:text-white space-y-1 font-mono">${Utils.escapeHtml(streetRates).replace(/\n/g, '<br>')}</div>
                            </div>
                        ` : ''}

                        <a href="https://www.easypark.com" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-bold transition shadow-xs">
                            <span>🅿️</span> ${I18n.t('parking_easypark_btn')} ↗
                        </a>
                    </div>
                </div>
            `;
        }

        // 4. ACCORDÉON - SECTION 3 : Parkings privés & couverts
        if (privateParkings.length > 0) {
            let privCards = '';
            privateParkings.forEach(p => {
                const name = isEn ? (p.name_en || p.name_fr) : p.name_fr;
                const walk = isEn ? (p.walking_time_en || p.walking_time_fr) : p.walking_time_fr;
                const rates = isEn ? (p.rates_en || p.rates_fr) : p.rates_fr;
                const hours = isEn ? (p.opening_hours_en || p.opening_hours_fr) : p.opening_hours_fr;
                const vehicles = isEn ? (p.suitable_vehicles_en || p.suitable_vehicles_fr) : p.suitable_vehicles_fr;
                const notes = isEn ? (p.notes_en || p.notes_fr) : p.notes_fr;
                const mapsQuery = encodeURIComponent(p.address || name);

                privCards += `
                    <div class="p-3 sm:p-3.5 rounded-xl bg-[#faf9f6] dark:bg-[#1e2739] border border-[#e2e4e6] dark:border-[#38455d] space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold text-ink dark:text-white">${Utils.escapeHtml(name)}</h5>
                                <p class="text-[11px] text-ink-faint dark:text-gray-400 mt-0.5">📍 ${Utils.escapeHtml(p.address || '')}</p>
                            </div>
                            ${p.operator ? `<span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-gold/15 text-gold-text dark:text-gold border border-gold/30 flex-shrink-0 whitespace-nowrap">${Utils.escapeHtml(p.operator)}</span>` : ''}
                        </div>

                        <div class="flex flex-wrap gap-1.5 text-[10px]">
                            ${walk ? `<span class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-ink dark:text-gray-300 font-medium">🚶 ${Utils.escapeHtml(walk)}</span>` : ''}
                            ${p.max_height ? `<span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-700 dark:text-amber-400 font-semibold">↕️ Max ${Utils.escapeHtml(p.max_height)}</span>` : ''}
                            ${hours ? `<span class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-ink dark:text-gray-300">🕒 ${Utils.escapeHtml(hours)}</span>` : ''}
                        </div>

                        ${rates ? `<p class="text-xs font-semibold text-gold-text dark:text-gold bg-gold/10 dark:bg-gold/15 p-2 rounded-lg">💶 ${Utils.escapeHtml(rates)}</p>` : ''}
                        ${vehicles ? `<p class="text-[11px] text-ink-soft dark:text-gray-300">🚗 ${Utils.escapeHtml(vehicles)}</p>` : ''}
                        ${notes ? `<p class="text-[10px] text-ink-faint dark:text-gray-400 italic">${Utils.escapeHtml(notes)}</p>` : ''}

                        <div class="flex items-center gap-2 pt-1">
                            <a href="https://www.google.com/maps/search/?api=1&query=${mapsQuery}" target="_blank" rel="noopener noreferrer"
                               class="flex-1 py-1.5 px-3 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gold hover:text-white dark:hover:bg-gold text-ink dark:text-white text-xs font-semibold text-center transition">
                                📍 ${I18n.t('parking_maps_btn')}
                            </a>
                            ${p.booking_url ? `
                                <a href="${p.booking_url}" target="_blank" rel="noopener noreferrer"
                                   class="flex-1 py-1.5 px-3 rounded-lg bg-gold hover:bg-[#a38234] text-white text-xs font-bold text-center transition shadow-xs">
                                    🎫 ${I18n.t('parking_book_btn')} ↗
                                </a>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="rounded-2xl border border-[#e2e4e6] dark:border-[#38455d] overflow-hidden bg-white dark:bg-[#243047] shadow-2xs">
                    <button type="button" id="parking-acc-trigger-private" onclick="toggleParkingSection('private')"
                            class="w-full flex items-center justify-between p-3.5 bg-[#faf9f6] dark:bg-[#1e2739] hover:bg-gold/5 transition text-left cursor-pointer group">
                        <div class="flex items-center space-x-3 min-w-0 pr-2">
                            <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0 text-sm">
                                🏢
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-xs sm:text-sm font-bold text-ink dark:text-white group-hover:text-gold transition truncate">${I18n.t('parking_sec_private_title')}</h4>
                                    <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex-shrink-0">${privateParkings.length} ${isEn ? 'lots' : 'parkings'}</span>
                                </div>
                                <p class="text-[11px] text-ink-soft dark:text-gray-400 truncate mt-0.5">${I18n.t('parking_sec_private_desc')}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-ink-faint dark:text-gray-400 transform transition-transform duration-200 acc-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="parking-acc-content-private" class="p-3.5 space-y-2.5 border-t border-[#e2e4e6] dark:border-[#38455d] hidden">
                        ${privCards}
                    </div>
                </div>
            `;
        }

        // 5. ACCORDÉON - SECTION 4 : Parkings Relais TCL (P+R)
        if (tclPrs.length > 0) {
            let tclCards = '';
            const tclCond = isEn ? (gen.tcl_conditions_en || gen.tcl_conditions_fr) : (gen.tcl_conditions_fr || gen.tcl_conditions_en);

            if (tclCond) {
                tclCards += `
                    <div class="p-3 rounded-xl bg-amber-500/10 dark:bg-amber-500/15 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-xs leading-relaxed font-medium">
                        <span class="font-bold text-[10px] uppercase tracking-wider block text-amber-800 dark:text-amber-300 mb-0.5">🚇 ${I18n.t('parking_tcl_rule_title')}</span>
                        ${Utils.escapeHtml(tclCond)}
                    </div>
                `;
            }

            tclPrs.forEach(pr => {
                const name = isEn ? (pr.name_en || pr.name_fr) : pr.name_fr;
                const hours = isEn ? (pr.opening_hours_en || pr.opening_hours_fr) : pr.opening_hours_fr;
                const transit = isEn ? (pr.transit_time_en || pr.transit_time_fr) : pr.transit_time_fr;
                const reach = isEn ? (pr.how_to_reach_en || pr.how_to_reach_fr) : pr.how_to_reach_fr;
                const notes = isEn ? (pr.notes_en || pr.notes_fr) : pr.notes_fr;
                const mapsQuery = encodeURIComponent(pr.address || name);

                tclCards += `
                    <div class="p-3 sm:p-3.5 rounded-xl bg-[#faf9f6] dark:bg-[#1e2739] border border-[#e2e4e6] dark:border-[#38455d] space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold text-ink dark:text-white">${Utils.escapeHtml(name)}</h5>
                                <p class="text-[11px] text-ink-faint dark:text-gray-400 mt-0.5">📍 ${Utils.escapeHtml(pr.address || '')}</p>
                            </div>
                            ${pr.capacity ? `<span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex-shrink-0">${Utils.escapeHtml(pr.capacity)}</span>` : ''}
                        </div>

                        <div class="flex flex-wrap gap-1.5 text-[10px]">
                            ${transit ? `<span class="px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-700 dark:text-blue-300 font-semibold">⏱️ ${Utils.escapeHtml(transit)}</span>` : ''}
                            ${hours ? `<span class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-ink dark:text-gray-300">🕒 ${Utils.escapeHtml(hours)}</span>` : ''}
                        </div>

                        ${reach ? `
                            <div class="p-2.5 rounded-lg bg-blue-50/70 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40 text-xs text-blue-900 dark:text-blue-200 leading-relaxed">
                                <span class="font-bold block text-[10px] uppercase tracking-wider text-blue-700 dark:text-blue-400 mb-0.5">${I18n.t('parking_how_to_reach')} :</span>
                                ${Utils.escapeHtml(reach)}
                            </div>
                        ` : ''}

                        ${notes ? `<p class="text-[10px] text-ink-faint dark:text-gray-400 italic">${Utils.escapeHtml(notes)}</p>` : ''}

                        <div class="flex items-center gap-2 pt-1">
                            <a href="https://www.google.com/maps/search/?api=1&query=${mapsQuery}" target="_blank" rel="noopener noreferrer"
                               class="flex-1 py-1.5 px-3 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gold hover:text-white text-ink dark:text-white text-xs font-semibold text-center transition">
                                📍 ${I18n.t('parking_maps_btn')}
                            </a>
                            <a href="https://www.tcl.fr" target="_blank" rel="noopener noreferrer"
                               class="flex-1 py-1.5 px-3 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold text-center transition shadow-xs">
                                🚇 ${I18n.t('parking_tcl_btn')} ↗
                            </a>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="rounded-2xl border border-[#e2e4e6] dark:border-[#38455d] overflow-hidden bg-white dark:bg-[#243047] shadow-2xs">
                    <button type="button" id="parking-acc-trigger-tcl" onclick="toggleParkingSection('tcl')"
                            class="w-full flex items-center justify-between p-3.5 bg-[#faf9f6] dark:bg-[#1e2739] hover:bg-gold/5 transition text-left cursor-pointer group">
                        <div class="flex items-center space-x-3 min-w-0 pr-2">
                            <div class="w-8 h-8 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center flex-shrink-0 text-sm">
                                🚇
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-xs sm:text-sm font-bold text-ink dark:text-white group-hover:text-gold transition truncate">${I18n.t('parking_sec_tcl_title')}</h4>
                                    <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 flex-shrink-0">TCL P+R</span>
                                </div>
                                <p class="text-[11px] text-ink-soft dark:text-gray-400 truncate mt-0.5">${I18n.t('parking_sec_tcl_desc')}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-ink-faint dark:text-gray-400 transform transition-transform duration-200 acc-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="parking-acc-content-tcl" class="p-3.5 space-y-2.5 border-t border-[#e2e4e6] dark:border-[#38455d] hidden">
                        ${tclCards}
                    </div>
                </div>
            `;
        }

        container.innerHTML = html;
    },

    renderVideos(videos) {
        const container = document.getElementById('videos-list');
        container.innerHTML = '';
        const isEn = I18n.currentLang === 'en';

        videos.forEach((video, index) => {
            const card = document.createElement('div');
            card.className = 'video-card card-interactive cascade-item cursor-pointer flex items-center p-3.5 rounded-2xl shadow-xs group';
            card.style.animationDelay = `${Math.min(index * 40, 400)}ms`;
            card.setAttribute('data-title', (video.title_fr + ' ' + (video.title_en || '')).toLowerCase());
            card.onclick = () => ModalManager.openVideo(video);

            const bgClass = Utils.sanitizeClassName(video.icon_bg_color, 'bg-blue-50');
            const textClass = Utils.sanitizeClassName(video.icon_text_color, 'text-blue-500');

            const primaryTitle = isEn ? (video.title_en || video.title_fr) : video.title_fr;
            const secondaryTitle = isEn ? (video.title_en ? video.title_fr : '') : (video.title_en || '');

            card.innerHTML = `
                <div class="w-11 h-11 flex-shrink-0 ${bgClass} ${textClass} rounded-xl flex items-center justify-center text-xl mr-3.5">
                    ${Utils.escapeHtml(video.icon || '💡')}
                </div>
                <div class="flex-1 min-w-0 pr-2">
                    <h3 class="font-semibold text-sm text-ink dark:text-white truncate group-hover:text-gold transition">
                        ${Utils.escapeHtml(primaryTitle)}
                    </h3>
                    ${secondaryTitle ? `<p class="text-[11px] text-ink-faint dark:text-gray-400 truncate">${Utils.escapeHtml(secondaryTitle)}</p>` : ''}
                </div>
                <div class="w-8 h-8 rounded-full bg-[#f3f3ec] dark:bg-[#1e2739] flex items-center justify-center text-gold group-hover:bg-gold group-hover:text-white transition flex-shrink-0">
                    <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
            `;
            container.appendChild(card);
        });
    },

    _tabAnimTimeout: null,
    _motionTimer: null,

    triggerTabMotion(tabId) {
        const btnGuides = document.getElementById('btn-tab-guides');
        const btnVideos = document.getElementById('btn-tab-videos');
        if (!btnGuides || !btnVideos) return;

        // 1. Annuler tout timer précédent
        if (this._motionTimer) {
            clearTimeout(this._motionTimer);
            this._motionTimer = null;
        }

        // 2. Nettoyer immédiatement la classe d'animation sur TOUTES les icônes d'onglets
        const allIcons = document.querySelectorAll('.segmented-nav .tab-svg-icon');
        allIcons.forEach(icon => {
            icon.classList.remove('is-animating');
        });

        // 3. Identifier le bouton HTML et l'icône SVG à animer
        const targetBtn = tabId === 'guides' ? btnGuides : btnVideos;
        const targetIcon = targetBtn ? targetBtn.querySelector('.tab-svg-icon') : null;
        if (!targetBtn || !targetIcon) return;

        // 4. Forcer un vrai reflow du moteur de rendu sur les éléments HTML parents (HTMLElement.offsetWidth)
        // Indispensable car SVGElement.offsetWidth est undefined en spécification SVG standard
        void targetBtn.offsetWidth;
        const iconWrapper = targetBtn.querySelector('.tab-icon-wrapper');
        if (iconWrapper) void iconWrapper.offsetWidth;

        // 5. Réenclencher l'animation au frame suivant pour garantir le redémarrage à 0ms
        requestAnimationFrame(() => {
            targetIcon.classList.add('is-animating');

            // 6. Nettoyer automatiquement après la fin de l'animation (480ms) pour préparer le prochain clic
            this._motionTimer = setTimeout(() => {
                targetIcon.classList.remove('is-animating');
                this._motionTimer = null;
            }, 480);
        });
    },

    switchTab(tabId, animate = false) {
        const prevTab = AppState.currentActiveTab;
        if (prevTab === tabId && !animate) return;

        AppState.currentActiveTab = tabId;

        const btnGuides = document.getElementById('btn-tab-guides');
        const btnVideos = document.getElementById('btn-tab-videos');
        const contentGuides = document.getElementById('content-guides');
        const contentVideos = document.getElementById('content-videos');
        const wrapper = document.getElementById('tab-panels-wrapper');

        if (!contentGuides || !contentVideos) return;

        const isGuides = tabId === 'guides';

        if (btnGuides) {
            btnGuides.classList.toggle('active', isGuides);
            btnGuides.setAttribute('aria-selected', isGuides ? 'true' : 'false');
        }
        if (btnVideos) {
            btnVideos.classList.toggle('active', !isGuides);
            btnVideos.setAttribute('aria-selected', !isGuides ? 'true' : 'false');
        }

        // Déclenchement du motion design exclusif sur l'icône SVG de l'onglet sélectionné
        this.triggerTabMotion(tabId);

        // Si pas d'animation demandée ou premier chargement
        if (!animate || !prevTab || prevTab === tabId) {
            contentGuides.classList.toggle('hidden', !isGuides);
            contentVideos.classList.toggle('hidden', isGuides);
            contentGuides.classList.remove('tab-slide-in-right', 'tab-slide-in-left', 'tab-slide-out-left', 'tab-slide-out-right');
            contentVideos.classList.remove('tab-slide-in-right', 'tab-slide-in-left', 'tab-slide-out-left', 'tab-slide-out-right');
            if (wrapper) {
                wrapper.style.height = '';
                wrapper.style.transition = '';
            }
            ScrollManager.updatePageScrollState();
        } else {
            // Direction du glissement :
            // Guides -> Vidéos : glissement vers la gauche (forward)
            // Vidéos -> Guides : glissement vers la droite (backward)
            const isForward = !isGuides;

            const outgoing = isGuides ? contentVideos : contentGuides;
            const incoming = isGuides ? contentGuides : contentVideos;

            if (this._tabAnimTimeout) {
                clearTimeout(this._tabAnimTimeout);
                this._tabAnimTimeout = null;
                if (wrapper) {
                    wrapper.style.height = '';
                    wrapper.style.transition = '';
                }
            }

            contentGuides.classList.remove('tab-slide-in-right', 'tab-slide-in-left', 'tab-slide-out-left', 'tab-slide-out-right');
            contentVideos.classList.remove('tab-slide-in-right', 'tab-slide-in-left', 'tab-slide-out-left', 'tab-slide-out-right');

            // Stabilisation absolue de la hauteur pour empêcher tout sursaut vertical
            if (wrapper) {
                const currentH = outgoing.offsetHeight || 0;
                incoming.classList.remove('hidden');
                const targetH = incoming.offsetHeight || currentH;
                if (currentH > 0 && targetH > 0) {
                    wrapper.style.height = `${currentH}px`;
                    wrapper.style.transition = 'height 0.32s cubic-bezier(0.25, 1, 0.5, 1)';
                    requestAnimationFrame(() => {
                        wrapper.style.height = `${targetH}px`;
                    });
                }
            } else {
                incoming.classList.remove('hidden');
            }

            void outgoing.offsetHeight; // Force reflow

            if (isForward) {
                outgoing.classList.add('tab-slide-out-left');
                incoming.classList.add('tab-slide-in-right');
            } else {
                outgoing.classList.add('tab-slide-out-right');
                incoming.classList.add('tab-slide-in-left');
            }

            this._tabAnimTimeout = setTimeout(() => {
                outgoing.classList.add('hidden');
                outgoing.classList.remove('tab-slide-out-left', 'tab-slide-out-right');
                incoming.classList.remove('tab-slide-in-right', 'tab-slide-in-left');
                if (wrapper) {
                    wrapper.style.height = '';
                    wrapper.style.transition = '';
                }
                this._tabAnimTimeout = null;
                ScrollManager.updatePageScrollState();
            }, 320);
        }
    },

    navigateTab(tabId) {
        const isSwitching = AppState.currentActiveTab && AppState.currentActiveTab !== tabId;
        this.switchTab(tabId, isSwitching);
        if (!isSwitching) {
            // Rejouer l'animation de micro-interaction même au clic sur l'onglet déjà actif
            this.triggerTabMotion(tabId);
        }
        Router.setUrl(AppState.currentSlug, tabId);
    }
};

export const ListRenderer = {
    render(props) {
        const container = document.getElementById('properties-container');
        container.innerHTML = '';

        // Réinitialisation du manifeste PWA sur le portail d'accueil
        const manifestLink = document.getElementById('app-manifest-link');
        if (manifestLink) {
            manifestLink.href = `${BASE_PATH}manifest.php`;
        }
        const appleTitleMeta = document.querySelector('meta[name="apple-mobile-web-app-title"]');
        if (appleTitleMeta) {
            appleTitleMeta.setAttribute('content', "Carré d'Hôtes");
        }
        const canonicalLink = document.getElementById('app-canonical-link');
        if (canonicalLink) {
            canonicalLink.href = BASE_PATH;
        }

        if (!props || props.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-8 text-xs text-ink-soft dark:text-gray-300 bg-[#faf9f6] dark:bg-[#243047] rounded-2xl border border-line p-6">
                    <p class="font-semibold text-ink dark:text-white mb-1">Aucun hébergement trouvé dans la base.</p>
                    <p>Importez le fichier <code>database/install.sql</code> pour charger vos logements.</p>
                </div>
            `;
        } else {
            props.forEach((p, index) => {
                const card = document.createElement('div');
                card.className = 'card-interactive cascade-item cursor-pointer flex items-center justify-between p-4 rounded-2xl group';
                card.style.animationDelay = `${Math.min(index * 45, 500)}ms`;
                // Navigation directe pour charger le manifeste PWA propre à ce logement
                card.onclick = () => {
                    window.location.href = BASE_PATH + encodeURIComponent(p.slug);
                };
                
                const count = parseInt(p.videos_count || p.tutorials_count || 0, 10);
                const isEn = I18n.currentLang === 'en';
                const subtitleText = count > 0 
                    ? (isEn ? `Information & ${count} videos` : `Informations & ${count} vidéos`) 
                    : (isEn ? `Welcome Guide (FR / EN)` : `Guide de bienvenue (FR / EN)`);

                card.innerHTML = `
                    <div class="flex items-center min-w-0 pr-3">
                        <div class="w-10 h-10 rounded-xl bg-[#f3f3ec] dark:bg-[#1e2739] flex items-center justify-center text-lg mr-3 text-ink dark:text-gold group-hover:bg-gold group-hover:text-white transition flex-shrink-0">
                            🏡
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-ink dark:text-white group-hover:text-gold transition truncate">
                                ${Utils.escapeHtml(p.name)}
                            </h3>
                            <p class="text-[11px] text-ink-faint dark:text-gray-400 truncate">${subtitleText}</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-ink-faint group-hover:text-gold transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                `;
                container.appendChild(card);
            });
        }

        document.getElementById('view-list').classList.remove('hidden');
        ScrollManager.resetToTop();
        ScrollManager.updatePageScrollState();
    }
};

// -----------------------------------------------------------------------------
// 9. RECHERCHE RAPIDE DE VIDÉOS
// -----------------------------------------------------------------------------
export const VideoSearch = {
    _debounceTimer: null,

    filter() {
        const query = document.getElementById('video-search').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.video-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            if (title.includes(query)) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        document.getElementById('no-video-found').classList.toggle('hidden', visibleCount > 0);
        ScrollManager.updatePageScrollState();

        if (this._debounceTimer) clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => {
            if (query.length > 0) {
                const template = I18n.t('a11y_search_results');
                Utils.announceA11y(template.replace('{count}', visibleCount));
            }
        }, 400);
    }
};



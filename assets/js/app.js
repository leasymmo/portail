/**
 * ==============================================================================
 * PORTAIL VOYAGEURS - CONCIERGERIE CARRÉ D'HÔTES
 * Application Frontend & Progressive Web App (PWA)
 * Version: 2.4.0
 * ==============================================================================
 */

'use strict';

// -----------------------------------------------------------------------------
// 1. ÉTAT GLOBAL DE L'APPLICATION (STATE)
// -----------------------------------------------------------------------------
const AppState = {
    currentProperty: null,
    currentVideos: [],
    currentActiveTab: 'guides',
    currentSlug: '',
    isPdfModalOpen: false,
    isVideoModalOpen: false,
    isBookingModalOpen: false,
    isWifiModalOpen: false,
    isReviewModalOpen: false,
    isOffline: !navigator.onLine
};

// -----------------------------------------------------------------------------
// 1b. GESTION DU MULTILINGUE (FR / EN) & I18N
// -----------------------------------------------------------------------------
const I18n = {
    currentLang: 'fr',

    translations: {
        fr: {
            app_loading: "Chargement de votre portail...",
            topbar_visit: "Visiter carredhotes.fr",
            theme_toggle: "Basculer le mode sombre / clair",
            wifi_network: "Réseau WiFi",
            wifi_prefix: "Wi-Fi : ",
            wifi_pwd_prefix: "Mot de passe : ",
            wifi_card_title: "Connexion Wi-Fi",
            wifi_card_desc: "Réseau & mot de passe",
            wifi_modal_title: "Connexion Wi-Fi",
            wifi_modal_subtitle: "Accès Internet du logement",
            wifi_modal_network_label: "Nom du réseau (SSID)",
            wifi_modal_pwd_label: "Mot de passe",
            wifi_modal_hint: "Touchez l'icône pour copier dans le presse-papiers",
            wifi_ssid_copied_toast: "Nom du réseau copié !",
            wifi_copy: "Copier",
            wifi_copied: "Copié !",
            wifi_copied_toast: "Mot de passe Wi-Fi copié !",
            copy_network_title: "Copier le nom du réseau",
            copy_pwd_title: "Copier le mot de passe",
            tab_infos: "Informations",
            tab_videos: "Vidéos",
            welcome_title: "Guide de bienvenue",
            welcome_subtitle: "Toutes les informations pratiques pour votre séjour",
            booklet_title: "Guide de bienvenue",
            booklet_desc: "Toutes les infos pratiques",
            booking_card_title: "Réservez votre prochain séjour",
            booking_card_desc: "Bénéficiez de nos meilleurs tarifs en direct",
            review_card_title: "Vous appréciez votre séjour ?",
            review_card_desc: "Soutenez notre équipe avec 5 étoiles",
            review_modal_title: "Votre avis compte pour nous !",
            review_modal_subtitle: "Conciergerie Carré d'Hôtes · Avis Google",
            review_modal_heading: "Votre satisfaction est notre priorité",
            review_modal_msg: "Votre retour est la plus belle récompense pour notre équipe locale. 1 minute suffit pour illuminer notre journée et nous aider à faire rayonner notre conciergerie !",
            review_modal_btn: "Laisser mes 5 étoiles sur Google ✨",
            review_modal_help: "Une remarque ou un imprévu ? Contactez-nous en direct",
            need_help: "Besoin d'aide ?",
            call_concierge: "Appeler la conciergerie",
            videos_title: "Modes d'emploi",
            videos_subtitle: "Fonctionnement des équipements en vidéo",
            video_search_placeholder: "Rechercher un équipement (ex: café, lave-linge...)",
            no_video_found: "Aucun équipement trouvé pour cette recherche.",
            home_title: "Portail Voyageurs",
            home_subtitle: "Conciergerie Carré d'Hôtes",
            home_welcome: "Bienvenue",
            home_welcome_desc: "Sélectionnez votre hébergement pour consulter vos guides et vidéos.",
            home_not_booked: "Vous n'avez pas encore réservé ?",
            home_not_booked_desc: "Découvrez tous nos hébergements et réservez votre séjour en direct.",
            home_book_btn: "Réservez votre prochain séjour",
            not_found_title: "Logement introuvable",
            not_found_desc: "Aucun hébergement actif ne correspond à cette adresse.",
            not_found_btn: "Voir tous les logements",
            error_title: "Configuration requise",
            error_desc: "Impossible de se connecter à la base MySQL.",
            error_retry: "Lancer le test de connexion",
            footer_wish: "Nous vous souhaitons un excellent séjour !",
            footer_concierge: "Carré d'Hôtes — Conciergerie à Lyon",
            pdf_title_fr: "Guide de bienvenue",
            pdf_title_en: "Welcome Guide",
            pdf_reading: "Lecture directe",
            pdf_open_drive: "Ouvrir dans un nouvel onglet",
            pdf_loading: "Chargement du guide de bienvenue...",
            pdf_close: "Fermer",
            video_modal_title: "Vidéo explicative",
            booking_modal_title: "Réservez votre prochain séjour",
            booking_modal_subtitle: "Conciergerie Carré d'Hôtes · Réservation directe",
            booking_spinner: "Chargement du moteur de recherche...",
            offline_toast: "Mode hors-ligne actif (guides consultés disponibles)",
            pwa_title: "Installer l'application",
            pwa_desc: "Accédez à vos guides et vidéos en un clic depuis votre écran d'accueil, même hors ligne.",
            pwa_later: "Plus tard",
            pwa_add: "Ajouter à l'écran d'accueil",
            pwa_ios_step1: "Appuyez sur Partager (en bas de votre écran)",
            pwa_ios_step2: "Faites défiler et choisissez « Sur l'écran d'accueil »",
            pwa_ios_gotit: "J'ai compris"
        },
        en: {
            app_loading: "Loading your portal...",
            topbar_visit: "Visit carredhotes.fr",
            theme_toggle: "Toggle dark / light mode",
            wifi_network: "WiFi Network",
            wifi_prefix: "Wi-Fi: ",
            wifi_pwd_prefix: "Password: ",
            wifi_card_title: "Wi-Fi Connection",
            wifi_card_desc: "Network & password",
            wifi_modal_title: "Wi-Fi Connection",
            wifi_modal_subtitle: "Accommodation Internet access",
            wifi_modal_network_label: "Network name (SSID)",
            wifi_modal_pwd_label: "Password",
            wifi_modal_hint: "Tap the icon to copy to clipboard",
            wifi_ssid_copied_toast: "Network name copied!",
            wifi_copy: "Copy",
            wifi_copied: "Copied!",
            wifi_copied_toast: "Wi-Fi password copied!",
            copy_network_title: "Copy network name",
            copy_pwd_title: "Copy password",
            tab_infos: "Information",
            tab_videos: "Videos",
            welcome_title: "Welcome Guide",
            welcome_subtitle: "Practical information and tips for a wonderful stay",
            booklet_title: "Welcome Guide",
            booklet_desc: "All practical information",
            booking_card_title: "Book your next stay",
            booking_card_desc: "Enjoy our best direct rates without fees",
            review_card_title: "Enjoying your stay?",
            review_card_desc: "Support our local team with 5 stars",
            review_modal_title: "Your feedback means the world to us!",
            review_modal_subtitle: "Carré d'Hôtes Concierge · Google Review",
            review_modal_heading: "Your satisfaction is our priority",
            review_modal_msg: "Your feedback is the greatest reward for our local team. Just 1 minute to brighten our day and support our independent concierge!",
            review_modal_btn: "Leave my 5 stars on Google ✨",
            review_modal_help: "Any issue or suggestion? Contact us directly",
            need_help: "Need help?",
            call_concierge: "Call the concierge",
            videos_title: "Instructions & Tutorials",
            videos_subtitle: "How to use equipment in video",
            video_search_placeholder: "Search equipment (e.g. coffee, washer, stove)...",
            no_video_found: "No equipment found for this search.",
            home_title: "Guest Portal",
            home_subtitle: "Carré d'Hôtes Concierge",
            home_welcome: "Welcome",
            home_welcome_desc: "Select your accommodation to access your guides and videos.",
            home_not_booked: "Haven't booked yet?",
            home_not_booked_desc: "Discover all our accommodations and book directly.",
            home_book_btn: "Book your next stay",
            not_found_title: "Accommodation not found",
            not_found_desc: "No active accommodation matches this address.",
            not_found_btn: "View all accommodations",
            error_title: "Configuration required",
            error_desc: "Unable to connect to the MySQL database.",
            error_retry: "Run connection test",
            footer_wish: "Have a wonderful stay in Lyon!",
            footer_concierge: "Carré d'Hôtes — Concierge in Lyon",
            pdf_title_fr: "Guide de bienvenue",
            pdf_title_en: "Welcome Guide",
            pdf_reading: "Direct reading",
            pdf_open_drive: "Open in a new tab",
            pdf_loading: "Loading welcome guide...",
            pdf_close: "Close",
            video_modal_title: "Tutorial video",
            booking_modal_title: "Book your next stay",
            booking_modal_subtitle: "Carré d'Hôtes Concierge · Direct booking",
            booking_spinner: "Loading search engine...",
            offline_toast: "Offline mode active (viewed guides available)",
            pwa_title: "Install the app",
            pwa_desc: "Access your guides and videos in one tap from your home screen, even offline.",
            pwa_later: "Later",
            pwa_add: "Add to home screen",
            pwa_ios_step1: "Tap Share (at the bottom of your screen)",
            pwa_ios_step2: "Scroll down and choose 'Add to Home Screen'",
            pwa_ios_gotit: "Got it"
        }
    },

    init() {
        const saved = localStorage.getItem('cdh_language');
        if (saved === 'fr' || saved === 'en') {
            this.currentLang = saved;
        } else {
            const browserLang = (navigator.language || navigator.userLanguage || 'fr').toLowerCase();
            this.currentLang = browserLang.startsWith('fr') ? 'fr' : 'en';
        }
        this.apply();
    },

    toggle() {
        this.setLanguage(this.currentLang === 'fr' ? 'en' : 'fr');
    },

    setLanguage(lang) {
        if (lang !== 'fr' && lang !== 'en') return;
        this.currentLang = lang;
        try { localStorage.setItem('cdh_language', lang); } catch (e) {}
        this.apply();
        if (AppState.currentProperty) {
            PropertyRenderer.updateLanguageContent();
        } else if (AppState.allProperties && AppState.allProperties.length > 0) {
            ListRenderer.render(AppState.allProperties);
        }
    },

    t(key) {
        return (this.translations[this.currentLang] && this.translations[this.currentLang][key])
            || (this.translations['fr'] && this.translations['fr'][key])
            || key;
    },

    apply() {
        document.documentElement.lang = this.currentLang;
        const flagFr = document.getElementById('lang-flag-fr');
        const flagEn = document.getElementById('lang-flag-en');
        if (flagFr && flagEn) {
            flagFr.classList.toggle('hidden', this.currentLang !== 'fr');
            flagEn.classList.toggle('hidden', this.currentLang !== 'en');
        }
        const langBtn = document.getElementById('lang-toggle-btn');
        if (langBtn) {
            const title = this.currentLang === 'fr' ? 'Switch to English' : 'Passer en Français';
            langBtn.title = title;
            langBtn.setAttribute('aria-label', title);
        }

        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            const translation = this.t(key);
            if (translation) el.textContent = translation;
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
            const key = el.getAttribute('data-i18n-placeholder');
            const translation = this.t(key);
            if (translation) el.placeholder = translation;
        });

        document.querySelectorAll('[data-i18n-title]').forEach((el) => {
            const key = el.getAttribute('data-i18n-title');
            const translation = this.t(key);
            if (translation) el.title = translation;
        });
    }
};

// -----------------------------------------------------------------------------
// 2. UTILITAIRES & SÉCURITÉ (XSS & URLS)
// -----------------------------------------------------------------------------
const Utils = {
    /**
     * Échappe les caractères HTML dangereux pour éviter toute injection XSS
     */
    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    /**
     * Valide et sécurise les URLs externes avant injection dans le DOM
     */
    safeUrl(url) {
        if (!url || typeof url !== 'string') return '#';
        const trimmed = url.trim();
        if (/^(https?:\/\/|\/|tel:|mailto:)/i.test(trimmed)) {
            return trimmed;
        }
        return '#';
    },

    /**
     * Nettoie les noms de classes CSS dynamiques
     */
    sanitizeClassName(cls, defaultClass) {
        if (!cls || typeof cls !== 'string') return defaultClass;
        const sanitized = cls.replace(/[^a-zA-Z0-9_\-\s]/g, '').trim();
        return sanitized || defaultClass;
    },

    /**
     * Détermine si une URL vidéo provient de Google Drive
     */
    isGoogleDriveUrl(url) {
        return /drive\.google\.com/i.test(url);
    },

    /**
     * Transforme une URL Google Drive en lien intégrable /preview
     */
    getGoogleDriveEmbedUrl(url) {
        const match = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
        if (match && match[1]) {
            return `https://drive.google.com/file/d/${match[1]}/preview`;
        }
        return url;
    },

    /**
     * Calcule ou extrait le slug d'une vidéo
     */
    getVideoSlug(v) {
        if (v.slug && v.slug.trim()) return v.slug.trim();
        return String(v.title_fr || '')
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/['’]/g, '-')
            .replace(/[^a-z0-9\-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') || `video-${v.id}`;
    }
};

// -----------------------------------------------------------------------------
// 3. GESTION DU DÉFILEMENT (SCROLL MANAGER)
// -----------------------------------------------------------------------------
const ScrollManager = {
    init() {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        this.updateTopbarHeight();
        window.addEventListener('resize', () => {
            this.updateTopbarHeight();
            this.updatePageScrollState();
        });
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.updateTopbarHeight();
                this.updatePageScrollState();
            }, 100);
        });
        window.addEventListener('beforeunload', () => this.resetToTop());
        window.addEventListener('pageshow', () => {
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
            this.resetToTop();
            this.updateTopbarHeight();
            this.updatePageScrollState();
        });
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => {
                this.updateTopbarHeight();
                this.updatePageScrollState();
            });
        }
    },

    updateTopbarHeight() {
        const topbar = document.getElementById('main-topbar');
        if (topbar) {
            const h = topbar.offsetHeight;
            if (h > 0) {
                document.documentElement.style.setProperty('--topbar-height', `${h}px`);
            }
        }
    },

    resetToTop() {
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
        if (document.documentElement) document.documentElement.scrollTop = 0;
        if (document.body) document.body.scrollTop = 0;
        const mainEl = document.querySelector('main');
        if (mainEl) mainEl.scrollTop = 0;

        const propScroller = document.getElementById('scroll-container');
        if (propScroller) propScroller.scrollTop = 0;

        const listScroller = document.getElementById('list-scroll-container');
        if (listScroller) listScroller.scrollTop = 0;

        this.flushWebKitLayers();
    },

    flushWebKitLayers() {
        // Stabilisation propre des couches de composition sans défilement artificiel
        const topbar = document.getElementById('main-topbar');
        if (topbar) {
            topbar.style.transform = 'translateZ(0)';
        }
    },

    updatePageScrollState() {
        requestAnimationFrame(() => {
            const isPropertyView = !document.getElementById('view-property').classList.contains('hidden');
            const isListView = !document.getElementById('view-list').classList.contains('hidden');
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
            let contentHeight = 0;

            const topbar = document.getElementById('main-topbar');
            const topbarH = topbar ? topbar.offsetHeight : 0;
            const footer = document.querySelector('footer');
            const footerH = footer ? footer.offsetHeight : 0;

            if (isPropertyView) {
                const propHeader = document.querySelector('#view-property > header');
                const propHeaderH = propHeader ? propHeader.offsetHeight : 0;

                const tabs = document.getElementById('property-tabs-container');
                const tabsH = (tabs && !tabs.classList.contains('hidden')) ? tabs.offsetHeight : 0;

                const isGuides = AppState.currentActiveTab === 'guides';
                const activeContent = isGuides ? document.getElementById('content-guides') : document.getElementById('content-videos');
                const activeContentH = activeContent ? activeContent.offsetHeight : 0;

                const scrollContainer = document.getElementById('scroll-container');
                let paddingY = 28;
                if (scrollContainer) {
                    const cs = window.getComputedStyle(scrollContainer);
                    paddingY = (parseFloat(cs.paddingTop) || 14) + (parseFloat(cs.paddingBottom) || 14);
                }

                contentHeight = topbarH + propHeaderH + tabsH + activeContentH + paddingY + footerH;
            } else if (isListView) {
                const listHeader = document.querySelector('#view-list > header');
                const listHeaderH = listHeader ? listHeader.offsetHeight : 0;

                const listCards = document.getElementById('properties-container');
                const listCardsH = listCards ? listCards.offsetHeight : 0;

                const listContainer = document.getElementById('list-scroll-container');
                let listPaddingY = 40;
                if (listContainer) {
                    const cs = window.getComputedStyle(listContainer);
                    listPaddingY = (parseFloat(cs.paddingTop) || 20) + (parseFloat(cs.paddingBottom) || 20);
                }

                const welcomeBlock = listContainer ? listContainer.querySelector('.text-center.mb-6') : null;
                const welcomeH = welcomeBlock ? welcomeBlock.offsetHeight : 60;
                const promoBlock = listContainer ? listContainer.querySelector('.border-t') : null;
                const promoH = promoBlock ? promoBlock.offsetHeight : 120;

                contentHeight = topbarH + listHeaderH + welcomeH + listCardsH + promoH + listPaddingY + footerH;
            }

            if (contentHeight > 0 && contentHeight <= viewportHeight) {
                document.documentElement.classList.add('no-scroll');
                document.body.classList.add('no-scroll');
                window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
                if (document.documentElement) document.documentElement.scrollTop = 0;
                if (document.body) document.body.scrollTop = 0;
            } else {
                document.documentElement.classList.remove('no-scroll');
                document.body.classList.remove('no-scroll');
            }
        });
    },

    lock() {
        document.body.classList.add('modal-open');
    },

    unlock() {
        if (!AppState.isPdfModalOpen && !AppState.isVideoModalOpen && !AppState.isBookingModalOpen && !AppState.isWifiModalOpen && !AppState.isReviewModalOpen) {
            document.body.classList.remove('modal-open');
            this.updatePageScrollState();
        }
    }
};

// -----------------------------------------------------------------------------
// 4. GESTION DU THÈME (LIGHT / DARK)
// -----------------------------------------------------------------------------
const ThemeManager = {
    init() {
        const isDark = document.documentElement.classList.contains('dark');
        this.updateUI(isDark);

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('ch_theme')) {
                this.apply(e.matches ? 'dark' : 'light', false);
            }
        });
    },

    toggle() {
        const isCurrentDark = document.documentElement.classList.contains('dark');
        this.apply(isCurrentDark ? 'light' : 'dark', true);
    },

    apply(theme, save = true) {
        const isDark = theme === 'dark';
        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.setAttribute('data-theme', theme);
        if (save) {
            localStorage.setItem('ch_theme', theme);
        }
        this.updateUI(isDark);
    },

    updateUI(isDark) {
        const metaTheme = document.getElementById('meta-theme-color');
        if (metaTheme) {
            metaTheme.setAttribute('content', isDark ? '#243047' : '#faf9f6');
        }

        const topbar = document.getElementById('main-topbar');
        if (topbar) {
            topbar.style.backgroundColor = isDark ? '#243047' : '#faf9f6';
        }
        const guard = document.getElementById('ios-statusbar-guard');
        if (guard) {
            guard.style.backgroundColor = isDark ? '#243047' : '#faf9f6';
        }

        const iconSun = document.getElementById('icon-sun');
        const iconMoon = document.getElementById('icon-moon');
        if (iconSun && iconMoon) {
            iconSun.classList.toggle('hidden', isDark);
            iconMoon.classList.toggle('hidden', !isDark);
        }

        const logoPath = isDark ? 'assets/images/picto-dark.png' : 'assets/images/picto-light.png';
        const logos = [
            document.getElementById('brand-logo'),
            document.getElementById('prop-logo'),
            document.getElementById('list-logo'),
            document.getElementById('loading-logo'),
            document.getElementById('notfound-logo'),
            document.getElementById('error-logo')
        ];

        logos.forEach(img => {
            if (img) img.src = logoPath;
        });
    }
};

// -----------------------------------------------------------------------------
// 5. ROUTEUR SPA PROPRE SANS HASH (/portail/{slug}/videos/{video})
// -----------------------------------------------------------------------------
const BASE_PATH = window.__CDH_BASE_PATH || (window.location.pathname.startsWith('/portail/dev') ? '/portail/dev/' : '/portail/');

const Router = {
    parseRoute() {
        let path = window.location.pathname;
        const prefixRegex = new RegExp('^' + BASE_PATH.replace(/\/$/, '') + '/?');
        path = path.replace(prefixRegex, '').replace(/\/$/, '');

        if (!path || path === 'index.html' || path === 'index.php') {
            return { slug: '', section: '', subSection: '' };
        }

        const parts = path.split('/').filter(Boolean);
        const first = (parts[0] || '').toLowerCase().trim();

        if (first === 'reservation') {
            return { slug: '', section: 'reservation', subSection: '' };
        }

        const slug = first;
        const part1 = (parts[1] || '').toLowerCase().trim();
        const part2 = (parts[2] || '').toLowerCase().trim();

        let section = 'guides';
        let subSection = '';

        if (part1 === 'reservation') {
            section = 'reservation';
        } else if (part1 === 'videos' || part1 === 'tutos') {
            section = 'videos';
            subSection = part2;
        } else if (part1 === 'guides' || part1 === 'infos' || part1 === 'info') {
            section = 'guides';
            subSection = part2;
        } else if (part1.startsWith('pdf-')) {
            section = 'guides';
            subSection = part1;
        } else if (part1.startsWith('video-')) {
            section = 'videos';
            subSection = part1;
        }

        return { slug, section, subSection };
    },

    setUrl(slug, section, subSection = '') {
        let newPath = BASE_PATH;
        if (slug) {
            newPath += slug;
            if (section === 'videos') {
                newPath += '/videos';
                if (subSection) newPath += '/' + subSection;
            } else if (section === 'guides' || section === 'infos') {
                if (subSection) newPath += '/guides/' + subSection;
            } else if (section === 'reservation') {
                newPath += '/reservation';
            }
        } else if (section === 'reservation') {
            newPath += 'reservation';
        }
        if (window.location.pathname !== newPath) {
            history.pushState(null, '', newPath);
        }
    },

    navigate(url) {
        history.pushState(null, '', url);
        ScrollManager.resetToTop();
        App.load();
    }
};

// -----------------------------------------------------------------------------
// 6. SERVICE API (FETCH & GESTION HORS-LIGNE)
// -----------------------------------------------------------------------------
const ApiService = {
    async fetchRouteData(slug) {
        const apiUrl = slug ? `api.php?slug=${encodeURIComponent(slug)}` : 'api.php';
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 12000);

        try {
            const response = await fetch(apiUrl, { signal: controller.signal });
            clearTimeout(timeoutId);
            const data = await response.json();
            return { ok: response.ok, status: response.status, data };
        } catch (err) {
            clearTimeout(timeoutId);
            return {
                ok: false,
                status: 0,
                data: {
                    success: false,
                    error: err.name === 'AbortError' 
                        ? 'Délai d\'attente dépassé. Vérifiez votre connexion.' 
                        : 'Impossible de joindre le serveur.',
                    offline: !navigator.onLine
                }
            };
        }
    }
};

// -----------------------------------------------------------------------------
// 7. GESTION DES MODALES (PDF, VIDÉO & RÉSERVATION AVEC ANIMATION BOTTOM-SHEET)
// -----------------------------------------------------------------------------
const ModalManager = {
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
        PdfViewer.load(AppState.currentSlug, lang, pdfUrl);

        if (updateUrl) {
            Router.setUrl(AppState.currentSlug, 'guides', `pdf-${lang}`);
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
            PdfViewer.destroy();
            Router.setUrl(AppState.currentSlug, AppState.currentActiveTab);
            return;
        }

        // Animation de fermeture fluide : descente progressive jusqu'en bas
        modal.classList.remove('open');
        modal.classList.add('is-closing');
        modal.setAttribute('aria-hidden', 'true');

        setTimeout(() => {
            modal.classList.remove('is-closing', 'is-active');
            PdfViewer.destroy();
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
    openBooking(updateUrl = true) {
        const modal = document.getElementById('modal-booking');
        const spinner = document.getElementById('booking-loading-spinner');
        const iframe = document.getElementById('booking-search-engine-rentals');
        if (!modal) return;

        if (spinner) {
            spinner.classList.remove('hidden');
            spinner.style.opacity = '1';
        }

        if (iframe && (!iframe.src || iframe.src === '' || iframe.src === 'about:blank')) {
            iframe.src = "https://connect.superhote.com/integrations/iframes/a3f19f43-5233-4d1a-a327-0c8146c2f4a9/rentals-search";
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
        const spinner = document.getElementById('booking-loading-spinner');
        if (spinner) spinner.classList.add('hidden');
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

    onBackdropClick(event, type) {
        if (!event || !event.target || event.target.classList.contains('modal-overlay')) {
            if (type === 'pdf') this.closePdf();
            if (type === 'video') this.closeVideo();
            if (type === 'booking') this.closeBooking();
            if (type === 'wifi') this.closeWifi();
            if (type === 'review') this.closeReview();
        }
    }
};

// -----------------------------------------------------------------------------
// GESTIONNAIRE DE GESTES TACTILES : GLISSEMENT VERS LE BAS (SWIPE TO DISMISS)
// -----------------------------------------------------------------------------
const SheetGestureController = {
    init() {
        const modals = [
            { id: 'modal-pdf', close: (immediate) => ModalManager.closePdf(immediate) },
            { id: 'modal-video', close: (immediate) => ModalManager.closeVideo(immediate) },
            { id: 'modal-booking', close: (immediate) => ModalManager.closeBooking(immediate) },
            { id: 'modal-wifi', close: (immediate) => ModalManager.closeWifi(immediate) },
            { id: 'modal-review', close: (immediate) => ModalManager.closeReview(immediate) }
        ];

        modals.forEach(({ id, close }) => {
            const overlay = document.getElementById(id);
            if (!overlay) return;
            const sheet = overlay.querySelector('.modal-sheet, .modal-sheet-compact');
            if (!sheet) return;

            this.attachGesture(overlay, sheet, close);
        });
    },

    attachGesture(overlay, sheet, closeFn) {
        let startY = 0;
        let startX = 0;
        let currentY = 0;
        let startTime = 0;
        let isDragging = false;
        let isTracking = false;

        const isHandleOrHeader = (target) => {
            return target.closest('.sheet-drag-handle') || 
                   target.closest('.modal-header-fixed');
        };

        const onTouchStart = (e) => {
            const touch = e.touches ? e.touches[0] : e;
            const target = e.target;

            // Détection : clic/toucher sur la poignée, l'en-tête fixe ou les 60px du haut
            const rect = sheet.getBoundingClientRect();
            const touchInTopZone = (touch.clientY - rect.top) <= 60;

            if (!isHandleOrHeader(target) && !touchInTopZone) {
                return;
            }

            // Ignorer l'appui sur les boutons interactifs (fermeture, lien externe, etc.)
            if (target.closest('button') || target.closest('a')) {
                return;
            }

            isTracking = true;
            isDragging = false;
            startY = touch.clientY;
            startX = touch.clientX;
            currentY = startY;
            startTime = Date.now();
        };

        const onTouchMove = (e) => {
            if (!isTracking) return;

            const touch = e.touches ? e.touches[0] : e;
            const deltaY = touch.clientY - startY;
            const deltaX = touch.clientX - startX;

            // Si mouvement majoritairement horizontal, annuler le geste
            if (!isDragging && Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
                isTracking = false;
                return;
            }

            // Enclenchement dès un léger glissement vers le bas (> 5px)
            if (!isDragging && deltaY > 5) {
                isDragging = true;
                sheet.classList.add('is-dragging');
            }

            if (!isDragging) return;

            if (e.cancelable) {
                e.preventDefault();
            }

            if (deltaY > 0) {
                // Suit physiquement le doigt vers le bas
                sheet.style.transform = `translateY(${deltaY}px)`;
                const progress = Math.min(deltaY / 350, 1);
                overlay.style.backgroundColor = `rgba(15, 23, 42, ${(0.72 * (1 - progress * 0.7)).toFixed(3)})`;
            } else {
                // Résistance élastique si l'utilisateur tire vers le haut
                const rubberBand = deltaY * 0.18;
                sheet.style.transform = `translateY(${rubberBand}px)`;
            }

            currentY = touch.clientY;
        };

        const onTouchEnd = () => {
            if (!isTracking && !isDragging) return;

            isTracking = false;

            if (!isDragging) return;
            isDragging = false;
            sheet.classList.remove('is-dragging');

            const deltaY = currentY - startY;
            const timeElapsed = Date.now() - startTime;
            const velocityY = deltaY / Math.max(timeElapsed, 1);

            // Seuil de fermeture : glissement > 100px OU impulsion rapide vers le bas (flick v > 0.42)
            const shouldDismiss = (deltaY > 100) || (deltaY > 35 && velocityY > 0.42);

            if (shouldDismiss) {
                sheet.style.transition = 'transform 0.26s cubic-bezier(0.32, 0.72, 0, 1)';
                sheet.style.transform = 'translateY(100%)';
                overlay.style.transition = 'opacity 0.26s ease';
                overlay.style.opacity = '0';

                setTimeout(() => {
                    closeFn(true);
                    sheet.style.transform = '';
                    sheet.style.transition = '';
                    overlay.style.backgroundColor = '';
                    overlay.style.opacity = '';
                    overlay.style.transition = '';
                }, 260);
            } else {
                // Rebond fluide élastique à l'état initial
                sheet.style.transition = 'transform 0.26s cubic-bezier(0.16, 1, 0.3, 1)';
                sheet.style.transform = 'translateY(0)';
                overlay.style.transition = 'background-color 0.26s ease';
                overlay.style.backgroundColor = '';

                setTimeout(() => {
                    sheet.style.transform = '';
                    sheet.style.transition = '';
                    overlay.style.transition = '';
                }, 260);
            }
        };

        // Événements tactiles mobiles & tablettes
        sheet.addEventListener('touchstart', onTouchStart, { passive: true });
        window.addEventListener('touchmove', onTouchMove, { passive: false });
        window.addEventListener('touchend', onTouchEnd, { passive: true });
        window.addEventListener('touchcancel', onTouchEnd, { passive: true });

        // Événements souris / pointeur pour test sur desktop
        sheet.addEventListener('pointerdown', onTouchStart);
        window.addEventListener('pointermove', onTouchMove);
        window.addEventListener('pointerup', onTouchEnd);
        window.addEventListener('pointercancel', onTouchEnd);
    }
};

// -----------------------------------------------------------------------------
// GESTIONNAIRE DE GESTES TACTILES : GLISSEMENT LATÉRAL ENTRE ONGLETS (SWIPE)
// -----------------------------------------------------------------------------
const TabGestureController = {
    startX: 0,
    startY: 0,
    startTime: 0,
    isTracking: false,
    isSwiping: false,
    isVerticalScroll: false,

    init() {
        const container = document.getElementById('view-property');
        if (!container) return;

        const onTouchStart = (e) => {
            // Uniquement si sur le portail d'un logement, sans modale ouverte et avec vidéos disponibles
            if (AppState.isPdfModalOpen || AppState.isVideoModalOpen || AppState.isBookingModalOpen) return;
            if (!AppState.currentProperty || AppState.currentVideos.length === 0) return;
            if (!e.touches || e.touches.length !== 1) return;

            const target = e.target;
            if (target && target.closest && (target.closest('input') || target.closest('textarea') || target.closest('select'))) {
                return;
            }

            this.isTracking = true;
            this.isSwiping = false;
            this.isVerticalScroll = false;
            this.startX = e.touches[0].clientX;
            this.startY = e.touches[0].clientY;
            this.startTime = Date.now();
        };

        const onTouchMove = (e) => {
            if (!this.isTracking || !e.touches || e.touches.length !== 1) return;

            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;
            const deltaX = currentX - this.startX;
            const deltaY = currentY - this.startY;

            // Détection précoce du scroll vertical
            if (!this.isSwiping && !this.isVerticalScroll) {
                if (Math.abs(deltaY) > Math.abs(deltaX) && Math.abs(deltaY) > 8) {
                    this.isVerticalScroll = true;
                    this.isTracking = false;
                    return;
                }
                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
                    const currentTab = AppState.currentActiveTab || 'guides';
                    if ((currentTab === 'guides' && deltaX < 0) || (currentTab === 'videos' && deltaX > 0)) {
                        this.isSwiping = true;
                    } else {
                        this.isTracking = false;
                        return;
                    }
                }
            }
        };

        const onTouchEnd = (e) => {
            if (!this.isTracking || !this.isSwiping || this.isVerticalScroll) {
                this.isTracking = false;
                this.isSwiping = false;
                return;
            }

            this.isTracking = false;
            const endTouch = e.changedTouches ? e.changedTouches[0] : null;
            if (!endTouch) return;

            const deltaX = endTouch.clientX - this.startX;
            const timeElapsed = Date.now() - this.startTime;
            const velocityX = Math.abs(deltaX) / Math.max(timeElapsed, 1);

            this.isSwiping = false;

            // Seuil de déclenchement : glissement > 40px OU impulsion rapide (> 25px avec vélocité > 0.35)
            const isIntentionalSwipe = (Math.abs(deltaX) > 40) || (Math.abs(deltaX) > 25 && velocityX > 0.35);
            if (!isIntentionalSwipe) return;

            const currentTab = AppState.currentActiveTab || 'guides';

            // Swipe de droite à gauche : Guides -> Vidéos
            if (currentTab === 'guides' && deltaX < 0) {
                PropertyRenderer.navigateTab('videos');
            }
            // Swipe de gauche à droite : Vidéos -> Guides
            else if (currentTab === 'videos' && deltaX > 0) {
                PropertyRenderer.navigateTab('guides');
            }
        };

        container.addEventListener('touchstart', onTouchStart, { passive: true });
        container.addEventListener('touchmove', onTouchMove, { passive: true });
        container.addEventListener('touchend', onTouchEnd, { passive: true });
        container.addEventListener('touchcancel', () => {
            this.isTracking = false;
            this.isSwiping = false;
        }, { passive: true });
    }
};

// -----------------------------------------------------------------------------
// 7.1 LECTEUR PDF VECTORIEL UNIVERSEL (MOZILLA PDF.JS & PINCH-TO-ZOOM CENTRÉ)
// -----------------------------------------------------------------------------
const PdfViewer = {
    pdfDoc: null,
    currentRenderTasks: [],

    // État de transformation vectorielle
    scale: 1.0,
    minScale: 1.0,
    maxScale: 4.0,
    x: 0,
    y: 0,
    baseScale: 1.0,

    // Suivi gestuel tactile (Pinch & Pan)
    _isPinching: false,
    _pinchStartDist: 0,
    _pinchStartScale: 1.0,
    _pinchStartX: 0,
    _pinchStartY: 0,
    _pinchStartMidX: 0,
    _pinchStartMidY: 0,

    _isDragging: false,
    _touchStartX: 0,
    _touchStartY: 0,
    _startX: 0,
    _startY: 0,
    _lastTouchY: 0,
    _lastTouchTime: 0,
    _velocityY: 0,
    _animId: null,
    _lastTapTime: 0,
    _resizeTimer: null,

    init() {
        const viewport = document.getElementById('pdf-viewport');
        if (!viewport) return;

        // Écouteur tactile 1 : touchstart
        viewport.addEventListener('touchstart', (e) => {
            if (!this.pdfDoc) return;
            if (this._animId) {
                cancelAnimationFrame(this._animId);
                this._animId = null;
            }

            const wrapper = document.getElementById('pdf-pages-wrapper');
            if (wrapper) wrapper.style.transition = 'none';

            if (e.touches.length === 2) {
                // Initialisation du geste Pinch-to-Zoom
                this._isPinching = true;
                this._isDragging = false;
                const p1 = e.touches[0];
                const p2 = e.touches[1];
                this._pinchStartDist = Math.hypot(p2.clientX - p1.clientX, p2.clientY - p1.clientY);
                this._pinchStartScale = this.scale;
                this._pinchStartX = this.x;
                this._pinchStartY = this.y;

                const rect = viewport.getBoundingClientRect();
                this._pinchStartMidX = (p1.clientX + p2.clientX) / 2 - rect.left;
                this._pinchStartMidY = (p1.clientY + p2.clientY) / 2 - rect.top;
            } else if (e.touches.length === 1) {
                // Initialisation du déplacement (Pan ou défilement vertical)
                this._isPinching = false;
                this._isDragging = true;
                const p = e.touches[0];
                this._touchStartX = p.clientX;
                this._touchStartY = p.clientY;
                this._startX = this.x;
                this._startY = this.y;
                this._lastTouchY = p.clientY;
                this._lastTouchTime = performance.now();
                this._velocityY = 0;
            }
        }, { passive: false });

        // Écouteur tactile 2 : touchmove
        viewport.addEventListener('touchmove', (e) => {
            if (!this.pdfDoc) return;
            if (e.cancelable) e.preventDefault(); // Bloque strictement tout zoom de la page globale

            if (this._isPinching && e.touches.length === 2) {
                const p1 = e.touches[0];
                const p2 = e.touches[1];
                const currentDist = Math.hypot(p2.clientX - p1.clientX, p2.clientY - p1.clientY);
                if (this._pinchStartDist <= 0) return;

                const factor = currentDist / this._pinchStartDist;
                // Clamp élastique [0.85, 4.5]
                const targetScale = Math.max(0.85, Math.min(4.5, this._pinchStartScale * factor));

                const rect = viewport.getBoundingClientRect();
                const currMidX = (p1.clientX + p2.clientX) / 2 - rect.left;
                const currMidY = (p1.clientY + p2.clientY) / 2 - rect.top;

                // Formule mathématique exacte du point focal :
                // Le point de contenu situé sous le milieu des deux doigts reste exactement immobile sous les doigts
                const Px = (this._pinchStartMidX - this._pinchStartX) / this._pinchStartScale;
                const Py = (this._pinchStartMidY - this._pinchStartY) / this._pinchStartScale;

                this.scale = targetScale;
                this.x = currMidX - Px * targetScale;
                this.y = currMidY - Py * targetScale;

                this.applyTransform();
            } else if (this._isDragging && e.touches.length === 1) {
                const p = e.touches[0];
                const dx = p.clientX - this._touchStartX;
                const dy = p.clientY - this._touchStartY;

                if (this.scale > 1.05) {
                    // Zoom actif : navigation libre 2D (pan horizontal et vertical)
                    this.x = this._startX + dx;
                    this.y = this._startY + dy;
                } else {
                    // Échelle normale 1.0x : défilement vertical fluide
                    this.x = this.getCenteredX();
                    this.y = this._startY + dy;

                    const now = performance.now();
                    const dt = now - this._lastTouchTime;
                    if (dt > 10) {
                        this._velocityY = (p.clientY - this._lastTouchY) / dt;
                        this._lastTouchY = p.clientY;
                        this._lastTouchTime = now;
                    }
                }
                this.applyTransform();
            }
        }, { passive: false });

        // Écouteur tactile 3 : touchend / touchcancel
        const handleTouchEnd = (e) => {
            if (!this.pdfDoc) return;

            if (this._isPinching) {
                if (!e.touches || e.touches.length < 2) {
                    this._isPinching = false;
                    // Calage de l'échelle entre 1.0 et 4.0
                    if (this.scale < 1.05) {
                        this.scale = 1.0;
                    } else if (this.scale > this.maxScale) {
                        this.scale = this.maxScale;
                    }
                    this.snapToBounds(true);

                    if (e.touches && e.touches.length === 1) {
                        this._isDragging = true;
                        const p = e.touches[0];
                        this._touchStartX = p.clientX;
                        this._touchStartY = p.clientY;
                        this._startX = this.x;
                        this._startY = this.y;
                    }
                }
            } else if (this._isDragging && (!e.touches || e.touches.length === 0)) {
                this._isDragging = false;

                if (this.scale <= 1.05) {
                    // Défilement avec inertie fluide (momentum)
                    if (Math.abs(this._velocityY) > 0.15) {
                        this.startMomentum();
                    } else {
                        this.snapToBounds(true);
                    }
                } else {
                    this.snapToBounds(true);
                }

                // Détection double-tap (standard universel mobile)
                const now = performance.now();
                if (now - this._lastTapTime < 320) {
                    const rect = viewport.getBoundingClientRect();
                    const tapX = (this._touchStartX || (viewport.clientWidth / 2)) - rect.left;
                    const tapY = (this._touchStartY || (viewport.clientHeight / 2)) - rect.top;
                    this.handleDoubleTap(tapX, tapY);
                }
                this._lastTapTime = now;
            }
        };

        viewport.addEventListener('touchend', handleTouchEnd, { passive: true });
        viewport.addEventListener('touchcancel', handleTouchEnd, { passive: true });

        // Molette souris / Trackpad (Desktop & Mac)
        viewport.addEventListener('wheel', (e) => {
            if (!this.pdfDoc) return;
            if (e.cancelable) e.preventDefault();

            const rect = viewport.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            if (e.ctrlKey || e.metaKey) {
                // Pinch sur pavé tactile
                const factor = 1 - e.deltaY * 0.01;
                const targetScale = Math.max(1.0, Math.min(4.0, this.scale * factor));
                const Px = (mouseX - this.x) / this.scale;
                const Py = (mouseY - this.y) / this.scale;

                this.scale = targetScale;
                this.x = mouseX - Px * targetScale;
                this.y = mouseY - Py * targetScale;
                this.snapToBounds(false);
            } else {
                // Molette de défilement
                this.y -= e.deltaY;
                if (e.deltaX) this.x -= e.deltaX;
                this.snapToBounds(false);
            }
        }, { passive: false });

        // Rotation d'écran
        window.addEventListener('resize', () => {
            if (AppState.isPdfModalOpen && this.pdfDoc) {
                clearTimeout(this._resizeTimer);
                this._resizeTimer = setTimeout(() => {
                    this.renderAllPages();
                }, 180);
            }
        });
    },

    startMomentum() {
        if (this._animId) cancelAnimationFrame(this._animId);

        let currentVY = this._velocityY * 16;
        const friction = 0.94;

        const step = () => {
            if (Math.abs(currentVY) < 0.25) {
                this.snapToBounds(true);
                return;
            }

            this.y += currentVY;
            currentVY *= friction;

            const bounds = this.getBounds();
            if (this.y > bounds.maxY || this.y < bounds.minY) {
                currentVY *= 0.65;
            }

            this.applyTransform();
            this._animId = requestAnimationFrame(step);
        };

        this._animId = requestAnimationFrame(step);
    },

    handleDoubleTap(tapX, tapY) {
        if (this.scale > 1.2) {
            // Réinitialise à 100% avec animation fluide
            this.scale = 1.0;
            this.x = this.getCenteredX();
            this.snapToBounds(true);
        } else {
            // Zoom à 2.2x centré sur le point touché
            const targetScale = 2.2;
            const Px = (tapX - this.x) / this.scale;
            const Py = (tapY - this.y) / this.scale;

            this.scale = targetScale;
            this.x = tapX - Px * targetScale;
            this.y = tapY - Py * targetScale;
            this.snapToBounds(true);
        }
    },

    getCenteredX() {
        const viewport = document.getElementById('pdf-viewport');
        const wrapper = document.getElementById('pdf-pages-wrapper');
        if (!viewport || !wrapper) return 0;
        const vw = viewport.clientWidth;
        const contentW = (wrapper.offsetWidth || vw) * this.scale;
        return (contentW <= vw) ? (vw - contentW) / 2 : 0;
    },

    getBounds() {
        const viewport = document.getElementById('pdf-viewport');
        const wrapper = document.getElementById('pdf-pages-wrapper');
        if (!viewport || !wrapper) return { minX: 0, maxX: 0, minY: 0, maxY: 0 };

        const vw = viewport.clientWidth;
        const vh = viewport.clientHeight;
        const contentW = (wrapper.offsetWidth || vw) * this.scale;
        const contentH = (wrapper.scrollHeight || wrapper.offsetHeight) * this.scale;

        let minX = 0, maxX = 0;
        if (contentW <= vw) {
            const centered = (vw - contentW) / 2;
            minX = centered;
            maxX = centered;
        } else {
            minX = vw - contentW;
            maxX = 0;
        }

        let minY = 0, maxY = 0;
        if (contentH <= vh) {
            minY = 0;
            maxY = 0;
        } else {
            minY = vh - contentH;
            maxY = 0;
        }

        return { minX, maxX, minY, maxY };
    },

    snapToBounds(animate = true) {
        const wrapper = document.getElementById('pdf-pages-wrapper');
        if (!wrapper) return;

        const bounds = this.getBounds();
        const targetX = Math.max(bounds.minX, Math.min(bounds.maxX, this.x));
        const targetY = Math.max(bounds.minY, Math.min(bounds.maxY, this.y));

        if (animate) {
            wrapper.style.transition = 'transform 0.28s cubic-bezier(0.16, 1, 0.3, 1)';
        } else {
            wrapper.style.transition = 'none';
        }

        this.x = targetX;
        this.y = targetY;
        this.applyTransform();
    },

    applyTransform() {
        const wrapper = document.getElementById('pdf-pages-wrapper');
        if (!wrapper) return;
        wrapper.style.transform = `translate3d(${Math.round(this.x * 10) / 10}px, ${Math.round(this.y * 10) / 10}px, 0) scale(${Math.round(this.scale * 1000) / 1000})`;
    },

    async load(slug, lang, directUrl) {
        this.destroy();
        this.scale = 1.0;
        this.x = 0;
        this.y = 0;

        const spinner = document.getElementById('pdf-loading-spinner');
        const statusEl = document.getElementById('pdf-loading-status');
        const wrapper = document.getElementById('pdf-pages-wrapper');

        if (spinner) spinner.classList.remove('hidden');
        if (statusEl) statusEl.textContent = "Chargement du guide de bienvenue...";
        if (wrapper) {
            wrapper.style.transform = 'translate3d(0, 0, 0) scale(1)';
            wrapper.innerHTML = '';
        }

        // Source de données même origine pour éviter tout blocage CORS
        const proxyUrl = `api.php?pdf=1&slug=${encodeURIComponent(slug)}&lang=${encodeURIComponent(lang)}`;
        const sourceUrl = slug ? proxyUrl : directUrl;

        try {
            if (!window.pdfjsLib) {
                await this.waitForPdfJs();
            }

            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            const loadingTask = pdfjsLib.getDocument({
                url: sourceUrl,
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true
            });

            this.pdfDoc = await loadingTask.promise;
            await this.renderAllPages();
        } catch (err) {
            console.warn('[PdfViewer] Essai direct avec URL d\'origine :', err);
            if (sourceUrl !== directUrl && directUrl) {
                try {
                    const fallbackTask = pdfjsLib.getDocument({
                        url: directUrl,
                        cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                        cMapPacked: true
                    });
                    this.pdfDoc = await fallbackTask.promise;
                    await this.renderAllPages();
                    return;
                } catch (e2) {
                    console.error('[PdfViewer] Échec rendu PDF.js :', e2);
                }
            }
            this.fallbackIframe(directUrl || sourceUrl);
        } finally {
            if (spinner) spinner.classList.add('hidden');
        }
    },

    async waitForPdfJs() {
        return new Promise((resolve, reject) => {
            let checks = 0;
            const interval = setInterval(() => {
                checks++;
                if (window.pdfjsLib) {
                    clearInterval(interval);
                    resolve();
                } else if (checks > 50) {
                    clearInterval(interval);
                    reject(new Error("Timeout chargement PDF.js"));
                }
            }, 100);
        });
    },

    async renderAllPages() {
        if (!this.pdfDoc) return;
        this.cancelCurrentRenders();

        const wrapper = document.getElementById('pdf-pages-wrapper');
        const viewportEl = document.getElementById('pdf-viewport');
        const spinner = document.getElementById('pdf-loading-spinner');
        if (!wrapper || !viewportEl) return;

        wrapper.innerHTML = '';
        const numPages = this.pdfDoc.numPages;

        // Largeur disponible calculée pour un ajustement Fit-to-Width exact
        const viewportWidth = (viewportEl.clientWidth > 100) ? viewportEl.clientWidth : window.innerWidth;
        const availableWidth = Math.max(viewportWidth - 16, 260);

        // Analyse de la première page pour déterminer le ratio natif exact
        const firstPage = await this.pdfDoc.getPage(1);
        const unscaledViewport = firstPage.getViewport({ scale: 1.0 });
        this.baseScale = availableWidth / unscaledViewport.width;

        const dpr = Math.min(window.devicePixelRatio || 1, 2.0);

        // Réinitialise la position et l'échelle
        this.scale = 1.0;
        this.x = this.getCenteredX();
        this.y = 0;
        wrapper.style.transition = 'none';
        this.applyTransform();

        // 1. Rendu prioritaire immédiat de la Page 1 (apparition instantanée)
        await this.renderSinglePage(firstPage, 1, this.baseScale, dpr, wrapper);
        if (spinner) spinner.classList.add('hidden');

        // 2. Rendu consécutif des pages suivantes en arrière-plan
        for (let i = 2; i <= numPages; i++) {
            if (!AppState.isPdfModalOpen) break;
            const page = await this.pdfDoc.getPage(i);
            await this.renderSinglePage(page, i, this.baseScale, dpr, wrapper);
        }

        // Calage final propre une fois toutes les pages en place
        this.snapToBounds(false);
    },

    async renderSinglePage(page, pageNum, scale, dpr, container) {
        const viewport = page.getViewport({ scale: scale });

        const canvas = document.createElement('canvas');
        canvas.id = `pdf-canvas-page-${pageNum}`;
        canvas.className = 'pdf-page-canvas';
        const ctx = canvas.getContext('2d');

        // Dimensions de mémoire haute résolution (Retina DPR 2.0)
        canvas.width = Math.floor(viewport.width * dpr);
        canvas.height = Math.floor(viewport.height * dpr);

        // Dimensions CSS logiques
        canvas.style.width = `${Math.floor(viewport.width)}px`;
        canvas.style.height = `${Math.floor(viewport.height)}px`;

        const transform = (dpr !== 1) ? [dpr, 0, 0, dpr, 0, 0] : null;
        const renderTask = page.render({
            canvasContext: ctx,
            viewport: viewport,
            transform: transform
        });

        this.currentRenderTasks.push(renderTask);
        try {
            await renderTask.promise;
            container.appendChild(canvas);
        } catch (err) {
            if (err && err.name !== 'RenderingCancelledException') {
                console.warn(`[PdfViewer] Erreur page ${pageNum} :`, err);
            }
        }
    },

    cancelCurrentRenders() {
        this.currentRenderTasks.forEach(task => {
            try { task.cancel(); } catch(e) {}
        });
        this.currentRenderTasks = [];
    },

    resetZoom() {
        this.scale = 1.0;
        this.x = this.getCenteredX();
        this.snapToBounds(true);
    },

    destroy() {
        this.cancelCurrentRenders();
        if (this._animId) {
            cancelAnimationFrame(this._animId);
            this._animId = null;
        }
        this.pdfDoc = null;
        this.scale = 1.0;
        this.x = 0;
        this.y = 0;
        const wrapper = document.getElementById('pdf-pages-wrapper');
        if (wrapper) {
            wrapper.style.transform = '';
            wrapper.innerHTML = '';
        }
    },

    fallbackIframe(url) {
        const wrapper = document.getElementById('pdf-pages-wrapper');
        const spinner = document.getElementById('pdf-loading-spinner');
        if (spinner) spinner.classList.add('hidden');
        if (!wrapper) return;
        wrapper.innerHTML = `<iframe src="${Utils.safeUrl(url)}" class="w-full h-full min-h-[75vh] border-0 block" type="application/pdf"></iframe>`;
    }
};

// -----------------------------------------------------------------------------
// 8. RENDU DE L'INTERFACE UTILISATEUR (UI RENDERERS)
// -----------------------------------------------------------------------------
const PropertyRenderer = {
    render(prop, videos, route) {
        AppState.currentProperty = prop;
        AppState.currentVideos = videos || [];

        document.title = `${prop.name} - Portail Voyageurs | Carré d'Hôtes`;

        // Mise à jour dynamique du manifeste PWA & des métadonnées pour ce logement spécifique
        const manifestLink = document.getElementById('app-manifest-link');
        if (manifestLink && prop.slug) {
            manifestLink.href = `manifest.php?slug=${encodeURIComponent(prop.slug)}`;
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
        if (propSub) propSub.textContent = I18n.t('home_subtitle');

        // Bloc Wi-Fi
        const wifiCard = document.getElementById('prop-wifi-card');
        const infoGrid = document.getElementById('info-cards-grid');
        if (prop.wifi_ssid) {
            const modalSsid = document.getElementById('wifi-modal-ssid');
            const modalPwd = document.getElementById('wifi-modal-password');
            if (modalSsid) modalSsid.textContent = prop.wifi_ssid;
            if (modalPwd) modalPwd.textContent = prop.wifi_password || '—';

            if (wifiCard) {
                wifiCard.classList.remove('hidden');
                wifiCard.classList.add('cascade-item');
                wifiCard.style.animationDelay = '0.08s';
            }
            if (infoGrid) {
                infoGrid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 max-w-3xl mx-auto';
            }
        } else {
            if (wifiCard) {
                wifiCard.classList.add('hidden');
            }
            if (infoGrid) {
                infoGrid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 max-w-4xl mx-auto';
            }
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

        // 3. Actualiser la grille des vidéos avec les titres traduits
        if (AppState.currentVideos && AppState.currentVideos.length > 0) {
            this.renderVideos(AppState.currentVideos);
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
        const willAnimate = AppState.currentActiveTab && AppState.currentActiveTab !== tabId;
        this.switchTab(tabId, willAnimate);
        Router.setUrl(AppState.currentSlug, tabId);
    }
};

const ListRenderer = {
    render(props) {
        const container = document.getElementById('properties-container');
        container.innerHTML = '';

        // Réinitialisation du manifeste PWA sur le portail d'accueil
        const manifestLink = document.getElementById('app-manifest-link');
        if (manifestLink) {
            manifestLink.href = 'manifest.php';
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
const VideoSearch = {
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
    }
};

// -----------------------------------------------------------------------------
// 10. PROGRESSIVE WEB APP (SERVICE WORKER, OFFLINE & INSTALL PROMPT)
// -----------------------------------------------------------------------------
const PwaManager = {
    deferredPrompt: null,
    promptTimeout: null,

    init() {
        this.registerServiceWorker();
        this.setupConnectivityListeners();
        this.setupInstallPrompt();
    },

    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register(`${BASE_PATH}sw.js`, { scope: BASE_PATH })
                    .then((reg) => {
                        reg.update();
                        // Écoute les mises à jour en tâche de fond
                        reg.addEventListener('updatefound', () => {
                            const newWorker = reg.installing;
                            if (newWorker) {
                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        console.log('[PWA] Nouvelle version disponible.');
                                    }
                                });
                            }
                        });
                    })
                    .catch((err) => {
                        console.warn('[PWA] Échec enregistrement Service Worker :', err.message);
                    });
            });
        }
    },

    setupConnectivityListeners() {
        const offlineToast = document.getElementById('offline-toast');

        const updateStatus = () => {
            AppState.isOffline = !navigator.onLine;
            if (offlineToast) {
                if (AppState.isOffline) {
                    offlineToast.classList.add('show');
                } else {
                    offlineToast.classList.remove('show');
                }
            }
        };

        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
        updateStatus();
    },

    setupInstallPrompt() {
        // Permet de forcer l'affichage avec ?install=1 dans l'URL (utile pour les tests)
        const urlParams = new URLSearchParams(window.location.search);
        const forceInstall = urlParams.has('install') || window.location.hash.includes('install');

        // 1. Si déjà installé en mode autonome (standalone PWA), ne pas afficher (sauf si forcé en test)
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (isStandalone && !forceInstall) {
            console.log('[PWA] Déjà en mode autonome PWA. Bannière d\'installation masquée.');
            return;
        }

        // 2. Vérifie si l'utilisateur a refusé récemment (dans les dernières 24h)
        if (!forceInstall) {
            try {
                const dismissedAt = localStorage.getItem('cdh_pwa_prompt_dismissed');
                if (dismissedAt) {
                    const hoursSince = (Date.now() - parseInt(dismissedAt, 10)) / (1000 * 60 * 60);
                    if (hoursSince < 24) {
                        return;
                    }
                }
            } catch(e) {}
        }

        // 3. Détection iOS (iPhone, iPad, iPod et iPadOS)
        const isIos = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase()) || 
                      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        if (isIos) {
            // Sur iOS, beforeinstallprompt n'existe pas : on affiche directement les instructions de partage
            this.schedulePrompt('ios');
            return;
        }

        // 4. Écoute de l'événement natif avant installation (Android, Chrome, Edge)
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.schedulePrompt('native');
        });

        // 5. Écoute de l'événement d'installation réussie
        window.addEventListener('appinstalled', () => {
            this.deferredPrompt = null;
            this.hidePrompt();
            console.log('[PWA] Application installée avec succès sur l\'écran d\'accueil !');
        });
    },

    schedulePrompt(type) {
        if (this.promptTimeout) clearTimeout(this.promptTimeout);
        this.promptTimeout = setTimeout(() => {
            this.showPrompt(type);
        }, 1200);
    },

    showPrompt(type) {
        const banner = document.getElementById('pwa-install-banner');
        if (!banner) return;

        // Personnalisation du titre selon le logement actif
        const titleEl = document.getElementById('pwa-prompt-title');
        const descEl = document.getElementById('pwa-prompt-desc');
        const currentProp = AppState.currentProperty;

        if (currentProp && currentProp.name) {
            if (titleEl) titleEl.textContent = `Ajouter ${currentProp.name}`;
            if (descEl) descEl.textContent = "Gardez votre livret d'accueil et vos vidéos d'équipements sous la main en 1 clic.";
        }

        const nativeBlock = document.getElementById('pwa-prompt-native');
        const iosBlock = document.getElementById('pwa-prompt-ios');

        if (type === 'ios') {
            if (nativeBlock) nativeBlock.classList.add('hidden');
            if (iosBlock) iosBlock.classList.remove('hidden');
        } else {
            if (nativeBlock) nativeBlock.classList.remove('hidden');
            if (iosBlock) iosBlock.classList.add('hidden');
        }

        banner.classList.remove('hidden');
        requestAnimationFrame(() => {
            banner.classList.add('show');
        });
    },

    async installApp() {
        if (!this.deferredPrompt) {
            this.hidePrompt();
            return;
        }

        try {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log('[PWA] Choix installation utilisateur :', outcome);
        } catch (err) {
            console.warn('[PWA] Erreur déclenchement prompt :', err.message);
        }
        this.deferredPrompt = null;
        this.hidePrompt();
    },

    dismissPrompt() {
        try {
            localStorage.setItem('cdh_pwa_prompt_dismissed', String(Date.now()));
        } catch(e) {}
        this.hidePrompt();
    },

    hidePrompt() {
        const banner = document.getElementById('pwa-install-banner');
        if (!banner) return;
        banner.classList.remove('show');
        setTimeout(() => {
            banner.classList.add('hidden');
        }, 350);
    }
};

// -----------------------------------------------------------------------------
// 11. CONTRÔLEUR PRINCIPAL (APP)
// -----------------------------------------------------------------------------
const App = {
    async load() {
        const route = Router.parseRoute();
        AppState.currentSlug = route.slug;

        // Détection mode autonome PWA : Restauration immédiate du logement enregistré
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

            // Ouverture automatique si l'URL pointe vers la réservation
            if (route.section === 'reservation') {
                ModalManager.openBooking(false);
            }
        } catch (err) {
            this.hideAllViews();
            this.renderError(err.message);
        }
    },

    hideAllViews() {
        document.getElementById('view-loading').classList.add('hidden');
        document.getElementById('view-property').classList.add('hidden');
        document.getElementById('view-list').classList.add('hidden');
        document.getElementById('view-not-found').classList.add('hidden');
        document.getElementById('view-error').classList.add('hidden');
    },

    renderNotFound(slug) {
        document.getElementById('not-found-msg').innerHTML = `Aucun logement ne correspond à l'identifiant <strong>"${Utils.escapeHtml(slug)}"</strong>.`;
        document.getElementById('view-not-found').classList.remove('hidden');
    },

    renderError(msg) {
        document.getElementById('error-details').textContent = msg || 'Erreur de connexion.';
        document.getElementById('view-error').classList.remove('hidden');
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
        // Initialisation du contrôleur gestuel de glissement vers le bas (Swipe-to-dismiss)
        SheetGestureController.init();

        // Initialisation du contrôleur gestuel de glissement latéral entre onglets (Swipe-to-switch tabs)
        TabGestureController.init();

        // Touche Échap
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (AppState.isPdfModalOpen) ModalManager.closePdf();
                if (AppState.isVideoModalOpen) ModalManager.closeVideo();
                if (AppState.isBookingModalOpen) ModalManager.closeBooking();
                if (AppState.isWifiModalOpen) ModalManager.closeWifi();
                if (AppState.isReviewModalOpen) ModalManager.closeReview();
            }
        });

        // Historique retour / avant
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
            this.load();
        });

        // Redimensionnement de fenêtre (adaptation modale vidéo 720x1280)
        window.addEventListener('resize', () => {
            if (AppState.isVideoModalOpen) {
                ModalManager.adjustVideoDimensions();
            }
        });

        // Empêche le défilement tactile arrière-plan lors de l'interaction avec la barre de la modale
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('touchmove', (e) => {
                if (e.target === overlay || e.target.closest('.modal-header-fixed') || e.target.closest('.sheet-drag-handle')) {
                    e.preventDefault();
                }
            }, { passive: false });
        });

        // Verrouillage strict anti-zoom sur l'application (sauf dans le viewport PDF)
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

// -----------------------------------------------------------------------------
// 12. PONT DE COMPATIBILITÉ GLOBALE (POUR LES GESTIONNAIRES ONCLICK EN HTML)
// -----------------------------------------------------------------------------
window.setLanguage = (lang) => I18n.setLanguage(lang);
window.toggleLanguage = () => I18n.toggle();
window.openCurrentGuide = () => PropertyRenderer.openCurrentGuide();
window.toggleTheme = () => ThemeManager.toggle();
window.navigateToTab = (tabId) => PropertyRenderer.navigateTab(tabId);
window.openPdfModal = (lang) => ModalManager.openPdf(lang);
window.closePdfModal = () => ModalManager.closePdf();
window.onPdfLoaded = () => ModalManager.onPdfLoaded();
window.openVideoModal = (video) => ModalManager.openVideo(video);
window.closeVideoModal = () => ModalManager.closeVideo();
window.openBookingModal = () => ModalManager.openBooking();
window.closeBookingModal = () => ModalManager.closeBooking();
window.onBookingLoaded = () => ModalManager.onBookingLoaded();
window.onModalBackdropClick = (event, type) => ModalManager.onBackdropClick(event, type);
window.openWifiModal = () => ModalManager.openWifi();
window.closeWifiModal = () => ModalManager.closeWifi();
window.openReviewModal = () => ModalManager.openReview();
window.closeReviewModal = () => ModalManager.closeReview();
window.copyNetworkName = (e) => App.copyNetwork(e);
window.copyWifiPassword = (e) => App.copyWifi(e);
window.filterVideos = () => VideoSearch.filter();
window.navigateToUrl = (url) => Router.navigate(url);
window.loadApp = () => App.load();
window.resetScrollToTop = () => ScrollManager.resetToTop();
window.PwaManager = PwaManager;
window.PdfViewer = PdfViewer;
window.showInstallPrompt = (type = null) => {
    const isIos = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase()) || 
                  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    PwaManager.showPrompt(type || (isIos ? 'ios' : 'native'));
};

// -----------------------------------------------------------------------------
// 13. INITIALISATION AU CHARGEMENT DU DOM
// -----------------------------------------------------------------------------
window.addEventListener('DOMContentLoaded', () => {
    I18n.init();
    ScrollManager.init();
    ScrollManager.resetToTop();
    ThemeManager.init();
    PwaManager.init();
    PdfViewer.init();
    App.initEvents();
    App.load();
});

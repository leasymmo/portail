/**
 * ==============================================================================
 * MODULE MULTILINGUE & INTERNATIONALISATION (I18N)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=11.0';
import { Utils } from './utils.js?v=11.0';

export const I18n = {
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
            booklet_desc: "Toutes les infos pratiques pour votre séjour",
            parking_card_title: "Stationnement",
            parking_badge: "Gratuit & Payant",
            parking_card_desc: "Rues gratuites, parkings & P+R",
            parking_modal_title: "Stationnement",
            parking_modal_subtitle: "Toutes les solutions pour vous garer",
            parking_rule_label: "Règle importante",
            parking_sec_free_title: "Stationnement gratuit",
            parking_sec_free_desc: "Rues à proximité & arrêts minute",
            parking_sec_street_title: "Stationnement payant dans la rue",
            parking_sec_street_desc: "Tarifs horaires & zones EasyPark",
            parking_sec_private_title: "Parkings privés & couverts",
            parking_sec_private_desc: "Zenpark, LPA&CO, Q-Park sécurisés",
            parking_sec_tcl_title: "Parkings Relais TCL (P+R)",
            parking_sec_tcl_desc: "Gratuits avec un titre de transport TCL",
            parking_easypark_btn: "Gérer avec EasyPark",
            parking_book_btn: "Réserver",
            parking_tcl_btn: "Site TCL",
            parking_maps_btn: "Itinéraire GPS",
            parking_max_height: "Hauteur max",
            parking_capacity: "Capacité",
            parking_hours: "Horaires",
            parking_rates: "Tarifs",
            parking_vehicles: "Véhicules adaptés",
            parking_access: "Accès",
            parking_how_to_reach: "Rejoindre le logement",
            parking_street_rates_title: "Tarifs selon le véhicule :",
            parking_street_schedule_title: "Plages horaires :",
            parking_tcl_rule_title: "Règle de gratuité TCL :",
            parking_free_tag: "Gratuit",
            booking_card_title: "Réserver un séjour",
            booking_card_desc: "Bénéficiez de nos meilleurs tarifs en direct",
            review_card_title: "Avis Google",
            review_card_desc: "Soutenez notre équipe avec 5 étoiles",
            review_modal_title: "Votre avis compte pour nous !",
            review_modal_subtitle: "Conciergerie Carré d'Hôtes · Avis Google",
            review_modal_heading: "Votre satisfaction est notre priorité",
            review_modal_msg: "Votre retour est la plus belle récompense pour notre équipe locale. 1 minute suffit pour illuminer notre journée et nous aider à faire rayonner notre conciergerie !",
            review_modal_btn: "Laisser mes 5 étoiles sur Google ✨",
            review_modal_help: "Une remarque ou un imprévu ? Contactez-nous en direct",
            contact_card_title: "Contacter Anthony",
            contact_card_desc: "Votre concierge référent disponible 7j/7",
            anthony_role: "Concierge Référent",
            anthony_banner_desc: "Une question durant votre séjour ? Anthony et l'équipe vous accompagnent par messagerie directe.",
            anthony_btn_label: "Contacter Anthony",
            contact_modal_title: "Contacter votre référent",
            contact_modal_subtitle: "Anthony · Conciergerie Carré d'Hôtes",
            contact_triage_heading: "Réponses immédiates en 1 clic",
            triage_videos_title: "Modes d'emploi des équipements",
            triage_videos_desc: "Cafetière, lave-linge, TV, chauffage en vidéo...",
            triage_wifi_title: "Code & Mot de passe Wi-Fi",
            triage_wifi_desc: "Copiez les identifiants en un clic",
            triage_guide_title: "Guide de bienvenue complet",
            triage_guide_desc: "Règles, départ, poubelles, commerces...",
            triage_see: "Voir →",
            contact_direct_heading: "Autre demande ou urgence ?",
            contact_whatsapp_btn: "Écrire à Anthony sur WhatsApp",
            contact_hours_hint: "Réponse rapide par WhatsApp · Disponibilité 7j/7 de 9h à 21h",
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
            error_retry: "Réessayer",
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
            pwa_android_step1: "Appuyez sur le menu ⋮ (les 3 points en haut à droite)",
            pwa_android_step2: "Choisissez « Installer l'application » ou « Ajouter à l'écran d'accueil »",
            pwa_ios_gotit: "J'ai compris",
            hero_badge: "Conciergerie Privée • Séjour d'Exception",
            hero_badge_vip: "✦ Séjour Privilège",
            vip_welcome_prefix: "Bienvenue,",
            vip_welcome_at: "au",
            vip_welcome_subtitle: "Nous vous souhaitons un séjour d'exception à Lyon.",
            reservation_card_title: "Détails de votre séjour",
            reservation_card_badge_confirmed: "Confirmée",
            reservation_card_badge_unconfirmed: "En attente",
            reservation_card_checkin: "Arrivée",
            reservation_card_checkin_time_prefix: "Dès",
            reservation_card_checkout: "Départ",
            reservation_card_checkout_time_prefix: "Avant",
            reservation_card_duration: "Durée",
            reservation_card_nights_single: "nuit",
            reservation_card_nights_plural: "nuits",
            reservation_card_guests: "Voyageurs",
            reservation_card_guests_single: "personne",
            reservation_card_guests_plural: "personnes",
            reservation_card_adults: "adulte(s)",
            reservation_card_children: "enfant(s)",
            reservation_card_ref: "Réf. #",
            reservation_card_reassurance: "Logement vérifié et préparé avec soin par l'équipe Carré d'Hôtes.",
            res_not_found_title: "Réservation introuvable",
            res_not_found_desc: "Aucune réservation ne correspond au numéro",
            res_not_found_hint: "Veuillez vérifier le lien fourni ou contacter notre conciergerie.",
            res_unconfirmed_title: "Réservation non confirmée",
            res_unconfirmed_desc: "Votre réservation est actuellement",
            res_unconfirmed_hint: "Si vous venez tout juste de réserver, la confirmation peut prendre quelques minutes selon votre plateforme.",
            res_status_unconfirmed: "non confirmée",
            res_status_canceled: "annulée",
            res_btn_contact_concierge: "Contacter Anthony",
            res_btn_view_properties: "Découvrir tous les logements",
            wifi_quick_copy: "Copier",
            wifi_quick_copied: "Copié !",
            wifi_quick_copy_aria: "Copier immédiatement le mot de passe Wi-Fi dans le presse-papier",
            guide_badge: "14 pages HD",
            anthony_status_online: "Disponible pour votre séjour",
            anthony_status_offline: "Disponible dès 9h (horaires : 9h - 21h)",
            booking_badge: "Direct",
            review_badge: "★★★★★",
            a11y_wifi_copied: "Mot de passe Wi-Fi copié dans le presse-papier.",
            a11y_lang_changed: "Langue modifiée en Français.",
            a11y_theme_dark: "Mode sombre activé.",
            a11y_theme_light: "Mode clair activé.",
            a11y_search_results: "{count} équipements trouvés."
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
            booklet_desc: "All practical information for your stay",
            parking_card_title: "Parking",
            parking_badge: "Free & Paid",
            parking_card_desc: "Free streets, car parks & P+R",
            parking_modal_title: "Parking",
            parking_modal_subtitle: "All parking options near your stay",
            parking_rule_label: "Important notice",
            parking_sec_free_title: "Free street parking",
            parking_sec_free_desc: "Nearby streets & quick drop-off",
            parking_sec_street_title: "Paid on-street parking",
            parking_sec_street_desc: "Hourly rates & EasyPark zones",
            parking_sec_private_title: "Private & covered car parks",
            parking_sec_private_desc: "Secured Zenpark, LPA&CO, Q-Park",
            parking_sec_tcl_title: "TCL Park & Ride (P+R)",
            parking_sec_tcl_desc: "Free with a validated transit ticket",
            parking_easypark_btn: "Manage on EasyPark",
            parking_book_btn: "Book a spot",
            parking_tcl_btn: "TCL Website",
            parking_maps_btn: "GPS Directions",
            parking_max_height: "Max clearance",
            parking_capacity: "Capacity",
            parking_hours: "Opening hours",
            parking_rates: "Rates",
            parking_vehicles: "Suitable vehicles",
            parking_access: "Access",
            parking_how_to_reach: "Getting to the apartment",
            parking_street_rates_title: "Rates by vehicle category:",
            parking_street_schedule_title: "Operating hours:",
            parking_tcl_rule_title: "TCL Free Parking Rule:",
            parking_free_tag: "Free",
            booking_card_title: "Book your stay",
            booking_card_desc: "Enjoy our best direct rates without fees",
            review_card_title: "Google Reviews",
            review_card_desc: "Support our local team with 5 stars",
            review_modal_title: "Your feedback means the world to us!",
            review_modal_subtitle: "Carré d'Hôtes Concierge · Google Review",
            review_modal_heading: "Your satisfaction is our priority",
            review_modal_msg: "Your feedback is the greatest reward for our local team. Just 1 minute to brighten our day and support our independent concierge!",
            review_modal_btn: "Leave my 5 stars on Google ✨",
            review_modal_help: "Any issue or suggestion? Contact us directly",
            contact_card_title: "Contact Anthony",
            contact_card_desc: "Your dedicated concierge available 7/7",
            anthony_role: "Guest Referent",
            anthony_banner_desc: "Have a question during your stay? Anthony and the team are available via direct messaging.",
            anthony_btn_label: "Contact Anthony",
            contact_modal_title: "Contact your referent",
            contact_modal_subtitle: "Anthony · Carré d'Hôtes Concierge",
            contact_triage_heading: "Instant answers in 1 click",
            triage_videos_title: "Equipment video guides",
            triage_videos_desc: "Coffee machine, washer, TV, heating tutorials...",
            triage_wifi_title: "Wi-Fi Name & Password",
            triage_wifi_desc: "Copy credentials in one tap",
            triage_guide_title: "Complete Welcome Guide",
            triage_guide_desc: "Rules, checkout, waste disposal, local tips...",
            triage_see: "View →",
            contact_direct_heading: "Other inquiry or emergency?",
            contact_whatsapp_btn: "Message Anthony on WhatsApp",
            contact_hours_hint: "Fast WhatsApp reply · 7/7 availability from 9am to 9pm",
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
            error_retry: "Try again",
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
            pwa_android_step1: "Tap the browser menu ⋮ (3 dots at top right)",
            pwa_android_step2: "Select 'Install app' or 'Add to Home Screen'",
            pwa_ios_gotit: "Got it",
            hero_badge: "Private Concierge • Exceptional Stay",
            hero_badge_vip: "✦ VIP Stay",
            vip_welcome_prefix: "Welcome,",
            vip_welcome_at: "at",
            vip_welcome_subtitle: "We wish you an exceptional stay in Lyon.",
            reservation_card_title: "Your Stay Details",
            reservation_card_badge_confirmed: "Confirmed",
            reservation_card_badge_unconfirmed: "Pending",
            reservation_card_checkin: "Check-in",
            reservation_card_checkin_time_prefix: "From",
            reservation_card_checkout: "Check-out",
            reservation_card_checkout_time_prefix: "Before",
            reservation_card_duration: "Duration",
            reservation_card_nights_single: "night",
            reservation_card_nights_plural: "nights",
            reservation_card_guests: "Guests",
            reservation_card_guests_single: "guest",
            reservation_card_guests_plural: "guests",
            reservation_card_adults: "adult(s)",
            reservation_card_children: "child(ren)",
            reservation_card_ref: "Ref. #",
            reservation_card_reassurance: "Accommodation inspected and prepared with care by Carré d'Hôtes team.",
            res_not_found_title: "Booking Not Found",
            res_not_found_desc: "No booking matches reference",
            res_not_found_hint: "Please double-check your link or contact our concierge team.",
            res_unconfirmed_title: "Booking Not Confirmed",
            res_unconfirmed_desc: "Your booking is currently",
            res_unconfirmed_hint: "If you have just booked, confirmation may take a few minutes depending on your platform.",
            res_status_unconfirmed: "unconfirmed",
            res_status_canceled: "canceled",
            res_btn_contact_concierge: "Contact Anthony",
            res_btn_view_properties: "Explore all accommodations",
            wifi_quick_copy: "Copy",
            wifi_quick_copied: "Copied!",
            wifi_quick_copy_aria: "Copy Wi-Fi password immediately to clipboard",
            guide_badge: "14 HD pages",
            anthony_status_online: "Available for your stay",
            anthony_status_offline: "Available from 9am (hours: 9am - 9pm)",
            booking_badge: "Direct",
            review_badge: "★★★★★",
            a11y_wifi_copied: "Wi-Fi password copied to clipboard.",
            a11y_lang_changed: "Language switched to English.",
            a11y_theme_dark: "Dark mode activated.",
            a11y_theme_light: "Light mode activated.",
            a11y_search_results: "{count} equipment items found."
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

        if (window.PropertyRenderer && AppState.currentProperty) {
            window.PropertyRenderer.updateLanguageContent();
        } else if (window.ListRenderer && AppState.allProperties && AppState.allProperties.length > 0) {
            window.ListRenderer.render(AppState.allProperties);
        }

        if (window.ModalManager) {
            window.ModalManager.updateBookingLanguage();
        }

        if (window.PdfViewer && AppState.currentProperty && AppState.currentProperty.slug) {
            const prop = AppState.currentProperty;
            let targetLang = lang;
            if (lang === 'en' && !prop.guide_en_url && prop.guide_fr_url) {
                targetLang = 'fr';
            } else if (lang === 'fr' && !prop.guide_fr_url && prop.guide_en_url) {
                targetLang = 'en';
            }
            window.PdfViewer.preload(prop.slug, targetLang);
        }
        Utils.announceA11y(this.t('a11y_lang_changed'));
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

/**
 * ==============================================================================
 * PORTAIL VOYAGEURS - CONCIERGERIE CARRÉ D'HÔTES
 * Application Frontend & Progressive Web App (PWA)
 * Version: 10.0.0 (Architecture Modulaire ES6)
 * ==============================================================================
 */

'use strict';

// -----------------------------------------------------------------------------
// 1. IMPORTATION DES MODULES APPLICATIFS
// -----------------------------------------------------------------------------
import { AppState } from './modules/state.js?v=10.6';
import { Utils } from './modules/utils.js?v=10.6';
import { I18n } from './modules/i18n.js?v=10.6';
import { ThemeManager } from './modules/theme.js?v=10.6';
import { ScrollManager } from './modules/scroll.js?v=10.6';
import { Router, BASE_PATH } from './modules/router.js?v=10.6';
import { ApiService } from './modules/api.js?v=10.6';
import { SheetGestureController, TabGestureController } from './modules/gestures.js?v=10.6';
import { PdfViewer } from './modules/pdf-viewer.js?v=10.6';
import { ModalManager } from './modules/modals.js?v=10.6';
import { PropertyRenderer, ListRenderer, VideoSearch } from './modules/renderer.js?v=10.6';
import { PwaManager } from './modules/pwa.js?v=10.6';
import { AppController } from './modules/app-controller.js?v=10.6';

// -----------------------------------------------------------------------------
// 2. EXPOSITION DES MODULES SUR L'OBJET GLOBAL (POUR DÉBOGAGE & INTEROPÉRABILITÉ)
// -----------------------------------------------------------------------------
window.AppState = AppState;
window.Utils = Utils;
window.I18n = I18n;
window.ThemeManager = ThemeManager;
window.ScrollManager = ScrollManager;
window.Router = Router;
window.BASE_PATH = BASE_PATH;
window.ApiService = ApiService;
window.SheetGestureController = SheetGestureController;
window.TabGestureController = TabGestureController;
window.PdfViewer = PdfViewer;
window.ModalManager = ModalManager;
window.PropertyRenderer = PropertyRenderer;
window.ListRenderer = ListRenderer;
window.VideoSearch = VideoSearch;
window.PwaManager = PwaManager;
window.App = AppController;

// -----------------------------------------------------------------------------
// 3. PONT DE COMPATIBILITÉ GLOBALE (POUR LES GESTIONNAIRES ONCLICK EN HTML)
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
window.openParkingModal = () => ModalManager.openParking();
window.closeParkingModal = () => ModalManager.closeParking();
window.toggleParkingSection = (id) => PropertyRenderer.toggleParkingSection(id);
window.openReviewModal = () => ModalManager.openReview();
window.closeReviewModal = () => ModalManager.closeReview();
window.openGoogleReview = (e) => ModalManager.openGoogleReview(e);
window.switchFromReviewToContact = (e) => ModalManager.switchFromReviewToContact(e);
window.openContactModal = () => ModalManager.openContact();
window.closeContactModal = () => ModalManager.closeContact();
window.openWhatsAppAnthony = (e) => ModalManager.openWhatsAppAnthony(e);
window.goToVideosFromContact = (e) => ModalManager.switchToVideosFromContact(e);
window.goToWifiFromContact = (e) => ModalManager.switchToWifiFromContact(e);
window.goToGuideFromContact = (e) => ModalManager.switchToGuideFromContact(e);
window.copyNetworkName = (e) => AppController.copyNetwork(e);
window.copyWifiPassword = (e) => AppController.copyWifi(e);
window.copyWifiFromCard = (e) => AppController.copyWifiFromCard(e);
window.filterVideos = () => VideoSearch.filter();
window.navigateToUrl = (url) => Router.navigate(url);
window.loadApp = () => AppController.load();
window.resetScrollToTop = () => ScrollManager.resetToTop();
window.showInstallPrompt = (type = null) => {
    const isIos = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase()) || 
                  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    PwaManager.showInstallBanner(type || (isIos ? 'ios' : 'native'));
};

// -----------------------------------------------------------------------------
// 4. INITIALISATION AU CHARGEMENT DU DOM
// -----------------------------------------------------------------------------
window.addEventListener('DOMContentLoaded', () => {
    I18n.init();
    ScrollManager.init();
    ScrollManager.resetToTop();
    ThemeManager.init();
    PwaManager.init();
    PdfViewer.init();
    AppController.initEvents();
    AppController.load();
});

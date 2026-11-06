/**
 * ==============================================================================
 * MODULE ÉTAT GLOBAL (STATE STORE)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

export const AppState = {
    currentProperty: null,
    currentVideos: [],
    currentActiveTab: 'guides',
    currentSlug: '',
    isPdfModalOpen: false,
    isVideoModalOpen: false,
    isBookingModalOpen: false,
    isWifiModalOpen: false,
    isReviewModalOpen: false,
    isContactModalOpen: false,
    isParkingModalOpen: false,
    isOffline: !navigator.onLine
};

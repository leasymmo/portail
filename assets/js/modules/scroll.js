/**
 * ==============================================================================
 * MODULE DÉFILEMENT & HAUTEURS CRITIQUES (SCROLL MANAGER)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=10.5';

export const ScrollManager = {
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
        const topbar = document.getElementById('main-topbar');
        if (topbar) {
            topbar.style.transform = 'translateZ(0)';
        }
    },

    updatePageScrollState() {
        // Garantit que le défilement vertical du document reste 100% natif et fluide
        if (document.documentElement.classList.contains('no-scroll')) {
            document.documentElement.classList.remove('no-scroll');
        }
        if (document.body.classList.contains('no-scroll')) {
            document.body.classList.remove('no-scroll');
        }
    },

    lock() {
        document.body.classList.add('modal-open');
    },

    unlock() {
        if (!AppState.isPdfModalOpen && !AppState.isVideoModalOpen && !AppState.isBookingModalOpen && 
            !AppState.isWifiModalOpen && !AppState.isReviewModalOpen && !AppState.isContactModalOpen) {
            document.body.classList.remove('modal-open');
            this.updatePageScrollState();
        }
    }
};

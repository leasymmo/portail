/**
 * ==============================================================================
 * MODULE DÉFILEMENT & HAUTEURS CRITIQUES (SCROLL MANAGER)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js';

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
        requestAnimationFrame(() => {
            const propView = document.getElementById('view-property');
            const listView = document.getElementById('view-list');
            const isPropertyView = propView && !propView.classList.contains('hidden');
            const isListView = listView && !listView.classList.contains('hidden');
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
        if (!AppState.isPdfModalOpen && !AppState.isVideoModalOpen && !AppState.isBookingModalOpen && 
            !AppState.isWifiModalOpen && !AppState.isReviewModalOpen && !AppState.isContactModalOpen) {
            document.body.classList.remove('modal-open');
            this.updatePageScrollState();
        }
    }
};

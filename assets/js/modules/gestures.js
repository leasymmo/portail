/**
 * ==============================================================================
 * MODULE CONTRÔLEURS DE GESTES TACTILES & SOURIS (SWIPE TO DISMISS & TABS)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=11.1';

export const SheetGestureController = {
    init() {
        const modals = [
            { id: 'modal-pdf', close: (immediate) => window.ModalManager && window.ModalManager.closePdf(immediate) },
            { id: 'modal-video', close: (immediate) => window.ModalManager && window.ModalManager.closeVideo(immediate) },
            { id: 'modal-booking', close: (immediate) => window.ModalManager && window.ModalManager.closeBooking(immediate) },
            { id: 'modal-wifi', close: (immediate) => window.ModalManager && window.ModalManager.closeWifi(immediate) },
            { id: 'modal-review', close: (immediate) => window.ModalManager && window.ModalManager.closeReview(immediate) },
            { id: 'modal-contact-anthony', close: (immediate) => window.ModalManager && window.ModalManager.closeContact(immediate) },
            { id: 'modal-parking', close: (immediate) => window.ModalManager && window.ModalManager.closeParking(immediate) },
            { id: 'modal-reservation', close: (immediate) => window.ModalManager && window.ModalManager.closeReservation(immediate) }
        ];

        modals.forEach(({ id, close }) => {
            const overlay = document.getElementById(id);
            if (!overlay) return;
            const sheet = overlay.querySelector('.modal-sheet, .modal-sheet-compact, .modal-sheet-video') || overlay.firstElementChild;
            if (!sheet) return;

            this.attachGesture(overlay, sheet, close);
        });
    },

    attachGesture(overlay, sheet, closeFn) {
        let startY = 0;
        let startX = 0;
        let currentY = 0;
        let startTime = 0;
        let lastY = 0;
        let lastTime = 0;
        let recentVelocityY = 0;
        let isDragging = false;
        let isTracking = false;
        let activePointerId = null;
        let animFrameId = null;

        const isHandleOrHeader = (target) => {
            return target && (
                target.closest('.sheet-drag-handle') || 
                target.closest('.modal-header-fixed')
            );
        };

        const onStart = (clientX, clientY, target, pointerId = null) => {
            if (!overlay.classList.contains('open')) return false;

            const rect = sheet.getBoundingClientRect();
            const inTopZone = (clientY - rect.top) <= 80;

            if (!isHandleOrHeader(target) && !inTopZone) {
                return false;
            }

            if (target && target.closest('button, a, input, select, textarea, .btn-close-modal')) {
                return false;
            }

            isTracking = true;
            isDragging = false;
            activePointerId = pointerId;
            startY = clientY;
            startX = clientX;
            currentY = startY;
            startTime = Date.now();
            lastY = startY;
            lastTime = startTime;
            recentVelocityY = 0;

            try {
                if (pointerId !== null && sheet.setPointerCapture) {
                    sheet.setPointerCapture(pointerId);
                }
            } catch (err) {}

            return true;
        };

        const onMove = (clientX, clientY, e = null) => {
            if (!isTracking || !overlay.classList.contains('open')) return;

            const deltaY = clientY - startY;
            const deltaX = clientX - startX;

            if (!isDragging && Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
                isTracking = false;
                return;
            }

            if (!isDragging && (deltaY > 4 || deltaY < -4)) {
                isDragging = true;
                sheet.classList.add('is-dragging');
                document.body.classList.add('is-modal-dragging');
                sheet.style.setProperty('transition', 'none', 'important');
                overlay.style.transition = 'none';
            }

            if (!isDragging) return;

            if (e && e.cancelable) {
                e.preventDefault();
            }

            const now = Date.now();
            const dt = now - lastTime;
            if (dt >= 5) {
                const instantVelocity = (clientY - lastY) / dt;
                recentVelocityY = recentVelocityY === 0 ? instantVelocity : (recentVelocityY * 0.25) + (instantVelocity * 0.75);
                lastY = clientY;
                lastTime = now;
            }

            currentY = clientY;

            if (animFrameId) cancelAnimationFrame(animFrameId);
            animFrameId = requestAnimationFrame(() => {
                const sheetHeight = sheet.offsetHeight || window.innerHeight * 0.8;
                if (deltaY > 0) {
                    sheet.style.setProperty('transform', `translate3d(0, ${deltaY}px, 0)`, 'important');
                    const progress = Math.min(deltaY / sheetHeight, 1);
                    overlay.style.backgroundColor = `rgba(15, 23, 42, ${(0.72 * (1 - progress * 0.75)).toFixed(3)})`;
                } else {
                    const rubberBand = deltaY * 0.18;
                    sheet.style.setProperty('transform', `translate3d(0, ${rubberBand}px, 0)`, 'important');
                }
            });
        };

        const onEnd = (target = null, endX = null, endY = null) => {
            if (!isTracking && !isDragging) return;

            if (endY !== null && typeof endY === 'number') {
                currentY = endY;
            }

            try {
                if (sheet.releasePointerCapture && activePointerId !== null) {
                    sheet.releasePointerCapture(activePointerId);
                }
            } catch (err) {}

            isTracking = false;
            activePointerId = null;

            if (animFrameId) {
                cancelAnimationFrame(animFrameId);
                animFrameId = null;
            }

            if (!isDragging) return;
            isDragging = false;
            sheet.classList.remove('is-dragging');
            document.body.classList.remove('is-modal-dragging');

            const now = Date.now();
            const deltaY = currentY - startY;
            const totalTime = Math.max(now - startTime, 1);
            const overallVelocity = deltaY / totalTime;
            const finalVelocity = (now - lastTime < 250) ? Math.max(recentVelocityY, overallVelocity) : overallVelocity;
            const sheetHeight = sheet.offsetHeight || window.innerHeight * 0.8;
            const distanceRatio = deltaY / sheetHeight;

            const isPastHalfway = distanceRatio >= 0.50;
            const isQuickFlickDown = (finalVelocity >= 0.20 && deltaY >= 35) || (finalVelocity >= 0.35 && deltaY >= 20);
            const shouldDismiss = isPastHalfway || isQuickFlickDown;

            if (shouldDismiss) {
                const remainingDistance = Math.max(sheetHeight - deltaY, 0);
                const v = Math.max(finalVelocity, overallVelocity, 0.6);
                const animDuration = Math.min(Math.max(remainingDistance / (v * 1000), 0.16), 0.28);

                sheet.style.setProperty('transition', `transform ${animDuration.toFixed(2)}s cubic-bezier(0.32, 0.72, 0, 1)`, 'important');
                sheet.style.setProperty('transform', 'translate3d(0, 100%, 0)', 'important');
                overlay.style.transition = `opacity ${animDuration.toFixed(2)}s ease`;
                overlay.style.opacity = '0';

                setTimeout(() => {
                    closeFn(true);
                    sheet.style.removeProperty('transform');
                    sheet.style.removeProperty('transition');
                    overlay.style.backgroundColor = '';
                    overlay.style.opacity = '';
                    overlay.style.transition = '';
                }, Math.round(animDuration * 1000));
            } else {
                sheet.style.setProperty('transition', 'transform 0.32s cubic-bezier(0.16, 1, 0.3, 1)', 'important');
                sheet.style.setProperty('transform', 'translate3d(0, 0, 0)', 'important');
                overlay.style.transition = 'background-color 0.32s ease';
                overlay.style.backgroundColor = '';

                setTimeout(() => {
                    sheet.style.removeProperty('transform');
                    sheet.style.removeProperty('transition');
                    overlay.style.transition = '';
                }, 320);
            }
        };

        sheet.addEventListener('pointerdown', (e) => {
            if (e.button !== undefined && e.button !== 0) return;
            onStart(e.clientX, e.clientY, e.target, e.pointerId);
        }, { passive: false });

        window.addEventListener('pointermove', (e) => {
            if (activePointerId !== null && e.pointerId !== undefined && e.pointerId !== activePointerId) return;
            onMove(e.clientX, e.clientY, e);
        }, { passive: false });

        window.addEventListener('pointerup', (e) => {
            if (activePointerId !== null && e.pointerId !== undefined && e.pointerId !== activePointerId) return;
            onEnd(e.target, e.clientX, e.clientY);
        }, { passive: true });

        window.addEventListener('pointercancel', (e) => {
            if (activePointerId !== null && e.pointerId !== undefined && e.pointerId !== activePointerId) return;
            onEnd(e.target, e.clientX, e.clientY);
        }, { passive: true });

        sheet.addEventListener('touchstart', (e) => {
            if (e.touches && e.touches.length === 1) {
                const t = e.touches[0];
                onStart(t.clientX, t.clientY, e.target, null);
            }
        }, { passive: true });

        window.addEventListener('touchmove', (e) => {
            if (isDragging && e.touches && e.touches.length === 1) {
                const t = e.touches[0];
                onMove(t.clientX, t.clientY, e);
            }
        }, { passive: false });

        window.addEventListener('touchend', (e) => {
            if (isTracking || isDragging) {
                const t = (e.changedTouches && e.changedTouches[0]) || (e.touches && e.touches[0]);
                onEnd(e.target, t ? t.clientX : null, t ? t.clientY : null);
            }
        }, { passive: true });
    }
};

export const TabGestureController = {
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
            if (AppState.isPdfModalOpen || AppState.isVideoModalOpen || AppState.isBookingModalOpen ||
                AppState.isWifiModalOpen || AppState.isReviewModalOpen || AppState.isContactModalOpen ||
                AppState.isParkingModalOpen) return;
            if (!AppState.currentProperty || AppState.currentVideos.length === 0) return;
            if (!e.touches || e.touches.length !== 1) return;

            const target = e.target;
            if (target && target.closest && (target.closest('input') || target.closest('textarea') || target.closest('select') || target.closest('button') || target.closest('a') || target.closest('iframe'))) {
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
            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);

            if (!this.isSwiping && !this.isVerticalScroll) {
                // Détecte la direction prédominante après un seuil initial de 12px
                if (absX > 12 || absY > 12) {
                    if (absX > absY * 1.2) {
                        const currentTab = AppState.currentActiveTab || 'guides';
                        if ((currentTab === 'guides' && deltaX < 0) || (currentTab === 'videos' && deltaX > 0)) {
                            this.isSwiping = true;
                        } else {
                            this.isTracking = false;
                        }
                    } else {
                        // Défilement vertical standard : laisser le navigateur faire son travail natif
                        this.isVerticalScroll = true;
                        this.isTracking = false;
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
            const deltaY = endTouch.clientY - this.startY;
            const timeElapsed = Date.now() - this.startTime;
            const velocityX = Math.abs(deltaX) / Math.max(timeElapsed, 1);

            this.isSwiping = false;

            const isIntentionalSwipe = (Math.abs(deltaX) > 45 && Math.abs(deltaX) > Math.abs(deltaY) * 1.2) || 
                                       (Math.abs(deltaX) > 30 && velocityX > 0.35);
            if (!isIntentionalSwipe) return;

            const currentTab = AppState.currentActiveTab || 'guides';

            if (currentTab === 'guides' && deltaX < 0) {
                if (window.PropertyRenderer) window.PropertyRenderer.navigateTab('videos');
            } else if (currentTab === 'videos' && deltaX > 0) {
                if (window.PropertyRenderer) window.PropertyRenderer.navigateTab('guides');
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

/**
 * ==============================================================================
 * MODULE LECTEUR PDF VECTORIEL UNIVERSEL (MOZILLA PDF.JS & PINCH-TO-ZOOM CENTRÉ)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=10.5';
import { BASE_PATH } from './router.js?v=10.5';
import { I18n } from './i18n.js?v=10.5';

export const PdfViewer = {
    pdfDoc: null,
    loadedSlug: null,
    loadedLang: null,
    _cachedBuffer: null,
    _isPreloading: false,
    _preloadPromise: null,
    _preloadSeq: 0,
    _targetPreloadSlug: null,
    _targetPreloadLang: null,
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

    async preload(slug, lang) {
        if (!slug) return;
        const targetLang = lang || 'fr';

        const wrapper = document.getElementById('pdf-pages-wrapper');
        const renderedCount = wrapper ? wrapper.querySelectorAll('canvas').length : 0;

        // Si le document est déjà totalement chargé et pré-rendu dans la langue cible, aucun travail nécessaire
        if (this.loadedSlug === slug && this.loadedLang === targetLang && this.pdfDoc && renderedCount >= this.pdfDoc.numPages) {
            return;
        }

        // Si un préchargement identique est déjà en cours d'exécution, on renvoie sa promesse
        if (this._isPreloading && this._preloadPromise && this._targetPreloadSlug === slug && this._targetPreloadLang === targetLang) {
            return this._preloadPromise;
        }

        // Annule les rendus de pages précédents en cours
        this.cancelCurrentRenders();

        this._targetPreloadSlug = slug;
        this._targetPreloadLang = targetLang;
        this._preloadSeq = (this._preloadSeq || 0) + 1;
        const currentSeq = this._preloadSeq;

        // Invalide l'état de langue chargée si la langue cible est différente
        if (this.loadedLang !== targetLang) {
            this.loadedLang = null;
        }

        this._isPreloading = true;
        this._preloadPromise = (async () => {
            try {
                if (!window.pdfjsLib) {
                    await this.waitForPdfJs();
                }
                if (this._preloadSeq !== currentSeq) return;

                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

                const base = document.baseURI || (window.location.origin + BASE_PATH);
                const proxyUrl = new URL(`api.php?pdf=1&slug=${encodeURIComponent(slug)}&lang=${encodeURIComponent(targetLang)}`, base).href;

                // Téléchargement sécurisé en ArrayBuffer dans le thread principal (fiabilité universelle Safari/Chrome/iOS)
                const resp = await fetch(proxyUrl);
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                const buffer = await resp.arrayBuffer();
                if (this._preloadSeq !== currentSeq) return;

                this._cachedBuffer = buffer;

                const loadingTask = pdfjsLib.getDocument({
                    data: new Uint8Array(buffer),
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true,
                    standardFontDataUrl: 'https://unpkg.com/pdfjs-dist@3.11.174/standard_fonts/'
                });

                const doc = await loadingTask.promise;
                if (this._preloadSeq !== currentSeq) return;
                this.pdfDoc = doc;

                // Pré-rendu immédiat dans le DOM pour un affichage instantané (0ms) dès l'ouverture
                const currentWrapper = document.getElementById('pdf-pages-wrapper');
                if (currentWrapper) {
                    if (AppState.isPdfModalOpen) {
                        const spinner = document.getElementById('pdf-loading-spinner');
                        if (spinner) spinner.classList.remove('hidden');
                    }
                    await this.renderAllPages(currentSeq);
                    if (this._preloadSeq !== currentSeq) return;

                    this.loadedSlug = slug;
                    this.loadedLang = targetLang;

                    if (AppState.isPdfModalOpen) {
                        const spinner = document.getElementById('pdf-loading-spinner');
                        if (spinner) spinner.classList.add('hidden');
                        this.resetZoom();
                    }
                }
            } catch (err) {
                console.warn('[PdfViewer] Préchargement en arrière-plan :', err.message);
            } finally {
                if (this._preloadSeq === currentSeq) {
                    this._isPreloading = false;
                    this._preloadPromise = null;
                }
            }
        })();

        return this._preloadPromise;
    },

    async load(slug, lang, directUrl) {
        const targetLang = lang || 'fr';
        const wrapper = document.getElementById('pdf-pages-wrapper');
        const spinner = document.getElementById('pdf-loading-spinner');
        const statusEl = document.getElementById('pdf-loading-status');

        // Cas 1 : Document déjà préchargé et pages déjà dans le DOM -> affichage immédiat 0ms
        if (this.pdfDoc && this.loadedSlug === slug && this.loadedLang === targetLang && wrapper) {
            const renderedCount = wrapper.querySelectorAll('canvas').length;
            if (renderedCount >= this.pdfDoc.numPages) {
                if (spinner) spinner.classList.add('hidden');
                this.resetZoom();
                return;
            } else if (renderedCount > 0) {
                if (spinner) spinner.classList.add('hidden');
                this.resetZoom();
                const dpr = Math.min(window.devicePixelRatio || 1, 2.0);
                for (let i = renderedCount + 1; i <= this.pdfDoc.numPages; i++) {
                    if (!AppState.isPdfModalOpen) break;
                    const page = await this.pdfDoc.getPage(i);
                    await this.renderSinglePage(page, i, this.baseScale, dpr, wrapper);
                }
                return;
            }
        }

        // Cas 2 : Préchargement en cours en arrière-plan -> on attend sa résolution
        if (this._isPreloading && this._preloadPromise) {
            if (spinner) spinner.classList.remove('hidden');
            if (statusEl) statusEl.textContent = (typeof I18n !== 'undefined' && I18n.t) ? I18n.t('pdf_loading') : "Chargement du guide de bienvenue...";
            await this._preloadPromise;
            if (this.pdfDoc && this.loadedSlug === slug && this.loadedLang === targetLang) {
                const renderedCount = wrapper ? wrapper.querySelectorAll('canvas').length : 0;
                if (!wrapper || renderedCount < this.pdfDoc.numPages) {
                    await this.renderAllPages();
                }
                if (spinner) spinner.classList.add('hidden');
                this.resetZoom();
                return;
            }
        }

        // Cas 3 : Chargement direct (accès direct par lien profond ou premier chargement)
        this.scale = 1.0;
        this.x = 0;
        this.y = 0;

        if (spinner) spinner.classList.remove('hidden');
        if (statusEl) statusEl.textContent = (typeof I18n !== 'undefined' && I18n.t) ? I18n.t('pdf_loading') : "Chargement du guide de bienvenue...";
        if (wrapper) {
            wrapper.style.transform = 'translate3d(0, 0, 0) scale(1)';
            wrapper.innerHTML = '';
        }

        const base = document.baseURI || (window.location.origin + BASE_PATH);
        const proxyUrl = new URL(`api.php?pdf=1&slug=${encodeURIComponent(slug)}&lang=${encodeURIComponent(targetLang)}`, base).href;
        const sourceUrl = slug ? proxyUrl : directUrl;

        try {
            if (!window.pdfjsLib) {
                await this.waitForPdfJs();
            }

            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            let arrayBuffer = (this.loadedSlug === slug && this.loadedLang === targetLang) ? this._cachedBuffer : null;
            if (!arrayBuffer) {
                const resp = await fetch(sourceUrl);
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                arrayBuffer = await resp.arrayBuffer();
                this._cachedBuffer = arrayBuffer;
            }

            const loadingTask = pdfjsLib.getDocument({
                data: new Uint8Array(arrayBuffer),
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: 'https://unpkg.com/pdfjs-dist@3.11.174/standard_fonts/'
            });

            this.pdfDoc = await loadingTask.promise;
            this.loadedSlug = slug;
            this.loadedLang = targetLang;
            await this.renderAllPages();
        } catch (err) {
            console.error('[PdfViewer] Erreur de chargement PDF.js :', err);
            // Fallback direct vers URL d'origine dans PDF.js (sans JAMAIS basculer en iframe natif)
            if (directUrl && sourceUrl !== directUrl) {
                try {
                    const fallbackTask = pdfjsLib.getDocument({
                        url: directUrl,
                        cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                        cMapPacked: true,
                        standardFontDataUrl: 'https://unpkg.com/pdfjs-dist@3.11.174/standard_fonts/'
                    });
                    this.pdfDoc = await fallbackTask.promise;
                    this.loadedSlug = slug;
                    this.loadedLang = targetLang;
                    await this.renderAllPages();
                    return;
                } catch (e2) {
                    console.error('[PdfViewer] Échec fallback direct :', e2);
                }
            }
            if (statusEl) statusEl.textContent = "Impossible de charger le livret. Veuillez vérifier votre connexion.";
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

    async renderAllPages(seq = null) {
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
        if (seq && this._preloadSeq !== seq) return;
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
        if (seq && this._preloadSeq !== seq) return;
        if (spinner && AppState.isPdfModalOpen) spinner.classList.add('hidden');

        // 2. Rendu consécutif des pages suivantes en arrière-plan
        for (let i = 2; i <= numPages; i++) {
            if (seq && this._preloadSeq !== seq) break;
            if (!AppState.isPdfModalOpen && !this._isPreloading) break;
            const page = await this.pdfDoc.getPage(i);
            if (seq && this._preloadSeq !== seq) break;
            await this.renderSinglePage(page, i, this.baseScale, dpr, wrapper);
        }

        // Calage final propre une fois toutes les pages en place
        if (!seq || this._preloadSeq === seq) {
            this.snapToBounds(false);
        }
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
        this._preloadSeq = (this._preloadSeq || 0) + 1;
        this._targetPreloadSlug = null;
        this._targetPreloadLang = null;
        this.pdfDoc = null;
        this.loadedSlug = null;
        this.loadedLang = null;
        this._cachedBuffer = null;
        this._isPreloading = false;
        this._preloadPromise = null;
        this.scale = 1.0;
        this.x = 0;
        this.y = 0;
        const wrapper = document.getElementById('pdf-pages-wrapper');
        if (wrapper) {
            wrapper.style.transform = '';
            wrapper.innerHTML = '';
        }
    }
};



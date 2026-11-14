/**
 * ==============================================================================
 * MODULE CLIENT API (FETCH, RESEAU & GESTION HORS-LIGNE)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { BASE_PATH } from './router.js?v=11.0';

export const ApiService = {
    async fetchRouteData(slug, reservationId = '') {
        const base = document.baseURI || (window.location.origin + BASE_PATH);
        let endpoint = 'api.php';
        if (reservationId) {
            endpoint = `api.php?reservation_id=${encodeURIComponent(reservationId)}`;
        } else if (slug) {
            endpoint = `api.php?slug=${encodeURIComponent(slug)}`;
        }
        const apiUrl = new URL(endpoint, base).href;
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

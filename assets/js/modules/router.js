/**
 * ==============================================================================
 * MODULE ROUTEUR SPA SANS HASH (/portail/{slug} ou /portail/reservation/{id})
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { AppState } from './state.js?v=11.4';

export const BASE_PATH = window.__CDH_BASE_PATH || (window.location.pathname.startsWith('/portail/dev') ? '/portail/dev/' : '/portail/');

export const Router = {
    parseRoute() {
        let path = window.location.pathname;
        const prefixRegex = new RegExp('^' + BASE_PATH.replace(/\/$/, '') + '/?');
        path = path.replace(prefixRegex, '').replace(/\/$/, '');

        if (!path || path === 'index.html' || path === 'index.php') {
            return { slug: '', reservationId: '', section: '', subSection: '' };
        }

        const parts = path.split('/').filter(Boolean);
        const first = (parts[0] || '').toLowerCase().trim();

        // 1. Route personnalisée /reservation/{reservationId}
        if (first === 'reservation') {
            const second = (parts[1] || '').trim();
            if (second && /^[0-9]+$/.test(second)) {
                const part2 = (parts[2] || '').toLowerCase().trim();
                const part3 = (parts[3] || '').toLowerCase().trim();
                let section = 'guides';
                let subSection = '';
                if (part2 === 'parking' || part2 === 'stationnement') {
                    section = 'parking';
                } else if (part2 === 'videos' || part2 === 'tutos') {
                    section = 'videos';
                    subSection = part3;
                } else if (part2.startsWith('video-')) {
                    section = 'videos';
                    subSection = part2;
                } else if (part2 === 'guides' || part2 === 'infos') {
                    section = 'guides';
                    subSection = part3;
                } else if (part2.startsWith('pdf-')) {
                    section = 'guides';
                    subSection = part2;
                }
                return { slug: '', reservationId: second, section, subSection };
            }
            return { slug: '', reservationId: '', section: 'reservation', subSection: '' };
        }

        // 2. Route directe numérique /{reservationId}
        if (/^[0-9]{5,}$/.test(first)) {
            const part1 = (parts[1] || '').toLowerCase().trim();
            const part2 = (parts[2] || '').toLowerCase().trim();
            let section = 'guides';
            let subSection = '';
            if (part1 === 'parking' || part1 === 'stationnement') {
                section = 'parking';
            } else if (part1 === 'videos' || part1 === 'tutos') {
                section = 'videos';
                subSection = part2;
            } else if (part1.startsWith('video-')) {
                section = 'videos';
                subSection = part1;
            } else if (part1 === 'guides' || part1 === 'infos') {
                section = 'guides';
                subSection = part2;
            } else if (part1.startsWith('pdf-')) {
                section = 'guides';
                subSection = part1;
            }
            return { slug: '', reservationId: first, section, subSection };
        }

        // 3. Route standard par slug de logement /{slug}
        const slug = first;
        const part1 = (parts[1] || '').toLowerCase().trim();
        const part2 = (parts[2] || '').toLowerCase().trim();

        let section = 'guides';
        let subSection = '';

        if (part1 === 'reservation') {
            section = 'reservation';
        } else if (part1 === 'parking' || part1 === 'stationnement') {
            section = 'parking';
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

        return { slug, reservationId: '', section, subSection };
    },

    setUrl(slug, section, subSection = '', reservationId = '') {
        const resId = reservationId || (AppState && AppState.currentReservationId) || '';
        let newPath = BASE_PATH;
        if (resId) {
            newPath += 'reservation/' + resId;
            if (section === 'videos') {
                newPath += '/videos';
                if (subSection) newPath += '/' + subSection;
            } else if (section === 'guides' || section === 'infos') {
                if (subSection) newPath += '/guides/' + subSection;
            } else if (section === 'parking') {
                newPath += '/parking';
            }
        } else if (slug) {
            newPath += slug;
            if (section === 'videos') {
                newPath += '/videos';
                if (subSection) newPath += '/' + subSection;
            } else if (section === 'guides' || section === 'infos') {
                if (subSection) newPath += '/guides/' + subSection;
            } else if (section === 'reservation') {
                newPath += '/reservation';
            } else if (section === 'parking') {
                newPath += '/parking';
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
        if (window.ScrollManager) {
            window.ScrollManager.resetToTop();
        }
        if (window.App) {
            window.App.load();
        }
    }
};

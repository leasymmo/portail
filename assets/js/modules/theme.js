/**
 * ==============================================================================
 * MODULE THÈME (LIGHT / DARK & SAFE AREA SYNC)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

import { Utils } from './utils.js?v=10.7';
import { I18n } from './i18n.js?v=10.7';

export const ThemeManager = {
    init() {
        const isDark = document.documentElement.classList.contains('dark');
        this.updateUI(isDark);

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('ch_theme')) {
                    this.apply(e.matches ? 'dark' : 'light', false);
                }
            });
        }
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
            try { localStorage.setItem('ch_theme', theme); } catch (e) {}
        }
        this.updateUI(isDark);
        Utils.announceA11y(I18n.t(isDark ? 'a11y_theme_dark' : 'a11y_theme_light'));
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

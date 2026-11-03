/**
 * ==============================================================================
 * MODULE UTILITAIRES, FORMATAGE & SÉCURITÉ XSS
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

export const Utils = {
    /**
     * Annonce un message vocal pour les lecteurs d'écran (VoiceOver / TalkBack)
     */
    announceA11y(message) {
        if (!message) return;
        const el = document.getElementById('a11y-status');
        if (!el) return;
        el.textContent = '';
        setTimeout(() => {
            el.textContent = message;
        }, 50);
    },

    /**
     * Échappe les caractères HTML dangereux pour éviter toute injection XSS
     */
    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    /**
     * Valide et sécurise les URLs externes avant injection dans le DOM
     */
    safeUrl(url) {
        if (!url || typeof url !== 'string') return '#';
        const trimmed = url.trim();
        if (/^(https?:\/\/|\/|tel:|mailto:)/i.test(trimmed)) {
            return trimmed;
        }
        return '#';
    },

    /**
     * Nettoie les noms de classes CSS dynamiques
     */
    sanitizeClassName(cls, defaultClass) {
        if (!cls || typeof cls !== 'string') return defaultClass;
        const sanitized = cls.replace(/[^a-zA-Z0-9_\-\s]/g, '').trim();
        return sanitized || defaultClass;
    },

    /**
     * Détermine si une URL vidéo provient de Google Drive
     */
    isGoogleDriveUrl(url) {
        return /drive\.google\.com/i.test(url);
    },

    /**
     * Transforme une URL Google Drive en lien intégrable /preview
     */
    getGoogleDriveEmbedUrl(url) {
        const match = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
        if (match && match[1]) {
            return `https://drive.google.com/file/d/${match[1]}/preview`;
        }
        return url;
    },

    /**
     * Calcule ou extrait le slug d'une vidéo
     */
    getVideoSlug(v) {
        if (v.slug && v.slug.trim()) return v.slug.trim();
        return String(v.title_fr || '')
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/['’]/g, '-')
            .replace(/[^a-z0-9\-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') || `video-${v.id}`;
    },

    /**
     * Copie un texte dans le presse-papiers avec repli universel
     */
    async copyToClipboard(text) {
        if (!text) return false;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch (e) {}

        try {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            textArea.style.top = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textArea);
            return success;
        } catch (e) {
            return false;
        }
    }
};

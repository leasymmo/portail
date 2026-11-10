/**
 * ==============================================================================
 * APPLICATION BACK-OFFICE SPA - CARRÉ D'HÔTES
 * Conciergerie de Luxe - Interface d'Administration
 * ==============================================================================
 */

const AdminApp = {
    state: {
        user: null,
        csrfToken: '',
        stats: null,
        settings: null,
        properties: [],
        currentPropertyId: null,
        currentVideos: [],
        currentParkingGeneral: null,
        currentParkingLocations: [],
        users: [],
        logs: [],
        activeTab: 'dashboard',
        isDev: false,
        pendingVideoFile: null,
        pendingVideoObjectUrl: null,
        pendingGuideFrFile: null,
        pendingGuideFrObjectUrl: null,
        pendingGuideEnFile: null,
        pendingGuideEnObjectUrl: null
    },

    // --------------------------------------------------------------------------
    // INITIALISATION
    // --------------------------------------------------------------------------
    async init() {
        this.state.isDev = window.location.pathname.includes('/portail/dev');
        
        // Initialisation de la barre latérale et navigation
        this.initSidebarState();
        this.setupNavigation();
        this.setupMobileMenu();

        // Chargement initial des données
        await this.loadInitialData();

        // Application de la route de l'URL (onglet + hébergement sélectionné)
        this.applyRouteFromUrl(true);

        // Écouter les changements de navigation dans l'historique (Back/Forward)
        window.addEventListener('hashchange', () => this.applyRouteFromUrl(false));
    },

    async loadInitialData() {
        this.showLoading(true);
        try {
            const res = await fetch('api/index.php?action=init');
            const data = await res.json();
            if (data.unauthorized) {
                window.location.href = 'login.php';
                return;
            }
            if (res.ok && data.ok) {
                this.state.user = data.user;
                this.state.csrfToken = data.csrf_token;
                this.state.stats = data.stats;
                this.state.properties = data.properties || [];
                this.state.logs = data.recent_logs || [];
                this.state.settings = data.settings || null;

                // Mise à jour de l'UI utilisateur & badges IA
                this.updateUserBadge();
                this.updateAiBadges();

                // Rendu du Dashboard
                this.renderDashboard();

                // Initialiser le premier logement pour les sélecteurs
                if (this.state.properties.length > 0 && !this.state.currentPropertyId) {
                    this.state.currentPropertyId = this.state.properties[0].id;
                }
            } else {
                this.showToast(data.error || 'Erreur lors du chargement des données', 'error');
            }
        } catch (err) {
            this.showToast('Erreur de connexion au serveur d\'administration', 'error');
        } finally {
            this.showLoading(false);
        }
    },

    // --------------------------------------------------------------------------
    // GESTION DES REQUÊTES API AVEC CSRF
    // --------------------------------------------------------------------------
    async apiRequest(endpoint, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.state.csrfToken
            }
        };
        if (body && ['POST', 'PUT', 'DELETE'].includes(method)) {
            options.body = JSON.stringify(body);
        }

        const res = await fetch(endpoint, options);
        const data = await res.json();

        if (res.status === 401 || data.unauthorized) {
            window.location.href = 'login.php';
            throw new Error('Session expirée');
        }

        if (!res.ok || data.ok === false) {
            throw new Error(data.error || 'Une erreur est survenue.');
        }

        return data;
    },

    // --------------------------------------------------------------------------
    // NAVIGATION & ONGLETS
    // --------------------------------------------------------------------------
    setupNavigation() {
        document.querySelectorAll('.nav-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = btn.dataset.tab;
                this.switchTab(tab);
            });
        });
    },

    setupMobileMenu() {
        const toggleBtn = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('mobile-menu-overlay');

        if (toggleBtn && sidebar && overlay) {
            const toggle = () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            };
            toggleBtn.addEventListener('click', toggle);
            overlay.addEventListener('click', toggle);
        }
    },

    closeMobileMenu() {
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('mobile-menu-overlay');
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
    },

    toggleSidebarCollapse() {
        const sidebar = document.getElementById('admin-sidebar');
        if (!sidebar) return;
        const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem('cdh_admin_sidebar_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
    },

    initSidebarState() {
        try {
            if (localStorage.getItem('cdh_admin_sidebar_collapsed') === '1') {
                const sidebar = document.getElementById('admin-sidebar');
                if (sidebar) sidebar.classList.add('sidebar-collapsed');
            }
        } catch (e) {}

        // Raccourci clavier Ctrl+B / Cmd+B pour basculer la sidebar sur ordinateur
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'b' || e.key === 'B')) {
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
                e.preventDefault();
                this.toggleSidebarCollapse();
            }
        });
    },

    parseRoute() {
        let hash = (window.location.hash || '').replace(/^#\/?/, '').trim();
        let tab = 'dashboard';
        let propertyRef = null;
        let parkingSubtab = null;

        if (hash) {
            if (hash.includes('?')) {
                const parts = hash.split('?');
                tab = parts[0].trim();
                const params = new URLSearchParams(parts[1]);
                propertyRef = params.get('property') || params.get('logement') || params.get('property_id') || params.get('id');
                parkingSubtab = params.get('subtab');
            } else if (hash.includes('/')) {
                const parts = hash.split('/');
                tab = parts[0].trim();
                if (parts[1]) propertyRef = parts[1].trim();
            } else {
                tab = hash;
            }
        }

        // Repli sur les paramètres de recherche de l'URL (?property=...)
        if (!propertyRef) {
            const urlParams = new URLSearchParams(window.location.search);
            propertyRef = urlParams.get('property') || urlParams.get('logement') || urlParams.get('property_id') || urlParams.get('id');
            if (!parkingSubtab) parkingSubtab = urlParams.get('subtab');
        }

        const validTabs = ['dashboard', 'properties', 'videos', 'parking', 'logs', 'users'];
        if (!validTabs.includes(tab)) {
            tab = 'dashboard';
        }

        return { tab, propertyRef, parkingSubtab };
    },

    applyRouteFromUrl(isInitial = false) {
        const { tab, propertyRef, parkingSubtab } = this.parseRoute();

        if (propertyRef && this.state.properties.length > 0) {
            const matched = this.state.properties.find(p => 
                p.slug === propertyRef || 
                p.slug.toLowerCase() === propertyRef.toLowerCase() || 
                String(p.id) === String(propertyRef)
            );
            if (matched) {
                this.state.currentPropertyId = matched.id;
            }
        }

        if (!this.state.currentPropertyId && this.state.properties.length > 0) {
            this.state.currentPropertyId = this.state.properties[0].id;
        }

        if (parkingSubtab && ['general', 'locations'].includes(parkingSubtab)) {
            this.state.parkingSubtab = parkingSubtab;
        }

        this.switchTab(tab, true);
    },

    updateUrlHash() {
        const tab = this.state.activeTab || 'dashboard';
        let newHash = `#${tab}`;

        if (['videos', 'parking'].includes(tab)) {
            const prop = this.state.properties.find(p => p.id == this.state.currentPropertyId);
            const propParam = prop ? (prop.slug || prop.id) : this.state.currentPropertyId;
            if (propParam) {
                if (tab === 'parking' && this.state.parkingSubtab === 'locations') {
                    newHash = `#${tab}?property=${encodeURIComponent(propParam)}&subtab=locations`;
                } else {
                    newHash = `#${tab}?property=${encodeURIComponent(propParam)}`;
                }
            }
        }

        if (window.location.hash !== newHash) {
            history.replaceState(null, '', newHash);
        }
    },

    switchTab(tabName, updateHash = true) {
        // Contrôle des droits d'accès
        if (tabName === 'users' && this.state.user && this.state.user.role !== 'superadmin') {
            this.showToast('Accès restreint aux super-administrateurs', 'error');
            return;
        }

        this.state.activeTab = tabName;
        if (updateHash) {
            this.updateUrlHash();
        }

        // Mise à jour visuelle des boutons de navigation
        document.querySelectorAll('.nav-tab-btn').forEach(btn => {
            const isActive = btn.dataset.tab === tabName;
            if (isActive) {
                btn.classList.add('bg-gold/15', 'text-gold', 'border-gold/40');
                btn.classList.remove('text-gray-400', 'hover:text-white', 'hover:bg-white/5');
            } else {
                btn.classList.remove('bg-gold/15', 'text-gold', 'border-gold/40');
                btn.classList.add('text-gray-400', 'hover:text-white', 'hover:bg-white/5');
            }
        });

        // Masquer toutes les sections
        document.querySelectorAll('.admin-section').forEach(sec => sec.classList.add('hidden'));

        // Afficher la section active
        const targetSec = document.getElementById(`section-${tabName}`);
        if (targetSec) {
            targetSec.classList.remove('hidden');
        }

        // Fermer le menu mobile si ouvert
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('mobile-menu-overlay');
        if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        }

        // Déclencher le chargement des données spécifiques
        if (tabName === 'dashboard') this.renderDashboard();
        if (tabName === 'properties') this.renderProperties();
        if (tabName === 'videos') this.loadVideosView();
        if (tabName === 'parking') this.loadParkingView();
        if (tabName === 'logs') this.loadLogsView();
        if (tabName === 'users') this.loadUsersView();
    },

    updateUserBadge() {
        const u = this.state.user;
        if (!u) return;

        const nameEl = document.getElementById('user-display-name');
        const roleEl = document.getElementById('user-display-role');
        const avatarEl = document.getElementById('user-display-avatar');

        if (nameEl) nameEl.textContent = u.name;
        if (roleEl) {
            const roleLabels = { superadmin: 'Super Admin', admin: 'Administrateur', editor: 'Éditeur' };
            roleEl.textContent = roleLabels[u.role] || u.role;
        }
        if (avatarEl) {
            avatarEl.textContent = u.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        }

        // Masquer le menu Users si non-superadmin
        const usersNav = document.getElementById('nav-item-users');
        if (usersNav) {
            if (u.role === 'superadmin') {
                usersNav.classList.remove('hidden');
            } else {
                usersNav.classList.add('hidden');
            }
        }
    },

    // --------------------------------------------------------------------------
    // SECTION 1 : TABLEAU DE BORD (DASHBOARD)
    // --------------------------------------------------------------------------
    renderDashboard() {
        const s = this.state.stats;
        if (!s) return;

        // KPI Counters
        const kpiProps = document.getElementById('kpi-properties');
        const kpiVideos = document.getElementById('kpi-videos');
        const kpiParkings = document.getElementById('kpi-parkings');
        const kpiLocations = document.getElementById('kpi-locations');

        if (kpiProps) kpiProps.textContent = `${s.active_properties} / ${s.total_properties}`;
        if (kpiVideos) kpiVideos.textContent = `${s.active_videos} / ${s.total_videos}`;
        if (kpiParkings) kpiParkings.textContent = s.total_parkings;
        if (kpiLocations) kpiLocations.textContent = `${s.active_locations} / ${s.total_locations}`;

        // Tableau synthétique des logements sur le Dashboard (Bureau / Tablette)
        const tbody = document.getElementById('dashboard-properties-tbody');
        if (tbody) {
            tbody.innerHTML = this.state.properties.map(p => `
                <tr class="border-b border-night-border/50 hover:bg-white/[0.02] transition">
                    <td class="py-3.5 px-4 font-semibold text-white flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-xl bg-gold/15 text-gold flex items-center justify-center text-xs font-bold">
                            ${p.name.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <div>${this.escape(p.name)}</div>
                            <div class="text-xs text-gray-400 font-mono">/${this.escape(p.slug)}</div>
                        </div>
                    </td>
                    <td class="py-3.5 px-4">
                        <button type="button" onclick="AdminApp.togglePropertyStatus(${p.id})" title="Cliquer pour activer/désactiver"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold cursor-pointer transition ${p.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/20 hover:bg-gray-500/20'}">
                            ${p.active == 1 ? '● Actif' : '○ Inactif'}
                        </button>
                    </td>
                    <td class="py-3.5 px-4 text-xs text-gray-300">
                        🎬 ${p.videos_count} tutoriel(s)
                    </td>
                    <td class="py-3.5 px-4 text-xs text-gray-300">
                        🅿️ ${p.parking_locations_count} lieu(x)
                    </td>
                    <td class="py-3.5 px-4 text-right space-x-2">
                        <button type="button" onclick="AdminApp.quickManageProperty(${p.id})" class="px-2.5 py-1.5 rounded-lg bg-gold/10 hover:bg-gold text-gold hover:text-white text-xs font-semibold transition">
                            Gérer
                        </button>
                        <a href="${this.getPortalUrl(p.slug)}" target="_blank" rel="noopener noreferrer" class="p-1.5 inline-flex rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white text-xs transition" title="Voir le portail">
                            👁️
                        </a>
                    </td>
                </tr>
            `).join('');
        }

        // Cartes mobiles optimisées sans scroll horizontal (Mobile)
        const mobileContainer = document.getElementById('dashboard-properties-mobile-cards');
        if (mobileContainer) {
            mobileContainer.innerHTML = this.state.properties.map(p => `
                <div class="p-4 rounded-2xl bg-night border border-night-border/80 space-y-3 shadow-md">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-gold/15 text-gold flex items-center justify-center text-xs font-bold flex-shrink-0">
                                ${p.name.substring(0, 2).toUpperCase()}
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-white truncate">${this.escape(p.name)}</h4>
                                <span class="text-[11px] text-gray-400 font-mono block">/${this.escape(p.slug)}</span>
                            </div>
                        </div>
                        <button type="button" onclick="AdminApp.togglePropertyStatus(${p.id})" title="Cliquer pour activer/désactiver"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold cursor-pointer transition flex-shrink-0 ${p.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/20'}">
                            ${p.active == 1 ? '● Actif' : '○ Inactif'}
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-300 py-1.5 px-2.5 rounded-xl bg-night-card border border-night-border/50 text-center font-medium">
                        <span>🎬 ${p.videos_count} tutoriel(s)</span>
                        <span>🅿️ ${p.parking_locations_count} lieu(x)</span>
                    </div>

                    <div class="grid grid-cols-12 gap-2 pt-1">
                        <button type="button" onclick="AdminApp.quickManageProperty(${p.id})" 
                                class="col-span-8 py-2 px-3 rounded-xl bg-gold/15 hover:bg-gold text-gold hover:text-white border border-gold/40 text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm">
                            <span>✏️</span>
                            <span>Gérer l'hébergement</span>
                        </button>
                        <a href="${this.getPortalUrl(p.slug)}" target="_blank" rel="noopener noreferrer" 
                           class="col-span-4 py-2 px-2 rounded-xl bg-night-card border border-night-border hover:bg-white/10 text-gray-300 hover:text-white text-xs font-semibold transition flex items-center justify-center space-x-1 shadow-sm" title="Voir le portail">
                            <span>👁️</span>
                            <span>Portail</span>
                        </a>
                    </div>
                </div>
            `).join('');
        }

        // Flux des activités récentes
        const logsContainer = document.getElementById('dashboard-recent-logs');
        if (logsContainer) {
            if (this.state.logs.length === 0) {
                logsContainer.innerHTML = '<p class="text-xs text-gray-400 italic py-4">Aucune activité récente journalisée.</p>';
            } else {
                logsContainer.innerHTML = this.state.logs.slice(0, 8).map(l => `
                    <div class="flex items-start space-x-3 text-xs py-2 border-b border-night-border/40 last:border-0">
                        <div class="w-6 h-6 rounded-lg bg-white/5 flex items-center justify-center flex-shrink-0 text-[10px] mt-0.5">
                            ${this.getActionIcon(l.action)}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-gray-200 truncate">
                                <span class="font-bold text-white">${this.escape(l.user_name || 'Système')}</span> : 
                                <span class="text-gold font-medium">${this.getActionLabel(l.action)}</span>
                                <span class="text-gray-400">(${this.escape(l.entity_type)} ${l.entity_label ? '· ' + this.escape(l.entity_label) : ''})</span>
                            </p>
                            <p class="text-[10px] text-gray-500 mt-0.5">${this.formatDate(l.created_at)}</p>
                        </div>
                    </div>
                `).join('');
            }
        }
    },

    // --------------------------------------------------------------------------
    // SECTION 2 : GESTION DES HÉBERGEMENTS (PROPERTIES)
    // --------------------------------------------------------------------------
    renderProperties() {
        const container = document.getElementById('properties-grid');
        const searchInput = document.getElementById('properties-search-input');
        const filterStatus = document.getElementById('properties-filter-status');

        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const status = filterStatus ? filterStatus.value : 'all';

        let list = this.state.properties;
        if (query) {
            list = list.filter(p => p.name.toLowerCase().includes(query) || p.slug.toLowerCase().includes(query) || (p.address && p.address.toLowerCase().includes(query)));
        }
        if (status !== 'all') {
            list = list.filter(p => String(p.active) === status);
        }

        if (!container) return;

        if (list.length === 0) {
            container.innerHTML = `
                <div class="col-span-full py-12 text-center text-gray-400">
                    <p class="text-lg">Aucun hébergement trouvé.</p>
                    <p class="text-xs text-gray-500 mt-1">Modifiez vos critères de recherche ou ajoutez un nouveau logement.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = list.map(p => {
            const guideFrUrl = (p.guide_fr_url || p.guide_pdf_url_fr || '').trim();
            const guideEnUrl = (p.guide_en_url || p.guide_pdf_url_en || '').trim();
            const hasFrGuide = Boolean(guideFrUrl);
            const hasEnGuide = Boolean(guideEnUrl);
            return `
            <div class="rounded-2xl bg-night-card border border-night-border hover:border-gold/40 p-4 sm:p-5 transition flex flex-col justify-between group shadow-lg">
                <div>
                    <!-- En-tête hébergement -->
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center space-x-3 min-w-0 flex-1">
                            <div class="w-10 h-10 rounded-2xl bg-gold/15 text-gold border border-gold/30 flex items-center justify-center font-bold text-sm shadow-md shadow-gold/10 flex-shrink-0">
                                ${p.name.substring(0, 2).toUpperCase()}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-base font-bold text-white group-hover:text-gold transition truncate">${this.escape(p.name)}</h4>
                                <span class="text-xs font-mono text-gray-400 block truncate">/${this.escape(p.slug)}</span>
                            </div>
                        </div>
                        <button type="button" onclick="AdminApp.togglePropertyStatus(${p.id})" title="Cliquer pour activer/désactiver"
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold cursor-pointer transition flex-shrink-0 ${p.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30 hover:bg-gray-500/20'}">
                            ${p.active == 1 ? '● Actif' : '○ Inactif'}
                        </button>
                    </div>

                    ${p.address ? `<p class="text-xs text-gray-300 mb-3 flex items-center"><span class="mr-1.5 flex-shrink-0">📍</span><span class="truncate">${this.escape(p.address)}</span></p>` : ''}

                    <!-- Encadré Wi-Fi & Guides PDF Cliquables -->
                    <div class="p-3 rounded-xl bg-night/60 border border-night-border/50 space-y-2 mb-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-400 text-[11px] font-semibold flex items-center gap-1.5">
                                <span>📶</span><span>Wi-Fi :</span>
                            </span>
                            <span class="font-bold text-white truncate max-w-[170px]">${p.wifi_ssid ? this.escape(p.wifi_ssid) : '<span class="text-gray-500 italic font-normal">Non configuré</span>'}</span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-night-border/30 text-xs">
                            <span class="text-gray-400 text-[11px] font-semibold flex items-center gap-1.5">
                                <span>📖</span><span>Guides PDF :</span>
                            </span>
                            <div class="flex items-center space-x-1.5">
                                ${hasFrGuide ? `
                                    <a href="${this.escape(guideFrUrl)}" target="_blank" rel="noopener noreferrer" 
                                       class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500 hover:text-white transition inline-flex items-center shadow-sm" title="Ouvrir le guide PDF français (nouvel onglet)">
                                        FR
                                    </a>
                                ` : `
                                    <button type="button" onclick="AdminApp.openPropertyModal(${p.id})" 
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-night border border-night-border text-gray-500 hover:border-gold/40 hover:text-gray-300 transition" title="Aucun guide FR configuré — Cliquer pour en ajouter un">
                                        FR
                                    </button>
                                `}

                                ${hasEnGuide ? `
                                    <a href="${this.escape(guideEnUrl)}" target="_blank" rel="noopener noreferrer" 
                                       class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500 hover:text-white transition inline-flex items-center shadow-sm" title="Ouvrir le guide PDF anglais (nouvel onglet)">
                                        EN
                                    </a>
                                ` : `
                                    <button type="button" onclick="AdminApp.openPropertyModal(${p.id})" 
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-night border border-night-border text-gray-500 hover:border-gold/40 hover:text-gray-300 transition" title="Aucun guide EN configuré — Cliquer pour en ajouter un">
                                        EN
                                    </button>
                                `}
                            </div>
                        </div>
                    </div>

                    <!-- Raccourcis Modules (Tutoriels Vidéos & Stationnement) -->
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <button type="button" onclick="AdminApp.selectPropertyAndTab(${p.id}, 'videos')" 
                                class="flex items-center justify-between p-2.5 rounded-xl bg-night border border-night-border hover:border-blue-500/40 hover:bg-blue-500/5 transition group/btn">
                            <span class="text-xs font-semibold text-gray-300 group-hover/btn:text-white flex items-center space-x-1.5">
                                <span>🎬</span>
                                <span>Vidéos</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30">${p.videos_count}</span>
                        </button>
                        <button type="button" onclick="AdminApp.selectPropertyAndTab(${p.id}, 'parking')" 
                                class="flex items-center justify-between p-2.5 rounded-xl bg-night border border-night-border hover:border-amber-500/40 hover:bg-amber-500/5 transition group/btn">
                            <span class="text-xs font-semibold text-gray-300 group-hover/btn:text-white flex items-center space-x-1.5">
                                <span>🅿️</span>
                                <span>Parking</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">${p.parking_locations_count}</span>
                        </button>
                    </div>
                </div>

                <!-- Barre d'actions équilibrée (1 seule ligne, grille 12 colonnes sans chevauchement) -->
                <div class="pt-3 border-t border-night-border/50 grid grid-cols-12 gap-2">
                    <button type="button" onclick="AdminApp.openPropertyModal(${p.id})" 
                            class="col-span-6 py-2 px-2.5 rounded-xl bg-gold/15 hover:bg-gold text-gold hover:text-white border border-gold/40 text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm">
                        <span>✏️</span>
                        <span>Modifier</span>
                    </button>
                    <a href="${this.getPortalUrl(p.slug)}" target="_blank" rel="noopener noreferrer" 
                       class="col-span-4 py-2 px-2 rounded-xl bg-night border border-night-border hover:bg-white/10 text-gray-300 hover:text-white text-xs font-semibold transition flex items-center justify-center space-x-1 shadow-sm" title="Ouvrir le portail voyageur">
                        <span>👁️</span>
                        <span>Portail</span>
                    </a>
                    <button type="button" onclick="AdminApp.confirmDeleteProperty(${p.id})" 
                            class="col-span-2 py-2 rounded-xl bg-red-500/10 hover:bg-red-500 hover:text-white border border-red-500/20 text-xs font-semibold text-red-400 transition flex items-center justify-center shadow-sm" title="Supprimer cet hébergement">
                        <span>🗑️</span>
                    </button>
                </div>
            </div>
            `;
        }).join('');
    },

    openPropertyModal(id = 0) {
        const modal = document.getElementById('modal-property-form');
        const titleEl = document.getElementById('modal-property-title');
        const form = document.getElementById('property-form');
        if (!modal || !form) return;

        // Réinitialisation des fichiers guides en attente (FR et EN)
        if (this.state.pendingGuideFrObjectUrl) {
            try { URL.revokeObjectURL(this.state.pendingGuideFrObjectUrl); } catch(e) {}
            this.state.pendingGuideFrObjectUrl = null;
        }
        this.state.pendingGuideFrFile = null;

        if (this.state.pendingGuideEnObjectUrl) {
            try { URL.revokeObjectURL(this.state.pendingGuideEnObjectUrl); } catch(e) {}
            this.state.pendingGuideEnObjectUrl = null;
        }
        this.state.pendingGuideEnFile = null;

        const fiFr = document.getElementById('property-guide-fr-file');
        if (fiFr) fiFr.value = '';
        const fiEn = document.getElementById('property-guide-en-file');
        if (fiEn) fiEn.value = '';

        const progFr = document.getElementById('progress-guide-fr');
        if (progFr) progFr.classList.add('hidden');
        const progEn = document.getElementById('progress-guide-en');
        if (progEn) progEn.classList.add('hidden');

        const saveBtn = document.getElementById('btn-save-property');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span>Enregistrer</span>';
            saveBtn.classList.remove('opacity-75', 'cursor-not-allowed');
        }
        const cancelBtn = document.getElementById('btn-cancel-property');
        if (cancelBtn) cancelBtn.disabled = false;

        form.reset();
        document.getElementById('property-id').value = id;

        if (id > 0) {
            const prop = this.state.properties.find(p => p.id == id);
            if (!prop) return;
            titleEl.textContent = `Modifier : ${prop.name}`;
            document.getElementById('property-name').value = prop.name || '';
            document.getElementById('property-slug').value = prop.slug || '';
            document.getElementById('property-address').value = prop.address || '';
            document.getElementById('property-wifi-ssid').value = prop.wifi_ssid || '';
            document.getElementById('property-wifi-password').value = prop.wifi_password || '';
            document.getElementById('property-guide-fr').value = prop.guide_fr_url || '';
            document.getElementById('property-guide-en').value = prop.guide_en_url || '';
            document.getElementById('property-active').checked = (prop.active == 1);
        } else {
            titleEl.textContent = 'Ajouter un nouvel hébergement';
            document.getElementById('property-guide-fr').value = '';
            document.getElementById('property-guide-en').value = '';
            document.getElementById('property-active').checked = true;
        }

        // Met à jour l'affichage des prévisualisations et dropzones des guides
        this.updateGuideStagedPreview('fr');
        this.updateGuideStagedPreview('en');
        this.updateGuideR2Previews();

        modal.classList.remove('hidden');
    },

    closePropertyModal() {
        if (this.state.pendingGuideFrObjectUrl) {
            try { URL.revokeObjectURL(this.state.pendingGuideFrObjectUrl); } catch(e) {}
            this.state.pendingGuideFrObjectUrl = null;
        }
        this.state.pendingGuideFrFile = null;

        if (this.state.pendingGuideEnObjectUrl) {
            try { URL.revokeObjectURL(this.state.pendingGuideEnObjectUrl); } catch(e) {}
            this.state.pendingGuideEnObjectUrl = null;
        }
        this.state.pendingGuideEnFile = null;

        const modal = document.getElementById('modal-property-form');
        if (modal) modal.classList.add('hidden');
    },

    onPropertyNameInput(val) {
        const id = document.getElementById('property-id').value;
        const slugInput = document.getElementById('property-slug');
        if (id == 0 && slugInput && !slugInput.value.trim()) {
            slugInput.value = this.slugify(val);
        }
        this.updateGuideR2Previews();
    },

    onPropertySlugInput(val) {
        this.updateGuideR2Previews();
    },

    updateGuideR2Previews() {
        const slugInput = document.getElementById('property-slug');
        let slug = slugInput ? slugInput.value.trim() : '';
        if (!slug) {
            const nameInput = document.getElementById('property-name');
            if (nameInput && nameInput.value.trim()) {
                slug = this.slugify(nameInput.value.trim());
            }
        }
        const previewEl = document.getElementById('property-r2-dest-preview');
        if (previewEl) {
            if (slug) {
                previewEl.textContent = `carredhotes-guides/${slug}/guide-fr.pdf (et guide-en.pdf)`;
                previewEl.className = 'font-mono text-gold font-bold truncate';
            } else {
                previewEl.textContent = 'carredhotes-guides/[slug-logement]/guide-fr.pdf (champ slug obligatoire)';
                previewEl.className = 'font-mono text-amber-400 font-semibold truncate italic';
            }
        }
    },

    async saveProperty(e) {
        e.preventDefault();
        const id = parseInt(document.getElementById('property-id').value, 10);
        const name = (document.getElementById('property-name').value || '').trim();
        let slug = (document.getElementById('property-slug').value || '').trim();

        // 1. Validation du nom
        if (!name) {
            this.showToast("Le nom du logement est obligatoire.", "error");
            document.getElementById('property-name')?.focus();
            return;
        }

        // 2. Validation impérative du Slug (strictement obligatoire pour Cloudflare R2)
        if (!slug) {
            this.showToast("Le slug unique est obligatoire pour valider le formulaire et nommer le dossier sur Cloudflare R2.", "error");
            const slugInput = document.getElementById('property-slug');
            if (slugInput) {
                slugInput.focus();
                slugInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/50');
                setTimeout(() => slugInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500/50'), 2500);
            }
            return;
        }

        slug = this.slugify(slug);
        const slugInput = document.getElementById('property-slug');
        if (slugInput) slugInput.value = slug;

        let guideFrUrl = (document.getElementById('property-guide-fr').value || '').trim();
        let guideEnUrl = (document.getElementById('property-guide-en').value || '').trim();

        const saveBtn = document.getElementById('btn-save-property');
        const cancelBtn = document.getElementById('btn-cancel-property');
        const origBtnHtml = saveBtn ? saveBtn.innerHTML : '<span>Enregistrer</span>';

        const setBusyState = (isBusy, label) => {
            if (saveBtn) {
                saveBtn.disabled = isBusy;
                if (isBusy) {
                    saveBtn.innerHTML = `<span>⏳</span> <span>${label}</span>`;
                    saveBtn.classList.add('opacity-80', 'cursor-not-allowed');
                } else {
                    saveBtn.innerHTML = origBtnHtml;
                    saveBtn.classList.remove('opacity-80', 'cursor-not-allowed');
                }
            }
            if (cancelBtn) cancelBtn.disabled = isBusy;
        };

        // 3. Téléversement Cloudflare R2 du guide FR s'il est en attente
        if (this.state.pendingGuideFrFile) {
            const file = this.state.pendingGuideFrFile;
            setBusyState(true, "Envoi du guide FR vers Cloudflare R2...");

            const progressEl = document.getElementById('progress-guide-fr');
            const barEl = document.getElementById('progress-bar-guide-fr');
            const percentEl = document.getElementById('progress-percent-guide-fr');
            const textEl = document.getElementById('progress-text-guide-fr');

            if (progressEl) progressEl.classList.remove('hidden');
            if (barEl) barEl.style.width = '0%';
            if (percentEl) percentEl.textContent = '0%';
            if (textEl) textEl.textContent = `Téléversement de guide-fr.pdf vers ${slug}/...`;

            try {
                const res = await this.uploadMediaFile(file, 'guide', {
                    property_slug: slug,
                    lang: 'fr'
                }, (percent, loaded, total) => {
                    if (barEl) barEl.style.width = `${percent}%`;
                    if (percentEl) percentEl.textContent = `${percent}%`;
                    if (textEl) textEl.textContent = `${this.formatBytes(loaded)} / ${this.formatBytes(total)} (${percent}%)`;
                });

                guideFrUrl = res.url;
                const urlInput = document.getElementById('property-guide-fr');
                if (urlInput) urlInput.value = guideFrUrl;

                if (this.state.pendingGuideFrObjectUrl) {
                    try { URL.revokeObjectURL(this.state.pendingGuideFrObjectUrl); } catch(e) {}
                    this.state.pendingGuideFrObjectUrl = null;
                }
                this.state.pendingGuideFrFile = null;

            } catch (errFr) {
                setBusyState(false);
                if (progressEl) progressEl.classList.add('hidden');
                this.showToast(`Échec du téléversement du guide FR sur Cloudflare R2 : ${errFr.message}. Hébergement non enregistré.`, "error");
                return; // Bloque l'enregistrement en BDD
            } finally {
                if (progressEl) progressEl.classList.add('hidden');
            }
        }

        // 4. Téléversement Cloudflare R2 du guide EN s'il est en attente
        if (this.state.pendingGuideEnFile) {
            const file = this.state.pendingGuideEnFile;
            setBusyState(true, "Envoi du guide EN vers Cloudflare R2...");

            const progressEl = document.getElementById('progress-guide-en');
            const barEl = document.getElementById('progress-bar-guide-en');
            const percentEl = document.getElementById('progress-percent-guide-en');
            const textEl = document.getElementById('progress-text-guide-en');

            if (progressEl) progressEl.classList.remove('hidden');
            if (barEl) barEl.style.width = '0%';
            if (percentEl) percentEl.textContent = '0%';
            if (textEl) textEl.textContent = `Téléversement de guide-en.pdf vers ${slug}/...`;

            try {
                const res = await this.uploadMediaFile(file, 'guide', {
                    property_slug: slug,
                    lang: 'en'
                }, (percent, loaded, total) => {
                    if (barEl) barEl.style.width = `${percent}%`;
                    if (percentEl) percentEl.textContent = `${percent}%`;
                    if (textEl) textEl.textContent = `${this.formatBytes(loaded)} / ${this.formatBytes(total)} (${percent}%)`;
                });

                guideEnUrl = res.url;
                const urlInput = document.getElementById('property-guide-en');
                if (urlInput) urlInput.value = guideEnUrl;

                if (this.state.pendingGuideEnObjectUrl) {
                    try { URL.revokeObjectURL(this.state.pendingGuideEnObjectUrl); } catch(e) {}
                    this.state.pendingGuideEnObjectUrl = null;
                }
                this.state.pendingGuideEnFile = null;

            } catch (errEn) {
                setBusyState(false);
                if (progressEl) progressEl.classList.add('hidden');
                this.showToast(`Échec du téléversement du guide EN sur Cloudflare R2 : ${errEn.message}. Hébergement non enregistré.`, "error");
                return; // Bloque l'enregistrement en BDD
            } finally {
                if (progressEl) progressEl.classList.add('hidden');
            }
        }

        // 5. Enregistrement en base de données (strictement synchronisé avec Cloudflare R2)
        setBusyState(true, "Enregistrement en base...");

        const data = {
            id,
            name: name,
            slug: slug,
            address: (document.getElementById('property-address').value || '').trim(),
            wifi_ssid: (document.getElementById('property-wifi-ssid').value || '').trim(),
            wifi_password: (document.getElementById('property-wifi-password').value || '').trim(),
            guide_fr_url: guideFrUrl,
            guide_en_url: guideEnUrl,
            active: document.getElementById('property-active').checked ? 1 : 0
        };

        try {
            const res = await this.apiRequest('api/index.php?action=property_save', 'POST', { property: data });
            this.showToast(res.message || 'Hébergement enregistré et connecté avec succès.');
            this.closePropertyModal();
            await this.loadInitialData();
            this.renderProperties();
        } catch (err) {
            this.showToast(`Erreur d'enregistrement : ${err.message}`, 'error');
        } finally {
            setBusyState(false);
        }
    },

    async togglePropertyStatus(id) {
        try {
            await this.apiRequest('api/index.php?action=property_toggle', 'POST', { id });
            const prop = this.state.properties.find(p => p.id == id);
            if (prop) prop.active = (prop.active == 1 ? 0 : 1);
            this.renderProperties();
            this.showToast('Statut du logement actualisé.');
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    confirmDeleteProperty(id, name = null) {
        const prop = (this.state.properties || []).find(p => p.id == id);
        const resolvedName = name || (prop ? prop.name : 'cet hébergement');

        this.confirmAction(
            `Supprimer ${resolvedName} ?`,
            `Attention : cette action supprimera définitivement le logement, ses vidéos tutoriels et ses configurations de stationnement associées. Cette action est irréversible.`,
            async () => {
                try {
                    await this.apiRequest('api/index.php?action=property_delete', 'POST', { id });
                    this.showToast(`Hébergement ${resolvedName} supprimé.`);
                    await this.loadInitialData();
                    this.renderProperties();
                } catch (err) {
                    this.showToast(err.message, 'error');
                }
            }
        );
    },

    selectPropertyAndTab(propertyId, tabName) {
        this.state.currentPropertyId = propertyId;
        this.switchTab(tabName);
    },

    quickManageProperty(propertyId) {
        this.openPropertyModal(propertyId);
    },

    // --------------------------------------------------------------------------
    // SECTION 3 : GESTION DES VIDÉOS TUTORIELS
    // --------------------------------------------------------------------------
    async loadVideosView() {
        this.updatePropertySelector('videos-property-select', async (pid) => {
            this.state.currentPropertyId = pid;
            this.updateUrlHash();
            await this.fetchAndRenderVideos(pid);
        });

        if (this.state.currentPropertyId) {
            this.updateUrlHash();
            await this.fetchAndRenderVideos(this.state.currentPropertyId);
        }
    },

    async fetchAndRenderVideos(propertyId) {
        const container = document.getElementById('videos-list-container');
        if (!container) return;

        const property = this.state.properties.find(p => p.id == propertyId);
        const propName = property ? property.name : '';

        container.innerHTML = `
            <div class="rounded-2xl bg-night-card border border-night-border/80 p-12 text-center space-y-4 shadow-xl">
                <div class="relative w-14 h-14 mx-auto">
                    <div class="absolute inset-0 rounded-full border-2 border-gold/20"></div>
                    <div class="absolute inset-0 rounded-full border-2 border-gold border-t-transparent animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-xl">🎬</div>
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-bold text-white tracking-wide">Chargement des vidéos...</h4>
                    <p class="text-xs text-gray-400">Récupération des tutoriels pour « ${this.escape(propName)} »</p>
                </div>
            </div>
        `;

        try {
            const res = await this.apiRequest(`api/index.php?action=videos_get&property_id=${propertyId}`);
            this.state.currentVideos = res.videos || [];
            this.renderVideosList();
        } catch (err) {
            container.innerHTML = `<div class="py-8 text-center text-red-400">${this.escape(err.message)}</div>`;
        }
    },

    renderVideosList() {
        const container = document.getElementById('videos-list-container');
        if (!container) return;

        const list = this.state.currentVideos;
        if (list.length === 0) {
            container.innerHTML = `
                <div class="py-12 text-center text-gray-400 border border-dashed border-night-border rounded-2xl p-6">
                    <span class="text-3xl block mb-2">🎬</span>
                    <p class="text-base font-bold text-white">Aucun tutoriel vidéo pour cet hébergement</p>
                    <p class="text-xs text-gray-500 mt-1 mb-4">Ajoutez des guides pratiques (machine à café, digicode, plaques de cuisson...)</p>
                    <button type="button" onclick="AdminApp.openVideoModal(0)" class="px-4 py-2 rounded-xl bg-gold text-white font-bold text-xs shadow-lg shadow-gold/20 hover:bg-gold-light transition">
                        + Ajouter une vidéo
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="space-y-3.5" id="sortable-videos">
                ${list.map((v, index) => {
                    const isR2 = (v.video_url && (v.video_url.includes('carredhotes-videos') || v.video_url.includes('r2.dev') || v.video_url.includes('cloudflarestorage')));
                    return `
                    <div class="p-4 sm:p-5 rounded-2xl bg-night-card border border-night-border hover:border-gold/30 transition shadow-lg group" data-id="${v.id}">
                        <!-- LIGNE 1 : EN-TÊTE / IDENTITÉ DE LA VIDÉO (Pleine largeur sur mobile, titre jamais masqué) -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start space-x-3 sm:space-x-4 min-w-0 flex-1">
                                <!-- Poignée d'ordre ▲ / ▼ en micro-pillule élégante -->
                                <div class="flex flex-col space-y-0.5 bg-night p-1 rounded-xl border border-night-border/70 text-gray-400 flex-shrink-0">
                                    <button type="button" onclick="AdminApp.moveVideo(${v.id}, -1)" ${index === 0 ? 'disabled class="opacity-20 cursor-not-allowed p-0.5 text-[11px]"' : 'class="p-0.5 hover:text-gold text-[11px] transition"'} title="Monter">▲</button>
                                    <button type="button" onclick="AdminApp.moveVideo(${v.id}, 1)" ${index === list.length - 1 ? 'disabled class="opacity-20 cursor-not-allowed p-0.5 text-[11px]"' : 'class="p-0.5 hover:text-gold text-[11px] transition"'} title="Descendre">▼</button>
                                </div>

                                <!-- Badge icône -->
                                <div class="w-12 h-12 rounded-2xl ${this.escape(v.icon_bg_color || 'bg-blue-50')} ${this.escape(v.icon_text_color || 'text-blue-500')} flex items-center justify-center text-2xl flex-shrink-0 shadow-md">
                                    ${this.escape(v.icon || '🎬')}
                                </div>

                                <!-- Titre & métadonnées : S'affiche en pleine largeur sans être écrasé -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <h5 class="text-sm sm:text-base font-bold text-white group-hover:text-gold transition break-words">
                                            ${this.escape(v.title_fr)}
                                        </h5>
                                        <span class="text-[10px] text-gray-400 font-mono px-1.5 py-0.5 rounded bg-night border border-night-border">#${v.display_order}</span>
                                        ${isR2 ? '<span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gold/15 text-gold border border-gold/30">☁️ R2</span>' : ''}
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1 flex items-center gap-1.5 break-words">
                                        <span>🇬🇧</span>
                                        <span class="italic">${this.escape(v.title_en || v.title_fr)}</span>
                                    </p>
                                    <p class="text-[11px] text-gray-500 font-mono mt-1 truncate" title="${this.escape(v.video_url)}">
                                        🔗 ${this.escape(v.video_url)}
                                    </p>
                                </div>
                            </div>

                            <!-- Statut Actif / Masqué en haut à droite -->
                            <div class="flex-shrink-0">
                                <button type="button" onclick="AdminApp.toggleVideoStatus(${v.id})" title="Cliquer pour basculer la visibilité"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold transition cursor-pointer ${v.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30 hover:bg-gray-500/20'}">
                                    ${v.active == 1 ? '● Actif' : '○ Masqué'}
                                </button>
                            </div>
                        </div>

                        <!-- LIGNE 2 : BARRE D'ACTIONS (Grille 12 colonnes, 1 seule ligne, 0 chevauchement) -->
                        <div class="pt-3 mt-3.5 border-t border-night-border/50 grid grid-cols-12 gap-2">
                            <button type="button" onclick="AdminApp.previewVideo(${v.id})" 
                                    class="col-span-6 py-2 px-2.5 rounded-xl bg-gold/15 hover:bg-gold text-gold hover:text-white border border-gold/40 text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm">
                                <span>▶️</span>
                                <span>Lire la vidéo</span>
                            </button>

                            <button type="button" onclick="AdminApp.openVideoModal(${v.id})" 
                                    class="col-span-4 py-2 px-2 rounded-xl bg-night border border-night-border hover:border-gold hover:text-gold text-xs font-semibold text-gray-300 transition flex items-center justify-center space-x-1 shadow-sm" title="Modifier le tutoriel">
                                <span>✏️</span>
                                <span>Modifier</span>
                            </button>

                            <button type="button" onclick="AdminApp.confirmDeleteVideo(${v.id})" 
                                    class="col-span-2 py-2 rounded-xl bg-red-500/10 hover:bg-red-500 hover:text-white border border-red-500/20 text-xs font-semibold text-red-400 transition flex items-center justify-center shadow-sm" title="Supprimer la vidéo">
                                <span>🗑️</span>
                            </button>
                        </div>
                    </div>
                    `;
                }).join('')}
            </div>
        `;
    },

    openVideoModal(id = 0) {
        const modal = document.getElementById('modal-video-form');
        const titleEl = document.getElementById('modal-video-title');
        const form = document.getElementById('video-form');
        if (!modal || !form) return;

        // Réinitialisation de tout fichier en attente
        if (this.state.pendingVideoObjectUrl) {
            try { URL.revokeObjectURL(this.state.pendingVideoObjectUrl); } catch(e) {}
            this.state.pendingVideoObjectUrl = null;
        }
        this.state.pendingVideoFile = null;

        const fileInput = document.getElementById('video-file-input');
        if (fileInput) fileInput.value = '';

        const progressEl = document.getElementById('progress-video');
        if (progressEl) progressEl.classList.add('hidden');

        const saveBtn = document.getElementById('btn-save-video');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span>Enregistrer</span>';
            saveBtn.classList.remove('opacity-75', 'cursor-not-allowed');
        }
        const cancelBtn = document.getElementById('btn-cancel-video');
        if (cancelBtn) cancelBtn.disabled = false;

        form.reset();
        document.getElementById('video-id').value = id;
        document.getElementById('video-property-id').value = this.state.currentPropertyId;

        if (id > 0) {
            const v = this.state.currentVideos.find(item => item.id == id);
            if (!v) return;
            titleEl.textContent = `Modifier le tutoriel : ${v.title_fr}`;
            document.getElementById('video-title-fr').value = v.title_fr || '';
            document.getElementById('video-title-en').value = v.title_en || '';
            document.getElementById('video-slug').value = v.slug || '';
            document.getElementById('video-icon').value = v.icon || '🎬';
            document.getElementById('video-bg-color').value = v.icon_bg_color || 'bg-blue-50';
            document.getElementById('video-text-color').value = v.icon_text_color || 'text-blue-500';
            document.getElementById('video-url').value = v.video_url || '';
            document.getElementById('video-order').value = v.display_order || 0;
            document.getElementById('video-active').checked = (v.active == 1);
        } else {
            titleEl.textContent = 'Ajouter un tutoriel vidéo';
            document.getElementById('video-title-fr').value = '';
            document.getElementById('video-title-en').value = '';
            document.getElementById('video-slug').value = '';
            document.getElementById('video-url').value = '';
            document.getElementById('video-icon').value = '🎬';
            document.getElementById('video-bg-color').value = 'bg-blue-50';
            document.getElementById('video-text-color').value = 'text-blue-500';
            document.getElementById('video-order').value = this.state.currentVideos.length + 1;
            document.getElementById('video-active').checked = true;
        }

        // Met à jour l'affichage de l'aperçu et de la destination Cloudflare R2
        this.updateVideoStagedPreview();
        this.updateVideoR2Preview();

        modal.classList.remove('hidden');
    },

    onVideoTitleInput(val) {
        const slugInput = document.getElementById('video-slug');
        if (slugInput) {
            slugInput.value = this.slugify(val);
        }
        this.updateVideoR2Preview();
        if (this.state.pendingVideoFile) {
            this.updateVideoStagedPreview();
        }
    },

    onVideoSlugInput(val) {
        this.updateVideoR2Preview();
        if (this.state.pendingVideoFile) {
            this.updateVideoStagedPreview();
        }
    },

    updateVideoR2Preview() {
        const currentProp = this.state.properties.find(p => p.id == this.state.currentPropertyId);
        const propSlug = currentProp ? currentProp.slug : '[slug-logement]';
        const slugInput = document.getElementById('video-slug');
        let videoSlug = slugInput ? slugInput.value.trim() : '';
        if (!videoSlug) {
            const titleInput = document.getElementById('video-title-fr');
            if (titleInput && titleInput.value.trim()) {
                videoSlug = this.slugify(titleInput.value.trim());
            }
        }

        let ext = 'mp4';
        if (this.state.pendingVideoFile) {
            const fExt = this.state.pendingVideoFile.name.split('.').pop().toLowerCase();
            if (fExt) ext = fExt;
        }

        const previewEl = document.getElementById('video-r2-dest-preview');
        if (previewEl) {
            if (videoSlug) {
                previewEl.textContent = `carredhotes-videos/${propSlug}/${videoSlug}.${ext}`;
                previewEl.className = 'font-mono text-gold font-bold truncate';
            } else {
                previewEl.textContent = `carredhotes-videos/${propSlug}/[slug-vidéo].${ext} (champ obligatoire)`;
                previewEl.className = 'font-mono text-amber-400 font-semibold truncate italic';
            }
        }
    },

    closeVideoModal() {
        if (this.state.pendingVideoObjectUrl) {
            try { URL.revokeObjectURL(this.state.pendingVideoObjectUrl); } catch(e) {}
            this.state.pendingVideoObjectUrl = null;
        }
        this.state.pendingVideoFile = null;
        const modal = document.getElementById('modal-video-form');
        if (modal) modal.classList.add('hidden');
    },

    async saveVideo(e) {
        e.preventDefault();

        const id = parseInt(document.getElementById('video-id').value, 10);
        const propertyId = parseInt(document.getElementById('video-property-id').value, 10);
        const titleFr = (document.getElementById('video-title-fr').value || '').trim();
        const titleEn = (document.getElementById('video-title-en').value || '').trim();
        let slug = (document.getElementById('video-slug').value || '').trim();
        let videoUrl = (document.getElementById('video-url').value || '').trim();

        // 1. Validation de l'hébergement associé
        const currentProp = this.state.properties.find(p => p.id == propertyId);
        const propSlug = currentProp ? currentProp.slug : '';
        if (!propSlug) {
            this.showToast("Veuillez sélectionner un hébergement valide.", "error");
            return;
        }

        // 2. Validation du titre en français
        if (!titleFr) {
            this.showToast("Le titre en français est obligatoire.", "error");
            const titleInput = document.getElementById('video-title-fr');
            if (titleInput) {
                titleInput.focus();
                titleInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/50');
                setTimeout(() => titleInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500/50'), 2500);
            }
            return;
        }

        // 3. Validation impérative du Slug (strictement requis avant téléversement ou création)
        if (!slug) {
            this.showToast("Le slug unique est obligatoire pour valider le formulaire et nommer la vidéo sur Cloudflare.", "error");
            const slugInput = document.getElementById('video-slug');
            if (slugInput) {
                slugInput.focus();
                slugInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/50');
                setTimeout(() => slugInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500/50'), 2500);
            }
            return;
        }

        slug = this.slugify(slug);
        const slugInput = document.getElementById('video-slug');
        if (slugInput) slugInput.value = slug;

        // 4. Vérifier qu'un fichier est en attente OU qu'une URL directe existe
        if (!this.state.pendingVideoFile && !videoUrl) {
            this.showToast("Veuillez choisir un fichier vidéo ou saisir une URL directe.", "warning");
            return;
        }

        const saveBtn = document.getElementById('btn-save-video');
        const cancelBtn = document.getElementById('btn-cancel-video');
        const origBtnHtml = saveBtn ? saveBtn.innerHTML : '<span>Enregistrer</span>';

        const setBusyState = (isBusy, label) => {
            if (saveBtn) {
                saveBtn.disabled = isBusy;
                if (isBusy) {
                    saveBtn.innerHTML = `<span>⏳</span> <span>${label}</span>`;
                    saveBtn.classList.add('opacity-80', 'cursor-not-allowed');
                } else {
                    saveBtn.innerHTML = origBtnHtml;
                    saveBtn.classList.remove('opacity-80', 'cursor-not-allowed');
                }
            }
            if (cancelBtn) cancelBtn.disabled = isBusy;
        };

        const progressEl = document.getElementById('progress-video');
        const barEl = document.getElementById('progress-bar-video');
        const percentEl = document.getElementById('progress-percent-video');
        const textEl = document.getElementById('progress-text-video');

        // 5. Téléversement Cloudflare R2 UNIQUEMENT à la validation du formulaire
        if (this.state.pendingVideoFile) {
            const file = this.state.pendingVideoFile;
            setBusyState(true, "Téléversement vers Cloudflare R2...");

            if (progressEl) progressEl.classList.remove('hidden');
            if (barEl) barEl.style.width = '0%';
            if (percentEl) percentEl.textContent = '0%';
            if (textEl) textEl.textContent = `Téléversement vers carredhotes-videos/${propSlug}/${slug}...`;

            try {
                const res = await this.uploadMediaFile(file, 'video', {
                    property_slug: propSlug,
                    video_slug: slug
                }, (percent, loaded, total) => {
                    if (barEl) barEl.style.width = `${percent}%`;
                    if (percentEl) percentEl.textContent = `${percent}%`;
                    if (textEl) textEl.textContent = `${this.formatBytes(loaded)} / ${this.formatBytes(total)} (${percent}%)`;
                });

                // URL Cloudflare R2 finale
                videoUrl = res.url;
                const urlInput = document.getElementById('video-url');
                if (urlInput) urlInput.value = videoUrl;

                // Nettoyage de l'objet temporaire
                if (this.state.pendingVideoObjectUrl) {
                    try { URL.revokeObjectURL(this.state.pendingVideoObjectUrl); } catch(e) {}
                    this.state.pendingVideoObjectUrl = null;
                }
                this.state.pendingVideoFile = null;

            } catch (uploadErr) {
                setBusyState(false);
                if (progressEl) progressEl.classList.add('hidden');
                this.showToast(`Échec du téléversement sur Cloudflare R2 : ${uploadErr.message}. Aucun enregistrement créé.`, "error");
                return; // INTERRUPT : ne pas enregistrer en BDD si l'upload a échoué
            }
        }

        // 6. Enregistrement en base de données (strictement lié à la vidéo Cloudflare R2)
        setBusyState(true, "Enregistrement en base...");

        const data = {
            id,
            property_id: propertyId,
            title_fr: titleFr,
            title_en: titleEn,
            slug: slug,
            icon: document.getElementById('video-icon').value.trim() || '🎬',
            icon_bg_color: document.getElementById('video-bg-color').value.trim() || 'bg-blue-50',
            icon_text_color: document.getElementById('video-text-color').value.trim() || 'text-blue-500',
            video_url: videoUrl,
            display_order: parseInt(document.getElementById('video-order').value, 10) || 0,
            active: document.getElementById('video-active').checked ? 1 : 0
        };

        try {
            const res = await this.apiRequest('api/index.php?action=video_save', 'POST', { video: data });
            this.showToast(res.message || 'Tutoriel vidéo enregistré et connecté avec succès.');
            this.closeVideoModal();
            await this.fetchAndRenderVideos(data.property_id);
            await this.loadInitialData();
        } catch (err) {
            this.showToast(`Erreur d'enregistrement : ${err.message}`, 'error');
        } finally {
            setBusyState(false);
            if (progressEl) progressEl.classList.add('hidden');
        }
    },

    async moveVideo(id, direction) {
        const list = [...this.state.currentVideos];
        const idx = list.findIndex(v => v.id == id);
        if (idx < 0) return;

        const targetIdx = idx + direction;
        if (targetIdx < 0 || targetIdx >= list.length) return;

        const item = list.splice(idx, 1)[0];
        list.splice(targetIdx, 0, item);

        const orderArray = list.map(v => v.id);
        try {
            await this.apiRequest('api/index.php?action=video_reorder', 'POST', { order: orderArray });
            this.state.currentVideos = list;
            this.renderVideosList();
            this.showToast('Ordre des vidéos actualisé.');
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async toggleVideoStatus(id) {
        try {
            await this.apiRequest('api/index.php?action=video_toggle', 'POST', { id });
            const v = this.state.currentVideos.find(item => item.id == id);
            if (v) v.active = (v.active == 1 ? 0 : 1);
            this.renderVideosList();
            this.showToast('Statut de la vidéo actualisé.');
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    confirmDeleteVideo(id, title = null) {
        const video = (this.state.currentVideos || []).find(v => v.id == id);
        const resolvedTitle = title || (video ? video.title_fr : 'ce tutoriel vidéo');
        const hasR2 = video && video.video_url && this.isR2VideoUrl(video.video_url);
        window.__deleteR2Selected = hasR2 ? 1 : 0;

        const extraHtml = hasR2 ? `
            <label class="flex items-start space-x-2.5 cursor-pointer select-none">
                <input type="checkbox" id="confirm-delete-r2-checkbox" checked 
                       onchange="window.__deleteR2Selected = this.checked ? 1 : 0"
                       class="mt-0.5 rounded border-gray-600 text-red-500 focus:ring-red-400 bg-night-card">
                <div class="text-xs">
                    <span class="font-bold text-white">Supprimer également la vidéo sur Cloudflare R2</span>
                    <p class="text-[11px] text-gray-400 mt-0.5">Le fichier vidéo hébergé dans <span class="font-mono text-gold">${this.escape(video.video_url.split('/').slice(-2).join('/'))}</span> sera définitivement effacé du stockage Cloudflare.</p>
                </div>
            </label>
        ` : '';

        this.confirmAction(
            `Supprimer ${resolvedTitle} ?`,
            `Le tutoriel vidéo sera définitivement retiré du portail voyageur pour cet hébergement.`,
            async () => {
                const cb = document.getElementById('confirm-delete-r2-checkbox');
                const deleteR2 = (cb !== null) ? (cb.checked ? 1 : 0) : (window.__deleteR2Selected ?? (hasR2 ? 1 : 0));

                try {
                    const res = await this.apiRequest('api/index.php?action=video_delete', 'POST', {
                        id,
                        delete_r2: deleteR2
                    });
                    this.showToast(res.message || `Vidéo ${resolvedTitle} supprimée.`);
                    await this.fetchAndRenderVideos(this.state.currentPropertyId);
                    await this.loadInitialData();
                } catch (err) {
                    this.showToast(err.message, 'error');
                }
            },
            extraHtml
        );
    },

    previewVideo(urlOrId, title = null) {
        let url = urlOrId;
        let resolvedTitle = title;
        if (typeof urlOrId === 'number' || (typeof urlOrId === 'string' && /^\d+$/.test(urlOrId))) {
            const video = (this.state.currentVideos || []).find(v => v.id == urlOrId);
            if (video) {
                url = video.video_url;
                resolvedTitle = resolvedTitle || video.title_fr;
            }
        }

        const modal = document.getElementById('modal-video-preview');
        const container = document.getElementById('video-preview-player-container');
        const titleEl = document.getElementById('video-preview-title');
        if (!modal || !container || !url) return;

        titleEl.textContent = resolvedTitle || 'Aperçu vidéo';

        if (typeof url === 'string' && url.includes('drive.google.com')) {
            const embedUrl = url.replace(/\/view.*$/, '/preview');
            container.innerHTML = `<iframe src="${this.escape(embedUrl)}" class="w-full h-64 sm:h-80 rounded-xl border-0" allow="autoplay; fullscreen"></iframe>`;
        } else {
            container.innerHTML = `
                <video src="${this.escape(url)}" controls autoplay playsinline class="w-full max-h-[70vh] rounded-xl bg-black shadow-inner">
                    Votre navigateur ne prend pas en charge la lecture de cette vidéo.
                </video>
            `;
        }

        modal.classList.remove('hidden');
    },

    playVideoPreview(title, url) {
        this.previewVideo(url, title);
    },

    closeVideoPreview() {
        const modal = document.getElementById('modal-video-preview');
        const container = document.getElementById('video-preview-player-container');
        if (container) {
            const video = container.querySelector('video');
            if (video) {
                try { video.pause(); video.src = ''; } catch(e) {}
            }
            container.innerHTML = '';
        }
        if (modal) modal.classList.add('hidden');
    },

    // --------------------------------------------------------------------------
    // SECTION 4 : GESTION DU STATIONNEMENT (PARKING)
    // --------------------------------------------------------------------------
    async loadParkingView() {
        this.updatePropertySelector('parking-property-select', async (pid) => {
            this.state.currentPropertyId = pid;
            this.updateUrlHash();
            await this.fetchAndRenderParking(pid);
        });

        // Initialiser l'état du sous-onglet actif
        this.switchParkingSubtab(this.state.parkingSubtab || 'general', false);

        if (this.state.currentPropertyId) {
            this.updateUrlHash();
            await this.fetchAndRenderParking(this.state.currentPropertyId);
        }
    },

    async fetchAndRenderParking(propertyId) {
        const loadingState = document.getElementById('parking-loading-state');
        const contentWrapper = document.getElementById('parking-subtabs-wrapper');
        const propLabel = document.getElementById('parking-loading-property-label');

        const property = this.state.properties.find(p => p.id == propertyId);
        if (propLabel && property) {
            propLabel.textContent = `Actualisation des règles et emplacements pour « ${property.name} »...`;
        }

        // Afficher immédiatement l'état de chargement et masquer les données précédentes
        if (loadingState && contentWrapper) {
            loadingState.classList.remove('hidden');
            contentWrapper.classList.add('hidden');
        }

        try {
            const res = await this.apiRequest(`api/index.php?action=parking_get&property_id=${propertyId}`);
            this.state.currentParkingGeneral = res.general || null;
            this.state.currentParkingLocations = res.locations || [];

            this.fillParkingGeneralForm();
            this.renderParkingLocationsList();
            this.updateParkingLocationsBadge();
        } catch (err) {
            this.showToast(err.message, 'error');
        } finally {
            if (loadingState && contentWrapper) {
                loadingState.classList.add('hidden');
                contentWrapper.classList.remove('hidden');
            }
        }
    },

    switchParkingSubtab(subtab, updateHash = true) {
        this.state.parkingSubtab = subtab;
        if (updateHash && this.state.activeTab === 'parking') {
            this.updateUrlHash();
        }
        const btnGeneral = document.getElementById('parking-subtab-btn-general');
        const btnLocations = document.getElementById('parking-subtab-btn-locations');
        const tabGeneral = document.getElementById('parking-subtab-general');
        const tabLocations = document.getElementById('parking-subtab-locations');

        if (subtab === 'general') {
            if (tabGeneral) tabGeneral.classList.remove('hidden');
            if (tabLocations) tabLocations.classList.add('hidden');

            if (btnGeneral) {
                btnGeneral.className = 'parking-subtab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-gold text-white shadow-md shadow-gold/25';
            }
            if (btnLocations) {
                btnLocations.className = 'parking-subtab-btn px-4 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-white/5 transition flex items-center space-x-2';
            }
        } else {
            if (tabGeneral) tabGeneral.classList.add('hidden');
            if (tabLocations) tabLocations.classList.remove('hidden');

            if (btnGeneral) {
                btnGeneral.className = 'parking-subtab-btn px-4 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-white/5 transition flex items-center space-x-2';
            }
            if (btnLocations) {
                btnLocations.className = 'parking-subtab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-gold text-white shadow-md shadow-gold/25';
            }
        }
    },

    updateParkingLocationsBadge() {
        const badge = document.getElementById('parking-subtab-locations-badge');
        if (badge) {
            const count = (this.state.currentParkingLocations || []).length;
            badge.textContent = count;
            badge.className = count > 0
                ? 'px-2 py-0.5 rounded-full text-[10px] bg-white/20 text-white font-bold'
                : 'px-2 py-0.5 rounded-full text-[10px] bg-white/10 text-gray-400 font-bold';
        }
    },

    fillParkingGeneralForm() {
        const gen = this.state.currentParkingGeneral || {};
        document.getElementById('parking-intro-fr').value = gen.intro_fr || '';
        document.getElementById('parking-intro-en').value = gen.intro_en || '';
        document.getElementById('parking-rule-fr').value = gen.important_rule_fr || '';
        document.getElementById('parking-rule-en').value = gen.important_rule_en || '';
        document.getElementById('parking-free-notes-fr').value = gen.free_parking_notes_fr || '';
        document.getElementById('parking-free-notes-en').value = gen.free_parking_notes_en || '';
        document.getElementById('parking-street-info-fr').value = gen.street_parking_info_fr || '';
        document.getElementById('parking-street-info-en').value = gen.street_parking_info_en || '';
        document.getElementById('parking-street-sched-fr').value = gen.street_parking_schedule_fr || '';
        document.getElementById('parking-street-sched-en').value = gen.street_parking_schedule_en || '';
        document.getElementById('parking-street-rates-fr').value = gen.street_parking_rates_fr || '';
        document.getElementById('parking-street-rates-en').value = gen.street_parking_rates_en || '';
        document.getElementById('parking-street-app').value = gen.street_parking_app || 'EasyPark';
        document.getElementById('parking-tcl-cond-fr').value = gen.tcl_conditions_fr || '';
        document.getElementById('parking-tcl-cond-en').value = gen.tcl_conditions_en || '';
    },

    getTranslationFieldDefinitions(type) {
        if (type === 'parking_general') {
            return [
                {
                    key: 'intro',
                    label: 'Message d\'accueil & Introduction',
                    frId: 'parking-intro-fr',
                    enId: 'parking-intro-en',
                    isTextarea: true,
                    rows: 2
                },
                {
                    key: 'important_rule',
                    label: 'Alerte impérative / Règle d\'or',
                    frId: 'parking-rule-fr',
                    enId: 'parking-rule-en',
                    isTextarea: true,
                    rows: 2
                },
                {
                    key: 'free_parking_notes',
                    label: 'Places de voirie gratuites & Dépose-minute',
                    frId: 'parking-free-notes-fr',
                    enId: 'parking-free-notes-en',
                    isTextarea: false
                },
                {
                    key: 'street_parking_schedule',
                    label: 'Horaires de la voirie payante',
                    frId: 'parking-street-sched-fr',
                    enId: 'parking-street-sched-en',
                    isTextarea: false
                },
                {
                    key: 'street_parking_info',
                    label: 'Périmètre voirie payante & Horodateurs',
                    frId: 'parking-street-info-fr',
                    enId: 'parking-street-info-en',
                    isTextarea: true,
                    rows: 2
                },
                {
                    key: 'street_parking_rates',
                    label: 'Grille tarifaire de la voirie payante',
                    frId: 'parking-street-rates-fr',
                    enId: 'parking-street-rates-en',
                    isTextarea: true,
                    rows: 3
                },
                {
                    key: 'tcl_conditions',
                    label: 'Conditions de gratuité Parc Relais TCL (P+R)',
                    frId: 'parking-tcl-cond-fr',
                    enId: 'parking-tcl-cond-en',
                    isTextarea: true,
                    rows: 2
                }
            ];
        } else if (type === 'parking_location' || type === 'parking_item') {
            const cat = (document.getElementById('location-category') ? document.getElementById('location-category').value : 'free_street');
            
            if (cat === 'private_parking') {
                return [
                    { key: 'name', label: 'Nom du parking privé', frId: 'location-name-fr-priv', enId: 'location-name-en', isTextarea: false },
                    { key: 'walking_time', label: 'Temps de marche', frId: 'location-walking-fr-priv', enId: 'location-walking-en', isTextarea: false },
                    { key: 'opening_hours', label: 'Horaires d\'accès', frId: 'location-hours-fr-priv', enId: 'location-hours-en', isTextarea: false },
                    { key: 'rates', label: 'Grille tarifaire', frId: 'location-rates-fr-priv', enId: 'location-rates-en', isTextarea: true, rows: 2 },
                    { key: 'suitable_vehicles', label: 'Véhicules adaptés', frId: 'location-vehicles-fr', enId: 'location-vehicles-en', isTextarea: false },
                    { key: 'notes', label: 'Remarques pratiques', frId: 'location-notes-fr-priv', enId: 'location-notes-en', isTextarea: false }
                ];
            } else if (cat === 'tcl_pr') {
                return [
                    { key: 'name', label: 'Nom du Parc Relais TCL', frId: 'location-name-fr-tcl', enId: 'location-name-en', isTextarea: false },
                    { key: 'transit_time', label: 'Temps en transports TCL', frId: 'location-transit-fr', enId: 'location-transit-en', isTextarea: false },
                    { key: 'opening_hours', label: 'Horaires d\'accès', frId: 'location-hours-fr-tcl', enId: 'location-hours-en', isTextarea: false },
                    { key: 'how_to_reach', label: 'Itinéraire retour vers le logement', frId: 'location-reach-fr', enId: 'location-reach-en', isTextarea: true, rows: 2 },
                    { key: 'notes', label: 'Notes complémentaires', frId: 'location-notes-fr-tcl', enId: 'location-notes-en', isTextarea: false }
                ];
            } else {
                // Rues gratuites & Dépose minute
                return [
                    { key: 'name', label: 'Nom du lieu ou de la rue', frId: 'location-name-fr-free', enId: 'location-name-en', isTextarea: false },
                    { key: 'walking_time', label: 'Temps d\'accès à pied', frId: 'location-walking-fr-free', enId: 'location-walking-en', isTextarea: false },
                    { key: 'notes', label: 'Remarques & conseils', frId: 'location-notes-fr-free', enId: 'location-notes-en', isTextarea: false }
                ];
            }
        }
        return [];
    },

    openTranslationReviewModal(type, overrideEnData = null) {
        const defs = this.getTranslationFieldDefinitions(type);
        const container = document.getElementById('review-modal-fields-container');
        const modal = document.getElementById('modal-translation-review');
        if (!container || !modal) return;

        this.state.translationReview = { type, fields: [] };

        // Ajuster titre et sous-titre selon le mode (Flash Check IA ou Saisie Manuelle Côte-à-Côte)
        const titleEl = document.getElementById('modal-translation-review-title');
        const subtitleEl = document.getElementById('modal-translation-review-subtitle');
        if (titleEl) {
            titleEl.textContent = overrideEnData ? "Vérification de l'anglais" : "Saisie de l'anglais (Vue côte-à-côte)";
        }
        if (subtitleEl) {
            subtitleEl.textContent = overrideEnData 
                ? "Généré par l'IA — Un coup d'œil rapide avant enregistrement" 
                : "Ajustez les textes en anglais face à leur version française avant d'enregistrer";
        }

        const activeFields = [];
        defs.forEach(def => {
            const frInput = document.getElementById(def.frId);
            const enInput = document.getElementById(def.enId);
            const frVal = frInput ? frInput.value.trim() : '';

            let enVal = '';
            if (overrideEnData && overrideEnData[`${def.key}_en`] !== undefined) {
                enVal = overrideEnData[`${def.key}_en`];
            } else if (enInput) {
                enVal = enInput.value.trim();
            }

            if (frVal || enVal) {
                activeFields.push({
                    ...def,
                    frVal,
                    enVal
                });
            }
        });

        // Si aucun champ n'est encore rempli
        if (activeFields.length === 0) {
            if (overrideEnData !== null) {
                // Flux automatique lors de l'enregistrement sans texte
                if (type === 'parking_general') {
                    this.saveParkingGeneral(null, true);
                } else if (type === 'parking_location') {
                    this.saveLocation(null, true);
                }
                return;
            } else {
                // Saisie manuelle : inclure tous les champs pour que l'utilisateur puisse saisir
                defs.forEach(def => {
                    const frInput = document.getElementById(def.frId);
                    const enInput = document.getElementById(def.enId);
                    activeFields.push({
                        ...def,
                        frVal: frInput ? frInput.value.trim() : '',
                        enVal: enInput ? enInput.value.trim() : ''
                    });
                });
            }
        }

        this.state.translationReview.fields = activeFields;

        container.innerHTML = activeFields.map(f => `
            <div class="p-3.5 rounded-2xl bg-night/80 border border-night-border/80 hover:border-gold/30 transition space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-200 tracking-wide">${this.escape(f.label)}</span>
                    <span class="text-[10px] text-gray-500 font-medium">Source FR</span>
                </div>
                <p class="text-xs text-gray-400 leading-relaxed italic bg-night-card/60 p-2.5 rounded-xl border border-night-border/40 select-all">
                    ${f.frVal ? this.escape(f.frVal) : '<span class="text-gray-500">Non renseigné</span>'}
                </p>
                <div class="pt-0.5">
                    <span class="block text-[11px] font-semibold text-gold mb-1">Anglais (cliquez pour ajuster si besoin) :</span>
                    ${f.isTextarea ? `
                        <textarea data-review-en-id="${f.enId}" rows="${f.rows || 2}"
                                  class="w-full p-2.5 rounded-xl bg-night-card border border-gold/30 focus:border-gold focus:ring-1 focus:ring-gold text-xs text-white leading-relaxed font-sans transition"
                                  placeholder="Traduction anglaise...">${this.escape(f.enVal)}</textarea>
                    ` : `
                        <input type="text" data-review-en-id="${f.enId}" value="${this.escape(f.enVal)}"
                               class="w-full p-2.5 rounded-xl bg-night-card border border-gold/30 focus:border-gold focus:ring-1 focus:ring-gold text-xs text-white font-sans transition"
                               placeholder="Traduction anglaise...">
                    `}
                </div>
            </div>
        `).join('');

        modal.classList.remove('hidden');

        // Focus immédiat sur le bouton de confirmation (validation en 1 frappe Enter)
        setTimeout(() => {
            const confirmBtn = document.getElementById('btn-review-save-direct');
            if (confirmBtn) confirmBtn.focus();
        }, 60);

        // Raccourcis clavier : Entrée = valider, Echap = fermer
        if (this._reviewKeyHandler) {
            document.removeEventListener('keydown', this._reviewKeyHandler);
        }
        this._reviewKeyHandler = (e) => {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                this.confirmAndSaveReview();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.closeTranslationReviewModal();
            }
        };
        document.addEventListener('keydown', this._reviewKeyHandler);
    },

    closeTranslationReviewModal() {
        const modal = document.getElementById('modal-translation-review');
        if (modal) modal.classList.add('hidden');
        if (this._reviewKeyHandler) {
            document.removeEventListener('keydown', this._reviewKeyHandler);
            this._reviewKeyHandler = null;
        }
    },

    openDirectReviewModal(type) {
        this.openTranslationReviewModal(type, null);
    },

    async confirmAndSaveReview() {
        const reviewState = this.state.translationReview;
        if (!reviewState || !reviewState.type) return;

        // 1. Reporter toutes les valeurs vérifiées dans les champs anglais du formulaire
        document.querySelectorAll('[data-review-en-id]').forEach(input => {
            const targetId = input.dataset.reviewEnId;
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                targetEl.value = input.value.trim();
            }
        });

        const btnSave = document.getElementById('btn-review-save-direct');
        const iconEl = document.getElementById('btn-review-save-icon');
        const labelEl = document.getElementById('btn-review-save-label');
        const origLabel = labelEl ? labelEl.textContent : 'Confirmer & Enregistrer';

        try {
            if (btnSave) btnSave.disabled = true;
            if (iconEl) iconEl.innerHTML = '<span class="inline-block animate-spin">⏳</span>';
            if (labelEl) labelEl.textContent = 'Enregistrement...';

            if (reviewState.type === 'parking_general') {
                await this.saveParkingGeneral(null, true);
            } else if (reviewState.type === 'parking_location') {
                await this.saveLocation(null, true);
            }

            this.closeTranslationReviewModal();
        } catch (err) {
            this.showToast('Erreur lors de l\'enregistrement : ' + err.message, 'error');
        } finally {
            if (btnSave) btnSave.disabled = false;
            if (iconEl) iconEl.textContent = '✅';
            if (labelEl) labelEl.textContent = origLabel;
        }
    },

    async applyReviewTranslations(saveDirectly = false) {
        if (saveDirectly) {
            await this.confirmAndSaveReview();
        } else {
            document.querySelectorAll('[data-review-en-id]').forEach(input => {
                const targetId = input.dataset.reviewEnId;
                const targetEl = document.getElementById(targetId);
                if (targetEl) targetEl.value = input.value.trim();
            });
            this.closeTranslationReviewModal();
        }
    },

    async previewTranslateGeneral() {
        const data = {
            intro_fr: document.getElementById('parking-intro-fr').value.trim(),
            important_rule_fr: document.getElementById('parking-rule-fr').value.trim(),
            free_parking_notes_fr: document.getElementById('parking-free-notes-fr').value.trim(),
            street_parking_info_fr: document.getElementById('parking-street-info-fr').value.trim(),
            street_parking_schedule_fr: document.getElementById('parking-street-sched-fr').value.trim(),
            street_parking_rates_fr: document.getElementById('parking-street-rates-fr').value.trim(),
            tcl_conditions_fr: document.getElementById('parking-tcl-cond-fr').value.trim()
        };

        const hasContent = Object.values(data).some(v => v.length > 0);
        if (!hasContent) {
            this.showToast('Veuillez renseigner au moins un champ en français avant de traduire.', 'warning');
            return;
        }

        const btn = document.getElementById('btn-preview-translate-general');
        const btnIcon = document.getElementById('btn-preview-general-icon');
        const btnText = document.getElementById('btn-preview-general-text');
        const origIcon = btnIcon ? btnIcon.textContent : '✨';
        const origText = btnText ? btnText.textContent : 'Traduire & Réviser côte-à-côte';

        try {
            if (btn) btn.disabled = true;
            if (btnIcon) btnIcon.innerHTML = '<span class="inline-block animate-spin">⏳</span>';
            if (btnText) btnText.textContent = 'Traduction IA en cours...';

            const res = await this.apiRequest('api/index.php?action=translate_preview', 'POST', {
                type: 'parking_general',
                data: data
            });

            if (res.data) {
                if (res.data.intro_en !== undefined) document.getElementById('parking-intro-en').value = res.data.intro_en;
                if (res.data.important_rule_en !== undefined) document.getElementById('parking-rule-en').value = res.data.important_rule_en;
                if (res.data.free_parking_notes_en !== undefined) document.getElementById('parking-free-notes-en').value = res.data.free_parking_notes_en;
                if (res.data.street_parking_info_en !== undefined) document.getElementById('parking-street-info-en').value = res.data.street_parking_info_en;
                if (res.data.street_parking_schedule_en !== undefined) document.getElementById('parking-street-sched-en').value = res.data.street_parking_schedule_en;
                if (res.data.street_parking_rates_en !== undefined) document.getElementById('parking-street-rates-en').value = res.data.street_parking_rates_en;
                if (res.data.tcl_conditions_en !== undefined) document.getElementById('parking-tcl-cond-en').value = res.data.tcl_conditions_en;
            }

            // Ouvrir la modale de revue bilingue côte à côte
            this.openTranslationReviewModal('parking_general', res.data);
            this.showToast('Traduction générée ! Vérifiez le texte côte à côte avant enregistrement.', 'success');
        } catch (err) {
            this.showToast('Erreur de traduction : ' + err.message, 'error');
        } finally {
            if (btn) btn.disabled = false;
            if (btnIcon) btnIcon.textContent = origIcon;
            if (btnText) btnText.textContent = origText;
        }
    },

    async saveParkingGeneral(e, skipReview = false) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        const propertyId = this.state.currentPropertyId;

        // Étape 1 : Si pas encore vérifié, on traduit via l'IA et on ouvre le Flash Check (3 secondes)
        if (!skipReview) {
            const dataFr = {
                intro_fr: document.getElementById('parking-intro-fr').value.trim(),
                important_rule_fr: document.getElementById('parking-rule-fr').value.trim(),
                free_parking_notes_fr: document.getElementById('parking-free-notes-fr').value.trim(),
                street_parking_info_fr: document.getElementById('parking-street-info-fr').value.trim(),
                street_parking_schedule_fr: document.getElementById('parking-street-sched-fr').value.trim(),
                street_parking_rates_fr: document.getElementById('parking-street-rates-fr').value.trim(),
                tcl_conditions_fr: document.getElementById('parking-tcl-cond-fr').value.trim()
            };

            const hasFrenchContent = Object.values(dataFr).some(v => v.length > 0);
            if (hasFrenchContent) {
                const btnSave = document.getElementById('btn-save-parking-general');
                const iconEl = document.getElementById('btn-save-general-icon');
                const textEl = document.getElementById('btn-save-general-text');
                const origText = textEl ? textEl.textContent : 'Enregistrer les règles';

                try {
                    if (btnSave) btnSave.disabled = true;
                    if (iconEl) iconEl.innerHTML = '<span class="inline-block animate-spin">⏳</span>';
                    if (textEl) textEl.textContent = 'Traduction IA en cours...';

                    const res = await this.apiRequest('api/index.php?action=translate_preview', 'POST', {
                        type: 'parking_general',
                        data: dataFr
                    });

                    if (res.data) {
                        if (res.data.intro_en !== undefined) document.getElementById('parking-intro-en').value = res.data.intro_en;
                        if (res.data.important_rule_en !== undefined) document.getElementById('parking-rule-en').value = res.data.important_rule_en;
                        if (res.data.free_parking_notes_en !== undefined) document.getElementById('parking-free-notes-en').value = res.data.free_parking_notes_en;
                        if (res.data.street_parking_info_en !== undefined) document.getElementById('parking-street-info-en').value = res.data.street_parking_info_en;
                        if (res.data.street_parking_schedule_en !== undefined) document.getElementById('parking-street-sched-en').value = res.data.street_parking_schedule_en;
                        if (res.data.street_parking_rates_en !== undefined) document.getElementById('parking-street-rates-en').value = res.data.street_parking_rates_en;
                        if (res.data.tcl_conditions_en !== undefined) document.getElementById('parking-tcl-cond-en').value = res.data.tcl_conditions_en;
                    }

                    // Ouvre la vérification flash
                    this.openTranslationReviewModal('parking_general', res.data);
                    return;
                } catch (err) {
                    console.warn('[AI] Échec de la traduction préalable :', err);
                } finally {
                    if (btnSave) btnSave.disabled = false;
                    if (iconEl) iconEl.textContent = '💾';
                    if (textEl) textEl.textContent = origText;
                }
            }
        }

        // Étape 2 : Sauvegarde directe en base de données
        const data = {
            intro_fr: document.getElementById('parking-intro-fr').value.trim(),
            intro_en: document.getElementById('parking-intro-en').value.trim(),
            important_rule_fr: document.getElementById('parking-rule-fr').value.trim(),
            important_rule_en: document.getElementById('parking-rule-en').value.trim(),
            free_parking_notes_fr: document.getElementById('parking-free-notes-fr').value.trim(),
            free_parking_notes_en: document.getElementById('parking-free-notes-en').value.trim(),
            street_parking_info_fr: document.getElementById('parking-street-info-fr').value.trim(),
            street_parking_info_en: document.getElementById('parking-street-info-en').value.trim(),
            street_parking_schedule_fr: document.getElementById('parking-street-sched-fr').value.trim(),
            street_parking_schedule_en: document.getElementById('parking-street-sched-en').value.trim(),
            street_parking_rates_fr: document.getElementById('parking-street-rates-fr').value.trim(),
            street_parking_rates_en: document.getElementById('parking-street-rates-en').value.trim(),
            street_parking_app: document.getElementById('parking-street-app').value.trim() || 'EasyPark',
            tcl_conditions_fr: document.getElementById('parking-tcl-cond-fr').value.trim(),
            tcl_conditions_en: document.getElementById('parking-tcl-cond-en').value.trim()
        };

        try {
            const res = await this.apiRequest('api/index.php?action=parking_general_save', 'POST', {
                property_id: propertyId,
                general: data
            });
            this.showToast(res.message || 'Règles générales enregistrées avec succès !', 'success');
            await this.loadInitialData();
            await this.fetchAndRenderParking(propertyId);
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    onLocationCategoryChange(cat) {
        const catInput = document.getElementById('location-category');
        if (catInput) catInput.value = cat;

        // Synchroniser les radios
        const radios = document.getElementsByName('location-category-radio');
        radios.forEach(r => {
            r.checked = (r.value === cat || (r.value === 'free_street' && (cat === 'free_spot' || cat === 'free_street')));
        });

        const catFree = document.getElementById('cat-fields-free');
        const catPriv = document.getElementById('cat-fields-private');
        const catTcl = document.getElementById('cat-fields-tcl');

        if (cat === 'private_parking') {
            if (catFree) catFree.classList.add('hidden');
            if (catPriv) catPriv.classList.remove('hidden');
            if (catTcl) catTcl.classList.add('hidden');
        } else if (cat === 'tcl_pr') {
            if (catFree) catFree.classList.add('hidden');
            if (catPriv) catPriv.classList.add('hidden');
            if (catTcl) catTcl.classList.remove('hidden');
        } else {
            // free_street ou free_spot
            if (catFree) catFree.classList.remove('hidden');
            if (catPriv) catPriv.classList.add('hidden');
            if (catTcl) catTcl.classList.add('hidden');
            const subtypeEl = document.getElementById('location-free-subtype');
            if (subtypeEl) {
                subtypeEl.value = (cat === 'free_spot' ? 'free_spot' : 'free_street');
            }
        }
    },

    onFreeSubtypeChange(subtype) {
        const catInput = document.getElementById('location-category');
        if (catInput) catInput.value = subtype;
    },

    renderParkingLocationsList() {
        const container = document.getElementById('parking-locations-list');
        const filterCat = document.getElementById('parking-locations-filter-cat');
        if (!container) return;

        const cat = filterCat ? filterCat.value : 'all';
        const allLocs = this.state.currentParkingLocations || [];

        const catLabels = {
            free_street: { label: 'Rue gratuite', color: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' },
            free_spot: { label: 'Dépose-minute gratuit', color: 'bg-teal-500/10 text-teal-400 border-teal-500/30' },
            private_parking: { label: 'Parking privé / couvert', color: 'bg-amber-500/10 text-amber-400 border-amber-500/30' },
            tcl_pr: { label: 'Parc Relais TCL (P+R)', color: 'bg-red-500/10 text-red-400 border-red-500/30' }
        };

        const renderItemCard = (loc, listForCat, idx) => {
            const badge = catLabels[loc.category] || { label: loc.category, color: 'bg-gray-500/10 text-gray-400 border-gray-500/30' };
            const hasAddress = (loc.address && loc.address.trim().length > 0);
            return `
                <div class="p-4 sm:p-5 rounded-2xl bg-night-card border border-night-border hover:border-gold/30 transition shadow-lg group">
                    <!-- EN-TÊTE / NOM DE L'EMPLACEMENT & STATUT -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start space-x-3 sm:space-x-4 min-w-0 flex-1">
                            <!-- Poignée d'ordre ▲ / ▼ -->
                            <div class="flex flex-col space-y-1 text-gray-500 flex-shrink-0 pt-0.5">
                                <button type="button" onclick="AdminApp.moveLocation(${loc.id}, -1)" ${idx === 0 ? 'disabled class="opacity-20 cursor-not-allowed"' : 'class="p-1 rounded hover:bg-white/5 hover:text-gold transition"'} title="Monter">▲</button>
                                <button type="button" onclick="AdminApp.moveLocation(${loc.id}, 1)" ${idx === listForCat.length - 1 ? 'disabled class="opacity-20 cursor-not-allowed"' : 'class="p-1 rounded hover:bg-white/5 hover:text-gold transition"'} title="Descendre">▼</button>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center flex-wrap gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border ${badge.color}">${badge.label}</span>
                                    ${loc.operator ? `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gold/15 text-gold border border-gold/30">${this.escape(loc.operator)}</span>` : ''}
                                    <h5 class="text-sm sm:text-base font-bold text-white group-hover:text-gold transition break-words">
                                        ${this.escape(loc.name_fr)}
                                    </h5>
                                </div>
                                <p class="text-xs text-gray-400 flex items-center gap-1.5 break-words">
                                    <span>🇬🇧</span>
                                    <span class="italic">${this.escape(loc.name_en || loc.name_fr)}</span>
                                </p>
                                
                                <!-- Métriques adaptées à la catégorie -->
                                <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                                    ${loc.walking_time_fr ? `<span class="px-2.5 py-1 rounded-xl bg-night border border-night-border/80 text-gray-300">🚶 ${this.escape(loc.walking_time_fr)}</span>` : ''}
                                    ${loc.transit_time_fr ? `<span class="px-2.5 py-1 rounded-xl bg-night border border-night-border/80 text-gray-300">🚇 ${this.escape(loc.transit_time_fr)}</span>` : ''}
                                    ${loc.max_height ? `<span class="px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-semibold">↕️ Max ${this.escape(loc.max_height)}</span>` : ''}
                                    ${loc.capacity ? `<span class="px-2.5 py-1 rounded-xl bg-night border border-night-border/80 text-gray-300">🚗 ${this.escape(loc.capacity)}</span>` : ''}
                                    ${loc.opening_hours_fr ? `<span class="px-2.5 py-1 rounded-xl bg-night border border-night-border/80 text-gray-400">🕒 ${this.escape(loc.opening_hours_fr)}</span>` : ''}
                                    ${loc.rates_fr ? `<span class="px-2.5 py-1 rounded-xl bg-gold/10 border border-gold/30 text-gold font-semibold">💶 ${this.escape(loc.rates_fr)}</span>` : ''}
                                    ${loc.address ? `<span class="px-2.5 py-1 rounded-xl bg-night border border-night-border/80 text-gray-400 truncate max-w-xs" title="${this.escape(loc.address)}">📍 ${this.escape(loc.address)}</span>` : ''}
                                </div>

                                ${loc.notes_fr ? `<p class="mt-2 text-xs text-gray-400 italic">💡 ${this.escape(loc.notes_fr)}</p>` : ''}
                                ${loc.how_to_reach_fr ? `<p class="mt-1 text-xs text-blue-300/90 bg-blue-950/40 p-2 rounded-xl border border-blue-900/40">🚇 <strong>Accès retour :</strong> ${this.escape(loc.how_to_reach_fr)}</p>` : ''}
                            </div>
                        </div>

                        <!-- Statut Actif / Masqué -->
                        <div class="flex-shrink-0">
                            <button type="button" onclick="AdminApp.toggleLocationStatus(${loc.id})" title="Cliquer pour basculer la visibilité"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold transition cursor-pointer ${loc.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30 hover:bg-gray-500/20'}">
                                ${loc.active == 1 ? '● Actif' : '○ Masqué'}
                            </button>
                        </div>
                    </div>

                    <!-- BARRE D'ACTIONS -->
                    <div class="pt-3 mt-3.5 border-t border-night-border/50 grid grid-cols-12 gap-2">
                        ${hasAddress ? `
                            <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.address)}" target="_blank" rel="noopener noreferrer"
                               class="${loc.booking_url ? 'col-span-3' : 'col-span-6'} py-2 px-2.5 rounded-xl bg-night border border-night-border hover:border-gold hover:text-gold text-xs font-semibold text-gray-300 transition flex items-center justify-center space-x-1.5 shadow-sm">
                                <span>🗺️</span>
                                <span>Maps</span>
                            </a>
                        ` : `
                            <div class="${loc.booking_url ? 'col-span-3' : 'col-span-6'} py-2 px-2 rounded-xl bg-night/50 border border-night-border/40 text-[11px] text-gray-500 italic flex items-center justify-center">
                                <span>Sans adresse</span>
                            </div>
                        `}

                        ${loc.booking_url ? `
                            <a href="${loc.booking_url}" target="_blank" rel="noopener noreferrer"
                               class="col-span-3 py-2 px-2 rounded-xl bg-night border border-night-border hover:border-gold hover:text-gold text-xs font-semibold text-gray-300 transition flex items-center justify-center space-x-1 shadow-sm"
                               title="Lien réservation ou fiche officielle">
                                <span>🔗</span>
                                <span>Lien</span>
                            </a>
                        ` : ''}

                        <button type="button" onclick="AdminApp.openLocationModal(${loc.id})" 
                                class="${loc.booking_url ? 'col-span-4' : 'col-span-4'} py-2 px-2 rounded-xl bg-night border border-night-border hover:border-gold hover:text-gold text-xs font-semibold text-gray-300 transition flex items-center justify-center space-x-1 shadow-sm" title="Modifier l'emplacement">
                            <span>✏️</span>
                            <span>Modifier</span>
                        </button>
                        <button type="button" onclick="AdminApp.confirmDeleteLocation(${loc.id})" 
                                class="col-span-2 py-2 rounded-xl bg-red-500/10 hover:bg-red-500 hover:text-white border border-red-500/20 text-xs font-semibold text-red-400 transition flex items-center justify-center shadow-sm" title="Supprimer cet emplacement">
                            <span>🗑️</span>
                        </button>
                    </div>
                </div>
            `;
        };

        if (allLocs.length === 0) {
            container.innerHTML = `
                <div class="py-12 text-center text-gray-400 border border-dashed border-night-border rounded-2xl p-6">
                    <span class="text-3xl block mb-2">🅿️</span>
                    <p class="text-base font-bold text-white">Aucun emplacement répertorié pour cet hébergement</p>
                    <p class="text-xs text-gray-500 mt-1 mb-4">Ajoutez des rues gratuites, des garages privés ou des P+R TCL.</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <button type="button" onclick="AdminApp.openLocationModal(0, 'free_street')" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-lg hover:bg-emerald-500 transition">
                            + Rue / Dépose
                        </button>
                        <button type="button" onclick="AdminApp.openLocationModal(0, 'private_parking')" class="px-4 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs shadow-lg hover:bg-purple-500 transition">
                            + Parking privé
                        </button>
                        <button type="button" onclick="AdminApp.openLocationModal(0, 'tcl_pr')" class="px-4 py-2 rounded-xl bg-red-600 text-white font-bold text-xs shadow-lg hover:bg-red-500 transition">
                            + P+R TCL
                        </button>
                    </div>
                </div>
            `;
            return;
        }

        const freeList = allLocs.filter(l => l.category === 'free_street' || l.category === 'free_spot');
        const privateList = allLocs.filter(l => l.category === 'private_parking');
        const tclList = allLocs.filter(l => l.category === 'tcl_pr');

        let html = '';

        // Section 1 : Rues et Dépose
        if (cat === 'all' || cat === 'free_street') {
            html += `
                <div class="space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-night-border">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">🟢</span>
                            <h4 class="text-sm font-bold text-white">Rues gratuites & Dépose-minute</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">${freeList.length}</span>
                        </div>
                        <button type="button" onclick="AdminApp.openLocationModal(0, 'free_street')" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold transition">
                            + Ajouter une rue
                        </button>
                    </div>
                    ${freeList.length > 0 
                        ? `<div class="space-y-3">${freeList.map((loc, idx) => renderItemCard(loc, freeList, idx)).join('')}</div>`
                        : `<p class="text-xs text-gray-500 italic py-3 text-center bg-night/50 rounded-xl border border-night-border/40">Aucune rue gratuite configurée.</p>`
                    }
                </div>
            `;
        }

        // Section 2 : Parkings privés & couverts
        if (cat === 'all' || cat === 'private_parking') {
            html += `
                <div class="space-y-3 ${cat === 'all' ? 'pt-4' : ''}">
                    <div class="flex items-center justify-between pb-2 border-b border-night-border">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">🏢</span>
                            <h4 class="text-sm font-bold text-white">Parkings privés & couverts</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/30">${privateList.length}</span>
                        </div>
                        <button type="button" onclick="AdminApp.openLocationModal(0, 'private_parking')" class="text-xs text-purple-400 hover:text-purple-300 font-semibold transition">
                            + Ajouter un parking privé
                        </button>
                    </div>
                    ${privateList.length > 0 
                        ? `<div class="space-y-3">${privateList.map((loc, idx) => renderItemCard(loc, privateList, idx)).join('')}</div>`
                        : `<p class="text-xs text-gray-500 italic py-3 text-center bg-night/50 rounded-xl border border-night-border/40">Aucun parking privé configuré.</p>`
                    }
                </div>
            `;
        }

        // Section 3 : Parcs Relais TCL
        if (cat === 'all' || cat === 'tcl_pr') {
            html += `
                <div class="space-y-3 ${cat === 'all' ? 'pt-4' : ''}">
                    <div class="flex items-center justify-between pb-2 border-b border-night-border">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">🚇</span>
                            <h4 class="text-sm font-bold text-white">Parcs Relais TCL (P+R)</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/30">${tclList.length}</span>
                        </div>
                        <button type="button" onclick="AdminApp.openLocationModal(0, 'tcl_pr')" class="text-xs text-red-400 hover:text-red-300 font-semibold transition">
                            + Ajouter un P+R TCL
                        </button>
                    </div>
                    ${tclList.length > 0 
                        ? `<div class="space-y-3">${tclList.map((loc, idx) => renderItemCard(loc, tclList, idx)).join('')}</div>`
                        : `<p class="text-xs text-gray-500 italic py-3 text-center bg-night/50 rounded-xl border border-night-border/40">Aucun Parc Relais TCL configuré.</p>`
                    }
                </div>
            `;
        }

        container.innerHTML = html;
    },

    openLocationModal(id = 0, defaultCategory = 'free_street') {
        const modal = document.getElementById('modal-location-form');
        const titleEl = document.getElementById('modal-location-title');
        const form = document.getElementById('location-form');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('location-id').value = id;
        document.getElementById('location-property-id').value = this.state.currentPropertyId;

        // Réinitialiser les champs anglais masqués
        ['location-name-en', 'location-walking-en', 'location-transit-en', 'location-hours-en', 'location-rates-en', 'location-vehicles-en', 'location-reach-en', 'location-notes-en'].forEach(fid => {
            const el = document.getElementById(fid);
            if (el) el.value = '';
        });

        if (id > 0) {
            const loc = (this.state.currentParkingLocations || []).find(l => l.id == id);
            if (!loc) return;
            const cat = loc.category || 'free_street';
            titleEl.textContent = `Modifier : ${loc.name_fr}`;
            this.onLocationCategoryChange(cat);

            if (cat === 'private_parking') {
                if (document.getElementById('location-name-fr-priv')) document.getElementById('location-name-fr-priv').value = loc.name_fr || '';
                if (document.getElementById('location-operator')) document.getElementById('location-operator').value = loc.operator || '';
                if (document.getElementById('location-address-priv')) document.getElementById('location-address-priv').value = loc.address || '';
                if (document.getElementById('location-walking-fr-priv')) document.getElementById('location-walking-fr-priv').value = loc.walking_time_fr || '';
                if (document.getElementById('location-max-height')) document.getElementById('location-max-height').value = loc.max_height || '';
                if (document.getElementById('location-hours-fr-priv')) document.getElementById('location-hours-fr-priv').value = loc.opening_hours_fr || '';
                if (document.getElementById('location-vehicles-fr')) document.getElementById('location-vehicles-fr').value = loc.suitable_vehicles_fr || '';
                if (document.getElementById('location-rates-fr-priv')) document.getElementById('location-rates-fr-priv').value = loc.rates_fr || '';
                if (document.getElementById('location-booking-url-priv')) document.getElementById('location-booking-url-priv').value = loc.booking_url || '';
                if (document.getElementById('location-notes-fr-priv')) document.getElementById('location-notes-fr-priv').value = loc.notes_fr || '';
            } else if (cat === 'tcl_pr') {
                if (document.getElementById('location-name-fr-tcl')) document.getElementById('location-name-fr-tcl').value = loc.name_fr || '';
                if (document.getElementById('location-capacity')) document.getElementById('location-capacity').value = loc.capacity || '';
                if (document.getElementById('location-address-tcl')) document.getElementById('location-address-tcl').value = loc.address || '';
                if (document.getElementById('location-transit-fr')) document.getElementById('location-transit-fr').value = loc.transit_time_fr || '';
                if (document.getElementById('location-hours-fr-tcl')) document.getElementById('location-hours-fr-tcl').value = loc.opening_hours_fr || '';
                if (document.getElementById('location-reach-fr')) document.getElementById('location-reach-fr').value = loc.how_to_reach_fr || '';
                if (document.getElementById('location-booking-url-tcl')) document.getElementById('location-booking-url-tcl').value = loc.booking_url || '';
                if (document.getElementById('location-notes-fr-tcl')) document.getElementById('location-notes-fr-tcl').value = loc.notes_fr || '';
            } else {
                if (document.getElementById('location-free-subtype')) document.getElementById('location-free-subtype').value = cat === 'free_spot' ? 'free_spot' : 'free_street';
                if (document.getElementById('location-name-fr-free')) document.getElementById('location-name-fr-free').value = loc.name_fr || '';
                if (document.getElementById('location-address-free')) document.getElementById('location-address-free').value = loc.address || '';
                if (document.getElementById('location-walking-fr-free')) document.getElementById('location-walking-fr-free').value = loc.walking_time_fr || '';
                if (document.getElementById('location-notes-fr-free')) document.getElementById('location-notes-fr-free').value = loc.notes_fr || '';
            }

            // Report des champs anglais
            if (document.getElementById('location-name-en')) document.getElementById('location-name-en').value = loc.name_en || '';
            if (document.getElementById('location-walking-en')) document.getElementById('location-walking-en').value = loc.walking_time_en || '';
            if (document.getElementById('location-transit-en')) document.getElementById('location-transit-en').value = loc.transit_time_en || '';
            if (document.getElementById('location-hours-en')) document.getElementById('location-hours-en').value = loc.opening_hours_en || '';
            if (document.getElementById('location-rates-en')) document.getElementById('location-rates-en').value = loc.rates_en || '';
            if (document.getElementById('location-vehicles-en')) document.getElementById('location-vehicles-en').value = loc.suitable_vehicles_en || '';
            if (document.getElementById('location-reach-en')) document.getElementById('location-reach-en').value = loc.how_to_reach_en || '';
            if (document.getElementById('location-notes-en')) document.getElementById('location-notes-en').value = loc.notes_en || '';

            document.getElementById('location-order').value = loc.display_order || 1;
            document.getElementById('location-active').checked = (loc.active == 1);
        } else {
            titleEl.textContent = 'Ajouter un emplacement';
            this.onLocationCategoryChange(defaultCategory);
            const countForCat = (this.state.currentParkingLocations || []).filter(l => {
                if (defaultCategory === 'private_parking') return l.category === 'private_parking';
                if (defaultCategory === 'tcl_pr') return l.category === 'tcl_pr';
                return l.category === 'free_street' || l.category === 'free_spot';
            }).length;
            document.getElementById('location-order').value = countForCat + 1;
            document.getElementById('location-active').checked = true;
        }

        modal.classList.remove('hidden');
    },

    closeLocationModal() {
        const modal = document.getElementById('modal-location-form');
        if (modal) modal.classList.add('hidden');
    },

    async previewTranslateLocation() {
        const cat = document.getElementById('location-category').value;
        let nameFr = '';
        let data = { category: cat };

        if (cat === 'private_parking') {
            nameFr = (document.getElementById('location-name-fr-priv')?.value || '').trim();
            data.name_fr = nameFr;
            data.address = (document.getElementById('location-address-priv')?.value || '').trim();
            data.walking_time_fr = (document.getElementById('location-walking-fr-priv')?.value || '').trim();
            data.opening_hours_fr = (document.getElementById('location-hours-fr-priv')?.value || '').trim();
            data.rates_fr = (document.getElementById('location-rates-fr-priv')?.value || '').trim();
            data.suitable_vehicles_fr = (document.getElementById('location-vehicles-fr')?.value || '').trim();
            data.notes_fr = (document.getElementById('location-notes-fr-priv')?.value || '').trim();
        } else if (cat === 'tcl_pr') {
            nameFr = (document.getElementById('location-name-fr-tcl')?.value || '').trim();
            data.name_fr = nameFr;
            data.address = (document.getElementById('location-address-tcl')?.value || '').trim();
            data.transit_time_fr = (document.getElementById('location-transit-fr')?.value || '').trim();
            data.opening_hours_fr = (document.getElementById('location-hours-fr-tcl')?.value || '').trim();
            data.how_to_reach_fr = (document.getElementById('location-reach-fr')?.value || '').trim();
            data.notes_fr = (document.getElementById('location-notes-fr-tcl')?.value || '').trim();
        } else {
            nameFr = (document.getElementById('location-name-fr-free')?.value || '').trim();
            data.name_fr = nameFr;
            data.address = (document.getElementById('location-address-free')?.value || '').trim();
            data.walking_time_fr = (document.getElementById('location-walking-fr-free')?.value || '').trim();
            data.notes_fr = (document.getElementById('location-notes-fr-free')?.value || '').trim();
        }

        if (!nameFr) {
            this.showToast('Veuillez renseigner au moins le nom du lieu en français.', 'warning');
            return;
        }

        const btn = document.getElementById('btn-preview-translate-loc');
        const btnIcon = document.getElementById('btn-preview-loc-icon');
        const btnText = document.getElementById('btn-preview-loc-text');
        const origIcon = btnIcon ? btnIcon.textContent : '✨';
        const origText = btnText ? btnText.textContent : 'Traduire & Réviser côte-à-côte';

        try {
            if (btn) btn.disabled = true;
            if (btnIcon) btnIcon.innerHTML = '<span class="inline-block animate-spin">⏳</span>';
            if (btnText) btnText.textContent = 'Traduction IA en cours...';

            const res = await this.apiRequest('api/index.php?action=translate_preview', 'POST', {
                type: 'parking_location',
                data: data
            });

            if (res.data) {
                if (res.data.name_en !== undefined && document.getElementById('location-name-en')) {
                    document.getElementById('location-name-en').value = res.data.name_en;
                }
                if (res.data.walking_time_en !== undefined && document.getElementById('location-walking-en')) {
                    document.getElementById('location-walking-en').value = res.data.walking_time_en;
                }
                if (res.data.transit_time_en !== undefined && document.getElementById('location-transit-en')) {
                    document.getElementById('location-transit-en').value = res.data.transit_time_en;
                }
                if (res.data.opening_hours_en !== undefined && document.getElementById('location-hours-en')) {
                    document.getElementById('location-hours-en').value = res.data.opening_hours_en;
                }
                if (res.data.rates_en !== undefined && document.getElementById('location-rates-en')) {
                    document.getElementById('location-rates-en').value = res.data.rates_en;
                }
                if (res.data.suitable_vehicles_en !== undefined && document.getElementById('location-vehicles-en')) {
                    document.getElementById('location-vehicles-en').value = res.data.suitable_vehicles_en;
                }
                if (res.data.how_to_reach_en !== undefined && document.getElementById('location-reach-en')) {
                    document.getElementById('location-reach-en').value = res.data.how_to_reach_en;
                }
                if (res.data.notes_en !== undefined && document.getElementById('location-notes-en')) {
                    document.getElementById('location-notes-en').value = res.data.notes_en;
                }
            }

            // Ouvrir la modale de revue bilingue côte-à-côte
            this.openTranslationReviewModal('parking_location', res.data);
            this.showToast('Traduction générée ! Vérifiez le texte côte à côte avant enregistrement.', 'success');
        } catch (err) {
            this.showToast('Erreur de traduction : ' + err.message, 'error');
        } finally {
            if (btn) btn.disabled = false;
            if (btnIcon) btnIcon.textContent = origIcon;
            if (btnText) btnText.textContent = origText;
        }
    },

    async saveLocation(e, skipReview = false) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        const id = parseInt(document.getElementById('location-id').value, 10);
        let cat = document.getElementById('location-category').value || 'free_street';

        let nameFr = '';
        let address = '';
        let walkingTimeFr = '';
        let transitTimeFr = '';
        let maxHeight = '';
        let capacity = '';
        let openingHoursFr = '';
        let ratesFr = '';
        let suitableVehiclesFr = '';
        let howToReachFr = '';
        let operator = '';
        let bookingUrl = '';
        let notesFr = '';

        if (cat === 'private_parking') {
            nameFr = (document.getElementById('location-name-fr-priv')?.value || '').trim();
            operator = (document.getElementById('location-operator')?.value || '').trim();
            address = (document.getElementById('location-address-priv')?.value || '').trim();
            walkingTimeFr = (document.getElementById('location-walking-fr-priv')?.value || '').trim();
            maxHeight = (document.getElementById('location-max-height')?.value || '').trim();
            openingHoursFr = (document.getElementById('location-hours-fr-priv')?.value || '').trim();
            suitableVehiclesFr = (document.getElementById('location-vehicles-fr')?.value || '').trim();
            ratesFr = (document.getElementById('location-rates-fr-priv')?.value || '').trim();
            bookingUrl = (document.getElementById('location-booking-url-priv')?.value || '').trim();
            notesFr = (document.getElementById('location-notes-fr-priv')?.value || '').trim();
        } else if (cat === 'tcl_pr') {
            nameFr = (document.getElementById('location-name-fr-tcl')?.value || '').trim();
            capacity = (document.getElementById('location-capacity')?.value || '').trim();
            address = (document.getElementById('location-address-tcl')?.value || '').trim();
            transitTimeFr = (document.getElementById('location-transit-fr')?.value || '').trim();
            openingHoursFr = (document.getElementById('location-hours-fr-tcl')?.value || '').trim();
            howToReachFr = (document.getElementById('location-reach-fr')?.value || '').trim();
            bookingUrl = (document.getElementById('location-booking-url-tcl')?.value || '').trim();
            notesFr = (document.getElementById('location-notes-fr-tcl')?.value || '').trim();
        } else {
            const subtypeEl = document.getElementById('location-free-subtype');
            cat = subtypeEl ? subtypeEl.value : 'free_street';
            nameFr = (document.getElementById('location-name-fr-free')?.value || '').trim();
            address = (document.getElementById('location-address-free')?.value || '').trim();
            walkingTimeFr = (document.getElementById('location-walking-fr-free')?.value || '').trim();
            notesFr = (document.getElementById('location-notes-fr-free')?.value || '').trim();
        }

        if (!nameFr) {
            this.showToast('Veuillez renseigner au moins le nom du lieu en français.', 'warning');
            return;
        }

        // Étape 1 : Si pas encore vérifié, traduction IA + Flash Check
        if (!skipReview) {
            const dataFr = {
                name_fr: nameFr,
                category: cat,
                address: address,
                walking_time_fr: walkingTimeFr,
                transit_time_fr: transitTimeFr,
                opening_hours_fr: openingHoursFr,
                rates_fr: ratesFr,
                suitable_vehicles_fr: suitableVehiclesFr,
                how_to_reach_fr: howToReachFr,
                notes_fr: notesFr
            };

            const btnSave = document.getElementById('btn-save-location');
            const iconEl = document.getElementById('btn-save-loc-icon');
            const textEl = document.getElementById('btn-save-loc-text');
            const origText = textEl ? textEl.textContent : "Enregistrer l'emplacement";

            try {
                if (btnSave) btnSave.disabled = true;
                if (iconEl) iconEl.innerHTML = '<span class="inline-block animate-spin">⏳</span>';
                if (textEl) textEl.textContent = 'Traduction IA...';

                const res = await this.apiRequest('api/index.php?action=translate_preview', 'POST', {
                    type: 'parking_location',
                    data: dataFr
                });

                if (res.data) {
                    if (res.data.name_en !== undefined && document.getElementById('location-name-en')) {
                        document.getElementById('location-name-en').value = res.data.name_en;
                    }
                    if (res.data.walking_time_en !== undefined && document.getElementById('location-walking-en')) {
                        document.getElementById('location-walking-en').value = res.data.walking_time_en;
                    }
                    if (res.data.transit_time_en !== undefined && document.getElementById('location-transit-en')) {
                        document.getElementById('location-transit-en').value = res.data.transit_time_en;
                    }
                    if (res.data.opening_hours_en !== undefined && document.getElementById('location-hours-en')) {
                        document.getElementById('location-hours-en').value = res.data.opening_hours_en;
                    }
                    if (res.data.rates_en !== undefined && document.getElementById('location-rates-en')) {
                        document.getElementById('location-rates-en').value = res.data.rates_en;
                    }
                    if (res.data.suitable_vehicles_en !== undefined && document.getElementById('location-vehicles-en')) {
                        document.getElementById('location-vehicles-en').value = res.data.suitable_vehicles_en;
                    }
                    if (res.data.how_to_reach_en !== undefined && document.getElementById('location-reach-en')) {
                        document.getElementById('location-reach-en').value = res.data.how_to_reach_en;
                    }
                    if (res.data.notes_en !== undefined && document.getElementById('location-notes-en')) {
                        document.getElementById('location-notes-en').value = res.data.notes_en;
                    }
                }

                // Ouvre la vérification flash
                this.openTranslationReviewModal('parking_location', res.data);
                return;
            } catch (err) {
                console.warn('[AI] Échec de la traduction préalable :', err);
            } finally {
                if (btnSave) btnSave.disabled = false;
                if (iconEl) iconEl.textContent = '💾';
                if (textEl) textEl.textContent = origText;
            }
        }

        // Étape 2 : Sauvegarde directe en base de données
        const data = {
            id,
            property_id: parseInt(document.getElementById('location-property-id').value, 10),
            category: cat,
            name_fr: nameFr,
            name_en: (document.getElementById('location-name-en')?.value || '').trim(),
            address: address,
            walking_time_fr: walkingTimeFr,
            walking_time_en: (document.getElementById('location-walking-en')?.value || '').trim(),
            transit_time_fr: transitTimeFr,
            transit_time_en: (document.getElementById('location-transit-en')?.value || '').trim(),
            max_height: maxHeight,
            capacity: capacity,
            opening_hours_fr: openingHoursFr,
            opening_hours_en: (document.getElementById('location-hours-en')?.value || '').trim(),
            rates_fr: ratesFr,
            rates_en: (document.getElementById('location-rates-en')?.value || '').trim(),
            suitable_vehicles_fr: suitableVehiclesFr,
            suitable_vehicles_en: (document.getElementById('location-vehicles-en')?.value || '').trim(),
            how_to_reach_fr: howToReachFr,
            how_to_reach_en: (document.getElementById('location-reach-en')?.value || '').trim(),
            operator: operator,
            booking_url: bookingUrl,
            notes_fr: notesFr,
            notes_en: (document.getElementById('location-notes-en')?.value || '').trim(),
            display_order: parseInt(document.getElementById('location-order').value, 10) || 0,
            active: document.getElementById('location-active').checked ? 1 : 0
        };

        try {
            const res = await this.apiRequest('api/index.php?action=parking_location_save', 'POST', { location: data });
            this.showToast(res.message || 'Emplacement enregistré avec succès !', 'success');
            this.closeLocationModal();
            await this.fetchAndRenderParking(data.property_id);
            await this.loadInitialData();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async moveLocation(id, direction) {
        const item = (this.state.currentParkingLocations || []).find(l => l.id == id);
        if (!item) return;

        const cat = item.category;
        const isFree = (cat === 'free_street' || cat === 'free_spot');
        const list = (this.state.currentParkingLocations || []).filter(l => {
            if (isFree) return (l.category === 'free_street' || l.category === 'free_spot');
            return l.category === cat;
        });

        const idx = list.findIndex(l => l.id == id);
        if (idx < 0) return;

        const targetIdx = idx + direction;
        if (targetIdx < 0 || targetIdx >= list.length) return;

        const moved = list.splice(idx, 1)[0];
        list.splice(targetIdx, 0, moved);

        const orderArray = list.map(l => l.id);
        try {
            await this.apiRequest('api/index.php?action=parking_location_reorder', 'POST', {
                category: isFree ? 'free' : cat,
                order: orderArray
            });
            await this.fetchAndRenderParking(this.state.currentPropertyId);
            this.showToast('Ordre des emplacements actualisé.');
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async toggleLocationStatus(id) {
        const item = (this.state.currentParkingLocations || []).find(l => l.id == id);
        const cat = item ? item.category : null;
        try {
            await this.apiRequest('api/index.php?action=parking_location_toggle', 'POST', { id, category: cat });
            if (item) item.active = (item.active == 1 ? 0 : 1);
            this.renderParkingLocationsList();
            this.showToast('Statut de l\'emplacement actualisé.');
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    confirmDeleteLocation(id, name = null) {
        const loc = (this.state.currentParkingLocations || []).find(l => l.id == id);
        const resolvedName = name || (loc ? loc.name_fr : 'cet emplacement');
        const cat = loc ? loc.category : null;

        this.confirmAction(
            `Supprimer ${resolvedName} ?`,
            `L'emplacement de parking sera retiré de la liste pour cet hébergement.`,
            async () => {
                try {
                    await this.apiRequest('api/index.php?action=parking_location_delete', 'POST', { id, category: cat });
                    this.showToast(`Emplacement « ${resolvedName} » supprimé.`);
                    await this.fetchAndRenderParking(this.state.currentPropertyId);
                    await this.loadInitialData();
                } catch (err) {
                    this.showToast(err.message, 'error');
                }
            }
        );
    },

    // --------------------------------------------------------------------------
    // SECTION 5 : JOURNAL D'AUDIT (LOGS)
    // --------------------------------------------------------------------------
    async loadLogsView() {
        const tbody = document.getElementById('logs-tbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-400">Chargement du journal d\'activité...</td></tr>';

        try {
            const res = await this.apiRequest('api/index.php?action=logs&limit=60');
            this.state.logs = res.logs || [];

            if (this.state.logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-400 italic">Aucune activité enregistrée.</td></tr>';
                return;
            }

            tbody.innerHTML = this.state.logs.map(l => `
                <tr class="border-b border-night-border/40 hover:bg-white/[0.02] text-xs transition">
                    <td class="py-3 px-4 font-mono text-gray-400 whitespace-nowrap">${this.formatDate(l.created_at)}</td>
                    <td class="py-3 px-4 font-semibold text-white whitespace-nowrap">
                        ${this.escape(l.user_name || 'Système')}
                        <span class="block text-[10px] text-gray-500 font-mono">${this.escape(l.ip_address || '')}</span>
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${this.getActionBadgeClass(l.action)}">
                            ${this.getActionLabel(l.action)}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-gray-300">
                        <span class="font-bold text-white uppercase text-[10px]">${this.escape(l.entity_type)}</span>
                        ${l.entity_label ? ` · <span class="text-gold">${this.escape(l.entity_label)}</span>` : ''}
                    </td>
                    <td class="py-3 px-4 text-right">
                        ${l.details ? `
                            <button type="button" onclick="AdminApp.viewLogDetails(${l.id})" class="px-2 py-1 rounded bg-white/5 hover:bg-white/10 text-[10px] text-gray-300 transition">
                                Détails
                            </button>
                        ` : '<span class="text-gray-600 text-[10px]">-</span>'}
                    </td>
                </tr>
            `).join('');
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-red-400">${this.escape(err.message)}</td></tr>`;
        }
    },

    viewLogDetails(logId) {
        const log = this.state.logs.find(l => l.id == logId);
        if (!log || !log.details) return;

        const modal = document.getElementById('modal-log-details');
        const content = document.getElementById('log-details-json');
        if (!modal || !content) return;

        content.textContent = JSON.stringify(log.details, null, 2);
        modal.classList.remove('hidden');
    },

    closeLogDetails() {
        const modal = document.getElementById('modal-log-details');
        if (modal) modal.classList.add('hidden');
    },

    // --------------------------------------------------------------------------
    // SECTION 6 : ADMINISTRATEURS (SUPERADMIN ONLY)
    // --------------------------------------------------------------------------
    async loadUsersView() {
        const tbody = document.getElementById('users-tbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-400">Chargement des administrateurs...</td></tr>';

        try {
            const res = await this.apiRequest('api/index.php?action=users');
            this.state.users = res.users || [];

            tbody.innerHTML = this.state.users.map(u => `
                <tr class="border-b border-night-border/40 hover:bg-white/[0.02] text-xs transition">
                    <td class="py-3 px-4 font-semibold text-white flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-full bg-gold/15 text-gold flex items-center justify-center font-bold text-[10px]">
                            ${u.name.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <div>${this.escape(u.name)}</div>
                            <div class="text-gray-400 text-[11px] font-mono">${this.escape(u.email)}</div>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${u.role === 'superadmin' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : u.role === 'admin' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/20'}">
                            ${u.role}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ${u.active == 1 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'}">
                            ${u.active == 1 ? 'Actif' : 'Suspendu'}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-gray-400 text-[11px]">
                        ${u.last_login_at ? this.formatDate(u.last_login_at) : '<span class="italic text-gray-600">Jamais</span>'}
                    </td>
                    <td class="py-3 px-4 text-right space-x-1.5">
                        <button type="button" onclick="AdminApp.openUserModal(${u.id})" class="p-1.5 rounded-lg bg-white/5 hover:bg-gold hover:text-white text-gray-400 transition" title="Modifier">
                            ✏️
                        </button>
                        ${u.id !== this.state.user.id ? `
                            <button type="button" onclick="AdminApp.confirmDeleteUser(${u.id})" class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500 hover:text-white text-red-400 transition" title="Supprimer">
                                🗑️
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-red-400">${this.escape(err.message)}</td></tr>`;
        }
    },

    openUserModal(id = 0) {
        const modal = document.getElementById('modal-user-form');
        const titleEl = document.getElementById('modal-user-title');
        const form = document.getElementById('user-form');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('user-form-id').value = id;
        const pwdHelp = document.getElementById('user-pwd-help');

        if (id > 0) {
            const u = this.state.users.find(item => item.id == id);
            if (!u) return;
            titleEl.textContent = `Modifier : ${u.name}`;
            document.getElementById('user-name').value = u.name || '';
            document.getElementById('user-email').value = u.email || '';
            document.getElementById('user-role').value = u.role || 'admin';
            document.getElementById('user-active').checked = (u.active == 1);
            if (pwdHelp) pwdHelp.textContent = 'Laissez vide pour conserver le mot de passe actuel.';
            document.getElementById('user-password').removeAttribute('required');
        } else {
            titleEl.textContent = 'Ajouter un administrateur';
            document.getElementById('user-active').checked = true;
            document.getElementById('user-role').value = 'admin';
            if (pwdHelp) pwdHelp.textContent = 'Définissez le mot de passe temporaire initial.';
            document.getElementById('user-password').setAttribute('required', 'required');
        }

        modal.classList.remove('hidden');
    },

    closeUserModal() {
        const modal = document.getElementById('modal-user-form');
        if (modal) modal.classList.add('hidden');
    },

    async saveUser(e) {
        e.preventDefault();
        const id = parseInt(document.getElementById('user-form-id').value, 10);
        const data = {
            id,
            name: document.getElementById('user-name').value.trim(),
            email: document.getElementById('user-email').value.trim(),
            role: document.getElementById('user-role').value,
            password: document.getElementById('user-password').value,
            active: document.getElementById('user-active').checked ? 1 : 0
        };

        try {
            const res = await this.apiRequest('api/index.php?action=user_save', 'POST', { user: data });
            this.showToast(res.message || 'Utilisateur enregistré.');
            this.closeUserModal();
            await this.loadUsersView();
            await this.loadInitialData();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    confirmDeleteUser(id, name = null) {
        const user = (this.state.users || []).find(u => u.id == id);
        const resolvedName = name || (user ? user.name : 'cet utilisateur');

        this.confirmAction(
            `Supprimer le compte de ${resolvedName} ?`,
            `L'administrateur n'aura plus aucun accès à la console d'administration.`,
            async () => {
                try {
                    await this.apiRequest('api/index.php?action=user_delete', 'POST', { id });
                    this.showToast(`Compte de ${resolvedName} supprimé.`);
                    await this.loadUsersView();
                    await this.loadInitialData();
                } catch (err) {
                    this.showToast(err.message, 'error');
                }
            }
        );
    },

    // --------------------------------------------------------------------------
    // PROFIL & SÉCURITÉ
    // --------------------------------------------------------------------------
    openProfileModal() {
        const modal = document.getElementById('modal-profile');
        if (modal) modal.classList.remove('hidden');
    },

    closeProfileModal() {
        const modal = document.getElementById('modal-profile');
        if (modal) modal.classList.add('hidden');
    },

    async changePassword(e) {
        e.preventDefault();
        const old_password = document.getElementById('profile-old-pwd').value;
        const new_password = document.getElementById('profile-new-pwd').value;
        const confirm_password = document.getElementById('profile-confirm-pwd').value;

        if (new_password !== confirm_password) {
            this.showToast('La confirmation du mot de passe ne correspond pas.', 'error');
            return;
        }

        try {
            const res = await this.apiRequest('api/index.php?action=change_password', 'POST', {
                old_password,
                new_password
            });
            this.showToast(res.message || 'Mot de passe modifié avec succès !');
            this.closeProfileModal();
            document.getElementById('profile-pwd-form').reset();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async logout() {
        try {
            await fetch('api/auth.php?action=logout', { method: 'POST' });
        } finally {
            window.location.href = 'login.php';
        }
    },

    // --------------------------------------------------------------------------
    // OUTILS, TOASTS & MODALES GÉNÉRIQUES
    // --------------------------------------------------------------------------
    updatePropertySelector(selectId, onChangeCallback) {
        const select = document.getElementById(selectId);
        if (!select) return;

        select.innerHTML = this.state.properties.map(p => `
            <option value="${p.id}" ${p.id == this.state.currentPropertyId ? 'selected' : ''}>
                ${this.escape(p.name)} (/${this.escape(p.slug)})
            </option>
        `).join('');

        select.onchange = (e) => {
            const pid = parseInt(e.target.value, 10);
            if (onChangeCallback) onChangeCallback(pid);
        };
    },

    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const colors = {
            success: 'bg-emerald-600 text-white border-emerald-400/30',
            error: 'bg-red-600 text-white border-red-400/30',
            info: 'bg-gold text-white border-gold-light/40'
        };

        const toast = document.createElement('div');
        toast.className = `p-3.5 px-4 rounded-xl shadow-2xl border text-xs font-semibold flex items-center space-x-2.5 toast-animate-in pointer-events-auto ${colors[type] || colors.info}`;
        toast.innerHTML = `
            <span>${type === 'success' ? '✅' : type === 'error' ? '⚠️' : 'ℹ️'}</span>
            <span class="flex-1">${this.escape(message)}</span>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'all 0.25s ease';
            setTimeout(() => toast.remove(), 260);
        }, 4000);
    },

    confirmAction(title, message, onConfirm, extraHtml = '') {
        const modal = document.getElementById('modal-confirmation');
        const titleEl = document.getElementById('confirm-title');
        const msgEl = document.getElementById('confirm-message');
        const extraEl = document.getElementById('confirm-extra');
        const btnConfirm = document.getElementById('confirm-btn-yes');

        if (!modal || !btnConfirm) {
            if (confirm(`${title}\n\n${message}`)) onConfirm();
            return;
        }

        titleEl.textContent = title;
        msgEl.textContent = message;

        if (extraEl) {
            if (extraHtml) {
                extraEl.innerHTML = extraHtml;
                extraEl.classList.remove('hidden');
            } else {
                extraEl.innerHTML = '';
                extraEl.classList.add('hidden');
            }
        }

        btnConfirm.onclick = () => {
            try {
                onConfirm();
            } finally {
                modal.classList.add('hidden');
                setTimeout(() => {
                    if (extraEl) {
                        extraEl.innerHTML = '';
                        extraEl.classList.add('hidden');
                    }
                }, 100);
            }
        };

        modal.classList.remove('hidden');
    },

    closeConfirmModal() {
        const modal = document.getElementById('modal-confirmation');
        const extraEl = document.getElementById('confirm-extra');
        if (extraEl) {
            extraEl.innerHTML = '';
            extraEl.classList.add('hidden');
        }
        if (modal) modal.classList.add('hidden');
    },

    showLoading(show) {
        const el = document.getElementById('global-loading-indicator');
        if (el) {
            if (show) el.classList.remove('hidden');
            else el.classList.add('hidden');
        }
    },

    getPortalUrl(slug = '') {
        const base = this.state.isDev ? '/portail/dev/' : '/portail/';
        return `${base}${slug}`;
    },

    slugify(text) {
        return text
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-');
    },

    escape(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr.replace(' ', 'T'));
            return d.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch(e) {
            return dateStr;
        }
    },

    getActionIcon(action) {
        const icons = {
            create: '✨',
            update: '✏️',
            delete: '🗑️',
            toggle: '🔄',
            login: '🔑',
            logout: '🚪'
        };
        return icons[action] || '📌';
    },

    getActionLabel(action) {
        const labels = {
            create: 'Création',
            update: 'Modification',
            delete: 'Suppression',
            toggle: 'Changement de statut',
            login: 'Connexion',
            logout: 'Déconnexion'
        };
        return labels[action] || action;
    },

    getActionBadgeClass(action) {
        const classes = {
            create: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            update: 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
            delete: 'bg-red-500/10 text-red-400 border border-red-500/20',
            toggle: 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            login: 'bg-purple-500/10 text-purple-400 border border-purple-500/20',
            logout: 'bg-gray-500/10 text-gray-400 border border-gray-500/20'
        };
        return classes[action] || 'bg-gray-500/10 text-gray-400';
    },

    // --------------------------------------------------------------------------
    // CONFIGURATION (IA & STOCKAGE CLOUDFLARE R2 / LOCAL)
    // --------------------------------------------------------------------------

    openSettingsModal() {
        this.openAiSettingsModal();
    },

    openAiSettingsModal() {
        const modal = document.getElementById('modal-ai-settings');
        if (!modal) return;

        const s = this.state.settings || {};
        
        // Configuration IA
        const providerSelect = document.getElementById('ai-provider-select');
        if (providerSelect) {
            providerSelect.value = s.ai_provider || 'gemini';
        }

        const keyGemini = document.getElementById('ai-key-gemini');
        if (keyGemini) keyGemini.value = s.masked_key_gemini || '';
        const keyOpenai = document.getElementById('ai-key-openai');
        if (keyOpenai) keyOpenai.value = s.masked_key_openai || '';
        const keyClaude = document.getElementById('ai-key-claude');
        if (keyClaude) keyClaude.value = s.masked_key_claude || '';

        this.handleAiProviderChange();
        this.refreshAiModels();

        const resultEl = document.getElementById('ai-test-result');
        if (resultEl) {
            resultEl.classList.add('hidden');
            resultEl.innerHTML = '';
        }

        // Configuration Cloudflare R2
        if (document.getElementById('r2-account-id')) {
            document.getElementById('r2-account-id').value = s.r2_account_id || '';
        }
        if (document.getElementById('r2-access-key-id')) {
            document.getElementById('r2-access-key-id').value = s.r2_access_key_id || '';
        }
        if (document.getElementById('r2-secret-access-key')) {
            document.getElementById('r2-secret-access-key').value = s.masked_r2_secret || '';
        }
        if (document.getElementById('r2-bucket-guides')) {
            document.getElementById('r2-bucket-guides').value = s.r2_bucket_guides || 'carredhotes-guides';
        }
        if (document.getElementById('r2-domain-guides')) {
            document.getElementById('r2-domain-guides').value = s.r2_domain_guides || 'https://guides.carredhotes.fr';
        }
        if (document.getElementById('r2-bucket-videos')) {
            document.getElementById('r2-bucket-videos').value = s.r2_bucket_videos || 'carredhotes-videos';
        }
        if (document.getElementById('r2-domain-videos')) {
            document.getElementById('r2-domain-videos').value = s.r2_domain_videos || 'https://videos.carredhotes.fr';
        }

        const r2ResultEl = document.getElementById('r2-test-result');
        if (r2ResultEl) {
            r2ResultEl.classList.add('hidden');
            r2ResultEl.innerHTML = '';
        }

        // Par défaut sur le premier onglet
        this.switchSettingsTab('ai');

        modal.classList.remove('hidden');
    },

    closeAiSettingsModal() {
        const modal = document.getElementById('modal-ai-settings');
        if (modal) modal.classList.add('hidden');
    },

    switchSettingsTab(tab) {
        const btnAi = document.getElementById('settings-tab-btn-ai');
        const btnStorage = document.getElementById('settings-tab-btn-storage');
        const panelAi = document.getElementById('settings-panel-ai');
        const panelStorage = document.getElementById('settings-panel-storage');

        if (tab === 'storage') {
            if (btnStorage) btnStorage.className = 'settings-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-gold text-white shadow-md shadow-gold/25';
            if (btnAi) btnAi.className = 'settings-tab-btn px-4 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-white/5 transition flex items-center space-x-2';
            if (panelStorage) panelStorage.classList.remove('hidden');
            if (panelAi) panelAi.classList.add('hidden');
        } else {
            if (btnAi) btnAi.className = 'settings-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-gold text-white shadow-md shadow-gold/25';
            if (btnStorage) btnStorage.className = 'settings-tab-btn px-4 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-white/5 transition flex items-center space-x-2';
            if (panelAi) panelAi.classList.remove('hidden');
            if (panelStorage) panelStorage.classList.add('hidden');
        }
    },

    async testR2Connection() {
        const btn = document.getElementById('btn-test-r2');
        const resultEl = document.getElementById('r2-test-result');
        if (!resultEl) return;

        const config = {
            account_id: document.getElementById('r2-account-id')?.value.trim() || '',
            access_key_id: document.getElementById('r2-access-key-id')?.value.trim() || '',
            secret_access_key: document.getElementById('r2-secret-access-key')?.value.trim() || '',
            bucket_guides: document.getElementById('r2-bucket-guides')?.value.trim() || '',
            bucket_videos: document.getElementById('r2-bucket-videos')?.value.trim() || ''
        };

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<span>⏳</span><span>Test de l'accès Cloudflare R2...</span>`;
        }

        resultEl.classList.remove('hidden');
        resultEl.className = 'p-3 rounded-xl text-xs space-y-1 border bg-night/80 border-gold/30 text-gold-light';
        resultEl.innerHTML = `<p class="flex items-center gap-2"><span>🔄</span><span>Interrogation de l'API S3 Cloudflare R2...</span></p>`;

        try {
            const res = await this.apiRequest('api/index.php?action=r2_test', 'POST', config);
            resultEl.className = 'p-3.5 rounded-xl text-xs space-y-1.5 border bg-emerald-500/10 border-emerald-500/30 text-emerald-300';
            resultEl.innerHTML = `<div class="flex items-center space-x-2 font-bold text-emerald-400"><span>✅</span><span>${this.escape(res.message || 'Connexion R2 validée !')}</span></div>`;
            this.showToast("Connexion Cloudflare R2 réussie !", "success");
        } catch (err) {
            resultEl.className = 'p-3.5 rounded-xl text-xs space-y-1 border bg-red-500/10 border-red-500/30 text-red-300';
            resultEl.innerHTML = `<div class="flex items-center space-x-2 font-bold text-red-400"><span>❌</span><span>Échec de connexion R2</span></div><p class="text-[11px] text-red-200 mt-1">${this.escape(err.message)}</p>`;
            this.showToast(`Erreur R2 : ${err.message}`, "error");
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<span>⚡</span><span>Tester la connexion Cloudflare R2</span>`;
            }
        }
    },

    handleAiProviderChange() {
        const select = document.getElementById('ai-provider-select');
        if (!select) return;
        const provider = select.value;

        document.querySelectorAll('.ai-key-group').forEach(el => el.classList.add('hidden'));
        const activeGroup = document.getElementById(`ai-key-group-${provider}`);
        if (activeGroup) {
            activeGroup.classList.remove('hidden');
        }

        this.refreshAiModels();
    },

    async refreshAiModels() {
        const selectProvider = document.getElementById('ai-provider-select');
        const provider = selectProvider ? selectProvider.value : 'gemini';
        const keyInput = document.getElementById(`ai-key-${provider}`);
        const apiKey = keyInput ? keyInput.value.trim() : '';

        const modelSelect = document.getElementById('ai-model-select');
        const refreshIcon = document.getElementById('ai-models-refresh-icon');
        const refreshLabel = document.getElementById('ai-models-refresh-label');

        if (refreshIcon) refreshIcon.classList.add('animate-spin');
        if (refreshLabel) refreshLabel.textContent = 'Interrogation API...';
        if (modelSelect) {
            modelSelect.disabled = true;
            modelSelect.innerHTML = `<option value="">Interrogation de l'API ${provider.toUpperCase()} en cours...</option>`;
        }

        try {
            const res = await this.apiRequest(`api/index.php?action=models_list&provider=${encodeURIComponent(provider)}&api_key=${encodeURIComponent(apiKey)}`);
            if (res.ok && Array.isArray(res.models) && res.models.length > 0) {
                const s = this.state.settings || {};
                const activeModel = s[`ai_model_${provider}`] || res.active_model || '';

                modelSelect.innerHTML = res.models.map(m => {
                    const isSelected = (m.id === activeModel || (!activeModel && m.recommended));
                    return `<option value="${this.escape(m.id)}" ${isSelected ? 'selected' : ''}>${this.escape(m.name)} [${this.escape(m.id)}]</option>`;
                }).join('');
            } else {
                modelSelect.innerHTML = `<option value="">Aucun modèle détecté (vérifiez votre clé API)</option>`;
            }
        } catch (err) {
            modelSelect.innerHTML = `<option value="">Erreur lors de la récupération des modèles</option>`;
            this.showToast(`Impossible de lister les modèles : ${err.message}`, 'error');
        } finally {
            if (modelSelect) modelSelect.disabled = false;
            if (refreshIcon) refreshIcon.classList.remove('animate-spin');
            if (refreshLabel) refreshLabel.textContent = 'Actualiser les modèles';
        }
    },

    toggleKeyVisibility(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
    },

    async testAiConnection() {
        const select = document.getElementById('ai-provider-select');
        const provider = select ? select.value : 'gemini';
        const keyInput = document.getElementById(`ai-key-${provider}`);
        const apiKey = keyInput ? keyInput.value.trim() : '';
        const modelSelect = document.getElementById('ai-model-select');
        const model = modelSelect ? modelSelect.value : null;

        const testBtn = document.getElementById('btn-test-ai');
        const resultEl = document.getElementById('ai-test-result');
        if (!resultEl) return;

        if (testBtn) {
            testBtn.disabled = true;
            testBtn.innerHTML = `<span>⏳</span><span>Test en cours...</span>`;
        }

        resultEl.classList.remove('hidden');
        resultEl.className = 'p-3 rounded-xl text-xs space-y-1 border bg-night/80 border-gold/30 text-gold-light';
        resultEl.innerHTML = `<p class="flex items-center gap-2"><span>🔄</span><span>Envoi d'une requête de test à ${provider.toUpperCase()} (${model || 'modèle par défaut'})...</span></p>`;

        try {
            const res = await this.apiRequest('api/index.php?action=test_ai', 'POST', {
                provider,
                api_key: apiKey,
                model
            });

            resultEl.className = 'p-3.5 rounded-xl text-xs space-y-1.5 border bg-emerald-500/10 border-emerald-500/30 text-emerald-300';
            resultEl.innerHTML = `
                <div class="flex items-center space-x-2 font-bold text-emerald-400">
                    <span>✅</span>
                    <span>Connexion réussie avec ${res.provider.toUpperCase()} [${this.escape(res.model)}] (${res.duration_ms} ms)</span>
                </div>
                <div class="text-[11px] text-gray-300 space-y-0.5 pt-1">
                    <p><strong class="text-gray-400">FR :</strong> "${this.escape(res.sample_fr)}"</p>
                    <p><strong class="text-emerald-400">EN :</strong> "${this.escape(res.sample_en)}"</p>
                </div>
            `;
            this.showToast(`Connexion à ${provider.toUpperCase()} (${res.model}) validée !`, 'success');
        } catch (err) {
            resultEl.className = 'p-3.5 rounded-xl text-xs space-y-1 border bg-red-500/10 border-red-500/30 text-red-300';
            resultEl.innerHTML = `
                <div class="flex items-center space-x-2 font-bold text-red-400">
                    <span>❌</span>
                    <span>Échec de connexion (${provider.toUpperCase()})</span>
                </div>
                <p class="text-[11px] text-red-200 mt-1">${this.escape(err.message)}</p>
            `;
            this.showToast(`Erreur IA : ${err.message}`, 'error');
        } finally {
            if (testBtn) {
                testBtn.disabled = false;
                testBtn.innerHTML = `<span>⚡</span><span>Tester la connexion IA</span>`;
            }
        }
    },

    async saveAiSettings(e) {
        e.preventDefault();
        const select = document.getElementById('ai-provider-select');
        const provider = select ? select.value : 'gemini';
        const modelSelect = document.getElementById('ai-model-select');
        const selectedModel = modelSelect ? modelSelect.value : '';

        const payload = {
            ai_provider: provider,
            ai_api_key_gemini: (document.getElementById('ai-key-gemini')?.value || '').trim(),
            ai_api_key_openai: (document.getElementById('ai-key-openai')?.value || '').trim(),
            ai_api_key_claude: (document.getElementById('ai-key-claude')?.value || '').trim(),
            // Paramètres Cloudflare R2 (stockage exclusif)
            storage_driver: 'r2',
            r2_account_id: (document.getElementById('r2-account-id')?.value || '').trim(),
            r2_access_key_id: (document.getElementById('r2-access-key-id')?.value || '').trim(),
            r2_secret_access_key: (document.getElementById('r2-secret-access-key')?.value || '').trim(),
            r2_bucket_guides: (document.getElementById('r2-bucket-guides')?.value || '').trim(),
            r2_domain_guides: (document.getElementById('r2-domain-guides')?.value || '').trim(),
            r2_bucket_videos: (document.getElementById('r2-bucket-videos')?.value || '').trim(),
            r2_domain_videos: (document.getElementById('r2-domain-videos')?.value || '').trim(),
        };

        if (selectedModel) {
            payload[`ai_model_${provider}`] = selectedModel;
        }

        try {
            const res = await this.apiRequest('api/index.php?action=settings_save', 'POST', {
                settings: payload
            });

            this.state.settings = res.settings;
            this.updateAiBadges();
            this.showToast(res.message || 'Configuration enregistrée avec succès.', 'success');
            this.closeAiSettingsModal();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    updateAiBadges() {
        const s = this.state.settings || {};
        const p = s.ai_provider || 'gemini';
        const names = { gemini: 'Gemini', openai: 'ChatGPT', claude: 'Claude' };
        const displayName = names[p] || p;

        const sidebarBadge = document.getElementById('nav-ai-provider-badge');
        if (sidebarBadge) {
            sidebarBadge.textContent = displayName;
        }

        const parkingBadge = document.getElementById('parking-general-ai-badge');
        if (parkingBadge) {
            parkingBadge.textContent = displayName;
        }
    },

    // --------------------------------------------------------------------------
    // TÉLÉVERSEMENT DE MÉDIAS (GUIDES PDF & VIDÉOS TUTORIELS)
    // --------------------------------------------------------------------------

    uploadMediaFile(file, category, extraParams = {}, progressCallback = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/index.php?action=upload_media', true);

            if (this.state.csrfToken) {
                xhr.setRequestHeader('X-CSRF-Token', this.state.csrfToken);
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('category', category);
            if (this.state.csrfToken) {
                formData.append('csrf_token', this.state.csrfToken);
            }

            for (const [k, v] of Object.entries(extraParams)) {
                if (v !== undefined && v !== null) {
                    formData.append(k, v);
                }
            }

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable && typeof progressCallback === 'function') {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressCallback(percent, e.loaded, e.total);
                }
            };

            xhr.onload = () => {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && res.ok) {
                        resolve(res);
                    } else {
                        reject(new Error(res.error || `Erreur serveur HTTP ${xhr.status}`));
                    }
                } catch (err) {
                    reject(new Error(`Réponse serveur inattendue : ${xhr.responseText.substring(0, 100)}`));
                }
            };

            xhr.onerror = () => {
                reject(new Error("Erreur réseau lors de l'envoi du fichier."));
            };

            xhr.send(formData);
        });
    },

    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.add('dragover');
    },

    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');
    },

    handleDrop(e, category, lang = null) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0];
            if (category === 'guide') {
                this.stageGuideFile(file, lang || 'fr');
            } else if (category === 'video') {
                this.stageVideoFile(file);
            }
        }
    },

    toggleManualUrl(containerId) {
        const el = document.getElementById(containerId);
        if (el) el.classList.toggle('hidden');
    },

    handleGuideFileUpload(inputEl, lang) {
        if (!inputEl || !inputEl.files || inputEl.files.length === 0) return;
        const file = inputEl.files[0];
        this.stageGuideFile(file, lang);
    },

    stageGuideFile(file, lang) {
        if (!file) return;
        if (!file.name.toLowerCase().endsWith('.pdf') && file.type !== 'application/pdf') {
            this.showToast("Seuls les livrets au format PDF (.pdf) sont acceptés.", "error");
            const fileInput = document.getElementById(`property-guide-${lang}-file`);
            if (fileInput) fileInput.value = '';
            return;
        }
        if (file.size > 52428800) {
            this.showToast("Le livret PDF ne doit pas dépasser 50 Mo.", "error");
            const fileInput = document.getElementById(`property-guide-${lang}-file`);
            if (fileInput) fileInput.value = '';
            return;
        }

        const upperLang = (lang === 'en' ? 'En' : 'Fr');

        // Nettoyage de l'ancien object URL
        if (this.state[`pendingGuide${upperLang}ObjectUrl`]) {
            try { URL.revokeObjectURL(this.state[`pendingGuide${upperLang}ObjectUrl`]); } catch(e) {}
            this.state[`pendingGuide${upperLang}ObjectUrl`] = null;
        }

        this.state[`pendingGuide${upperLang}File`] = file;
        try {
            this.state[`pendingGuide${upperLang}ObjectUrl`] = URL.createObjectURL(file);
        } catch(e) {
            this.state[`pendingGuide${upperLang}ObjectUrl`] = null;
        }

        // Auto-suggérer le nom ou slug si vides
        const slugInput = document.getElementById('property-slug');
        const nameInput = document.getElementById('property-name');
        if (nameInput && !nameInput.value.trim() && slugInput && !slugInput.value.trim()) {
            const raw = file.name.replace(/\.[^/.]+$/, "").replace(/guide[-_]?/i, "").trim();
            if (raw) {
                slugInput.value = this.slugify(raw);
                this.updateGuideR2Previews();
            }
        }

        this.updateGuideStagedPreview(lang);
        this.updateGuideR2Previews();

        this.showToast(`Guide PDF (${lang.toUpperCase()}) sélectionné. Il sera téléversé à la validation du formulaire.`, "info");
    },

    uploadGuideFile(file, lang) {
        // Alias de rétrocompatibilité
        this.stageGuideFile(file, lang);
    },

    updateGuideStagedPreview(lang) {
        const upperLang = (lang === 'en' ? 'En' : 'Fr');
        const pendingFile = this.state[`pendingGuide${upperLang}File`];
        const pendingUrl = this.state[`pendingGuide${upperLang}ObjectUrl`];

        const dropzoneEl = document.getElementById(`dropzone-guide-${lang}`);
        const previewEl = document.getElementById(`preview-guide-${lang}`);
        const previewNameEl = document.getElementById(`preview-filename-guide-${lang}`);
        const previewLinkEl = document.getElementById(`link-preview-guide-${lang}`);
        const badgeEl = document.getElementById(`preview-badge-guide-${lang}`);

        if (pendingFile) {
            if (previewEl) {
                previewEl.className = 'flex items-center justify-between p-2.5 rounded-xl bg-gold/10 border border-gold/40 text-xs transition-all';
                previewEl.classList.remove('hidden');
            }
            if (dropzoneEl) dropzoneEl.classList.add('hidden');
            if (badgeEl) {
                badgeEl.textContent = '📄';
                badgeEl.className = 'text-gold text-sm';
            }
            if (previewNameEl) {
                previewNameEl.textContent = `${pendingFile.name} (${this.formatBytes(pendingFile.size)}) • En attente d'enregistrement`;
                previewNameEl.className = 'text-white font-mono text-xs font-semibold truncate max-w-[200px]';
            }
            if (previewLinkEl) {
                previewLinkEl.href = pendingUrl || '#';
                previewLinkEl.textContent = 'Voir ↗';
                previewLinkEl.classList.remove('hidden');
            }
        } else {
            const urlInput = document.getElementById(`property-guide-${lang}`);
            const url = urlInput ? urlInput.value.trim() : '';
            if (url) {
                if (previewEl) {
                    previewEl.className = 'flex items-center justify-between p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs transition-all';
                    previewEl.classList.remove('hidden');
                }
                if (dropzoneEl) dropzoneEl.classList.add('hidden');
                if (badgeEl) {
                    badgeEl.textContent = '✓';
                    badgeEl.className = 'text-emerald-400 text-sm';
                }
                if (previewNameEl) {
                    const fname = url.split('/').pop().split('?')[0] || `guide-${lang}.pdf`;
                    previewNameEl.textContent = `${fname} • En ligne`;
                    previewNameEl.className = 'text-white font-mono text-xs font-semibold truncate max-w-[200px]';
                }
                if (previewLinkEl) {
                    previewLinkEl.href = url;
                    previewLinkEl.textContent = 'Voir ↗';
                    previewLinkEl.classList.remove('hidden');
                }
            } else {
                if (previewEl) previewEl.classList.add('hidden');
                if (dropzoneEl) dropzoneEl.classList.remove('hidden');
            }
        }
    },

    onGuideUrlInput(lang) {
        const upperLang = (lang === 'en' ? 'En' : 'Fr');
        if (this.state[`pendingGuide${upperLang}File`]) {
            this.updateGuideStagedPreview(lang);
            return;
        }
        this.updateGuideStagedPreview(lang);
    },

    handleVideoFileUpload(inputEl) {
        if (!inputEl || !inputEl.files || inputEl.files.length === 0) return;
        const file = inputEl.files[0];
        this.stageVideoFile(file);
    },

    stageVideoFile(file) {
        if (!file) return;
        const allowedExts = ['.mp4', '.webm', '.mov', '.m4v'];
        const ext = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedExts.includes(ext) && !file.type.startsWith('video/')) {
            this.showToast("Seuls les formats vidéo MP4, WebM ou MOV sont acceptés.", "error");
            const fileInput = document.getElementById('video-file-input');
            if (fileInput) fileInput.value = '';
            return;
        }
        if (file.size > 262144000) {
            this.showToast("La vidéo ne doit pas dépasser 250 Mo.", "error");
            const fileInput = document.getElementById('video-file-input');
            if (fileInput) fileInput.value = '';
            return;
        }

        // Nettoyage de l'ancien object URL
        if (this.state.pendingVideoObjectUrl) {
            try { URL.revokeObjectURL(this.state.pendingVideoObjectUrl); } catch(e) {}
            this.state.pendingVideoObjectUrl = null;
        }

        this.state.pendingVideoFile = file;
        try {
            this.state.pendingVideoObjectUrl = URL.createObjectURL(file);
        } catch(e) {
            this.state.pendingVideoObjectUrl = null;
        }

        // Pré-remplissage intelligent du titre et du slug si non encore saisis
        const titleInput = document.getElementById('video-title-fr');
        const slugInput = document.getElementById('video-slug');
        const baseName = file.name.replace(/\.[^/.]+$/, "");

        if (titleInput && !titleInput.value.trim()) {
            const cleanTitle = baseName.replace(/[-_]+/g, ' ');
            titleInput.value = cleanTitle.charAt(0).toUpperCase() + cleanTitle.slice(1);
        }

        if (slugInput && !slugInput.value.trim()) {
            const seed = titleInput && titleInput.value.trim() ? titleInput.value.trim() : baseName;
            slugInput.value = this.slugify(seed);
        }

        this.updateVideoStagedPreview();
        this.updateVideoR2Preview();

        this.showToast(`Vidéo sélectionnée (${file.name}). Elle sera téléversée lors de l'enregistrement.`, "info");
    },

    uploadVideoFile(file) {
        // Alias de compatibilité
        this.stageVideoFile(file);
    },

    isR2VideoUrl(url) {
        if (!url || typeof url !== 'string') return false;
        const u = url.toLowerCase();
        return u.includes('videos.carredhotes.fr') ||
               u.includes('r2.cloudflarestorage.com') ||
               u.includes('r2.dev') ||
               (this.state.settings && this.state.settings.r2_domain_videos && u.includes(this.state.settings.r2_domain_videos.toLowerCase()));
    },

    updateVideoStagedPreview() {
        const dropzoneEl = document.getElementById('dropzone-video');
        const previewEl = document.getElementById('preview-video-container');
        const previewNameEl = document.getElementById('preview-filename-video');
        const previewMetaEl = document.getElementById('preview-meta-video');
        const badgeEl = document.getElementById('preview-badge-video');
        const playBtn = document.getElementById('preview-btn-play-video');
        const actionBtn = document.getElementById('preview-btn-action-video');

        if (this.state.pendingVideoFile) {
            const file = this.state.pendingVideoFile;
            if (previewEl) {
                previewEl.className = 'flex items-center justify-between p-3 rounded-xl bg-gold/10 border border-gold/40 text-xs transition-all';
                previewEl.classList.remove('hidden');
            }
            if (dropzoneEl) dropzoneEl.classList.add('hidden');
            if (badgeEl) {
                badgeEl.textContent = '🎬';
                badgeEl.className = 'text-gold text-base';
            }
            if (previewNameEl) {
                previewNameEl.textContent = file.name;
                previewNameEl.className = 'text-white font-mono text-xs font-semibold truncate max-w-[220px]';
            }
            if (previewMetaEl) {
                previewMetaEl.textContent = `${this.formatBytes(file.size)} • Sera téléversé à la validation`;
                previewMetaEl.className = 'text-[10px] text-gold font-medium';
            }
            if (playBtn) playBtn.classList.remove('hidden');
            if (actionBtn) {
                actionBtn.textContent = 'Retirer';
                actionBtn.className = 'px-2.5 py-1 rounded-lg bg-gray-500/15 text-gray-300 hover:bg-gray-500/30 text-[11px] font-semibold transition';
                actionBtn.title = 'Annuler la sélection de ce fichier local';
            }
        } else {
            const urlInput = document.getElementById('video-url');
            const url = urlInput ? urlInput.value.trim() : '';
            if (url) {
                const isR2 = this.isR2VideoUrl(url);
                if (previewEl) {
                    previewEl.className = isR2
                        ? 'flex items-center justify-between p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs transition-all'
                        : 'flex items-center justify-between p-3 rounded-xl bg-blue-500/10 border border-blue-500/30 text-xs transition-all';
                    previewEl.classList.remove('hidden');
                }
                if (dropzoneEl) dropzoneEl.classList.add('hidden');
                if (badgeEl) {
                    badgeEl.textContent = isR2 ? '☁️' : '🔗';
                    badgeEl.className = isR2 ? 'text-emerald-400 text-base' : 'text-blue-400 text-base';
                }
                if (previewNameEl) {
                    const fname = url.split('/').pop().split('?')[0] || 'Vidéo';
                    previewNameEl.textContent = fname;
                    previewNameEl.className = 'text-white font-mono text-xs font-semibold truncate max-w-[220px]';
                }
                if (previewMetaEl) {
                    previewMetaEl.textContent = isR2 ? 'Hébergé sur Cloudflare R2' : 'Lien externe configuré';
                    previewMetaEl.className = isR2 ? 'text-[10px] text-emerald-300 font-medium' : 'text-[10px] text-blue-300 font-medium';
                }
                if (playBtn) playBtn.classList.remove('hidden');
                if (actionBtn) {
                    if (isR2) {
                        actionBtn.textContent = 'Supprimer';
                        actionBtn.className = 'px-2.5 py-1 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white border border-red-500/30 text-[11px] font-bold transition flex items-center space-x-1';
                        actionBtn.title = 'Supprimer définitivement cette vidéo sur Cloudflare R2';
                    } else {
                        actionBtn.textContent = 'Retirer';
                        actionBtn.className = 'px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 text-[11px] font-semibold transition';
                        actionBtn.title = 'Retirer le lien externe';
                    }
                }
            } else {
                if (previewEl) previewEl.classList.add('hidden');
                if (dropzoneEl) dropzoneEl.classList.remove('hidden');
            }
        }
    },

    handleVideoFileAction() {
        if (this.state.pendingVideoFile) {
            // Fichier local en attente : on l'enlève simplement sans toucher à Cloudflare
            this.clearUploadedMedia('video');
            return;
        }

        const urlInput = document.getElementById('video-url');
        const url = urlInput ? urlInput.value.trim() : '';
        if (url && this.isR2VideoUrl(url)) {
            // Vidéo déjà uploadée sur Cloudflare : confirmation avec avertissement explicite
            this.confirmDeleteVideoFileFromR2(url);
        } else {
            this.clearUploadedMedia('video');
        }
    },

    confirmDeleteVideoFileFromR2(url) {
        const id = parseInt(document.getElementById('video-id')?.value || '0', 10);
        const propertyId = parseInt(document.getElementById('video-property-id')?.value || '0', 10);
        const fname = url.split('/').pop().split('?')[0] || 'cette vidéo';

        this.confirmAction(
            "Supprimer la vidéo sur Cloudflare ?",
            `Attention : cette vidéo (${fname}) sera immédiatement et définitivement supprimée sur Cloudflare R2 dans le dossier de cet hébergement. Cette action est irréversible.`,
            async () => {
                try {
                    this.showLoading(true);
                    const res = await this.apiRequest('api/index.php?action=video_delete_cloudflare_file', 'POST', {
                        id,
                        video_url: url,
                        property_id: propertyId
                    });

                    this.showToast(res.message || "Vidéo supprimée de Cloudflare R2 avec succès.");

                    // Vider le champ URL et restaurer le dropzone
                    this.clearUploadedMedia('video');

                    // Si nous modifions un enregistrement existant, rafraîchir la liste en arrière-plan
                    if (id > 0) {
                        await this.fetchAndRenderVideos(this.state.currentPropertyId);
                    }
                } catch (err) {
                    this.showToast(`Échec de la suppression sur Cloudflare : ${err.message}`, "error");
                } finally {
                    this.showLoading(false);
                }
            }
        );
    },

    onVideoUrlInput() {
        if (this.state.pendingVideoFile) {
            this.updateVideoStagedPreview();
            return;
        }
        const urlInput = document.getElementById('video-url');
        const url = urlInput ? urlInput.value.trim() : '';
        const previewEl = document.getElementById('preview-video-container');
        const previewNameEl = document.getElementById('preview-filename-video');
        const previewMetaEl = document.getElementById('preview-meta-video');
        const badgeEl = document.getElementById('preview-badge-video');
        const dropzoneEl = document.getElementById('dropzone-video');

        if (url) {
            const fname = url.split('/').pop().split('?')[0] || 'video.mp4';
            if (previewNameEl) {
                previewNameEl.textContent = fname;
                previewNameEl.className = 'text-white font-mono text-xs font-semibold truncate max-w-[220px]';
            }
            if (previewMetaEl) {
                previewMetaEl.textContent = 'URL directe configurée';
                previewMetaEl.className = 'text-[10px] text-emerald-300';
            }
            if (badgeEl) {
                badgeEl.textContent = '✓';
                badgeEl.className = 'text-emerald-400 text-base';
            }
            if (previewEl) {
                previewEl.className = 'flex items-center justify-between p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs transition-all';
                previewEl.classList.remove('hidden');
            }
            if (dropzoneEl) dropzoneEl.classList.add('hidden');
        } else {
            if (previewEl) previewEl.classList.add('hidden');
            if (dropzoneEl) dropzoneEl.classList.remove('hidden');
        }
    },

    clearUploadedMedia(category, lang = null) {
        if (category === 'guide') {
            const upperLang = (lang === 'en' ? 'En' : 'Fr');
            if (this.state[`pendingGuide${upperLang}ObjectUrl`]) {
                try { URL.revokeObjectURL(this.state[`pendingGuide${upperLang}ObjectUrl`]); } catch(e) {}
                this.state[`pendingGuide${upperLang}ObjectUrl`] = null;
            }
            this.state[`pendingGuide${upperLang}File`] = null;

            const urlInput = document.getElementById(`property-guide-${lang}`);
            if (urlInput) urlInput.value = '';
            const previewEl = document.getElementById(`preview-guide-${lang}`);
            if (previewEl) previewEl.classList.add('hidden');
            const dropzoneEl = document.getElementById(`dropzone-guide-${lang}`);
            if (dropzoneEl) dropzoneEl.classList.remove('hidden');
            const fileInput = document.getElementById(`property-guide-${lang}-file`);
            if (fileInput) fileInput.value = '';
            this.updateGuideR2Previews();
        } else if (category === 'video') {
            if (this.state.pendingVideoObjectUrl) {
                try { URL.revokeObjectURL(this.state.pendingVideoObjectUrl); } catch(e) {}
                this.state.pendingVideoObjectUrl = null;
            }
            this.state.pendingVideoFile = null;

            const urlInput = document.getElementById('video-url');
            if (urlInput) urlInput.value = '';
            const previewEl = document.getElementById('preview-video-container');
            if (previewEl) previewEl.classList.add('hidden');
            const dropzoneEl = document.getElementById('dropzone-video');
            if (dropzoneEl) dropzoneEl.classList.remove('hidden');
            const fileInput = document.getElementById('video-file-input');
            if (fileInput) fileInput.value = '';
            this.updateVideoR2Preview();
        }
    },

    previewCurrentVideo() {
        let url = '';
        if (this.state.pendingVideoObjectUrl) {
            url = this.state.pendingVideoObjectUrl;
        } else {
            const urlInput = document.getElementById('video-url');
            url = urlInput ? urlInput.value.trim() : '';
        }
        if (!url) {
            this.showToast("Aucune vidéo sélectionnée ou configurée pour l'instant.", "warning");
            return;
        }
        const titleFr = document.getElementById('video-title-fr')?.value || 'Aperçu vidéo';
        this.previewVideo(url, titleFr);
    },

    formatBytes(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + ' Mo';
        }
        if (bytes >= 1024) {
            return Math.round(bytes / 1024) + ' Ko';
        }
        return bytes + ' o';
    }
};

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    AdminApp.init();
});

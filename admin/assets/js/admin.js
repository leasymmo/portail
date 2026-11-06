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
        isDev: false
    },

    // --------------------------------------------------------------------------
    // INITIALISATION
    // --------------------------------------------------------------------------
    async init() {
        this.state.isDev = window.location.pathname.includes('/portail/dev');
        
        // Initialisation de la navigation
        this.setupNavigation();
        this.setupMobileMenu();

        // Chargement initial des données
        await this.loadInitialData();

        // Gérer le hash d'URL s'il existe (#properties, #videos, #parking, etc.)
        const hash = window.location.hash.replace('#', '');
        if (['dashboard', 'properties', 'videos', 'parking', 'logs', 'users'].includes(hash)) {
            this.switchTab(hash);
        } else {
            this.switchTab('dashboard');
        }
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

    switchTab(tabName) {
        // Contrôle des droits d'accès
        if (tabName === 'users' && this.state.user && this.state.user.role !== 'superadmin') {
            this.showToast('Accès restreint aux super-administrateurs', 'error');
            return;
        }

        this.state.activeTab = tabName;
        window.location.hash = tabName;

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

        // Tableau synthétique des logements sur le Dashboard
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
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ${p.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/20'}">
                            ${p.active == 1 ? '● Actif' : '○ Inactif'}
                        </span>
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

        container.innerHTML = list.map(p => `
            <div class="rounded-2xl bg-night-card border border-night-border hover:border-gold/40 p-5 transition flex flex-col justify-between group shadow-lg">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-2xl bg-gold/15 text-gold border border-gold/30 flex items-center justify-center font-bold text-sm shadow-md shadow-gold/10">
                                ${p.name.substring(0, 2).toUpperCase()}
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-white group-hover:text-gold transition">${this.escape(p.name)}</h4>
                                <span class="text-xs font-mono text-gray-400">/${this.escape(p.slug)}</span>
                            </div>
                        </div>
                        <button type="button" onclick="AdminApp.togglePropertyStatus(${p.id})" title="Cliquer pour activer/désactiver"
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold cursor-pointer transition ${p.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30 hover:bg-gray-500/20'}">
                            ${p.active == 1 ? '● Actif' : '○ Inactif'}
                        </button>
                    </div>

                    ${p.address ? `<p class="text-xs text-gray-300 mb-3 flex items-center"><span class="mr-1.5">📍</span>${this.escape(p.address)}</p>` : ''}

                    <div class="grid grid-cols-2 gap-2 text-xs py-3 border-y border-night-border/50 my-3">
                        <div class="bg-night/50 p-2.5 rounded-xl border border-night-border/30">
                            <div class="text-gray-400 text-[10px] uppercase font-semibold">Wi-Fi</div>
                            <div class="font-bold text-white truncate mt-0.5">${p.wifi_ssid ? this.escape(p.wifi_ssid) : '<span class="text-gray-500 italic">Non configuré</span>'}</div>
                        </div>
                        <div class="bg-night/50 p-2.5 rounded-xl border border-night-border/30">
                            <div class="text-gray-400 text-[10px] uppercase font-semibold">Modules</div>
                            <div class="font-bold text-white truncate mt-0.5">🎬 ${p.videos_count} · 🅿️ ${p.parking_locations_count}</div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between border-t border-night-border/40 gap-2">
                    <div class="flex items-center space-x-1.5">
                        <button type="button" onclick="AdminApp.openPropertyModal(${p.id})" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-gold hover:text-white text-xs font-semibold text-gray-200 transition">
                            ✏️ Éditer
                        </button>
                        <button type="button" onclick="AdminApp.selectPropertyAndTab(${p.id}, 'videos')" class="px-2.5 py-1.5 rounded-xl bg-blue-500/10 hover:bg-blue-500 hover:text-white text-blue-400 text-xs font-semibold transition" title="Gérer les vidéos">
                            🎬 Vidéos
                        </button>
                        <button type="button" onclick="AdminApp.selectPropertyAndTab(${p.id}, 'parking')" class="px-2.5 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500 hover:text-white text-amber-400 text-xs font-semibold transition" title="Gérer le stationnement">
                            🅿️ Parking
                        </button>
                    </div>
                    <div class="flex items-center space-x-1">
                        <a href="${this.getPortalUrl(p.slug)}" target="_blank" rel="noopener noreferrer" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition" title="Tester le portail voyageur">
                            👁️
                        </a>
                        <button type="button" onclick="AdminApp.confirmDeleteProperty(${p.id}, '${this.escape(p.name)}')" class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500 hover:text-white text-red-400 transition" title="Supprimer">
                            🗑️
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    },

    openPropertyModal(id = 0) {
        const modal = document.getElementById('modal-property-form');
        const titleEl = document.getElementById('modal-property-title');
        const form = document.getElementById('property-form');
        if (!modal || !form) return;

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
            document.getElementById('property-active').checked = true;
        }

        modal.classList.remove('hidden');
    },

    closePropertyModal() {
        const modal = document.getElementById('modal-property-form');
        if (modal) modal.classList.add('hidden');
    },

    onPropertyNameInput(val) {
        const id = document.getElementById('property-id').value;
        const slugInput = document.getElementById('property-slug');
        // Génère le slug automatiquement lors de la création
        if (id == 0 && slugInput) {
            slugInput.value = this.slugify(val);
        }
    },

    async saveProperty(e) {
        e.preventDefault();
        const id = parseInt(document.getElementById('property-id').value, 10);
        const data = {
            id,
            name: document.getElementById('property-name').value.trim(),
            slug: document.getElementById('property-slug').value.trim(),
            address: document.getElementById('property-address').value.trim(),
            wifi_ssid: document.getElementById('property-wifi-ssid').value.trim(),
            wifi_password: document.getElementById('property-wifi-password').value.trim(),
            guide_fr_url: document.getElementById('property-guide-fr').value.trim(),
            guide_en_url: document.getElementById('property-guide-en').value.trim(),
            active: document.getElementById('property-active').checked ? 1 : 0
        };

        try {
            const res = await this.apiRequest('api/index.php?action=property_save', 'POST', { property: data });
            this.showToast(res.message || 'Hébergement enregistré avec succès.');
            this.closePropertyModal();
            await this.loadInitialData();
            this.renderProperties();
        } catch (err) {
            this.showToast(err.message, 'error');
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

    confirmDeleteProperty(id, name) {
        this.confirmAction(
            `Supprimer ${name} ?`,
            `Attention : cette action supprimera définitivement le logement, ses vidéos tutoriels et ses configurations de stationnement associées. Cette action est irréversible.`,
            async () => {
                try {
                    await this.apiRequest('api/index.php?action=property_delete', 'POST', { id });
                    this.showToast(`Hébergement ${name} supprimé.`);
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
            await this.fetchAndRenderVideos(pid);
        });

        if (this.state.currentPropertyId) {
            await this.fetchAndRenderVideos(this.state.currentPropertyId);
        }
    },

    async fetchAndRenderVideos(propertyId) {
        const container = document.getElementById('videos-list-container');
        if (!container) return;

        container.innerHTML = '<div class="py-8 text-center text-gray-400">Chargement des vidéos...</div>';

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
            <div class="space-y-3" id="sortable-videos">
                ${list.map((v, index) => `
                    <div class="p-4 rounded-2xl bg-night-card border border-night-border hover:border-gold/30 flex items-center justify-between gap-3 transition shadow-md" data-id="${v.id}">
                        <div class="flex items-center space-x-3.5 min-w-0">
                            <!-- Poignée de déplacement / ordre -->
                            <div class="flex flex-col space-y-1 text-gray-500">
                                <button type="button" onclick="AdminApp.moveVideo(${v.id}, -1)" ${index === 0 ? 'disabled class="opacity-20"' : 'class="hover:text-gold"'} title="Monter">▲</button>
                                <button type="button" onclick="AdminApp.moveVideo(${v.id}, 1)" ${index === list.length - 1 ? 'disabled class="opacity-20"' : 'class="hover:text-gold"'} title="Descendre">▼</button>
                            </div>

                            <!-- Badge icône -->
                            <div class="w-11 h-11 rounded-2xl ${this.escape(v.icon_bg_color || 'bg-blue-50')} ${this.escape(v.icon_text_color || 'text-blue-500')} flex items-center justify-center text-xl flex-shrink-0 shadow-sm">
                                ${this.escape(v.icon || '🎬')}
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h5 class="text-sm font-bold text-white truncate">${this.escape(v.title_fr)}</h5>
                                    <span class="text-[10px] text-gray-400 font-mono">#${v.display_order}</span>
                                </div>
                                <p class="text-xs text-gray-400 truncate mt-0.5">🇬🇧 ${this.escape(v.title_en || v.title_fr)}</p>
                                <p class="text-[10px] text-gray-500 truncate font-mono mt-0.5">${this.escape(v.video_url)}</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <button type="button" onclick="AdminApp.previewVideo('${this.escape(v.video_url)}', '${this.escape(v.title_fr)}')" class="px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300 transition" title="Lire la vidéo">
                                ▶️ Aperçu
                            </button>
                            <button type="button" onclick="AdminApp.toggleVideoStatus(${v.id})" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${v.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30'}">
                                ${v.active == 1 ? 'Actif' : 'Masqué'}
                            </button>
                            <button type="button" onclick="AdminApp.openVideoModal(${v.id})" class="p-1.5 rounded-lg bg-white/5 hover:bg-gold hover:text-white text-gray-400 transition" title="Modifier">
                                ✏️
                            </button>
                            <button type="button" onclick="AdminApp.confirmDeleteVideo(${v.id}, '${this.escape(v.title_fr)}')" class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500 hover:text-white text-red-400 transition" title="Supprimer">
                                🗑️
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },

    openVideoModal(id = 0) {
        const modal = document.getElementById('modal-video-form');
        const titleEl = document.getElementById('modal-video-title');
        const form = document.getElementById('video-form');
        if (!modal || !form) return;

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
            document.getElementById('video-icon').value = '🎬';
            document.getElementById('video-bg-color').value = 'bg-blue-50';
            document.getElementById('video-text-color').value = 'text-blue-500';
            document.getElementById('video-order').value = this.state.currentVideos.length + 1;
            document.getElementById('video-active').checked = true;
        }

        modal.classList.remove('hidden');
    },

    closeVideoModal() {
        const modal = document.getElementById('modal-video-form');
        if (modal) modal.classList.add('hidden');
    },

    async saveVideo(e) {
        e.preventDefault();
        const id = parseInt(document.getElementById('video-id').value, 10);
        const data = {
            id,
            property_id: parseInt(document.getElementById('video-property-id').value, 10),
            title_fr: document.getElementById('video-title-fr').value.trim(),
            title_en: document.getElementById('video-title-en').value.trim(),
            slug: document.getElementById('video-slug').value.trim(),
            icon: document.getElementById('video-icon').value.trim(),
            icon_bg_color: document.getElementById('video-bg-color').value.trim(),
            icon_text_color: document.getElementById('video-text-color').value.trim(),
            video_url: document.getElementById('video-url').value.trim(),
            display_order: parseInt(document.getElementById('video-order').value, 10) || 0,
            active: document.getElementById('video-active').checked ? 1 : 0
        };

        try {
            const res = await this.apiRequest('api/index.php?action=video_save', 'POST', { video: data });
            this.showToast(res.message || 'Tutoriel vidéo enregistré.');
            this.closeVideoModal();
            await this.fetchAndRenderVideos(data.property_id);
            await this.loadInitialData();
        } catch (err) {
            this.showToast(err.message, 'error');
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

    confirmDeleteVideo(id, title) {
        this.confirmAction(
            `Supprimer ${title} ?`,
            `Le tutoriel vidéo sera définitivement retiré du portail voyageur pour cet hébergement.`,
            async () => {
                try {
                    await this.apiRequest('api/index.php?action=video_delete', 'POST', { id });
                    this.showToast(`Vidéo ${title} supprimée.`);
                    await this.fetchAndRenderVideos(this.state.currentPropertyId);
                    await this.loadInitialData();
                } catch (err) {
                    this.showToast(err.message, 'error');
                }
            }
        );
    },

    previewVideo(url, title) {
        const modal = document.getElementById('modal-video-preview');
        const container = document.getElementById('video-preview-player-container');
        const titleEl = document.getElementById('video-preview-title');
        if (!modal || !container) return;

        titleEl.textContent = title;

        if (url.includes('drive.google.com')) {
            const embedUrl = url.replace(/\/view.*$/, '/preview');
            container.innerHTML = `<iframe src="${embedUrl}" class="w-full h-80 rounded-xl border-0" allow="autoplay; fullscreen"></iframe>`;
        } else {
            container.innerHTML = `<video controls autoplay class="w-full max-h-96 rounded-xl bg-black"><source src="${this.escape(url)}" type="video/mp4">Votre navigateur ne prend pas en charge la balise vidéo.</video>`;
        }

        modal.classList.remove('hidden');
    },

    closeVideoPreview() {
        const modal = document.getElementById('modal-video-preview');
        const container = document.getElementById('video-preview-player-container');
        if (modal) modal.classList.add('hidden');
        if (container) container.innerHTML = '';
    },

    // --------------------------------------------------------------------------
    // SECTION 4 : GESTION DU STATIONNEMENT (PARKING)
    // --------------------------------------------------------------------------
    async loadParkingView() {
        this.updatePropertySelector('parking-property-select', async (pid) => {
            this.state.currentPropertyId = pid;
            await this.fetchAndRenderParking(pid);
        });

        if (this.state.currentPropertyId) {
            await this.fetchAndRenderParking(this.state.currentPropertyId);
        }
    },

    async fetchAndRenderParking(propertyId) {
        try {
            const res = await this.apiRequest(`api/index.php?action=parking_get&property_id=${propertyId}`);
            this.state.currentParkingGeneral = res.general || null;
            this.state.currentParkingLocations = res.locations || [];

            this.fillParkingGeneralForm();
            this.renderParkingLocationsList();
        } catch (err) {
            this.showToast(err.message, 'error');
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
        document.getElementById('parking-tcl-notes-fr').value = gen.tcl_notes_fr || '';
        document.getElementById('parking-tcl-notes-en').value = gen.tcl_notes_en || '';

        this.renderEnglishGeneralPreview(gen);
    },

    renderEnglishGeneralPreview(gen) {
        const preview = document.getElementById('parking-general-en-preview');
        if (!preview) return;

        if (!gen || (!gen.intro_en && !gen.important_rule_en && !gen.street_parking_info_en && !gen.tcl_conditions_en)) {
            preview.innerHTML = `<p class="text-[11px] text-gray-400 italic">Aucune traduction anglaise enregistrée pour l'instant. Saisissez votre texte en français puis enregistrez pour que l'IA génère la traduction.</p>`;
            return;
        }

        const items = [];
        if (gen.intro_en) items.push(`<div><strong class="text-gold block mb-0.5">Introduction (EN) :</strong><span>${this.escape(gen.intro_en)}</span></div>`);
        if (gen.important_rule_en) items.push(`<div><strong class="text-amber-400 block mb-0.5">Alerte / Règle d'or (EN) :</strong><span>${this.escape(gen.important_rule_en)}</span></div>`);
        if (gen.free_parking_notes_en) items.push(`<div><strong class="text-gray-400 block mb-0.5">Notes places gratuites (EN) :</strong><span>${this.escape(gen.free_parking_notes_en)}</span></div>`);
        if (gen.street_parking_info_en) items.push(`<div><strong class="text-gray-400 block mb-0.5">Voirie payante (EN) :</strong><span>${this.escape(gen.street_parking_info_en)}</span></div>`);
        if (gen.street_parking_schedule_en) items.push(`<div><strong class="text-gray-400 block mb-0.5">Horaires payants (EN) :</strong><span>${this.escape(gen.street_parking_schedule_en)}</span></div>`);
        if (gen.street_parking_rates_en) items.push(`<div><strong class="text-gray-400 block mb-0.5">Tarifs voirie (EN) :</strong><pre class="font-mono text-[11px] text-gray-300 whitespace-pre-wrap">${this.escape(gen.street_parking_rates_en)}</pre></div>`);
        if (gen.tcl_conditions_en) items.push(`<div><strong class="text-gray-400 block mb-0.5">Conditions TCL P+R (EN) :</strong><span>${this.escape(gen.tcl_conditions_en)}</span></div>`);
        if (gen.tcl_notes_en) items.push(`<div><strong class="text-gray-400 block mb-0.5">Lignes & accès TCL (EN) :</strong><span>${this.escape(gen.tcl_notes_en)}</span></div>`);

        preview.innerHTML = items.join('');
    },

    async saveParkingGeneral(e) {
        e.preventDefault();
        const propertyId = this.state.currentPropertyId;
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
            tcl_conditions_en: document.getElementById('parking-tcl-cond-en').value.trim(),
            tcl_notes_fr: document.getElementById('parking-tcl-notes-fr').value.trim(),
            tcl_notes_en: document.getElementById('parking-tcl-notes-en').value.trim()
        };

        try {
            const res = await this.apiRequest('api/index.php?action=parking_general_save', 'POST', {
                property_id: propertyId,
                general: data
            });
            this.showToast(res.message || 'Règles générales enregistrées et traduites par IA !', 'success');
            await this.loadInitialData();
            await this.loadParkingData(propertyId);
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    renderParkingLocationsList() {
        const container = document.getElementById('parking-locations-list');
        const filterCat = document.getElementById('parking-locations-filter-cat');
        if (!container) return;

        const cat = filterCat ? filterCat.value : 'all';
        let list = this.state.currentParkingLocations;
        if (cat !== 'all') {
            list = list.filter(l => l.category === cat);
        }

        const catLabels = {
            free_street: { label: 'Rue gratuite', color: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' },
            free_spot: { label: 'Dépose-minute gratuit', color: 'bg-teal-500/10 text-teal-400 border-teal-500/30' },
            private_parking: { label: 'Parking privé / couvert', color: 'bg-amber-500/10 text-amber-400 border-amber-500/30' },
            tcl_pr: { label: 'Parc Relais TCL (P+R)', color: 'bg-red-500/10 text-red-400 border-red-500/30' }
        };

        if (list.length === 0) {
            container.innerHTML = `
                <div class="py-12 text-center text-gray-400 border border-dashed border-night-border rounded-2xl p-6">
                    <span class="text-3xl block mb-2">🅿️</span>
                    <p class="text-base font-bold text-white">Aucun emplacement répertorié pour cette catégorie</p>
                    <p class="text-xs text-gray-500 mt-1 mb-4">Ajoutez des rues gratuites, des garages privés ou des P+R TCL.</p>
                    <button type="button" onclick="AdminApp.openLocationModal(0)" class="px-4 py-2 rounded-xl bg-gold text-white font-bold text-xs shadow-lg shadow-gold/20 hover:bg-gold-light transition">
                        + Ajouter un emplacement
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="space-y-3">
                ${list.map((loc, idx) => {
                    const badge = catLabels[loc.category] || { label: loc.category, color: 'bg-gray-500/10 text-gray-400' };
                    return `
                        <div class="p-4 rounded-2xl bg-night-card border border-night-border hover:border-gold/30 flex items-center justify-between gap-3 transition shadow-md">
                            <div class="flex items-center space-x-3.5 min-w-0">
                                <div class="flex flex-col space-y-1 text-gray-500">
                                    <button type="button" onclick="AdminApp.moveLocation(${loc.id}, -1)" ${idx === 0 ? 'disabled class="opacity-20"' : 'class="hover:text-gold"'} title="Monter">▲</button>
                                    <button type="button" onclick="AdminApp.moveLocation(${loc.id}, 1)" ${idx === list.length - 1 ? 'disabled class="opacity-20"' : 'class="hover:text-gold"'} title="Descendre">▼</button>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border ${badge.color}">${badge.label}</span>
                                        <h5 class="text-sm font-bold text-white truncate">${this.escape(loc.name_fr)}</h5>
                                    </div>
                                    <p class="text-xs text-gray-400 truncate mt-0.5">🇬🇧 ${this.escape(loc.name_en || loc.name_fr)}</p>
                                    <p class="text-xs text-gray-300 mt-1 flex flex-wrap gap-2 text-[11px]">
                                        ${loc.walking_time_fr ? `<span>🚶 ${this.escape(loc.walking_time_fr)}</span>` : ''}
                                        ${loc.transit_time_fr ? `<span>🚇 ${this.escape(loc.transit_time_fr)}</span>` : ''}
                                        ${loc.capacity ? `<span>🚗 ${this.escape(loc.capacity)}</span>` : ''}
                                        ${loc.address ? `<span class="text-gray-500">📍 ${this.escape(loc.address)}</span>` : ''}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <button type="button" onclick="AdminApp.toggleLocationStatus(${loc.id})" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${loc.active == 1 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30'}">
                                    ${loc.active == 1 ? 'Actif' : 'Masqué'}
                                </button>
                                <button type="button" onclick="AdminApp.openLocationModal(${loc.id})" class="p-1.5 rounded-lg bg-white/5 hover:bg-gold hover:text-white text-gray-400 transition" title="Modifier">
                                    ✏️
                                </button>
                                <button type="button" onclick="AdminApp.confirmDeleteLocation(${loc.id}, '${this.escape(loc.name_fr)}')" class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500 hover:text-white text-red-400 transition" title="Supprimer">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    },

    openLocationModal(id = 0) {
        const modal = document.getElementById('modal-location-form');
        const titleEl = document.getElementById('modal-location-title');
        const form = document.getElementById('location-form');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('location-id').value = id;
        document.getElementById('location-property-id').value = this.state.currentPropertyId;

        if (id > 0) {
            const loc = this.state.currentParkingLocations.find(l => l.id == id);
            if (!loc) return;
            titleEl.textContent = `Modifier : ${loc.name_fr}`;
            document.getElementById('location-category').value = loc.category || 'free_street';
            document.getElementById('location-name-fr').value = loc.name_fr || '';
            document.getElementById('location-name-en').value = loc.name_en || '';
            document.getElementById('location-address').value = loc.address || '';
            document.getElementById('location-walking-fr').value = loc.walking_time_fr || '';
            document.getElementById('location-walking-en').value = loc.walking_time_en || '';
            document.getElementById('location-walking-min').value = loc.walking_minutes || '';
            document.getElementById('location-transit-fr').value = loc.transit_time_fr || '';
            document.getElementById('location-transit-en').value = loc.transit_time_en || '';
            document.getElementById('location-max-height').value = loc.max_height || '';
            document.getElementById('location-capacity').value = loc.capacity || '';
            document.getElementById('location-hours-fr').value = loc.opening_hours_fr || '';
            document.getElementById('location-hours-en').value = loc.opening_hours_en || '';
            document.getElementById('location-rates-fr').value = loc.rates_fr || '';
            document.getElementById('location-rates-en').value = loc.rates_en || '';
            document.getElementById('location-vehicles-fr').value = loc.suitable_vehicles_fr || '';
            document.getElementById('location-vehicles-en').value = loc.suitable_vehicles_en || '';
            document.getElementById('location-reach-fr').value = loc.how_to_reach_fr || '';
            document.getElementById('location-reach-en').value = loc.how_to_reach_en || '';
            document.getElementById('location-operator').value = loc.operator || '';
            document.getElementById('location-booking-url').value = loc.booking_url || '';
            document.getElementById('location-notes-fr').value = loc.notes_fr || '';
            document.getElementById('location-notes-en').value = loc.notes_en || '';
            document.getElementById('location-order').value = loc.display_order || 0;
            document.getElementById('location-active').checked = (loc.active == 1);
        } else {
            titleEl.textContent = 'Ajouter un emplacement de stationnement';
            document.getElementById('location-category').value = 'free_street';
            document.getElementById('location-order').value = this.state.currentParkingLocations.length + 1;
            document.getElementById('location-active').checked = true;
        }

        modal.classList.remove('hidden');
    },

    closeLocationModal() {
        const modal = document.getElementById('modal-location-form');
        if (modal) modal.classList.add('hidden');
    },

    async saveLocation(e) {
        e.preventDefault();
        const id = parseInt(document.getElementById('location-id').value, 10);
        const data = {
            id,
            property_id: parseInt(document.getElementById('location-property-id').value, 10),
            category: document.getElementById('location-category').value,
            name_fr: document.getElementById('location-name-fr').value.trim(),
            name_en: document.getElementById('location-name-en').value.trim(),
            address: document.getElementById('location-address').value.trim(),
            walking_time_fr: document.getElementById('location-walking-fr').value.trim(),
            walking_time_en: document.getElementById('location-walking-en').value.trim(),
            walking_minutes: parseInt(document.getElementById('location-walking-min').value, 10) || null,
            transit_time_fr: document.getElementById('location-transit-fr').value.trim(),
            transit_time_en: document.getElementById('location-transit-en').value.trim(),
            max_height: document.getElementById('location-max-height').value.trim(),
            capacity: document.getElementById('location-capacity').value.trim(),
            opening_hours_fr: document.getElementById('location-hours-fr').value.trim(),
            opening_hours_en: document.getElementById('location-hours-en').value.trim(),
            rates_fr: document.getElementById('location-rates-fr').value.trim(),
            rates_en: document.getElementById('location-rates-en').value.trim(),
            suitable_vehicles_fr: document.getElementById('location-vehicles-fr').value.trim(),
            suitable_vehicles_en: document.getElementById('location-vehicles-en').value.trim(),
            how_to_reach_fr: document.getElementById('location-reach-fr').value.trim(),
            how_to_reach_en: document.getElementById('location-reach-en').value.trim(),
            operator: document.getElementById('location-operator').value.trim(),
            booking_url: document.getElementById('location-booking-url').value.trim(),
            notes_fr: document.getElementById('location-notes-fr').value.trim(),
            notes_en: document.getElementById('location-notes-en').value.trim(),
            display_order: parseInt(document.getElementById('location-order').value, 10) || 0,
            active: document.getElementById('location-active').checked ? 1 : 0
        };

        try {
            const res = await this.apiRequest('api/index.php?action=parking_location_save', 'POST', { location: data });
            this.showToast(res.message || 'Emplacement enregistré.');
            this.closeLocationModal();
            await this.fetchAndRenderParking(data.property_id);
            await this.loadInitialData();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async moveLocation(id, direction) {
        const list = [...this.state.currentParkingLocations];
        const idx = list.findIndex(l => l.id == id);
        if (idx < 0) return;

        const targetIdx = idx + direction;
        if (targetIdx < 0 || targetIdx >= list.length) return;

        const item = list.splice(idx, 1)[0];
        list.splice(targetIdx, 0, item);

        const orderArray = list.map(l => l.id);
        try {
            await this.apiRequest('api/index.php?action=parking_location_reorder', 'POST', { order: orderArray });
            this.state.currentParkingLocations = list;
            this.renderParkingLocationsList();
            this.showToast('Ordre des emplacements actualisé.');
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async toggleLocationStatus(id) {
        try {
            await this.apiRequest('api/index.php?action=parking_location_toggle', 'POST', { id });
            const loc = this.state.currentParkingLocations.find(l => l.id == id);
            if (loc) loc.active = (loc.active == 1 ? 0 : 1);
            this.renderParkingLocationsList();
            this.showToast('Statut de l\'emplacement actualisé.');
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    confirmDeleteLocation(id, name) {
        this.confirmAction(
            `Supprimer ${name} ?`,
            `L'emplacement de parking sera retiré de la liste pour cet hébergement.`,
            async () => {
                try {
                    await this.apiRequest('api/index.php?action=parking_location_delete', 'POST', { id });
                    this.showToast(`Emplacement ${name} supprimé.`);
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
                            <button type="button" onclick="AdminApp.confirmDeleteUser(${u.id}, '${this.escape(u.name)}')" class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500 hover:text-white text-red-400 transition" title="Supprimer">
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

    confirmDeleteUser(id, name) {
        this.confirmAction(
            `Supprimer le compte de ${name} ?`,
            `L'administrateur n'aura plus aucun accès à la console d'administration.`,
            async () => {
                try {
                    await this.apiRequest('api/index.php?action=user_delete', 'POST', { id });
                    this.showToast(`Compte de ${name} supprimé.`);
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

    confirmAction(title, message, onConfirm) {
        const modal = document.getElementById('modal-confirmation');
        const titleEl = document.getElementById('confirm-title');
        const msgEl = document.getElementById('confirm-message');
        const btnConfirm = document.getElementById('confirm-btn-yes');

        if (!modal || !btnConfirm) {
            if (confirm(`${title}\n\n${message}`)) onConfirm();
            return;
        }

        titleEl.textContent = title;
        msgEl.textContent = message;

        btnConfirm.onclick = () => {
            modal.classList.add('hidden');
            onConfirm();
        };

        modal.classList.remove('hidden');
    },

    closeConfirmModal() {
        const modal = document.getElementById('modal-confirmation');
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
    // CONFIGURATION & GESTION DE L'IA (GEMINI / CHATGPT / CLAUDE)
    // --------------------------------------------------------------------------

    openAiSettingsModal() {
        const modal = document.getElementById('modal-ai-settings');
        if (!modal) return;

        const s = this.state.settings || {};
        const providerSelect = document.getElementById('ai-provider-select');
        if (providerSelect) {
            providerSelect.value = s.ai_provider || 'gemini';
        }

        const keyGemini = document.getElementById('ai-key-gemini');
        if (keyGemini) {
            keyGemini.value = s.masked_key_gemini || '';
        }
        const keyOpenai = document.getElementById('ai-key-openai');
        if (keyOpenai) {
            keyOpenai.value = s.masked_key_openai || '';
        }
        const keyClaude = document.getElementById('ai-key-claude');
        if (keyClaude) {
            keyClaude.value = s.masked_key_claude || '';
        }

        this.handleAiProviderChange();

        const resultEl = document.getElementById('ai-test-result');
        if (resultEl) {
            resultEl.classList.add('hidden');
            resultEl.innerHTML = '';
        }

        modal.classList.remove('hidden');
    },

    closeAiSettingsModal() {
        const modal = document.getElementById('modal-ai-settings');
        if (modal) modal.classList.add('hidden');
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

        const testBtn = document.getElementById('btn-test-ai');
        const resultEl = document.getElementById('ai-test-result');
        if (!resultEl) return;

        if (testBtn) {
            testBtn.disabled = true;
            testBtn.innerHTML = `<span>⏳</span><span>Test en cours...</span>`;
        }

        resultEl.classList.remove('hidden');
        resultEl.className = 'p-3 rounded-xl text-xs space-y-1 border bg-night/80 border-gold/30 text-gold-light';
        resultEl.innerHTML = `<p class="flex items-center gap-2"><span>🔄</span><span>Envoi d'une requête de test à ${provider.toUpperCase()}...</span></p>`;

        try {
            const res = await this.apiRequest('api/index.php?action=test_ai', 'POST', {
                provider,
                api_key: apiKey
            });

            resultEl.className = 'p-3.5 rounded-xl text-xs space-y-1.5 border bg-emerald-500/10 border-emerald-500/30 text-emerald-300';
            resultEl.innerHTML = `
                <div class="flex items-center space-x-2 font-bold text-emerald-400">
                    <span>✅</span>
                    <span>Connexion réussie avec ${res.provider.toUpperCase()} (${res.duration_ms} ms)</span>
                </div>
                <div class="text-[11px] text-gray-300 space-y-0.5 pt-1">
                    <p><strong class="text-gray-400">FR :</strong> "${this.escape(res.sample_fr)}"</p>
                    <p><strong class="text-emerald-400">EN :</strong> "${this.escape(res.sample_en)}"</p>
                </div>
            `;
            this.showToast(`Connexion à ${provider.toUpperCase()} validée avec succès !`, 'success');
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
                testBtn.innerHTML = `<span>⚡</span><span>Tester la connexion</span>`;
            }
        }
    },

    async saveAiSettings(e) {
        e.preventDefault();
        const select = document.getElementById('ai-provider-select');
        const provider = select ? select.value : 'gemini';

        const payload = {
            ai_provider: provider,
            ai_api_key_gemini: (document.getElementById('ai-key-gemini')?.value || '').trim(),
            ai_api_key_openai: (document.getElementById('ai-key-openai')?.value || '').trim(),
            ai_api_key_claude: (document.getElementById('ai-key-claude')?.value || '').trim()
        };

        try {
            const res = await this.apiRequest('api/index.php?action=settings_save', 'POST', {
                settings: payload
            });

            this.state.settings = res.settings;
            this.updateAiBadges();
            this.showToast(res.message || 'Configuration IA enregistrée avec succès.', 'success');
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
    }
};

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    AdminApp.init();
});

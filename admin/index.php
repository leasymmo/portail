<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/Admin/Auth.php';

use Cdh\Portal\Admin\Auth;

// Exige une authentification valide (redirection automatique vers login.php si non connecté)
$currentUser = Auth::requireAuth(false);
$isDev = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/portail/dev') !== false);
?>
<!DOCTYPE html>
<html lang="fr" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Carré d'Hôtes</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../assets/icons/apple-touch-icon.png">
    
    <!-- Tailwind CSS (avec configuration charte Carré d'Hôtes) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#ba9641',
                        'gold-light': '#d4af53',
                        'gold-dark': '#9b7b32',
                        night: '#0b0f19',
                        'night-card': '#131b2e',
                        'night-border': '#223048'
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="assets/css/admin.css?v=1.3">
</head>
<body class="bg-night text-gray-100 h-screen max-h-screen flex flex-col antialiased selection:bg-gold selection:text-white overflow-hidden">

    <!-- CONTENEUR GLOBAL APP -->
    <div class="flex-1 flex min-h-0 overflow-hidden">

        <!-- =============================================================== -->
        <!-- SIDEBAR LATÉRALE                                                -->
        <!-- =============================================================== -->
        <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 md:w-64 bg-night-card border-r border-night-border flex flex-col justify-between transform -translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out md:static flex-shrink-0 h-full max-h-screen overflow-y-auto select-none">
            <!-- Haut de la Sidebar -->
            <div>
                <!-- Logo & Titre & Bouton Réduire -->
                <div class="p-4 sm:p-5 border-b border-night-border/70 flex items-center justify-between">
                    <div id="sidebar-logo-container" class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-xl bg-gold/15 border border-gold/30 flex items-center justify-center text-gold shadow-md shadow-gold/10 flex-shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="3" rx="4"/>
                                <path d="m9 9 6 6m0-6-6 6"/>
                            </svg>
                        </div>
                        <div class="sidebar-text-label transition-opacity duration-200">
                            <span class="font-serif-luxury font-bold text-white text-base tracking-wide block truncate">Carré d'Hôtes</span>
                            <span class="text-[10px] uppercase tracking-widest text-gold font-semibold block truncate">Administration</span>
                        </div>
                    </div>

                    <!-- Bouton Réduire sur Bureau -->
                    <button type="button" id="sidebar-collapse-toggle" onclick="AdminApp.toggleSidebarCollapse()" class="hidden md:flex p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition flex-shrink-0" title="Réduire / Agrandir la barre latérale (ou Ctrl+B)">
                        <svg id="sidebar-collapse-icon" class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    </button>

                    <!-- Bouton Fermer sur Mobile -->
                    <button type="button" onclick="AdminApp.closeMobileMenu()" class="md:hidden p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition flex-shrink-0" title="Fermer le menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Navigation principale -->
                <nav class="p-3 space-y-1.5" aria-label="Menu principal">
                    <button type="button" data-tab="dashboard" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition" title="Tableau de bord">
                        <span class="text-base flex-shrink-0">📊</span>
                        <span class="sidebar-text-label truncate">Tableau de bord</span>
                    </button>

                    <button type="button" data-tab="properties" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition" title="Hébergements">
                        <span class="text-base flex-shrink-0">🏠</span>
                        <span class="sidebar-text-label truncate">Hébergements</span>
                    </button>

                    <button type="button" data-tab="videos" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition" title="Vidéos & Tutoriels">
                        <span class="text-base flex-shrink-0">🎬</span>
                        <span class="sidebar-text-label truncate">Vidéos & Tutoriels</span>
                    </button>

                    <button type="button" data-tab="parking" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition" title="Stationnement & Parkings">
                        <span class="text-base flex-shrink-0">🅿️</span>
                        <span class="sidebar-text-label truncate">Stationnement</span>
                    </button>

                    <button type="button" data-tab="logs" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition" title="Journal d'audit">
                        <span class="text-base flex-shrink-0">📜</span>
                        <span class="sidebar-text-label truncate">Journal d'audit</span>
                    </button>

                    <button type="button" id="nav-item-users" data-tab="users" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition <?= $currentUser['role'] !== 'superadmin' ? 'hidden' : '' ?>" title="Administrateurs">
                        <span class="text-base flex-shrink-0">👥</span>
                        <span class="sidebar-text-label truncate">Administrateurs</span>
                    </button>

                    <div class="pt-2 border-t border-night-border/50 my-1"></div>

                    <button type="button" onclick="AdminApp.openSettingsModal()" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent text-gray-300 hover:text-white hover:bg-gold/10 hover:border-gold/30 transition group" title="Configuration (IA, R2 & Stockage)">
                        <div class="flex items-center space-x-3 overflow-hidden">
                            <span class="text-base flex-shrink-0 group-hover:scale-110 transition-transform">⚙️</span>
                            <span class="sidebar-text-label text-gold-light group-hover:text-gold font-bold truncate">Configuration</span>
                        </div>
                        <span id="nav-ai-provider-badge" class="sidebar-badge text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-md bg-gold/15 text-gold border border-gold/30 flex-shrink-0">IA & Médias</span>
                    </button>
                </nav>
            </div>

            <!-- Bas de la Sidebar : Profil & Déconnexion -->
            <div class="p-3.5 sm:p-4 border-t border-night-border/70 bg-night/30">
                <div id="user-footer-wrapper" class="flex items-center justify-between gap-2">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <div id="user-display-avatar" class="w-8 h-8 rounded-full bg-gold/20 text-gold border border-gold/40 flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm" title="<?= htmlspecialchars($currentUser['name']) ?>">
                            AD
                        </div>
                        <div class="sidebar-text-label min-w-0">
                            <p id="user-display-name" class="text-xs font-bold text-white truncate"><?= htmlspecialchars($currentUser['name']) ?></p>
                            <p id="user-display-role" class="text-[10px] text-gray-400 capitalize truncate"><?= htmlspecialchars($currentUser['role']) ?></p>
                        </div>
                    </div>
                    <div id="user-actions-wrapper" class="flex items-center space-x-1 flex-shrink-0">
                        <button type="button" onclick="AdminApp.openProfileModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-gold hover:bg-white/5 transition" title="Modifier mon mot de passe">
                            ⚙️
                        </button>
                        <button type="button" onclick="AdminApp.logout()" class="p-1.5 rounded-lg text-gray-400 hover:text-red-400 hover:bg-white/5 transition" title="Se déconnecter">
                            🚪
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Overlay sombre pour menu mobile -->
        <div id="mobile-menu-overlay" class="fixed inset-0 bg-night/80 z-30 hidden md:hidden backdrop-blur-xs"></div>

        <!-- =============================================================== -->
        <!-- ZONE PRINCIPALE DE CONTENU                                      -->
        <!-- =============================================================== -->
        <div id="main-content-scrollable" class="flex-1 flex flex-col min-w-0 h-full max-h-screen overflow-y-auto">

            <!-- Barre supérieure (Header) -->
            <header class="sticky top-0 z-20 bg-night/80 backdrop-blur-md border-b border-night-border/80 px-4 sm:px-6 py-3.5 flex items-center justify-between">
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <button type="button" id="mobile-menu-toggle" class="md:hidden p-2 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition" aria-label="Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold <?= $isDev ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= $isDev ? 'bg-amber-400' : 'bg-emerald-400' ?> mr-1.5 animate-pulse"></span>
                        <span class="hidden sm:inline">Environnement </span><?= $isDev ? 'DEV (Staging)' : 'PRODUCTION' ?>
                    </span>
                    <span id="global-loading-indicator" class="hidden text-xs text-gold flex items-center space-x-1.5 font-medium animate-pulse">
                        <span>Actualisation...</span>
                    </span>
                </div>

                <div class="flex items-center space-x-2.5">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white border border-night-border text-xs font-semibold transition flex items-center space-x-1.5 shadow-sm">
                        <span>👁️</span>
                        <span class="hidden sm:inline">Portail Voyageurs</span>
                    </a>
                </div>
            </header>

            <!-- Contenu dynamique par onglet -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">

                <!-- ----------------------------------------------------------- -->
                <!-- SECTION 1 : TABLEAU DE BORD                                 -->
                <!-- ----------------------------------------------------------- -->
                <section id="section-dashboard" class="admin-section space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold font-serif-luxury text-white">Tableau de bord</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Vue globale des hébergements et contenus du portail.</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="AdminApp.openPropertyModal(0)" class="px-3.5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-1.5">
                                <span>+</span>
                                <span>Nouvel hébergement</span>
                            </button>
                        </div>
                    </div>

                    <!-- 4 KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                        <div class="p-4 sm:p-5 rounded-2xl bg-night-card border border-night-border relative overflow-hidden shadow-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Hébergements</span>
                                <span class="text-lg">🏠</span>
                            </div>
                            <div id="kpi-properties" class="text-2xl sm:text-3xl font-bold text-white mt-2">-</div>
                            <span class="text-[11px] text-emerald-400 mt-1 block">Actifs / Total</span>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-night-card border border-night-border relative overflow-hidden shadow-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Tutoriels Vidéos</span>
                                <span class="text-lg">🎬</span>
                            </div>
                            <div id="kpi-videos" class="text-2xl sm:text-3xl font-bold text-white mt-2">-</div>
                            <span class="text-[11px] text-blue-400 mt-1 block">Tutoriels en ligne</span>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-night-card border border-night-border relative overflow-hidden shadow-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Guides Parking</span>
                                <span class="text-lg">🅿️</span>
                            </div>
                            <div id="kpi-parkings" class="text-2xl sm:text-3xl font-bold text-white mt-2">-</div>
                            <span class="text-[11px] text-amber-400 mt-1 block">Hébergements équipés</span>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-night-card border border-night-border relative overflow-hidden shadow-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Emplacements</span>
                                <span class="text-lg">📍</span>
                            </div>
                            <div id="kpi-locations" class="text-2xl sm:text-3xl font-bold text-white mt-2">-</div>
                            <span class="text-[11px] text-teal-400 mt-1 block">Rues, Garages & P+R</span>
                        </div>
                    </div>

                    <!-- Grille Deux Colonnes : Logements & Logs -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Table des hébergements (2 colonnes) -->
                        <div class="lg:col-span-2 rounded-2xl bg-night-card border border-night-border p-5 shadow-lg flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Aperçu des hébergements</h3>
                                    <button type="button" onclick="AdminApp.switchTab('properties')" class="text-xs text-gold hover:underline font-semibold">
                                        Voir tout →
                                    </button>
                                </div>
                                <!-- Version Mobile : Cartes tactiles fluides sans scroll horizontal -->
                                <div class="block md:hidden space-y-3" id="dashboard-properties-mobile-cards">
                                    <div class="py-6 text-center text-gray-500">Chargement des hébergements...</div>
                                </div>

                                <!-- Version Bureau / Tablette : Tableau structuré -->
                                <div class="hidden md:block overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead>
                                            <tr class="border-b border-night-border text-gray-400 text-[11px] uppercase tracking-wider">
                                                <th class="py-2.5 px-4 font-semibold">Logement</th>
                                                <th class="py-2.5 px-4 font-semibold">Statut</th>
                                                <th class="py-2.5 px-4 font-semibold">Vidéos</th>
                                                <th class="py-2.5 px-4 font-semibold">Parking</th>
                                                <th class="py-2.5 px-4 font-semibold text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dashboard-properties-tbody">
                                            <tr><td colspan="5" class="py-6 text-center text-gray-500">Chargement...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Flux des activités récentes (1 colonne) -->
                        <div class="rounded-2xl bg-night-card border border-night-border p-5 shadow-lg flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Activité récente</h3>
                                    <button type="button" onclick="AdminApp.switchTab('logs')" class="text-xs text-gold hover:underline font-semibold">
                                        Journal →
                                    </button>
                                </div>
                                <div id="dashboard-recent-logs" class="space-y-1">
                                    <p class="text-xs text-gray-500 italic py-4">Chargement...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ----------------------------------------------------------- -->
                <!-- SECTION 2 : GESTION DES HÉBERGEMENTS                        -->
                <!-- ----------------------------------------------------------- -->
                <section id="section-properties" class="admin-section hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold font-serif-luxury text-white">Gestion des hébergements</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Ajoutez, modifiez ou activez les logements du portail.</p>
                        </div>
                        <button type="button" onclick="AdminApp.openPropertyModal(0)" class="px-3.5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-1.5 self-start sm:self-auto">
                            <span>+</span>
                            <span>Ajouter un hébergement</span>
                        </button>
                    </div>

                    <!-- Barre de recherche et filtre -->
                    <div class="p-3.5 rounded-2xl bg-night-card border border-night-border flex flex-col sm:flex-row items-center gap-3">
                        <div class="relative flex-1 w-full">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">🔍</span>
                            <input type="text" id="properties-search-input" oninput="AdminApp.renderProperties()" placeholder="Rechercher par nom, slug ou adresse..."
                                   class="w-full pl-9 pr-4 py-2 rounded-xl bg-night border border-night-border text-xs text-white placeholder-gray-500 focus:outline-none focus:border-gold">
                        </div>
                        <div class="w-full sm:w-48">
                            <select id="properties-filter-status" onchange="AdminApp.renderProperties()"
                                    class="w-full py-2 px-3 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                                <option value="all">Tous les statuts</option>
                                <option value="1">Actifs uniquement</option>
                                <option value="0">Inactifs uniquement</option>
                            </select>
                        </div>
                    </div>

                    <!-- Grille des hébergements -->
                    <div id="properties-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="col-span-full py-8 text-center text-gray-400">Chargement des hébergements...</div>
                    </div>
                </section>

                <!-- ----------------------------------------------------------- -->
                <!-- SECTION 3 : GESTION DES VIDÉOS & TUTORIELS                  -->
                <!-- ----------------------------------------------------------- -->
                <section id="section-videos" class="admin-section hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold font-serif-luxury text-white">Vidéos tutoriels</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Gérez et ordonnez les vidéos pratiques pour chaque logement.</p>
                        </div>
                        <button type="button" onclick="AdminApp.openVideoModal(0)" class="px-3.5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-1.5 self-start sm:self-auto">
                            <span>+</span>
                            <span>Ajouter une vidéo</span>
                        </button>
                    </div>

                    <!-- Sélecteur de logement -->
                    <div class="p-4 rounded-2xl bg-night-card border border-night-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <label class="text-xs font-semibold text-gray-300 uppercase tracking-wider flex items-center space-x-2">
                            <span>🏠 Choisir l'hébergement :</span>
                        </label>
                        <select id="videos-property-select" class="py-2 px-3 rounded-xl bg-night border border-night-border text-xs text-white font-semibold focus:outline-none focus:border-gold max-w-md w-full">
                            <!-- Rempli dynamiquement -->
                        </select>
                    </div>

                    <!-- Liste ordonnable des vidéos -->
                    <div id="videos-list-container">
                        <div class="py-8 text-center text-gray-400">Sélectionnez un hébergement pour afficher ses tutoriels.</div>
                    </div>
                </section>

                <!-- ----------------------------------------------------------- -->
                <!-- SECTION 4 : GESTION DU STATIONNEMENT                        -->
                <!-- ----------------------------------------------------------- -->
                <section id="section-parking" class="admin-section hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold font-serif-luxury text-white">Stationnement & Parkings</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Configurez les règles bilingues et répertoriez les rues, parkings et P+R.</p>
                        </div>
                    </div>

                    <!-- Sélecteur de logement -->
                    <div class="p-4 rounded-2xl bg-night-card border border-night-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <label class="text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            <span>🏠 Choisir l'hébergement :</span>
                        </label>
                        <select id="parking-property-select" class="py-2 px-3 rounded-xl bg-night border border-night-border text-xs text-white font-semibold focus:outline-none focus:border-gold max-w-md w-full">
                            <!-- Rempli dynamiquement -->
                        </select>
                    </div>

                    <!-- Sous-onglets Stationnement (Navigation segmented controls claire et intuitive) -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-night-border/70">
                        <div class="grid grid-cols-1 sm:inline-flex w-full sm:w-auto p-1.5 rounded-2xl bg-night-card border border-night-border shadow-inner gap-1">
                            <button type="button" id="parking-subtab-btn-general" onclick="AdminApp.switchParkingSubtab('general')"
                                    class="parking-subtab-btn px-4 py-2.5 sm:py-2 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 bg-gold text-white shadow-md shadow-gold/25"
                                    title="Informations générales, consignes, voirie et conditions TCL">
                                <span>📋</span>
                                <span>Règles Générales & Voirie</span>
                            </button>
                            <button type="button" id="parking-subtab-btn-locations" onclick="AdminApp.switchParkingSubtab('locations')"
                                    class="parking-subtab-btn px-4 py-2.5 sm:py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-white/5 transition flex items-center justify-center space-x-2"
                                    title="Rues gratuites, dépose-minute, parkings couverts privés et P+R">
                                <span>📍</span>
                                <span>Emplacements & Garages</span>
                                <span id="parking-subtab-locations-badge" class="px-2 py-0.5 rounded-full text-[10px] bg-white/10 text-gray-300 font-bold">0</span>
                            </button>
                        </div>

                        <div class="flex items-center space-x-2 w-full sm:w-auto">
                            <button type="button" onclick="AdminApp.openLocationModal(0)" class="w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-xl bg-gold/15 hover:bg-gold text-gold hover:text-white border border-gold/40 text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm">
                                <span>➕</span>
                                <span>Ajouter un emplacement</span>
                            </button>
                        </div>
                    </div>

                    <!-- ÉTAT DE CHARGEMENT ÉLÉGANT (Affiché lors du changement d'hébergement) -->
                    <div id="parking-loading-state" class="hidden rounded-2xl bg-night-card border border-gold/30 p-12 text-center space-y-4 shadow-xl">
                        <div class="relative w-14 h-14 mx-auto">
                            <div class="absolute inset-0 rounded-full border-2 border-gold/20"></div>
                            <div class="absolute inset-0 rounded-full border-2 border-gold border-t-transparent animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center text-xl">🅿️</div>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-white tracking-wide">Chargement des données de stationnement...</h4>
                            <p id="parking-loading-property-label" class="text-xs text-gray-400">Actualisation des règles et emplacements pour le logement sélectionné</p>
                        </div>
                    </div>

                    <!-- CONTENEUR DES SOUS-ONGLETS -->
                    <div id="parking-subtabs-wrapper" class="space-y-4">
                        <!-- SOUS-ONGLET A : RÈGLES GÉNÉRALES -->
                        <div id="parking-subtab-general" class="space-y-4">
                        <form id="parking-general-form" onsubmit="AdminApp.saveParkingGeneral(event)" class="rounded-2xl bg-night-card border border-night-border p-5 sm:p-6 space-y-5 shadow-lg">
                            <!-- Introduction -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">Message d'accueil & Introduction</label>
                                <textarea id="parking-intro-fr" rows="2" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" placeholder="Les meilleures solutions pour vous garer sereinement..."></textarea>
                            </div>

                            <!-- Bannière alerte impérative (Alerte rouge) -->
                            <div class="p-4 rounded-xl bg-amber-500/5 border border-amber-500/20 space-y-2">
                                <div class="flex items-center space-x-2 text-amber-400 font-bold text-xs">
                                    <span>⚠️</span>
                                    <span>Règle d'or impérative / Alerte (Optionnel)</span>
                                </div>
                                <textarea id="parking-rule-fr" rows="2" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" placeholder="Ex: RÈGLE STRICTE : Stationnement formellement INTERDIT dans la cour..."></textarea>
                            </div>

                            <!-- Notes voirie payante & Horaires -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">Astuces & notes sur les places gratuites</label>
                                <input type="text" id="parking-free-notes-fr" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" placeholder="Ex: Rues non violettes gratuites sur EasyPark...">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">Périmètre de voirie payante</label>
                                <textarea id="parking-street-info-fr" rows="2" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" placeholder="Ex: Stationnement payant à Villeurbanne dans l'ensemble du quartier..."></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-300 mb-1">Horaires payants voirie</label>
                                    <input type="text" id="parking-street-sched-fr" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" placeholder="Ex: Lun-Sam : 9h-19h (Gratuit nuit, dimanche et tout le mois d'août)">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-300 mb-1">Application mobile recommandée</label>
                                    <input type="text" id="parking-street-app" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" value="EasyPark">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">Grille tarifaire voirie</label>
                                <textarea id="parking-street-rates-fr" rows="3" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold" placeholder="• Citadines & Électriques : 1 €/h&#10;• Standards : 2 €/h&#10;• Gros modèles & SUV : 3,50 €/h"></textarea>
                            </div>

                            <!-- Conditions TCL -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">Conditions de gratuité Parcs Relais TCL (P+R)</label>
                                <textarea id="parking-tcl-cond-fr" rows="2" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" placeholder="Ex: Gratuit avec aller-retour TCL validé le jour même (scan du titre à la sortie). Sans validation : 20 €."></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">Lignes, métros et accès conseillés TCL</label>
                                <input type="text" id="parking-tcl-notes-fr" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold" placeholder="Ex: Parkings relais Laurent Bonnevay et La Soie situés à 10-15 min en Métro A.">
                            </div>

                            <!-- Champs anglais masqués (éditables directement via la vue côte-à-côte) -->
                            <div class="hidden" aria-hidden="true">
                                <textarea id="parking-intro-en"></textarea>
                                <textarea id="parking-rule-en"></textarea>
                                <input type="hidden" id="parking-free-notes-en">
                                <textarea id="parking-street-info-en"></textarea>
                                <input type="hidden" id="parking-street-sched-en">
                                <textarea id="parking-street-rates-en"></textarea>
                                <textarea id="parking-tcl-cond-en"></textarea>
                                <input type="hidden" id="parking-tcl-notes-en">
                            </div>

                            <div class="pt-4 flex flex-wrap items-center justify-between gap-3 border-t border-night-border/50">
                                <button type="button" onclick="AdminApp.openDirectReviewModal('parking_general')" 
                                        class="text-xs text-gray-400 hover:text-gold flex items-center space-x-1.5 transition py-1"
                                        title="Ouvrir la vue côte-à-côte pour consulter ou ajuster manuellement la version anglaise">
                                    <span>✏️</span>
                                    <span>Saisir l'anglais manuellement (Vue côte-à-côte)</span>
                                </button>
                                <button type="submit" id="btn-save-parking-general" 
                                        class="px-5 py-2.5 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-2 ml-auto">
                                    <span id="btn-save-general-icon">💾</span>
                                    <span id="btn-save-general-text">Enregistrer les règles</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- SOUS-ONGLET B : EMPLACEMENTS DÉTAILLÉS -->
                    <div id="parking-subtab-locations" class="hidden space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-2 w-full sm:w-auto">
                                <select id="parking-locations-filter-cat" onchange="AdminApp.renderParkingLocationsList()" class="w-full sm:w-auto py-2.5 sm:py-2 px-3 rounded-xl bg-night-card border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                                    <option value="all">Toutes les catégories</option>
                                    <option value="free_street">Rues gratuites</option>
                                    <option value="free_spot">Dépose-minute gratuit</option>
                                    <option value="private_parking">Parkings privés & couverts</option>
                                    <option value="tcl_pr">Parcs Relais TCL (P+R)</option>
                                </select>
                            </div>
                            <button type="button" onclick="AdminApp.openLocationModal(0)" class="w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center justify-center space-x-1.5 self-start sm:self-auto">
                                <span>+</span>
                                <span>Ajouter un emplacement</span>
                            </button>
                        </div>

                        <div id="parking-locations-list">
                            <div class="py-8 text-center text-gray-400">Chargement des emplacements...</div>
                        </div>
                    </div>
                </div>
            </section>

                <!-- ----------------------------------------------------------- -->
                <!-- SECTION 5 : JOURNAL D'AUDIT (LOGS)                          -->
                <!-- ----------------------------------------------------------- -->
                <section id="section-logs" class="admin-section hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold font-serif-luxury text-white">Journal d'audit & Traçabilité</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Historique chronologique complet des actions d'administration.</p>
                        </div>
                        <button type="button" onclick="AdminApp.loadLogsView()" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300 transition">
                            🔄 Actualiser
                        </button>
                    </div>

                    <div class="rounded-2xl bg-night-card border border-night-border overflow-hidden shadow-lg">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-night-border text-gray-400 text-[11px] uppercase tracking-wider bg-night/50">
                                        <th class="py-3 px-4 font-semibold">Date & Heure</th>
                                        <th class="py-3 px-4 font-semibold">Utilisateur</th>
                                        <th class="py-3 px-4 font-semibold">Action</th>
                                        <th class="py-3 px-4 font-semibold">Entité ciblée</th>
                                        <th class="py-3 px-4 font-semibold text-right">Détails</th>
                                    </tr>
                                </thead>
                                <tbody id="logs-tbody">
                                    <tr><td colspan="5" class="py-8 text-center text-gray-500">Chargement...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ----------------------------------------------------------- -->
                <!-- SECTION 6 : ADMINISTRATEURS (SUPERADMIN ONLY)               -->
                <!-- ----------------------------------------------------------- -->
                <section id="section-users" class="admin-section hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold font-serif-luxury text-white">Gestion des administrateurs</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Comptes d'accès à la console Carré d'Hôtes et attribution des rôles.</p>
                        </div>
                        <button type="button" onclick="AdminApp.openUserModal(0)" class="px-3.5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-1.5">
                            <span>+</span>
                            <span>Ajouter un administrateur</span>
                        </button>
                    </div>

                    <div class="rounded-2xl bg-night-card border border-night-border overflow-hidden shadow-lg">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-night-border text-gray-400 text-[11px] uppercase tracking-wider bg-night/50">
                                        <th class="py-3 px-4 font-semibold">Collaborateur</th>
                                        <th class="py-3 px-4 font-semibold">Rôle</th>
                                        <th class="py-3 px-4 font-semibold">Statut</th>
                                        <th class="py-3 px-4 font-semibold">Dernière connexion</th>
                                        <th class="py-3 px-4 font-semibold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="users-tbody">
                                    <tr><td colspan="5" class="py-8 text-center text-gray-500">Chargement...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODALES DE FORMULAIRES & INTERACTION                                -->
    <!-- =================================================================== -->

    <!-- 1. MODALE HÉBERGEMENT (AJOUT / MODIFICATION) -->
    <div id="modal-property-form" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-2xl sm:rounded-3xl w-full max-w-2xl max-h-[92vh] overflow-y-auto p-4 sm:p-6 sm:p-7 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-4 mb-5">
                <h3 id="modal-property-title" class="text-base font-bold text-white">Ajouter un hébergement</h3>
                <button type="button" onclick="AdminApp.closePropertyModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>

            <form id="property-form" onsubmit="AdminApp.saveProperty(event)" class="space-y-4">
                <input type="hidden" id="property-id" value="0">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Nom du logement *</label>
                        <input type="text" id="property-name" required oninput="AdminApp.onPropertyNameInput(this.value)" placeholder="Ex: Bataille"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-300">Slug URL unique *</label>
                            <span class="text-[10px] text-gold font-mono">Dossier Cloudflare R2</span>
                        </div>
                        <input type="text" id="property-slug" required oninput="AdminApp.onPropertySlugInput(this.value)" placeholder="bataille"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Adresse physique complète</label>
                    <input type="text" id="property-address" placeholder="91 rue Bataille, 69008 Lyon"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3.5 rounded-2xl bg-night/50 border border-night-border/40">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">SSID Réseau Wi-Fi</label>
                        <input type="text" id="property-wifi-ssid" placeholder="Bataille"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Mot de passe Wi-Fi</label>
                        <input type="text" id="property-wifi-password" placeholder="WelcomeBataille"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                    </div>
                </div>

                <!-- Livrets d'accueil & Guides Voyageurs (Téléversement direct PDF ou URL) -->
                <div class="space-y-3 p-4 rounded-2xl bg-night/50 border border-night-border/60">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-white flex items-center space-x-2">
                            <span>📄</span>
                            <span>Livrets d'accueil (Guides PDF)</span>
                        </label>
                        <span class="text-[10px] text-gray-400">PDF sur Cloudflare R2</span>
                    </div>

                    <!-- Prévisualisation emplacement Cloudflare R2 -->
                    <div class="px-3 py-2 rounded-xl bg-night border border-gold/30 text-[11px] text-gray-300 flex items-center justify-between">
                        <div class="flex items-center space-x-1.5 min-w-0">
                            <span class="text-gold">📁</span>
                            <span class="flex-shrink-0 text-gray-400">Dossier Cloudflare R2 :</span>
                            <span id="property-r2-dest-preview" class="font-mono text-gold font-bold truncate">carredhotes-guides/[slug-logement]/guide-fr.pdf</span>
                        </div>
                    </div>

                    <!-- Guide FR -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-night border border-night-border/80">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-200 flex items-center space-x-1.5">
                                <span>🇫🇷</span>
                                <span>Guide en Français</span>
                            </span>
                            <button type="button" onclick="AdminApp.toggleManualUrl('property-guide-fr-manual')" class="text-[11px] text-gold hover:underline">
                                URL directe ▾
                            </button>
                        </div>

                        <!-- Dropzone FR -->
                        <div id="dropzone-guide-fr" 
                             class="upload-dropzone border-2 border-dashed border-night-border hover:border-gold/60 rounded-xl p-3 text-center cursor-pointer transition bg-night/40 group"
                             onclick="document.getElementById('property-guide-fr-file').click()"
                             ondragover="AdminApp.handleDragOver(event)" 
                             ondragleave="AdminApp.handleDragLeave(event)" 
                             ondrop="AdminApp.handleDrop(event, 'guide', 'fr')">
                            <input type="file" id="property-guide-fr-file" accept="application/pdf,.pdf" class="hidden" onchange="AdminApp.handleGuideFileUpload(this, 'fr')">
                            <div class="flex items-center justify-center space-x-2 text-xs text-gray-300">
                                <span class="text-base group-hover:scale-110 transition-transform">📤</span>
                                <span><strong class="text-gold">Choisir le PDF (FR)</strong> ou glisser le fichier</span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-0.5">Le téléversement s'effectue à l'enregistrement (guide-fr.pdf)</p>
                        </div>

                        <!-- Barre de progression FR -->
                        <div id="progress-guide-fr" class="hidden space-y-1 pt-1">
                            <div class="flex justify-between text-[11px] text-gray-300">
                                <span id="progress-text-guide-fr">Envoi du livret FR...</span>
                                <span id="progress-percent-guide-fr" class="font-mono text-gold font-bold">0%</span>
                            </div>
                            <div class="w-full h-1.5 bg-night-border rounded-full overflow-hidden">
                                <div id="progress-bar-guide-fr" class="h-full bg-gold rounded-full transition-all duration-150" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Carte aperçu FR -->
                        <div id="preview-guide-fr" class="hidden items-center justify-between p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs transition-all">
                            <div class="flex items-center space-x-2 min-w-0">
                                <span id="preview-badge-guide-fr" class="text-emerald-400 text-sm">✓</span>
                                <span id="preview-filename-guide-fr" class="text-gray-200 font-mono truncate max-w-[200px]">guide-fr.pdf</span>
                            </div>
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <a id="link-preview-guide-fr" href="#" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 rounded-lg bg-gold/20 text-gold hover:bg-gold/30 text-[11px] font-bold">Voir ↗</a>
                                <button type="button" onclick="AdminApp.clearUploadedMedia('guide', 'fr')" class="px-2 py-0.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 text-[11px] font-semibold">Changer</button>
                            </div>
                        </div>

                        <!-- Champ URL manuel FR (repliable) -->
                        <div id="property-guide-fr-manual" class="hidden pt-1">
                            <input type="url" id="property-guide-fr" placeholder="https://guides.carredhotes.fr/bataille/guide-fr.pdf"
                                   oninput="AdminApp.onGuideUrlInput('fr')"
                                   class="w-full p-2 rounded-lg bg-night-card border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                        </div>
                    </div>

                    <!-- Guide EN -->
                    <div class="space-y-1.5 p-3 rounded-xl bg-night border border-night-border/80">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-200 flex items-center space-x-1.5">
                                <span>🇬🇧</span>
                                <span>Guide en Anglais</span>
                            </span>
                            <button type="button" onclick="AdminApp.toggleManualUrl('property-guide-en-manual')" class="text-[11px] text-gold hover:underline">
                                URL directe ▾
                            </button>
                        </div>

                        <!-- Dropzone EN -->
                        <div id="dropzone-guide-en" 
                             class="upload-dropzone border-2 border-dashed border-night-border hover:border-gold/60 rounded-xl p-3 text-center cursor-pointer transition bg-night/40 group"
                             onclick="document.getElementById('property-guide-en-file').click()"
                             ondragover="AdminApp.handleDragOver(event)" 
                             ondragleave="AdminApp.handleDragLeave(event)" 
                             ondrop="AdminApp.handleDrop(event, 'guide', 'en')">
                            <input type="file" id="property-guide-en-file" accept="application/pdf,.pdf" class="hidden" onchange="AdminApp.handleGuideFileUpload(this, 'en')">
                            <div class="flex items-center justify-center space-x-2 text-xs text-gray-300">
                                <span class="text-base group-hover:scale-110 transition-transform">📤</span>
                                <span><strong class="text-gold">Choisir le PDF (EN)</strong> ou glisser le fichier</span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-0.5">Le téléversement s'effectue à l'enregistrement (guide-en.pdf)</p>
                        </div>

                        <!-- Barre de progression EN -->
                        <div id="progress-guide-en" class="hidden space-y-1 pt-1">
                            <div class="flex justify-between text-[11px] text-gray-300">
                                <span id="progress-text-guide-en">Envoi du livret EN...</span>
                                <span id="progress-percent-guide-en" class="font-mono text-gold font-bold">0%</span>
                            </div>
                            <div class="w-full h-1.5 bg-night-border rounded-full overflow-hidden">
                                <div id="progress-bar-guide-en" class="h-full bg-gold rounded-full transition-all duration-150" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Carte aperçu EN -->
                        <div id="preview-guide-en" class="hidden items-center justify-between p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs transition-all">
                            <div class="flex items-center space-x-2 min-w-0">
                                <span id="preview-badge-guide-en" class="text-emerald-400 text-sm">✓</span>
                                <span id="preview-filename-guide-en" class="text-gray-200 font-mono truncate max-w-[200px]">guide-en.pdf</span>
                            </div>
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <a id="link-preview-guide-en" href="#" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 rounded-lg bg-gold/20 text-gold hover:bg-gold/30 text-[11px] font-bold">Voir ↗</a>
                                <button type="button" onclick="AdminApp.clearUploadedMedia('guide', 'en')" class="px-2 py-0.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 text-[11px] font-semibold">Changer</button>
                            </div>
                        </div>

                        <!-- Champ URL manuel EN (repliable) -->
                        <div id="property-guide-en-manual" class="hidden pt-1">
                            <input type="url" id="property-guide-en" placeholder="https://guides.carredhotes.fr/bataille/guide-en.pdf"
                                   oninput="AdminApp.onGuideUrlInput('en')"
                                   class="w-full p-2 rounded-lg bg-night-card border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" id="property-active" class="w-4 h-4 rounded bg-night border-night-border text-gold focus:ring-gold accent-[#ba9641]">
                    <label for="property-active" class="text-xs font-semibold text-gray-300 cursor-pointer">Hébergement actif (visible sur le portail)</label>
                </div>

                <div class="pt-4 border-t border-night-border flex justify-end space-x-2">
                    <button type="button" id="btn-cancel-property" onclick="AdminApp.closePropertyModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Annuler</button>
                    <button type="submit" id="btn-save-property" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-1.5">
                        <span>Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. MODALE VIDÉO TUTORIEL -->
    <div id="modal-video-form" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-2xl sm:rounded-3xl w-full max-w-lg max-h-[92vh] overflow-y-auto p-4 sm:p-6 sm:p-7 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-4 mb-5">
                <h3 id="modal-video-title" class="text-base font-bold text-white">Ajouter une vidéo</h3>
                <button type="button" onclick="AdminApp.closeVideoModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>

            <form id="video-form" onsubmit="AdminApp.saveVideo(event)" class="space-y-4">
                <input type="hidden" id="video-id" value="0">
                <input type="hidden" id="video-property-id" value="0">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Titre (Français) *</label>
                        <input type="text" id="video-title-fr" required oninput="AdminApp.onVideoTitleInput(this.value)" placeholder="Machine à café"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Titre (Anglais)</label>
                        <input type="text" id="video-title-en" placeholder="Coffee machine"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-gray-300">Slug URL unique *</label>
                        <span class="text-[10px] text-gold font-mono">Requis pour Cloudflare R2</span>
                    </div>
                    <input type="text" id="video-slug" required oninput="AdminApp.onVideoSlugInput(this.value)" placeholder="machine-a-cafe"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                    <p class="text-[10px] text-gray-400 mt-1">Obligatoire : définit le nom exact du fichier sur Cloudflare R2 (ex: <code class="text-gold">machine-a-cafe.mp4</code>).</p>
                </div>

                <!-- Téléversement direct du fichier vidéo ou saisie URL -->
                <div class="space-y-2 p-3.5 rounded-2xl bg-night/50 border border-night-border/60">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-white flex items-center space-x-1.5">
                            <span>🎬</span>
                            <span>Fichier Vidéo du tutoriel *</span>
                        </label>
                        <button type="button" onclick="AdminApp.toggleManualUrl('video-url-manual')" class="text-[11px] text-gold hover:underline">
                            URL directe ▾
                        </button>
                    </div>

                    <!-- Prévisualisation emplacement Cloudflare R2 -->
                    <div class="px-3 py-2 rounded-xl bg-night border border-gold/30 text-[11px] text-gray-300 flex items-center justify-between">
                        <div class="flex items-center space-x-1.5 min-w-0">
                            <span class="text-gold">📁</span>
                            <span class="flex-shrink-0 text-gray-400">Emplacement Cloudflare R2 :</span>
                            <span id="video-r2-dest-preview" class="font-mono text-gold font-bold truncate">carredhotes-videos/[slug-logement]/[slug-vidéo].mp4</span>
                        </div>
                    </div>

                    <!-- Dropzone Vidéo -->
                    <div id="dropzone-video" 
                         class="upload-dropzone border-2 border-dashed border-night-border hover:border-gold/60 rounded-xl p-4 text-center cursor-pointer transition bg-night/40 group"
                         onclick="document.getElementById('video-file-input').click()"
                         ondragover="AdminApp.handleDragOver(event)" 
                         ondragleave="AdminApp.handleDragLeave(event)" 
                         ondrop="AdminApp.handleDrop(event, 'video')">
                        <input type="file" id="video-file-input" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" class="hidden" onchange="AdminApp.handleVideoFileUpload(this)">
                        <div class="space-y-1">
                            <span class="text-2xl block group-hover:scale-110 transition-transform">🎥</span>
                            <p class="text-xs text-gray-200"><strong class="text-gold">Choisir la vidéo</strong> ou glisser le fichier</p>
                            <p class="text-[10px] text-gray-400">Le téléversement s'effectue à l'enregistrement (MP4, WebM ou MOV)</p>
                        </div>
                    </div>

                    <!-- Barre de progression Vidéo -->
                    <div id="progress-video" class="hidden space-y-1.5 pt-1">
                        <div class="flex justify-between text-[11px] text-gray-300">
                            <span id="progress-text-video">Téléversement de la vidéo...</span>
                            <span id="progress-percent-video" class="font-mono text-gold font-bold">0%</span>
                        </div>
                        <div class="w-full h-2 bg-night-border rounded-full overflow-hidden">
                            <div id="progress-bar-video" class="h-full bg-gold rounded-full transition-all duration-150" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Carte aperçu vidéo configurée ou fichier en attente -->
                    <div id="preview-video-container" class="hidden items-center justify-between p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs transition-all">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <span id="preview-badge-video" class="text-emerald-400 text-base">✓</span>
                            <div class="min-w-0">
                                <p id="preview-filename-video" class="text-white font-mono text-xs font-semibold truncate max-w-[220px]">video.mp4</p>
                                <p id="preview-meta-video" class="text-[10px] text-emerald-300">Vidéo prête pour diffusion</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <button type="button" id="preview-btn-play-video" onclick="AdminApp.previewCurrentVideo()" class="px-2.5 py-1 rounded-lg bg-gold/20 text-gold hover:bg-gold/30 text-[11px] font-bold">▶ Tester</button>
                            <button type="button" id="preview-btn-action-video" onclick="AdminApp.handleVideoFileAction()" class="px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 text-[11px] font-semibold">Changer</button>
                        </div>
                    </div>

                    <!-- Champ URL manuel Vidéo (repliable) -->
                    <div id="video-url-manual" class="hidden pt-1.5">
                        <label class="block text-[11px] font-semibold text-gray-300 mb-1">URL directe (Cloudflare R2, Google Drive, MP4)</label>
                        <input type="url" id="video-url" placeholder="https://videos.carredhotes.fr/bataille/machine-a-cafe.mp4"
                               oninput="AdminApp.onVideoUrlInput()"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 p-3.5 rounded-2xl bg-night/50 border border-night-border/40">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-300 mb-1">Icône Emoji</label>
                        <input type="text" id="video-icon" value="🎬" class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-center text-white focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-300 mb-1">Couleur Fond</label>
                        <select id="video-bg-color" class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                            <option value="bg-blue-50">Bleu clair</option>
                            <option value="bg-orange-50">Orange clair</option>
                            <option value="bg-amber-50">Ambre clair</option>
                            <option value="bg-purple-50">Violet clair</option>
                            <option value="bg-emerald-50">Émeraude clair</option>
                            <option value="bg-rose-50">Rose clair</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-300 mb-1">Couleur Texte</label>
                        <select id="video-text-color" class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                            <option value="text-blue-500">Bleu</option>
                            <option value="text-orange-500">Orange</option>
                            <option value="text-amber-500">Ambre</option>
                            <option value="text-purple-500">Violet</option>
                            <option value="text-emerald-500">Émeraude</option>
                            <option value="text-rose-500">Rose</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Ordre d'affichage</label>
                        <input type="number" id="video-order" min="1" value="1" class="w-20 p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div class="flex items-center space-x-2 pt-4">
                        <input type="checkbox" id="video-active" class="w-4 h-4 rounded bg-night border-night-border text-gold focus:ring-gold accent-[#ba9641]">
                        <label for="video-active" class="text-xs font-semibold text-gray-300 cursor-pointer">Actif</label>
                    </div>
                </div>

                <div class="pt-4 border-t border-night-border flex justify-end space-x-2">
                    <button type="button" id="btn-cancel-video" onclick="AdminApp.closeVideoModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Annuler</button>
                    <button type="submit" id="btn-save-video" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-1.5">
                        <span>Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. MODALE LECTEUR VIDÉO APERÇU -->
    <div id="modal-video-preview" class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto" onclick="if(event.target === this) AdminApp.closeVideoPreview()">
        <div class="bg-night-card border border-night-border rounded-2xl sm:rounded-3xl w-full max-w-xl max-h-[92vh] overflow-y-auto p-4 sm:p-5 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-3 mb-4">
                <h4 id="video-preview-title" class="text-sm font-bold text-white truncate">Aperçu de la vidéo</h4>
                <button type="button" onclick="AdminApp.closeVideoPreview()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>
            <div id="video-preview-player-container" class="rounded-xl overflow-hidden bg-black flex items-center justify-center">
                <!-- Injecté dynamiquement -->
            </div>
        </div>
    </div>

    <!-- 4. MODALE EMPLACEMENT DE PARKING -->
    <div id="modal-location-form" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-2xl sm:rounded-3xl w-full max-w-2xl max-h-[92vh] overflow-y-auto p-4 sm:p-6 sm:p-7 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-4 mb-5">
                <div>
                    <h3 id="modal-location-title" class="text-base font-bold text-white">Ajouter un emplacement</h3>
                    <p class="text-[11px] text-gray-400">Remplissez les informations en français.</p>
                </div>
                <button type="button" onclick="AdminApp.closeLocationModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>

            <form id="location-form" onsubmit="AdminApp.saveLocation(event)" class="space-y-4">
                <input type="hidden" id="location-id" value="0">
                <input type="hidden" id="location-property-id" value="0">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Catégorie *</label>
                        <select id="location-category" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                            <option value="free_street">Rue gratuite</option>
                            <option value="free_spot">Dépose-minute gratuit</option>
                            <option value="private_parking">Parking privé & couvert (Zenpark, LPA...)</option>
                            <option value="tcl_pr">Parc Relais TCL (P+R)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Opérateur (ex: LPA, Zenpark, TCL, Indigo)</label>
                        <input type="text" id="location-operator" placeholder="LPA&CO"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Nom du parking ou de la rue *</label>
                    <input type="text" id="location-name-fr" required placeholder="Rue de l'Est"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Adresse précise (avec bouton GPS pour Waze / Maps)</label>
                    <input type="text" id="location-address" placeholder="12 rue de l'Est, 69003 Lyon"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Temps de marche à pied</label>
                        <input type="text" id="location-walking-fr" placeholder="Environ 5 min à pied"
                               class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Minutes de marche (pour le tri par proximité)</label>
                        <input type="number" id="location-walking-min" placeholder="5"
                               class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Temps en transports en commun (TCL)</label>
                    <input type="text" id="location-transit-fr" placeholder="Environ 10 min en Métro A"
                           class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Hauteur max autorisée (gabarit)</label>
                        <input type="text" id="location-max-height" placeholder="1,90 m"
                               class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Capacité (nombre de places)</label>
                        <input type="text" id="location-capacity" placeholder="416 places"
                               class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Horaires d'ouverture</label>
                    <input type="text" id="location-hours-fr" placeholder="Ouvert 24h/24 et 7j/7"
                           class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Grille tarifaire</label>
                    <textarea id="location-rates-fr" rows="2" placeholder="15 min gratuites · 2h : 3 € · 24h : 24 €"
                              class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Véhicules adaptés</label>
                    <input type="text" id="location-vehicles-fr" placeholder="Citadines, SUV, berlines, 2 roues"
                           class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Itinéraire pour rejoindre le logement</label>
                    <textarea id="location-reach-fr" rows="2" placeholder="Prendre le Métro A direction Perrache jusqu'à la station Flachet..."
                              class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Lien de réservation / site web officiel</label>
                    <input type="url" id="location-booking-url" placeholder="https://zenpark.com/..."
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Notes & remarques complémentaires</label>
                    <input type="text" id="location-notes-fr" placeholder="Idéal pour décharger les bagages grâce aux 15 min offertes."
                           class="w-full p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <!-- Champs anglais masqués de l'emplacement (éditables via la vue côte-à-côte) -->
                <div class="hidden" aria-hidden="true">
                    <input type="hidden" id="location-name-en">
                    <input type="hidden" id="location-walking-en">
                    <input type="hidden" id="location-transit-en">
                    <input type="hidden" id="location-hours-en">
                    <textarea id="location-rates-en"></textarea>
                    <input type="hidden" id="location-vehicles-en">
                    <textarea id="location-reach-en"></textarea>
                    <input type="hidden" id="location-notes-en">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Ordre d'affichage</label>
                        <input type="number" id="location-order" min="1" value="1" class="w-20 p-2 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div class="flex items-center space-x-2 pt-4">
                        <input type="checkbox" id="location-active" class="w-4 h-4 rounded bg-night border-night-border text-gold focus:ring-gold accent-[#ba9641]">
                        <label for="location-active" class="text-xs font-semibold text-gray-300 cursor-pointer">Actif</label>
                    </div>
                </div>

                <div class="pt-4 border-t border-night-border flex flex-wrap items-center justify-between gap-3">
                    <button type="button" onclick="AdminApp.openDirectReviewModal('parking_location')" 
                            class="text-xs text-gray-400 hover:text-gold flex items-center space-x-1.5 transition py-1"
                            title="Ouvrir la vue côte-à-côte pour consulter ou ajuster manuellement la version anglaise">
                        <span>✏️</span>
                        <span>Saisir l'anglais manuellement (Vue côte-à-côte)</span>
                    </button>
                    <div class="flex items-center space-x-2 ml-auto">
                        <button type="button" onclick="AdminApp.closeLocationModal()" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300 transition">Annuler</button>
                        <button type="submit" id="btn-save-location" class="px-5 py-2.5 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-2">
                            <span id="btn-save-loc-icon">💾</span>
                            <span id="btn-save-loc-text">Enregistrer l'emplacement</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. MODALE UTILISATEUR ADMINISTRATEUR -->
    <div id="modal-user-form" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-2xl sm:rounded-3xl w-full max-w-md max-h-[92vh] overflow-y-auto p-4 sm:p-6 sm:p-7 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-4 mb-5">
                <h3 id="modal-user-title" class="text-base font-bold text-white">Ajouter un administrateur</h3>
                <button type="button" onclick="AdminApp.closeUserModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>

            <form id="user-form" onsubmit="AdminApp.saveUser(event)" class="space-y-4">
                <input type="hidden" id="user-form-id" value="0">

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Nom complet *</label>
                    <input type="text" id="user-name" required placeholder="Anthony Carré"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Adresse Email *</label>
                    <input type="email" id="user-email" required placeholder="anthony@carredhotes.fr"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Rôle d'administration *</label>
                    <select id="user-role" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                        <option value="admin">Administrateur (Gestion complète des contenus)</option>
                        <option value="superadmin">Super Administrateur (Gestion totale + Administrateurs)</option>
                        <option value="editor">Éditeur (Lecture & modification sans suppression)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Mot de passe</label>
                    <input type="password" id="user-password" placeholder="••••••••••••"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    <p id="user-pwd-help" class="text-[10px] text-gray-500 mt-1"></p>
                </div>

                <div class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" id="user-active" class="w-4 h-4 rounded bg-night border-night-border text-gold focus:ring-gold accent-[#ba9641]">
                    <label for="user-active" class="text-xs font-semibold text-gray-300 cursor-pointer">Compte actif (autorisé à se connecter)</label>
                </div>

                <div class="pt-4 border-t border-night-border flex justify-end space-x-2">
                    <button type="button" onclick="AdminApp.closeUserModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Annuler</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. MODALE PROFIL (CHANGEMENT DE MOT DE PASSE) -->
    <div id="modal-profile" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-2xl sm:rounded-3xl w-full max-w-md max-h-[92vh] overflow-y-auto p-4 sm:p-6 sm:p-7 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-4 mb-5">
                <h3 class="text-base font-bold text-white">Mon Profil & Mot de passe</h3>
                <button type="button" onclick="AdminApp.closeProfileModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>

            <form id="profile-pwd-form" onsubmit="AdminApp.changePassword(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Ancien mot de passe *</label>
                    <input type="password" id="profile-old-pwd" required placeholder="••••••••••••"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Nouveau mot de passe (min. 8 caractères) *</label>
                    <input type="password" id="profile-new-pwd" minlength="8" required placeholder="••••••••••••"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Confirmer le nouveau mot de passe *</label>
                    <input type="password" id="profile-confirm-pwd" minlength="8" required placeholder="••••••••••••"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                </div>

                <div class="pt-4 border-t border-night-border flex justify-end space-x-2">
                    <button type="button" onclick="AdminApp.closeProfileModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Annuler</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition">Changer mon mot de passe</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. MODALE DÉTAILS D'UN LOG D'ACTIVITÉ -->
    <div id="modal-log-details" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-2xl sm:rounded-3xl w-full max-w-lg max-h-[92vh] overflow-y-auto p-4 sm:p-6 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-3 mb-4">
                <h4 class="text-sm font-bold text-white">Détails des modifications (JSON)</h4>
                <button type="button" onclick="AdminApp.closeLogDetails()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>
            <pre id="log-details-json" class="p-4 rounded-xl bg-night border border-night-border text-[11px] font-mono text-gray-300 overflow-x-auto max-h-80"></pre>
        </div>
    </div>

    <!-- 8. MODALE DE CONFIRMATION GÉNÉRIQUE -->
    <div id="modal-confirmation" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-red-500/30 rounded-2xl sm:rounded-3xl w-full max-w-md max-h-[92vh] overflow-y-auto p-5 sm:p-6 shadow-2xl relative my-auto text-center space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-400 border border-red-500/30 flex items-center justify-center text-xl mx-auto">
                ⚠️
            </div>
            <h4 id="confirm-title" class="text-base font-bold text-white">Êtes-vous sûr ?</h4>
            <p id="confirm-message" class="text-xs text-gray-400 leading-relaxed"></p>
            <div id="confirm-extra" class="hidden text-left bg-night p-3.5 rounded-xl border border-night-border/80 text-xs"></div>
            <div class="pt-3 flex justify-center space-x-3">
                <button type="button" onclick="AdminApp.closeConfirmModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 font-semibold transition">
                    Annuler
                </button>
                <button type="button" id="confirm-btn-yes" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-lg shadow-red-600/30 transition">
                    Confirmer la suppression
                </button>
            </div>
        </div>
    </div>

    <!-- 9. MODALE CONFIGURATION (INTELLIGENCE ARTIFICIELLE & STOCKAGE R2) -->
    <div id="modal-ai-settings" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-gold/40 rounded-3xl w-full max-w-2xl max-h-[92vh] flex flex-col shadow-2xl relative my-auto overflow-hidden">
            <!-- Entête modale -->
            <div class="flex items-center justify-between border-b border-night-border/80 p-5 sm:p-6 pb-4 bg-night/50">
                <div class="flex items-center space-x-3">
                    <span class="w-10 h-10 rounded-2xl bg-gold/15 border border-gold/30 flex items-center justify-center text-gold text-lg shadow-sm shadow-gold/15 flex-shrink-0">⚙️</span>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-white tracking-wide">Configuration Générale</h3>
                        <p class="text-[11px] text-gray-400">Intelligence Artificielle, Moteurs de Traduction & Stockage Cloudflare R2</p>
                    </div>
                </div>
                <button type="button" onclick="AdminApp.closeAiSettingsModal()" class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white flex items-center justify-center transition text-sm" title="Fermer la fenêtre">✕</button>
            </div>

            <!-- Onglets de configuration -->
            <div class="px-5 sm:px-6 py-3 border-b border-night-border/70 flex space-x-2 bg-night/40">
                <button type="button" id="settings-tab-btn-ai" onclick="AdminApp.switchSettingsTab('ai')"
                        class="settings-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-gold text-white shadow-md shadow-gold/25">
                    <span>✨</span>
                    <span>Traduction IA</span>
                </button>
                <button type="button" id="settings-tab-btn-storage" onclick="AdminApp.switchSettingsTab('storage')"
                        class="settings-tab-btn px-4 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-white/5 transition flex items-center space-x-2">
                    <span>☁️</span>
                    <span>Stockage Cloudflare R2</span>
                </button>
            </div>

            <!-- Formulaire unifié -->
            <form id="ai-settings-form" onsubmit="AdminApp.saveAiSettings(event)" class="p-5 sm:p-6 overflow-y-auto space-y-5 flex-1">
                
                <!-- PANNEAU 1 : TRADUCTION IA -->
                <div id="settings-panel-ai" class="space-y-4">
                    
                    <!-- Carte Fournisseur & Clé API -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-night/60 border border-night-border/70 space-y-4">
                        <div class="flex items-center justify-between border-b border-night-border/50 pb-2.5">
                            <span class="text-xs font-bold text-gold flex items-center space-x-1.5">
                                <span>🤖</span>
                                <span>Moteur d'Intelligence Artificielle</span>
                            </span>
                            <span class="text-[10px] text-gray-400">Traduction FR ⇄ EN</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1.5">Fournisseur IA actif *</label>
                            <select id="ai-provider-select" onchange="AdminApp.handleAiProviderChange()" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold font-medium">
                                <option value="gemini">Google Gemini (Recommandé — Rapide, précis & économique)</option>
                                <option value="openai">ChatGPT / OpenAI (gpt-4o-mini)</option>
                                <option value="claude">Anthropic Claude (claude-3-5-haiku)</option>
                            </select>
                        </div>

                        <!-- Section Clé API Gemini -->
                        <div id="ai-key-group-gemini" class="ai-key-group space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-gray-300">Clé API Google Gemini *</label>
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="text-[11px] text-gold hover:underline flex items-center space-x-1">
                                    <span>Obtenir une clé Google AI Studio</span>
                                    <span>↗</span>
                                </a>
                            </div>
                            <div class="relative">
                                <input type="password" id="ai-key-gemini" placeholder="AIzaSy..." class="w-full p-2.5 pr-10 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                <button type="button" onclick="AdminApp.toggleKeyVisibility('ai-key-gemini')" class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs">👁️</button>
                            </div>
                            <p class="text-[11px] text-gray-400">Modèle par défaut : <code class="text-gold-light bg-gold/10 px-1 py-0.5 rounded font-mono">gemini-2.5-flash</code></p>
                        </div>

                        <!-- Section Clé API OpenAI -->
                        <div id="ai-key-group-openai" class="ai-key-group space-y-1.5 hidden pt-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-gray-300">Clé API OpenAI (ChatGPT) *</label>
                                <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer" class="text-[11px] text-gold hover:underline flex items-center space-x-1">
                                    <span>Obtenir une clé OpenAI</span>
                                    <span>↗</span>
                                </a>
                            </div>
                            <div class="relative">
                                <input type="password" id="ai-key-openai" placeholder="sk-proj-..." class="w-full p-2.5 pr-10 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                <button type="button" onclick="AdminApp.toggleKeyVisibility('ai-key-openai')" class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs">👁️</button>
                            </div>
                            <p class="text-[11px] text-gray-400">Modèle par défaut : <code class="text-gold-light bg-gold/10 px-1 py-0.5 rounded font-mono">gpt-4o-mini</code></p>
                        </div>

                        <!-- Section Clé API Claude -->
                        <div id="ai-key-group-claude" class="ai-key-group space-y-1.5 hidden pt-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-gray-300">Clé API Anthropic Claude *</label>
                                <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer" class="text-[11px] text-gold hover:underline flex items-center space-x-1">
                                    <span>Obtenir une clé Anthropic</span>
                                    <span>↗</span>
                                </a>
                            </div>
                            <div class="relative">
                                <input type="password" id="ai-key-claude" placeholder="sk-ant-..." class="w-full p-2.5 pr-10 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                <button type="button" onclick="AdminApp.toggleKeyVisibility('ai-key-claude')" class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs">👁️</button>
                            </div>
                            <p class="text-[11px] text-gray-400">Modèle par défaut : <code class="text-gold-light bg-gold/10 px-1 py-0.5 rounded font-mono">claude-3-5-haiku-latest</code></p>
                        </div>
                    </div>

                    <!-- Carte Sélection dynamique du modèle -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-night/60 border border-night-border/70 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-gray-300">Modèle actif *</label>
                            <button type="button" onclick="AdminApp.refreshAiModels()" class="text-[11px] text-gold hover:text-gold-light flex items-center space-x-1 hover:underline cursor-pointer" title="Interroger l'API pour récupérer les modèles disponibles">
                                <span id="ai-models-refresh-icon">🔄</span>
                                <span id="ai-models-refresh-label">Actualiser les modèles</span>
                            </button>
                        </div>
                        <select id="ai-model-select" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold font-mono">
                            <option value="">Chargement des modèles disponibles...</option>
                        </select>
                        <p class="text-[11px] text-gray-400">Les modèles sont interrogés en direct auprès du fournisseur sélectionné.</p>
                    </div>

                    <!-- Carte Test & Diagnostic IA -->
                    <div class="p-4 rounded-2xl bg-night/40 border border-night-border/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-300">Diagnostic de communication IA</span>
                            <button type="button" id="btn-test-ai" onclick="AdminApp.testAiConnection()" class="px-4 py-2 rounded-xl bg-gold/15 hover:bg-gold text-gold hover:text-white border border-gold/40 text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                                <span>⚡</span>
                                <span>Tester la connexion IA</span>
                            </button>
                        </div>
                        <!-- Zone résultat test connexion IA -->
                        <div id="ai-test-result" class="hidden p-3 rounded-xl text-xs space-y-1"></div>
                    </div>
                </div>

                <!-- PANNEAU 2 : STOCKAGE CLOUDFLARE R2 (EXCLUSIF) -->
                <div id="settings-panel-storage" class="space-y-4 hidden">
                    
                    <!-- Bannière informative R2 -->
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-gold/15 via-gold/5 to-transparent border border-gold/30 flex items-start space-x-3.5 shadow-sm">
                        <div class="w-9 h-9 rounded-xl bg-gold/20 text-gold border border-gold/30 flex items-center justify-center text-lg flex-shrink-0">
                            ☁️
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-white">Stockage Exclusif Cloudflare R2</h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Actif</span>
                            </div>
                            <p class="text-[11px] text-gray-300 mt-1 leading-relaxed">
                                Les livrets d'accueil PDF et les vidéos sont hébergés avec haute vitesse et bande passante mondiale illimitée sur vos sous-domaines dédiés.
                            </p>
                        </div>
                    </div>

                    <!-- Carte 1 : Identifiants API S3 -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-night/60 border border-night-border/70 space-y-3.5">
                        <div class="flex items-center justify-between border-b border-night-border/50 pb-2.5">
                            <span class="text-xs font-bold text-gold flex items-center space-x-1.5">
                                <span>🔑</span>
                                <span>Identifiants API S3 Cloudflare</span>
                            </span>
                            <a href="https://dash.cloudflare.com/?to=/:account/r2/api-tokens" target="_blank" rel="noopener noreferrer" class="text-[11px] text-gold hover:underline flex items-center space-x-1">
                                <span>Créer un token R2 S3</span>
                                <span>↗</span>
                            </a>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">ID de Compte Cloudflare (Account ID) *</label>
                            <input type="text" id="r2-account-id" placeholder="Ex: 8f2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d"
                                   class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">R2 Access Key ID *</label>
                                <input type="text" id="r2-access-key-id" placeholder="Clé d'accès S3 R2"
                                       class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-300 mb-1">R2 Secret Access Key *</label>
                                <div class="relative">
                                    <input type="password" id="r2-secret-access-key" placeholder="Clé secrète S3 R2"
                                           class="w-full p-2.5 pr-10 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                    <button type="button" onclick="AdminApp.toggleKeyVisibility('r2-secret-access-key')" class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs">👁️</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carte 2 : Buckets et Domaines Publics -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-night/60 border border-night-border/70 space-y-3.5">
                        <div class="flex items-center justify-between border-b border-night-border/50 pb-2.5">
                            <span class="text-xs font-bold text-gold flex items-center space-x-1.5">
                                <span>📁</span>
                                <span>Buckets & Sous-domaines Publics</span>
                            </span>
                            <span class="text-[10px] text-gray-400">Routage CDN</span>
                        </div>

                        <!-- Guides PDF -->
                        <div class="p-3 rounded-xl bg-night/80 border border-night-border/50 space-y-2">
                            <div class="flex items-center space-x-1.5 text-xs font-bold text-white">
                                <span>📄</span>
                                <span>Livrets d'accueil (Guides PDF)</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-400 mb-1">Nom du Bucket R2</label>
                                    <input type="text" id="r2-bucket-guides" placeholder="carredhotes-guides"
                                           class="w-full p-2 rounded-lg bg-night-card border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-400 mb-1">Domaine Public Dédié</label>
                                    <input type="url" id="r2-domain-guides" placeholder="https://guides.carredhotes.fr"
                                           class="w-full p-2 rounded-lg bg-night-card border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                </div>
                            </div>
                        </div>

                        <!-- Vidéos MP4 -->
                        <div class="p-3 rounded-xl bg-night/80 border border-night-border/50 space-y-2">
                            <div class="flex items-center space-x-1.5 text-xs font-bold text-white">
                                <span>🎬</span>
                                <span>Vidéos & Tutoriels (MP4 / WebM / MOV)</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-400 mb-1">Nom du Bucket R2</label>
                                    <input type="text" id="r2-bucket-videos" placeholder="carredhotes-videos"
                                           class="w-full p-2 rounded-lg bg-night-card border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-400 mb-1">Domaine Public Dédié</label>
                                    <input type="url" id="r2-domain-videos" placeholder="https://videos.carredhotes.fr"
                                           class="w-full p-2 rounded-lg bg-night-card border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Test & Diagnostic R2 -->
                    <div class="p-4 rounded-2xl bg-night/40 border border-night-border/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-300">Diagnostic de liaison Cloudflare R2</span>
                            <button type="button" id="btn-test-r2" onclick="AdminApp.testR2Connection()" class="px-4 py-2 rounded-xl bg-gold/15 hover:bg-gold text-gold hover:text-white border border-gold/40 text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                                <span>⚡</span>
                                <span>Tester la connexion R2</span>
                            </button>
                        </div>
                        <!-- Zone résultat test connexion R2 -->
                        <div id="r2-test-result" class="hidden p-3 rounded-xl text-xs space-y-1"></div>
                    </div>
                </div>

                <!-- Footer Modale -->
                <div class="pt-4 border-t border-night-border flex items-center justify-end space-x-3">
                    <button type="button" onclick="AdminApp.closeAiSettingsModal()" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 font-semibold transition">Fermer</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/25 transition">Enregistrer la configuration</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 10. MODALE FLASH CHECK : VÉRIFICATION RAPIDE DE L'ANGLAIS EN 3 SECONDES -->
    <div id="modal-translation-review" class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-gold/40 rounded-3xl w-full max-w-xl max-h-[90vh] flex flex-col shadow-2xl relative my-auto overflow-hidden">
            
            <!-- Entête épurée -->
            <div class="p-4 sm:p-5 border-b border-night-border/80 flex items-center justify-between bg-night/50 flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <span class="w-8 h-8 rounded-xl bg-gold/15 border border-gold/30 flex items-center justify-center text-gold text-base flex-shrink-0">
                        🇬🇧
                    </span>
                    <div>
                        <h3 id="modal-translation-review-title" class="text-sm sm:text-base font-bold text-white tracking-wide">Vérification de l'anglais</h3>
                        <p id="modal-translation-review-subtitle" class="text-[11px] text-gray-400">Généré par l'IA — Un coup d'œil rapide avant enregistrement</p>
                    </div>
                </div>
                <button type="button" onclick="AdminApp.closeTranslationReviewModal()" class="text-gray-400 hover:text-white text-base p-1.5 rounded-xl hover:bg-white/5 transition" title="Fermer">✕</button>
            </div>

            <!-- Corps vertical fluide et aéré -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3" id="review-modal-fields-container">
                <!-- Rempli dynamiquement avec les champs français et anglais -->
            </div>

            <!-- Pied de page ultra-simple : 2 boutons uniquement -->
            <div class="p-4 sm:p-5 border-t border-night-border/80 bg-night/70 flex items-center justify-between gap-3 flex-shrink-0">
                <button type="button" onclick="AdminApp.closeTranslationReviewModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300 transition">
                    Annuler
                </button>
                <button type="button" id="btn-review-save-direct" onclick="AdminApp.confirmAndSaveReview()" class="px-5 py-2.5 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/25 transition flex items-center space-x-2">
                    <span id="btn-review-save-icon">✅</span>
                    <span id="btn-review-save-label">Confirmer & Enregistrer</span>
                </button>
            </div>
        </div>
    </div>

    <!-- CONTENEUR DES TOASTS DE NOTIFICATION -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col space-y-2 pointer-events-none max-w-sm w-full"></div>

    <!-- Script principal de l'application -->
    <script src="assets/js/admin.js?v=2.9"></script>
</body>
</html>

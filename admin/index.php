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
    <link rel="stylesheet" href="assets/css/admin.css?v=1.0">
</head>
<body class="bg-night text-gray-100 min-h-screen flex flex-col antialiased selection:bg-gold selection:text-white">

    <!-- CONTENEUR GLOBAL APP -->
    <div class="flex-1 flex overflow-hidden">

        <!-- =============================================================== -->
        <!-- SIDEBAR LATÉRALE                                                -->
        <!-- =============================================================== -->
        <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-night-card border-r border-night-border flex flex-col justify-between transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out md:static flex-shrink-0">
            <!-- Haut de la Sidebar -->
            <div>
                <!-- Logo & Titre -->
                <div class="p-5 border-b border-night-border/70 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-gold/15 border border-gold/30 flex items-center justify-center text-gold shadow-md shadow-gold/10">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="3" rx="4"/>
                                <path d="m9 9 6 6m0-6-6 6"/>
                            </svg>
                        </div>
                        <div>
                            <span class="font-serif-luxury font-bold text-white text-base tracking-wide block">Carré d'Hôtes</span>
                            <span class="text-[10px] uppercase tracking-widest text-gold font-semibold block">Administration</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation principale -->
                <nav class="p-3.5 space-y-1.5" aria-label="Menu principal">
                    <button type="button" data-tab="dashboard" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition">
                        <span class="text-base">📊</span>
                        <span>Tableau de bord</span>
                    </button>

                    <button type="button" data-tab="properties" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition">
                        <span class="text-base">🏠</span>
                        <span>Hébergements</span>
                    </button>

                    <button type="button" data-tab="videos" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition">
                        <span class="text-base">🎬</span>
                        <span>Vidéos & Tutoriels</span>
                    </button>

                    <button type="button" data-tab="parking" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition">
                        <span class="text-base">🅿️</span>
                        <span>Stationnement</span>
                    </button>

                    <button type="button" data-tab="logs" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition">
                        <span class="text-base">📜</span>
                        <span>Journal d'audit</span>
                    </button>

                    <button type="button" id="nav-item-users" data-tab="users" class="nav-tab-btn w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent transition <?= $currentUser['role'] !== 'superadmin' ? 'hidden' : '' ?>">
                        <span class="text-base">👥</span>
                        <span>Administrateurs</span>
                    </button>

                    <div class="pt-2 border-t border-night-border/50 my-1"></div>

                    <button type="button" onclick="AdminApp.openAiSettingsModal()" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-transparent text-gray-300 hover:text-white hover:bg-gold/10 hover:border-gold/30 transition group">
                        <div class="flex items-center space-x-3">
                            <span class="text-base group-hover:scale-110 transition-transform">✨</span>
                            <span class="text-gold-light group-hover:text-gold font-bold">Traduction IA</span>
                        </div>
                        <span id="nav-ai-provider-badge" class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-md bg-gold/15 text-gold border border-gold/30">Gemini</span>
                    </button>
                </nav>
            </div>

            <!-- Bas de la Sidebar : Profil & Déconnexion -->
            <div class="p-4 border-t border-night-border/70 bg-night/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <div id="user-display-avatar" class="w-8 h-8 rounded-full bg-gold/20 text-gold border border-gold/40 flex items-center justify-center font-bold text-xs flex-shrink-0">
                            AD
                        </div>
                        <div class="min-w-0">
                            <p id="user-display-name" class="text-xs font-bold text-white truncate"><?= htmlspecialchars($currentUser['name']) ?></p>
                            <p id="user-display-role" class="text-[10px] text-gray-400 capitalize"><?= htmlspecialchars($currentUser['role']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1">
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
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

            <!-- Barre supérieure (Header) -->
            <header class="sticky top-0 z-20 bg-night/80 backdrop-blur-md border-b border-night-border/80 px-4 sm:px-6 py-3.5 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <button type="button" id="mobile-menu-toggle" class="md:hidden p-2 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition" aria-label="Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold <?= $isDev ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= $isDev ? 'bg-amber-400' : 'bg-emerald-400' ?> mr-1.5 animate-pulse"></span>
                        <?= $isDev ? 'Environnement DEV (Staging)' : 'Environnement PRODUCTION' ?>
                    </span>
                    <span id="global-loading-indicator" class="hidden text-xs text-gold flex items-center space-x-1.5 font-medium animate-pulse">
                        <span>Actualisation...</span>
                    </span>
                </div>

                <div class="flex items-center space-x-2.5">
                    <button type="button" onclick="AdminApp.openAiSettingsModal()" class="px-3 py-1.5 rounded-xl bg-gold/15 hover:bg-gold text-gold hover:text-white border border-gold/40 text-xs font-semibold transition flex items-center space-x-1.5 shadow-sm" title="Configurer l'intelligence artificielle pour les traductions">
                        <span>✨</span>
                        <span class="hidden sm:inline">Traduction IA</span>
                    </button>
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
                                <div class="overflow-x-auto">
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

                    <!-- Sous-onglets Stationnement -->
                    <div class="border-b border-night-border flex space-x-4">
                        <button type="button" onclick="document.getElementById('parking-subtab-general').classList.remove('hidden'); document.getElementById('parking-subtab-locations').classList.add('hidden'); this.classList.add('border-gold', 'text-gold'); this.nextElementSibling.classList.remove('border-gold', 'text-gold'); this.nextElementSibling.classList.add('text-gray-400');"
                                class="py-2.5 px-3 border-b-2 border-gold text-gold font-bold text-xs uppercase tracking-wider transition">
                            📋 Règles Générales & Voirie
                        </button>
                        <button type="button" onclick="document.getElementById('parking-subtab-locations').classList.remove('hidden'); document.getElementById('parking-subtab-general').classList.add('hidden'); this.classList.add('border-gold', 'text-gold'); this.previousElementSibling.classList.remove('border-gold', 'text-gold'); this.previousElementSibling.classList.add('text-gray-400');"
                                class="py-2.5 px-3 border-b-2 border-transparent text-gray-400 hover:text-white font-bold text-xs uppercase tracking-wider transition">
                            📍 Emplacements & Garages
                        </button>
                    </div>

                    <!-- SOUS-ONGLET A : RÈGLES GÉNÉRALES -->
                    <div id="parking-subtab-general" class="space-y-4">
                        <form id="parking-general-form" onsubmit="AdminApp.saveParkingGeneral(event)" class="rounded-2xl bg-night-card border border-night-border p-5 sm:p-6 space-y-5 shadow-lg">
                            
                            <!-- Bannière information IA & Traduction automatique -->
                            <div class="bg-gradient-to-r from-gold/15 via-gold/5 to-transparent border border-gold/30 rounded-2xl p-4 flex items-start gap-3.5 shadow-sm">
                                <span class="p-2 rounded-xl bg-gold/20 text-gold text-lg flex-shrink-0">✨</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between flex-wrap gap-2">
                                        <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                                            <span>Traduction bilingue automatique par IA</span>
                                            <span id="parking-general-ai-badge" class="px-2 py-0.5 rounded-full text-[10px] bg-gold/25 text-gold-light border border-gold/40 normal-case font-semibold">Gemini</span>
                                        </h4>
                                        <button type="button" onclick="AdminApp.openAiSettingsModal()" class="text-[11px] text-gold hover:text-gold-light underline font-medium">Paramètres IA ⚙️</button>
                                    </div>
                                    <p class="text-xs text-gray-300 mt-1 leading-relaxed">
                                        Saisissez uniquement vos consignes en <strong class="text-white">français</strong> ci-dessous. À chaque enregistrement, l'intelligence artificielle traduit et synchronise automatiquement toutes les versions en <strong class="text-white">anglais</strong> pour vos voyageurs internationaux.
                                    </p>
                                </div>
                            </div>

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

                            <!-- CHAMPS ANGLAIS MASQUÉS (Alimentés automatiquement par l'IA lors de la sauvegarde) -->
                            <input type="hidden" id="parking-intro-en">
                            <input type="hidden" id="parking-rule-en">
                            <input type="hidden" id="parking-free-notes-en">
                            <input type="hidden" id="parking-street-info-en">
                            <input type="hidden" id="parking-street-sched-en">
                            <input type="hidden" id="parking-street-rates-en">
                            <input type="hidden" id="parking-tcl-cond-en">
                            <input type="hidden" id="parking-tcl-notes-en">

                            <!-- Volet accordéon replié : Aperçu de la traduction anglaise en base -->
                            <details class="rounded-xl border border-night-border/70 bg-night/40 text-xs group">
                                <summary class="p-3 font-semibold text-gray-400 hover:text-white cursor-pointer select-none flex items-center justify-between">
                                    <span class="flex items-center space-x-2">
                                        <span>🇬🇧</span>
                                        <span>Consulter l'aperçu de la version anglaise (générée par l'IA)</span>
                                    </span>
                                    <span class="text-gray-500 group-open:rotate-180 transition-transform">▼</span>
                                </summary>
                                <div id="parking-general-en-preview" class="p-4 border-t border-night-border/70 space-y-3 text-gray-300 text-xs">
                                    <p class="text-[11px] text-gray-400 italic">Aucune traduction pour l'instant ou sélectionnez un logement.</p>
                                </div>
                            </details>

                            <div class="pt-4 flex items-center justify-between border-t border-night-border/50">
                                <span class="text-[11px] text-gray-400 flex items-center gap-1.5">
                                    <span>⚡</span>
                                    <span>Traduction IA instantanée à l'enregistrement</span>
                                </span>
                                <button type="submit" class="px-5 py-2.5 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-2">
                                    <span>💾</span>
                                    <span>Enregistrer & Traduire</span>
                                </button>
                            </div>
                            </div>
                        </form>
                    </div>

                    <!-- SOUS-ONGLET B : EMPLACEMENTS DÉTAILLÉS -->
                    <div id="parking-subtab-locations" class="hidden space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-2">
                                <select id="parking-locations-filter-cat" onchange="AdminApp.renderParkingLocationsList()" class="py-2 px-3 rounded-xl bg-night-card border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                                    <option value="all">Toutes les catégories</option>
                                    <option value="free_street">Rues gratuites</option>
                                    <option value="free_spot">Dépose-minute gratuit</option>
                                    <option value="private_parking">Parkings privés & couverts</option>
                                    <option value="tcl_pr">Parcs Relais TCL (P+R)</option>
                                </select>
                            </div>
                            <button type="button" onclick="AdminApp.openLocationModal(0)" class="px-3.5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition flex items-center space-x-1.5 self-start sm:self-auto">
                                <span>+</span>
                                <span>Ajouter un emplacement</span>
                            </button>
                        </div>

                        <div id="parking-locations-list">
                            <div class="py-8 text-center text-gray-400">Chargement des emplacements...</div>
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
    <div id="modal-property-form" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 sm:p-7 shadow-2xl relative my-auto">
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
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Slug URL unique *</label>
                        <input type="text" id="property-slug" required placeholder="bataille"
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

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Lien Guide de bienvenue PDF (Français)</label>
                        <input type="url" id="property-guide-fr" placeholder="https://guides.carredhotes.fr/bataille/guide-fr.pdf"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Lien Guide de bienvenue PDF (Anglais)</label>
                        <input type="url" id="property-guide-en" placeholder="https://guides.carredhotes.fr/bataille/guide-en.pdf"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" id="property-active" class="w-4 h-4 rounded bg-night border-night-border text-gold focus:ring-gold accent-[#ba9641]">
                    <label for="property-active" class="text-xs font-semibold text-gray-300 cursor-pointer">Hébergement actif (visible sur le portail)</label>
                </div>

                <div class="pt-4 border-t border-night-border flex justify-end space-x-2">
                    <button type="button" onclick="AdminApp.closePropertyModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Annuler</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. MODALE VIDÉO TUTORIEL -->
    <div id="modal-video-form" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-3xl w-full max-w-lg p-6 sm:p-7 shadow-2xl relative my-auto">
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
                        <input type="text" id="video-title-fr" required placeholder="Machine à café"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Titre (Anglais)</label>
                        <input type="text" id="video-title-en" placeholder="Coffee machine"
                               class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Slug URL unique</label>
                    <input type="text" id="video-slug" placeholder="machine-a-cafe"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">URL de la vidéo (MP4 ou Google Drive) *</label>
                    <input type="url" id="video-url" required placeholder="https://videos.carredhotes.fr/bataille/machine-a-cafe.mp4"
                           class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
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
                    <button type="button" onclick="AdminApp.closeVideoModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Annuler</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. MODALE LECTEUR VIDÉO APERÇU -->
    <div id="modal-video-preview" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden">
        <div class="bg-night-card border border-night-border rounded-3xl w-full max-w-xl p-5 shadow-2xl relative my-auto">
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
    <div id="modal-location-form" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden overflow-y-auto">
        <div class="bg-night-card border border-night-border rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 sm:p-7 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-4 mb-5">
                <div>
                    <h3 id="modal-location-title" class="text-base font-bold text-white">Ajouter un emplacement</h3>
                    <p class="text-[11px] text-gray-400">Remplissez les informations en français (traduction anglaise automatique par IA).</p>
                </div>
                <button type="button" onclick="AdminApp.closeLocationModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>

            <form id="location-form" onsubmit="AdminApp.saveLocation(event)" class="space-y-4">
                <input type="hidden" id="location-id" value="0">
                <input type="hidden" id="location-property-id" value="0">

                <!-- CHAMPS ANGLAIS MASQUÉS (remplis automatiquement par l'IA lors de la sauvegarde) -->
                <input type="hidden" id="location-name-en">
                <input type="hidden" id="location-walking-en">
                <input type="hidden" id="location-transit-en">
                <input type="hidden" id="location-hours-en">
                <input type="hidden" id="location-rates-en">
                <input type="hidden" id="location-vehicles-en">
                <input type="hidden" id="location-reach-en">
                <input type="hidden" id="location-notes-en">

                <!-- Bannière rappel IA -->
                <div class="bg-gold/10 border border-gold/30 rounded-xl p-3 flex items-center space-x-2 text-xs text-gold-light">
                    <span>✨</span>
                    <span>Traduction bilingue : Les équivalents anglais seront générés automatiquement par l'IA lors de l'enregistrement.</span>
                </div>

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

                <div class="pt-4 border-t border-night-border flex justify-end space-x-2">
                    <button type="button" onclick="AdminApp.closeLocationModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Annuler</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. MODALE UTILISATEUR ADMINISTRATEUR -->
    <div id="modal-user-form" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden">
        <div class="bg-night-card border border-night-border rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-2xl relative my-auto">
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
    <div id="modal-profile" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden">
        <div class="bg-night-card border border-night-border rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-2xl relative my-auto">
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
    <div id="modal-log-details" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden">
        <div class="bg-night-card border border-night-border rounded-3xl w-full max-w-lg p-6 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-3 mb-4">
                <h4 class="text-sm font-bold text-white">Détails des modifications (JSON)</h4>
                <button type="button" onclick="AdminApp.closeLogDetails()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>
            <pre id="log-details-json" class="p-4 rounded-xl bg-night border border-night-border text-[11px] font-mono text-gray-300 overflow-x-auto max-h-80"></pre>
        </div>
    </div>

    <!-- 8. MODALE DE CONFIRMATION GÉNÉRIQUE -->
    <div id="modal-confirmation" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden">
        <div class="bg-night-card border border-red-500/30 rounded-3xl w-full max-w-md p-6 shadow-2xl relative my-auto text-center space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-400 border border-red-500/30 flex items-center justify-center text-xl mx-auto">
                ⚠️
            </div>
            <h4 id="confirm-title" class="text-base font-bold text-white">Êtes-vous sûr ?</h4>
            <p id="confirm-message" class="text-xs text-gray-400 leading-relaxed"></p>
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

    <!-- 9. MODALE CONFIGURATION INTELLIGENCE ARTIFICIELLE (IA) -->
    <div id="modal-ai-settings" class="fixed inset-0 z-50 flex items-center justify-center p-4 admin-modal-backdrop hidden">
        <div class="bg-night-card border border-gold/40 rounded-3xl w-full max-w-lg p-6 sm:p-7 shadow-2xl relative my-auto">
            <div class="flex items-center justify-between border-b border-night-border pb-4 mb-5">
                <div class="flex items-center space-x-2.5">
                    <span class="w-8 h-8 rounded-xl bg-gold/15 border border-gold/30 flex items-center justify-center text-gold text-base">✨</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Traduction Automatique & IA</h3>
                        <p class="text-[11px] text-gray-400">Synchronisation bilingue FR ➜ EN instantanée</p>
                    </div>
                </div>
                <button type="button" onclick="AdminApp.closeAiSettingsModal()" class="text-gray-400 hover:text-white text-lg">✕</button>
            </div>

            <form id="ai-settings-form" onsubmit="AdminApp.saveAiSettings(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Moteur d'Intelligence Artificielle *</label>
                    <select id="ai-provider-select" onchange="AdminApp.handleAiProviderChange()" class="w-full p-2.5 rounded-xl bg-night border border-night-border text-xs text-white focus:outline-none focus:border-gold">
                        <option value="gemini">Google Gemini (Recommandé - rapide, précis & économique)</option>
                        <option value="openai">ChatGPT / OpenAI (gpt-4o-mini)</option>
                        <option value="claude">Anthropic Claude (claude-3-5-haiku)</option>
                    </select>
                </div>

                <!-- Section Clé API Gemini -->
                <div id="ai-key-group-gemini" class="ai-key-group space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-gray-300">Clé API Google Gemini</label>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="text-[11px] text-gold hover:underline">Obtenir une clé Google AI Studio ↗</a>
                    </div>
                    <div class="relative">
                        <input type="password" id="ai-key-gemini" placeholder="AIzaSy..." class="w-full p-2.5 pr-10 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                        <button type="button" onclick="AdminApp.toggleKeyVisibility('ai-key-gemini')" class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs">👁️</button>
                    </div>
                    <p class="text-[11px] text-gray-400">Modèle actif : <code class="text-gold-light bg-gold/10 px-1 py-0.5 rounded">gemini-3.5-flash</code> (repli auto sur <code class="text-gold-light bg-gold/10 px-1 py-0.5 rounded">gemini-3-flash-preview</code>)</p>
                </div>

                <!-- Section Clé API OpenAI -->
                <div id="ai-key-group-openai" class="ai-key-group space-y-1.5 hidden">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-gray-300">Clé API OpenAI (ChatGPT)</label>
                        <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer" class="text-[11px] text-gold hover:underline">Obtenir une clé OpenAI ↗</a>
                    </div>
                    <div class="relative">
                        <input type="password" id="ai-key-openai" placeholder="sk-proj-..." class="w-full p-2.5 pr-10 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                        <button type="button" onclick="AdminApp.toggleKeyVisibility('ai-key-openai')" class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs">👁️</button>
                    </div>
                    <p class="text-[11px] text-gray-400">Modèle actif : <code class="text-gold-light bg-gold/10 px-1 py-0.5 rounded">gpt-4o-mini</code></p>
                </div>

                <!-- Section Clé API Claude -->
                <div id="ai-key-group-claude" class="ai-key-group space-y-1.5 hidden">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-gray-300">Clé API Anthropic Claude</label>
                        <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer" class="text-[11px] text-gold hover:underline">Obtenir une clé Anthropic ↗</a>
                    </div>
                    <div class="relative">
                        <input type="password" id="ai-key-claude" placeholder="sk-ant-..." class="w-full p-2.5 pr-10 rounded-xl bg-night border border-night-border text-xs text-white font-mono focus:outline-none focus:border-gold">
                        <button type="button" onclick="AdminApp.toggleKeyVisibility('ai-key-claude')" class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs">👁️</button>
                    </div>
                    <p class="text-[11px] text-gray-400">Modèle actif : <code class="text-gold-light bg-gold/10 px-1 py-0.5 rounded">claude-3-5-haiku-latest</code></p>
                </div>

                <!-- Zone résultat test connexion -->
                <div id="ai-test-result" class="hidden p-3 rounded-xl text-xs space-y-1"></div>

                <div class="pt-4 border-t border-night-border flex items-center justify-between">
                    <button type="button" id="btn-test-ai" onclick="AdminApp.testAiConnection()" class="px-3.5 py-2 rounded-xl bg-night border border-night-border hover:border-gold/50 text-xs text-gray-300 hover:text-white transition flex items-center space-x-1.5">
                        <span>⚡</span>
                        <span>Tester la connexion</span>
                    </button>
                    <div class="flex space-x-2">
                        <button type="button" onclick="AdminApp.closeAiSettingsModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs text-gray-300 transition">Fermer</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-gold hover:bg-gold-light text-white font-bold text-xs shadow-lg shadow-gold/20 transition">Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- CONTENEUR DES TOASTS DE NOTIFICATION -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col space-y-2 pointer-events-none max-w-sm w-full"></div>

    <!-- Script principal de l'application -->
    <script src="assets/js/admin.js?v=1.1"></script>
</body>
</html>

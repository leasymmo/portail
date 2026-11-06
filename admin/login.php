<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/Admin/Auth.php';

use Cdh\Portal\Admin\Auth;

// Si déjà connecté, redirection directe vers le tableau de bord
if (Auth::user() !== null) {
    header('Location: index.php');
    exit;
}

$isDev = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/portail/dev') !== false);
?>
<!DOCTYPE html>
<html lang="fr" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration — Carré d'Hôtes</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../assets/icons/apple-touch-icon.png">
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
                        'night-card': '#141d2e',
                        'night-border': '#223048'
                    }
                }
            }
        };
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-serif-luxury { font-family: 'Playfair Display', Georgia, serif; }
        .bg-pattern {
            background-image: radial-gradient(rgba(186, 150, 65, 0.08) 1px, transparent 1px);
            background-size: 28px 28px;
        }
    </style>
</head>
<body class="bg-night text-gray-100 min-h-screen flex flex-col justify-center items-center p-4 sm:p-6 bg-pattern relative selection:bg-gold selection:text-white">

    <!-- En-tête Badge Dev / Prod -->
    <div class="absolute top-4 left-4 sm:top-6 sm:left-6 flex items-center space-x-2">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $isDev ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' ?>">
            <span class="w-1.5 h-1.5 rounded-full <?= $isDev ? 'bg-amber-400' : 'bg-emerald-400' ?> mr-1.5 animate-pulse"></span>
            <?= $isDev ? 'Environnement Dev (Staging)' : 'Environnement Production' ?>
        </span>
    </div>

    <!-- Carte de connexion -->
    <div class="w-full max-w-md bg-night-card border border-night-border rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden backdrop-blur-xl">
        <!-- Effet halo doré de fond -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-gold/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center mb-8 relative">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gold/15 border border-gold/30 flex items-center justify-center text-gold shadow-lg shadow-gold/10">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="4"/>
                    <path d="m9 9 6 6m0-6-6 6"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold font-serif-luxury tracking-wide text-white">Carré d'Hôtes</h1>
            <p class="text-xs uppercase tracking-widest text-gold mt-1 font-semibold">Console d'Administration</p>
            <p class="text-xs text-gray-400 mt-2">Connectez-vous pour administrer les hébergements, vidéos et stationnements.</p>
        </div>

        <!-- Bannière d'alerte erreur -->
        <div id="login-alert" class="hidden mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs flex items-start space-x-2.5">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span id="login-alert-msg" class="leading-relaxed"></span>
        </div>

        <!-- Formulaire -->
        <form id="login-form" onsubmit="handleLogin(event)" class="space-y-4">
            <div>
                <label for="email" class="block text-xs font-semibold text-gray-300 mb-1.5 uppercase tracking-wider">Adresse Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                    </span>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="admin@carredhotes.fr"
                           class="w-full pl-10 pr-4 py-3 rounded-xl bg-night border border-night-border text-sm text-white placeholder-gray-500 focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold transition">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-gray-300 mb-1.5 uppercase tracking-wider">Mot de passe</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••••••"
                           class="w-full pl-10 pr-10 py-3 rounded-xl bg-night border border-night-border text-sm text-white placeholder-gray-500 focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold transition">
                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-white transition" aria-label="Afficher ou masquer le mot de passe">
                        <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center space-x-2 cursor-pointer text-gray-300 hover:text-white transition select-none">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded bg-night border-night-border text-gold focus:ring-gold accent-[#ba9641]">
                    <span>Se souvenir de moi (30 jours)</span>
                </label>
            </div>

            <button type="submit" id="submit-btn"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-gold to-gold-light hover:from-gold-dark hover:to-gold text-white font-bold text-sm tracking-wide shadow-lg shadow-gold/20 transition transform active:scale-[0.98] flex items-center justify-center space-x-2">
                <span id="btn-label">Se connecter</span>
                <svg id="btn-spinner" class="hidden animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-night-border/60 text-center">
            <a href="../" class="inline-flex items-center text-xs text-gray-400 hover:text-gold transition">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour au portail voyageur
            </a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
        }

        async function handleLogin(e) {
            e.preventDefault();
            const alertBox = document.getElementById('login-alert');
            const alertMsg = document.getElementById('login-alert-msg');
            const btn = document.getElementById('submit-btn');
            const label = document.getElementById('btn-label');
            const spinner = document.getElementById('btn-spinner');

            alertBox.classList.add('hidden');
            btn.disabled = true;
            label.textContent = 'Vérification...';
            spinner.classList.remove('hidden');

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, remember })
                });

                const data = await res.json();
                if (res.ok && data.ok) {
                    label.textContent = 'Connexion réussie...';
                    window.location.href = 'index.php';
                } else {
                    alertMsg.textContent = data.error || 'Identifiants incorrects.';
                    alertBox.classList.remove('hidden');
                    btn.disabled = false;
                    label.textContent = 'Se connecter';
                    spinner.classList.add('hidden');
                }
            } catch (err) {
                alertMsg.textContent = 'Impossible de contacter le serveur d\'administration.';
                alertBox.classList.remove('hidden');
                btn.disabled = false;
                label.textContent = 'Se connecter';
                spinner.classList.add('hidden');
            }
        }
    </script>
</body>
</html>

# Portail Voyageurs — Conciergerie Carré d'Hôtes

Système unifié et dynamique pour les livrets d'accueil et vidéos des hébergements de **Carré d'Hôtes** à Lyon.

Hébergement : **`carredhotes.fr/portail`** (dossier `www/portail` sur OVH).

---

## 🌟 Points clés & Améliorations récentes

1. **Front-end 100% HTML, CSS & JavaScript pur (`index.html`)** :
   - Zéro code PHP dans le front-end.
   - Passerelle sécurisée en JSON (`api.php`) pour interagir avec MySQL 8.4.
2. **Onglets segmentés « Guides » et « Vidéos »** :
   - Contrôleur tactile ergonomique avec contraste renforcé pour identifier instantanément l'onglet actif.
3. **Modale Vidéo optimisée pour le format VERTICAL (9:16)** :
   - La modale vidéo monte à la même hauteur que la modale PDF (`94vh`).
   - Boîtier d'affichage vertical adapté aux vidéos enregistrées sur smartphone.
   - Compatible Google Drive (iframe officielle) et Cloudflare R2 (lecteur HTML5 haute définition).
4. **Modale PDF slide-up** :
   - Lecture directe des livrets d'accueil FR et EN avec possibilité de partage direct par URL (`/portail/{slug}/guides/pdf-fr`).
5. **Dark Mode & Light Mode** :
   - Détection automatique de l'OS / navigateur et bascule manuelle avec mémorisation.
   - Utilisation des deux pictos : `picto-light.png` en mode clair et `picto-dark.png` en mode sombre.
6. **Routes propres sans dièse (`#`)** :
   - `/portail/{slug}`
   - `/portail/{slug}/guides`
   - `/portail/{slug}/tutos`
   - `/portail/{slug}/guides/pdf-fr`

---

## 🔒 Audit de sécurité réalisé & protections actives

| Vecteur d'attaque | Protection mise en place |
| :--- | :--- |
| **Fuite du mot de passe MySQL** | Identifiants confinés dans `.env` et `.htaccess` bloque strictement tout accès HTTP à `.env`. |
| **Injections SQL** | Requêtes préparées PDO avec typage strict + validation regex du `slug` (`/^[a-z0-9_\-]{1,100}$/`). |
| **Failles XSS (Cross-Site Scripting)** | Échappement HTML systématique (`escapeHtml`) + filtrage strict des URLs (`safeUrl` bloquant `javascript:`). |
| **Vol de données / Listing** | `Options -Indexes` interdit le parcours des répertoires sur le serveur. |
| **Protection des dossiers d'infrastructure** | Accès interdit via `.htaccess` aux répertoires `database/` et `config/`. |
| **Clickjacking & Reniflage MIME** | En-têtes HTTP `X-Frame-Options: SAMEORIGIN` et `X-Content-Type-Options: nosniff`. |
| **Sécurisation de `test-db.php`** | Réinitialisation désactivée dès que des données existent en base. Fichier pouvant être supprimé en production. |

---

## 📁 Structure des fichiers

```text
www/portail/
├── index.html            # APPLICATION FRONT-END (HTML, CSS, JS pur)
├── api.php               # Passerelle JSON sécurisée vers MySQL
├── .env                  # Configuration du mot de passe MySQL (DB_PASS)
├── .env.example          # Modèle de configuration
├── .htaccess             # Réécriture Apache SPA sans # et sécurité HTTP
├── test-db.php           # Outil de diagnostic (à supprimer après vérification)
├── assets/
│   ├── css/
│   │   └── custom.css    # Styles Carré d'Hôtes, Dark Mode & vidéos verticales
│   └── images/
│       ├── picto-light.png # Logo sombre (Light Mode)
│       ├── picto-dark.png  # Logo clair (Dark Mode)
│       └── logo.svg      # Logo vectoriel
├── config/
│   └── database.php      # Connexion PDO MySQL
└── database/
    ├── schema.sql        # Schéma des tables
    ├── seed.sql          # Données des 9 logements
    └── install.sql       # Script complet pour phpMyAdmin
```

---

## 🚀 Mise à jour sur votre serveur OVH

Envoyez simplement par FTP dans `www/portail/` :
- `index.html`
- `api.php`
- `.htaccess`
- `assets/css/custom.css`
- `test-db.php`
- `README.md`

# Instructions & Protocole de Développement — Portail Carré d'Hôtes

Ce document définit les règles strictes de déploiement et le cycle de vie des fonctionnalités sur le projet du portail.

---

## Règle Absolue de Sécurité : Branche `main`
> [!CAUTION]
> **NE JAMAIS pousser ni fusionner directement dans la branche `main` sans l'autorisation expresse et écrite de l'utilisateur.**
> La branche `main` déclenche le déploiement immédiat en production sur le FTP OVH (`www/portail/`).

---

## Workflow Standard pour Toute Nouvelle Fonctionnalité

À chaque demande d'amélioration ou de nouvelle fonctionnalité, l'agent doit impérativement respecter le cycle en 4 étapes suivant :

### Étape 1 : Développement
* L'utilisateur formule une demande d'amélioration ou de correctif.
* Le code est implémenté et testé sur la branche **`dev`** (ou une sous-branche fusionnée dans `dev`).

### Étape 2 : Push sur `dev` (Déploiement Staging)
* Une fois la fonctionnalité développée, réaliser un push sur la branche **`dev`** :
  ```bash
  git checkout dev
  git add <fichiers>
  git commit -m "feat: ..."
  git push origin dev
  ```
* Le GitHub Action se déclenche automatiquement pour synchroniser les fichiers vers le dossier FTP : **`www/portail/dev/`**.

### Étape 3 : Proposition de recette à l'utilisateur
* Inviter l'utilisateur à vérifier et tester le rendu directement en ligne sur l'environnement de développement :
  👉 **`https://www.carredhotes.fr/portail/dev`**
* Attendre son retour et ses éventuels ajustements.

### Étape 4 : Déploiement en Production (après confirmation)
* **UNIQUEMENT** après confirmation et validation explicite de l'utilisateur :
  1. Basculer sur `main` :
     ```bash
     git checkout main
     git merge dev
     git push origin main
     ```
  2. Le GitHub Action déploie alors en production sur **`www/portail/`**.
  3. Inviter l'utilisateur à vérifier sur le portail en production :
     👉 **`https://www.carredhotes.fr/portail/`**
  4. Revenir sur la branche `dev` pour les futurs développements :
     ```bash
     git checkout dev
     ```

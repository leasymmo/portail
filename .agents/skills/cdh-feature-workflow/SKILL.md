---
name: cdh-feature-workflow
description: Protocole de cycle de vie et déploiement par étapes (dev -> recette www.carredhotes.fr/portail/dev -> accord utilisateur -> merge main -> production) pour le portail Carré d'Hôtes.
---

# Cycle de Développement & Déploiement CDH

Ce guide décrit le déroulement opérationnel pour toute intervention sur le code du Portail Voyageurs Carré d'Hôtes.

## Résumé du cycle
1. Développer l'amélioration sur `dev`.
2. Pousser sur `origin dev` -> GitHub Action déploie sur FTP `www/portail/dev/`.
3. Proposer la vérification sur `https://www.carredhotes.fr/portail/dev`.
4. Attendre l'accord explicite de l'utilisateur.
5. Fusionner `dev` dans `main` et pousser -> GitHub Action déploie sur `www/portail/`.
6. Proposer la vérification en production sur `https://www.carredhotes.fr/portail/`.

## Règle de garde-fou
Ne jamais exécuter `git push origin main` sans accord écrit explicite de l'utilisateur.

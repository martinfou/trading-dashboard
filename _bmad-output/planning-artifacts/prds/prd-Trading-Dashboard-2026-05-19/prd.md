---
title: PRD — Trading Dashboard (Backtest Module)
status: draft
created: 2026-05-19
updated: 2026-05-19
---

# PRD — Trading Dashboard — Module Backtest

## Contexte
Le projet Oanda Strategies produit des rapports de backtest (JSON, CSV, HTML).
Le trading-dashboard Laravel existant doit les afficher avec des visuels clairs.

## Objectif UX (pour Sally 🎨)
Créer une expérience de consultation des backtests qui soit :
- **Claire** : les métriques importantes en un coup d'œil
- **Comparable** : les 5 stratégies côte à côte
- **Explorable** : drill-down dans les trades individuels
- **Responsive** : utilisable sur mobile (téléphone de Martin)

## Pages à concevoir

### Page 1 — Index / Tableau de Bord
- Tuile pour chaque stratégie avec couleur (vert/jaune/rouge)
- Métriques clés : Return%, Sharpe, WinRate, Drawdown
- Bouton "Lancer un backtest" (appelle le jar Java)
- Filtre par stratégie / période

### Page 2 — Détail d'une stratégie
- Équity curve interactive (Chart.js)
- Drawdown chart en dessous
- Distribution des trades (gagnants/perdants)
- Tableau des trades avec tri et filtre
- Métriques détaillées

### Page 3 — Comparaison
- Radar chart des 5 stratégies (Sharpe, Return, WinRate, Drawdown)
- Tableau comparatif complet
- Recommandation automatique

## Design System
- Utiliser Tailwind CSS (déjà dans le projet)
- Thème dark (comme le dashboard existant)
- Graphiques Chart.js (déjà présent dans les dépendances)
- Icônes Heroicons (déjà présent)

## Notes pour Sally
L'interface Backtest est déjà partiellement construite dans :
- `resources/js/Pages/Backtest/Index.vue` (tableau)
- `resources/js/Pages/Backtest/Show.vue` (détail)

Le but est d'améliorer le design et l'UX, pas de tout réécrire.

# 🧪 Critique UX — Module Backtest (Trading Dashboard)

**Auteure :** Sally, UX Designer  
**Date :** 2026-05-19  
**Contexte :** Revue des pages `Index.vue` et `Show.vue` du module Backtest, après implémentation initiale par l'équipe dev.  
**Périmètre :** Interface utilisateur uniquement (hors moteur Java, logique métier, API).

---

## Résumé exécutif

Le module Backtest est fonctionnel mais présente **des lacunes UX significatives** qui nuisent à son adoption par un trader. Les données sont toutes présentes dans le JSON, mais leur présentation est **plate, sans hiérarchie, sans contexte visuel, et cassée sur mobile**. Le principal irritant : le graphique d'équity est un **iframe HTML généré par le backend Java**, qui ne s'affiche pas correctement et ne peut pas être stylisé.

**7 problèmes critiques**, **8 moyens**, **3 mineurs**. Des solutions concrètes sont proposées pour chaque problème, la plupart étant réalisables en < 2 jours.

---

## 1. Problèmes UX identifiés

### 1.1 Page Index (`Backtest/Index.vue`)

#### 🔴 Critique

| # | Problème | Détail |
|---|----------|--------|
| **I-1** | **Pas de hiérarchie visuelle entre les stratégies** | Le tableau comparatif traite toutes les stratégies de façon égale. Impossible de savoir en un coup d'œil laquelle est la plus performante. Les chiffres sont noyés dans un mur de texte. Le cerveau humain ne scanne pas un tableau 11 colonnes efficacement — il a besoin de **poids visuels** (couleur, taille, position). |
| **I-2** | **Aucun indicateur visuel de performance** | Return positif/négatif : seule une classe de couleur sur le texte (`text-emerald-400` / `text-red-400`) distingue les bonnes des mauvaises performances. Pas de dégradé, pas de carte, pas de barre de progression, pas de sparkline. Un trader prend des décisions en **millisecondes visuelles**, pas en lisant des cellules. |
| **I-3** | **Pas de vue comparative** | Impossible de comparer visuellement les stratégies entre elles. Pas de barres horizontales, pas de radar, pas de graphique. L'utilisateur doit faire le travail cognitif de comparer les chiffres colonne par colonne. |
| **I-4** | **Bouton "Lancer le backtest" sans feedback** | Pas de spinner, pas d'état loading, pas de barre de progression. L'utilisateur clique et ne sait pas si le backtest a démarré. Le process Java prend 1 à 5 minutes — c'est une éternité sans feedback. |

#### 🟡 Moyen

| # | Problème | Détail |
|---|----------|--------|
| **I-5** | **Tableau non responsive** | 11 colonnes serrées dans `text-[11px]`. Sur mobile (< 640px), les colonnes deviennent illisibles. Le scroll horizontal n'est pas optimisé (pas de sticky colonne stratégie). |
| **I-6** | **Pas d'icônes ou d'indices visuels dans les métriques** | `Profit Factor`, `Sharpe Ratio`, `Avg Win` — des concepts financiers abstraits sans icône ni contexte. Un utilisateur non-expert ne sait pas si 1.5 est bon ou mauvais. |
| **I-7** | **Section stats consécutives redondante** | Les infos `Max Cons. Wins` et `Max Cons. Losses` sont des détails avancés. Les mettre au même niveau que les métriques clés crée du bruit. |

#### 🟢 Mineur

| # | Problème | Détail |
|---|----------|--------|
| **I-8** | **Tri par date uniquement** | Les stratégies sont triées par date de création (plus récent d'abord). Le tri par défaut devrait être par performance. |
| **I-9** | **Pas de pagination** | Si un utilisateur lance 50 backtests, la page devient ingérable. |
| **I-10** | **Pas de filtre/recherche** | Impossible de chercher une stratégie par nom si la liste s'allonge. |

---

### 1.2 Page Show (`Backtest/Show.vue`)

#### 🔴 Critique

| # | Problème | Détail |
|---|----------|--------|
| **S-1** | **Graphique d'équity en iframe cassé** | Le rapport graphique est injecté via `iframe srcdoc` avec un HTML généré par le backend Java. Problèmes : (a) le HTML est souvent mal formé ou vide, (b) l'iframe ne s'affiche pas correctement dans un thème dark, (c) pas de redimensionnement responsive, (d) impossible d'interagir (tooltip, zoom), (e) chargement lent. **C'est le problème numéro 1.** |
| **S-2** | **Pas de courbe d'équity visible** | L'equity curve existe dans le JSON (`report.equityCurve`) mais n'est pas affichée. Le trader doit deviner l'évolution de son capital dans le temps. |
| **S-3** | **Pas de visualisation du drawdown** | Le drawdown maximum est un nombre dans une carte, mais il n'y a **aucune courbe de drawdown**. Le drawdown est un indicateur de risque critique — le voir dans le temps est essentiel. |
| **S-4** | **Grille de métriques sans priorité** | 12 cartes identiques. Trades côtoie Sharpe Ratio côtoie Max Cons. Losses. Aucune distinction entre métriques primaires (Return, Sharpe) et secondaires (Consecutive Wins). |

#### 🟡 Moyen

| # | Problème | Détail |
|---|----------|--------|
| **S-5** | **Tableau des trades lisible mais dense** | 11 colonnes, texte en `11px`. Les P&L sont en texte brut sans barre de proportion. |
| **S-6** | **Pas de vue synthétique des trades** | Impossible de voir la répartition gagnant/perdant, la distribution des P&L, ou la séquence temporelle. |
| **S-7** | **Bouton "Afficher le graphique" trompeur** | L'utilisateur clique, attend le chargement de l'iframe, et voit souvent un graphique cassé. |

#### 🟢 Mineur

| # | Problème | Détail |
|---|----------|--------|
| **S-8** | **Pas de breadcrumb** | Seul un lien "← Retour" en haut. Pas de navigation fil d'Ariane. |
| **S-9** | **Format des dates en ISO** | `entryTime: "2026-01-17T00:00:00"` affiché avec des `T` au lieu d'un format lisible. |

---

## 2. Solutions proposées

### 2.1 Page Index

#### 🔴 I-1 + I-2 : Cartes stratégies avec hiérarchie visuelle

**Solution :** Remplacer le tableau (ou le précéder) par des **cartes individuelles** avec :
- Fond en dégradé : vert foncé (`#0d2b1a` → `#161b22`) si profitable, rouge foncé (`#2d0f14` → `#161b22`) si perdant
- Return % en **gros chiffre** (text-3xl font-extrabold) — le premier élément que l'œil voit
- Mini sparkline proportionnelle (barre gagnants/perdants) sous les métriques
- Lien "Détails →" apparaissant au hover
- Bord supérieur lumineux (2px) couleur emerald ou red

**Pourquoi c'est mieux :** Le cerveau scanne les cartes en F-pattern. La couleur, la taille et la position créent une hiérarchie instantanée. Le trader voit en un coup d'œil quelle stratégie gagne et laquelle perd.

**Effort :** ⏱️ Moyen (modification complète du template, logique de tri)

#### 🔴 I-3 : Section comparaison visuelle

**Solution :** Ajouter une section **"Comparaison — Return total"** sous les cartes avec :
- Barres horizontales pour chaque stratégie, proportionnelles au Return%
- Couleur verte (profitable) ou rouge (perdante) avec dégradé
- Tri par performance descendante
- Optionnel : radar chart avec Chart.js pour comparer sur 5 métriques (Return, Sharpe, WinRate, Drawdown, ProfitFactor)

**Pourquoi c'est mieux :** Les barres horizontales sont le format le plus rapide à lire pour une comparaison. Pas de calcul mental.

**Effort :** ⏱️ Faible (CSS uniquement, pas de librairie)

#### 🔴 I-4 : Bouton avec spinner

**Solution :** Ajouter un état `running` avec :
- SVG spinner animé (animation CSS `animate-spin`)
- Texte qui passe de "Lancer le backtest" à "Exécution en cours…"
- Bouton disabled pendant l'exécution (`disabled:cursor-not-allowed disabled:opacity-60`)
- Barre de progression déterminée si possible (via polling SSE ou estimation)

**Pourquoi c'est mieux :** Feedback immédiat = confiance. L'utilisateur sait que son action a été reçue.

**Effort :** ⏱️ Faible (2 états dans le composant)

#### 🟡 I-5 : Tableau responsive

**Solution :**
- Sticky première colonne (nom de stratégie) pour garder le contexte visible
- Scroll horizontal avec `overflow-x-auto`
- Colonnes prioritaires : masquer `Avg Win`, `Avg Loss`, `Sharpe` sur `< lg`
- Police tabulaire (`tabular-nums`) pour aligner les chiffres

**Pourquoi c'est mieux :** Utilisable sur mobile sans perdre l'information clé.

**Effort :** ⏱️ Faible (classes Tailwind + `hidden lg:table-cell`)

#### 🟡 I-6 + I-7 : Métriques avec icônes et contexte

**Solution :** Ajouter un émoji/icône devant chaque métrique. Grouper les métriques :
- **Primaires** (carte) : Return, Sharpe, WinRate, DD Max
- **Secondaires** (section stats) : Max Cons. Wins/Losses, Balance, Période

**Pourquoi c'est mieux :** Réduit la charge cognitive. Les icônes sont reconnues plus vite que les mots.

**Effort :** ⏱️ Faible

---

### 2.2 Page Show

#### 🔴 S-1 + S-2 + S-3 : Remplacer l'iframe par Chart.js inline

**Solution :** Utiliser **Chart.js via vue-chartjs** avec deux canvas :
1. **Courbe d'équity** (Line chart) :
   - Données : `report.equityCurve`
   - Ligne bleue (`#3b82f6`) avec remplissage transparent
   - Axe Y formaté en dollars
   - Tooltip interactif au hover
   - Tension 0.15 pour courbes lissées

2. **Drawdown** (Area chart) :
   - Données calculées depuis l'equity curve : `(peak - current) / peak * 100`
   - Ligne rouge (`#ef4444`) avec remplissage rouge transparent
   - Axe Y inversé (drawdown négatif vers le bas)
   - Même échelle de temps que l'equity curve pour superposition mentale

**Pourquoi c'est mieux :**
- Finies les iframes cassées
- Graphiques interactifs (tooltips, hover)
- Responsive (Chart.js se redimensionne)
- Thème dark cohérent
- Pas de dépendance au backend pour le rendu

**Effort :** ⏱️ Moyen (installation chart.js + composant + calcul drawdown)

#### 🔴 S-4 : Grille de métriques priorisée

**Solution :** Conserver la grille 12 cartes mais ajouter :
- **Bordures colorées** : verte si bon, rouge si mauvais, grise si neutre
- **Icônes** émoji devant chaque label
- Métriques critiques (Return, Drawdown) visuellement plus grandes
- Hover avec shadow douce

**Pourquoi c'est mieux :** La couleur sur la bordure crée un repère visuel immédiat sans surcharger le fond.

**Effort :** ⏱️ Faible (classes dynamiques)

#### 🟡 S-5 : Tableau des trades amélioré

**Solution :**
- Badges colorés pour exitReason (SL/rouge, PT/vert, TRAILING/bleu)
- Badges direction stylisés (LONG/vert, SHORT/rouge) avec fond semi-transparent
- Sticky colonnes gauche (dates d'entrée)
- Police tabulaire pour alignement
- Mini barre de P&L (proportionnelle) pour voir la taille relative des trades

**Pourquoi c'est mieux :** Les badges sont reconnus 3x plus vite que du texte.

**Effort :** ⏱️ Faible (classes CSS)

#### 🟡 S-6 : Vue synthétique des trades

**Solution (future itération) :** Ajouter un mini histogramme des P&L ou une scatter plot (P&L vs barsHeld) avec Chart.js.

**Pourquoi c'est mieux :** Donne une vue d'ensemble de la distribution des trades.

**Effort :** ⏱️ Élevé (nécessite un canvas supplémentaire et de la réflexion design)

---

## 3. Mockups textuels

### 3.1 Page Index — Structure idéale (haut → bas)

```
┌──────────────────────────────────────────────────────────────┐
│ [Header]                                                    │
│   Backtest — Résultats des stratégies    [▶ Lancer] 🔄     │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌───────────────────┐ ┌───────────────────┐ ┌────────────┐ │
│  │ 📈 Strategy_A     │ │ 📈 Strategy_B     │ │ 📈 Str_C   │ │
│  │ ████████████████  │ │ ██████            │ │ █████████  │ │
│  │ +24.5% Return     │ │ -8.2% Return      │ │ +12.1% Ret │ │
│  │ Sharpe  Win  DD   │ │ Sharpe  Win  DD   │ │ Sh  Win DD │ │
│  │ ════🟢══🔴═══    │ │ ════🟢══🔴═══    │ │ ══🟢══🔴═  │ │
│  │ [Détails →]       │ │ [Détails →]       │ │ [Détails→] │ │
│  └───────────────────┘ └───────────────────┘ └────────────┘ │
│                                                              │
│  ┌── Comparaison — Return total ──────────────────────────┐ │
│  │ Strategy_A ████████████████████████  +24.5%            │ │
│  │ Strategy_B ██████████████              +12.1%           │ │
│  │ Strategy_C ███████                     -8.2%           │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌── Tableau détaillé ────────────────────────────────────┐ │
│  │ Stratégie │ Trades │ WR │ Return │ DD │ PF │ Sharpe │  │ │
│  │──────────┼────────┼────┼────────┼─────┼────┼────────│  │ │
│  │ Str_A    │    142 │ 62 │ +24.5% │ 12% │ 2.1│   1.45 │  │ │
│  │ Str_B    │     98 │ 55 │ +12.1% │ 18% │ 1.4│   0.89 │  │ │
│  │ Str_C    │    201 │ 38 │  -8.2% │ 31% │ 0.7│  -0.32 │  │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌── Stats additionnelles ────────────────────────────────┐ │
│  │ [Max Wins] [Max Losses] [Balance] [Période]            │ │
│  └─────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

#### Comportement responsive

| Breakpoint | Cartes | Comparaison | Tableau |
|------------|--------|-------------|---------|
| **Desktop ≥ 1280px** | 3 colonnes | Affiché | Complet (11 colonnes) |
| **Tablet ≥ 768px** | 2 colonnes | Affiché | 8 colonnes prioritaires |
| **Mobile < 640px** | 1 colonne | Scroll X | 5 colonnes essentielles |

#### Animations / transitions

- Cartes : `hover:scale-[1.02]` + shadow au survol
- Barres de comparaison : `transition-all duration-700` au chargement (s'étirent depuis 0)
- Bouton "Lancer" : transition smooth entre icône et spinner
- Apparition : `animate-fadeIn` sur chaque carte (staggered)

---

### 3.2 Page Show — Structure idéale (haut → bas)

```
┌──────────────────────────────────────────────────────────────┐
│ [Header]                                                    │
│   ← Retour    Backtest — Strategy_A      2025-01 → 2025-12 │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────────┐ │
│  │🔄    │ │🎯    │ │📈    │ │📉    │ │⚡    │ │📊        │ │
│  │Trades│ │WinR. │ │Ret%  │ │DD    │ │PF    │ │Sharpe    │ │
│  │  142 │ │  62% │ │+24.5%│ │ 12%  │ │ 2.1  │ │  1.45    │ │
│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────────┘ │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────────┐ │
│  │✅    │ │❌    │ │🏆    │ │💀    │ │💵    │ │💰        │ │
│  │Avg W│ │Avg L │ │C.Wins│ │C.Loss│ │Init.│ │Finale    │ │
│  │+45$  │ │-28$  │ │   8  │ │   5  │ │1000$│ │  1245$   │ │
│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────────┘ │
│                                                              │
│  ┌── Courbe d'équity ─────────────────────────────────────┐ │
│  │ ● ● ● ● ● ● ● ● ● ● ● ● ● ● ● ● ● ● ● ●              │ │
│  │  ╱╲ ╱╲  ╱╲  ╱╲  ╱╲  ╱╲  ╱╲  ╱╲  ╱╲  ╱╲              │ │
│  │ ╱  ╲╱  ╲╱  ╲╱  ╲╱  ╲╱  ╲╱  ╲╱  ╲╱  ╲╱               │ │
│  │ ────────────────────────────────────────────────────    │ │
│  │ #0    #20    #40    #60    #80    #100   #120   #140   │ │
│  │                                           142 points   │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌── Drawdown ────────────────────────────────────────────┐ │
│  │  0% ──────────────────────────────────────              │ │
│  │ -5%  \        \    \                                    │ │
│  │ -10%  \    ████\████\███                                │ │
│  │ -15%   \  ██      \    \██                              │ │
│  │        ███          ████                                │ │
│  │ ────────────────────────────────────────────────────    │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌── Trades exécutés ─────────────────────────────────────┐ │
│  │ 142 trades                                              │ │
│  │ Entrée         │ Sortie        │ Dir │ P&L   │ Raison  │ │
│  │────────────────┼───────────────┼─────┼───────┼─────────│ │
│  │ 2025-01-15     │ 2025-01-17    │ LONG│ +45$  │ PT      │ │
│  │ 2025-01-18     │ 2025-01-18    │ SHORT│ -28$ │ SL      │ │
│  │ ...                                                     │ │
│  └─────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

#### Comportement responsive

| Section | Desktop | Mobile |
|---------|---------|--------|
| **Métriques** | 6 colonnes | 2 colonnes |
| **Equity chart** | Hauteur 340px | Hauteur 220px |
| **Drawdown chart** | Hauteur 200px | Hauteur 140px |
| **Tableau trades** | 11 colonnes | 5 colonnes prioritaires avec scroll |

#### Animations / transitions

- Graphiques : animation `duration: 800` au chargement (Chart.js natif)
- Cartes métriques : hover avec `shadow-md` et bordure lumineuse
- Lien retour : flèche qui glisse à gauche (`-translate-x-0.5`) au hover
- Bouton rapport graphique : toggle smooth (si conservé)

---

## 4. Priorisation

| Priorité | ID | Problème | Solution | Effort |
|----------|----|----------|----------|--------|
| **P0** | S-1, S-2, S-3 | Graphique en iframe cassé / Pas de courbe d'équity / Pas de drawdown | Chart.js inline avec equity + drawdown | ⏱️ Moyen |
| **P0** | I-1, I-2 | Pas de hiérarchie / Pas d'indicateur visuel | Cartes stratégies avec dégradé, sparkline | ⏱️ Moyen |
| **P0** | I-4 | Bouton sans feedback | Spinner + état disabled | ⏱️ Faible |
| **P1** | I-3 | Pas de comparaison visuelle | Barres horizontales Return% | ⏱️ Faible |
| **P1** | S-4 | Grille de métriques sans priorité | Icônes + bordures colorées | ⏱️ Faible |
| **P1** | I-5 | Tableau non responsive | Sticky colonne + scroll + colonnes prioritaires | ⏱️ Faible |
| **P1** | S-5 | Tableau dense mais lisible | Badges colorés direction + exitReason | ⏱️ Faible |
| **P2** | I-6 | Pas d'icônes métriques | Émojis sur les métriques | ⏱️ Faible |
| **P2** | I-7 | Stats consécutives redondantes | Regroupement secondaire | ⏱️ Faible |
| **P2** | I-8 | Tri par date uniquement | Tri par Return% par défaut | ⏱️ Faible |
| **P2** | S-8 | Pas de breadcrumb | Fil d'Ariane optionnel | ⏱️ Faible |
| **P2** | S-9 | Format ISO dates | Format lisible en français | ⏱️ Faible |
| **P3** | S-6 | Pas de vue synthétique des trades | Histogramme des P&L | ⏱️ Élevé |
| **P3** | I-9 | Pas de pagination | Pagination si > 20 stratégies | ⏱️ Moyen |
| **P3** | I-10 | Pas de filtre | Champ de recherche | ⏱️ Moyen |

### Priorités recommandées pour le sprint

#### Sprint 1 (P0 — doit être fait maintenant)
1. **Show.vue** : Remplacer l'iframe par Chart.js inline (equity + drawdown) ← **le problème numéro 1**
2. **Index.vue** : Cartes stratégies avec hiérarchie visuelle ← **transformer la page**
3. **Index.vue** : Spinner sur le bouton ← **feedback utilisateur**
4. **Les deux pages** : Responsive de base (grilles, scroll tableaux)

#### Sprint 2 (P1 — ferait une grosse différence)
5. **Index.vue** : Section comparaison (barres horizontales)
6. **Les deux pages** : Icônes + bordures colorées sur les métriques
7. **Show.vue** : Badges colorés dans le tableau des trades

#### Backlog (P2/P3 — nice to have)
8. Pagination, filtre, breadcrumb, histogramme

---

## Conclusion

Le module Backtest a un excellent socle technique : les données sont propres, le JSON exporté contient tout ce qu'il faut (equityCurve, trades, metrics), et l'architecture Laravel/Inertia est saine. Mais l'UI actuelle **ne rend pas justice aux données**.

Les 4 P0 sont interdépendants : sans les graphiques, la page Show est vide ; sans la hiérarchie visuelle, la page Index est un mur de chiffres. Je recommande de les traiter dans un même sprint.

Le bon côté : **80% des solutions sont des changements CSS/Tailwind et des composants Vue existants**. L'investissement est faible pour un gain UX massif.

— Sally 🎨

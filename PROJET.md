<!--
  Registre d'état Foyer — SOURCE DE VÉRITÉ PARTAGÉE (PO ↔ dev).
  Tout agent LE LIT avant d'agir et LE MET À JOUR à chaque étape conclusive.
  Cœur méthodologique : dépôt Foyer voisin (../foyer) — pilote/, skills/, bmad/, kit-php/.
-->
# État Foyer — site-barbier

- **Porte active** : `nouveau`
- **Parcours** : `pilote/journeys/nouveau-projet.md` (dépôt Foyer)
- **Archétype** : stateful full-stack  <!-- défaut inféré : contenu + demandes persistés, écrans public et admin -->
- **Niveau CyFun® visé** : Essential  <!-- défaut : le site conserve nom + téléphone des clients et un compte éditeur -->
- **Substrat d'exécution** : conteneur (Docker 29.7) pour PHP + MariaDB ; Node 22 local pour compiler les îlots  <!-- sonde 0bis du 2026-10-22 : php, mysql, composer absents -->
- **Kit pressenti** : `kit-php` · HTTP `vanilla` · persistance `cqrs` (aucun Composer, adapté au mutualisé)
- **Démarré le** : 2026-10-22
- **Dernière mise à jour** : 2026-10-22 (par : agent Claude Code)

## Répartition des rôles

| Rôle | Qui | Depuis |
|---|---|---|
| **PO** (tranche les modalités, valide sur preuve, signe) | gilmry | 2026-10-22 |
| **Dev** (exécute, assemble la preuve, ne tranche jamais) | gilmry + agent Claude Code | 2026-10-22 |

<!-- Deux chapeaux sur une même personne : la séparation reste ÉCRITE. -->

## Gantt / vagues

- **Gantt** : — (Phase A bis, après le PASS BMAD)
- **Vague courante** : — · **barrière** : —
- **Branches ouvertes** : —
- **Barreau** : Scrum pressenti (Micro, un flux) — à confirmer au pilotage

## Position courante

- **Phase / étape** : Phase A · Conception BMAD · étape 2/5 (Product Manager → PRD v0.1 rédigé)
- **Prochaine action attendue** : le PO valide le PRD v0.1 → Architecte (architecture, ADR, plan de vulnérabilités)
- **Rôle à jouer** : `pilote/roles/conception-bmad.md`

## Livrables de conception

| # | Livrable | Fichier | État |
|---|---|---|---|
| 1 | Product Brief | `docs/conception/01-product-brief.md` | ✅ v1.0 validé (gilmry, 2026-10-22) |
| 2 | PRD | `docs/conception/02-prd.md` | 🟡 v0.1 à valider (26 FR × 4 classes) |
| 3 | Architecture + ADR + plan vulnérabilités | `docs/conception/03-architecture.md` | ⚪ |
| 4 | Epics & stories | `docs/conception/04-epics-stories.md` | ⚪ |
| 5 | Validation | `docs/conception/05-validation.md` | ⚪ |

Référence visuelle : `docs/design/reference-le-corbeau.md`.

## Gates (dernier statut)

| Gate | Statut | Quand | Commande |
|---|---|---|---|
| plancher (secrets · SBOM · conteneur · migrations si stateful) | ⚪ | | cf. `AGENTS.md` (à écrire en B) |
| verify structurel | ⚪ | | |
| contrat (anti-drift) | ⚪ | | |
| tests 4 couches | ⚪ | | |
| E2E (correctness) | ⚪ | | |
| régression visuelle | ⚪ | | |
| doc vivante (preuve de valeur) | ⚪ | | non bloquant |
| jalon (SAST · SCA · qualité) | — | | rapport, non bloquant |

**Dernière gate review** : —

## Périmètres / backlog

| Périmètre / story | État | Couches vertes | Bascule | Commit / ADR | Tours | Tokens | Wall-clock |
|---|---|---|---|---|---|---|---|
| — (backlog produit en Phase A, étape 4) | | | | | | | |

## Promotions

| Branche | Preuve complète (gate `promotion`) | Minutes d'arbitrage | Diff ouvert ? (pourquoi) | Décision | Le |
|---|---|---|---|---|---|

## Arbitrages

### 🔴 En attente (l'humain doit trancher une MODALITÉ)

- **Point** : validation du PRD v0.1 (sortie de l'étape Product Manager)
  - **Preuve jointe** : `docs/conception/02-prd.md` v0.1
  - **Hypothèses introduites** : pas de 15 min · horizon 60 j · image ≤ 8 Mo · 5 réservations/h/IP · session 30 min / 12 h · admin en français · `.ics` joint · purge au premier passage du jour · « sans préférence » réparti équitablement
  - **Options présentées** : valider tel quel / corriger une hypothèse

### ✅ Tranchés

| Point | Décision (modalité) | Par | Le | ADR |
|---|---|---|---|---|
| Pile sans Symfony/API Platform/Doctrine | Imposée par l'hébergement mutualisé LAMP demandé par le PO | gilmry (brief) | 2026-10-22 | ADR à rédiger par l'Architecte |
| Qui édite | Le gérant seul (un compte) | gilmry | 2026-10-22 | — (Brief §2) |
| Prise de rendez-vous | Réservation en ligne sur créneaux libres, dès le MVP | gilmry | 2026-10-22 | — (Brief §7) |
| Sort des réservations | Liste dans l'admin + e-mail au salon, purge automatique | gilmry | 2026-10-22 | — (Brief §7) |
| Langues | FR + NL + EN dès le MVP | gilmry | 2026-10-22 | — (Brief §14) |
| Product Brief v1.0 | Validé, hypothèses restantes acceptées | gilmry | 2026-10-22 | — |

### Défauts annoncés (réversibles, non votés)

- Archétype stateful full-stack · CyFun Essential · kit-php vanilla + CQRS · substrat Docker.
- Rendu des pages publiques côté serveur (PHP) + îlots Svelte pour formulaire et admin → ADR d'écart D2 en Architecture.
- Design de base : maquette « Le Corbeau » ; thème ThemeForest non exploitable et sous licence, non copié.

## Journal (chronologie courte)

- 2026-10-22 — Q0 = nouveau ; projet créé, registre + Brief v0.1 (commit `10ce28c`)
- 2026-10-22 — réponses métier du PO → Brief v0.2 (Petit, 4 BC, ~20 stories, ~15 j)
- 2026-10-22 — Brief validé (v1.0) ; PRD v0.1 rédigé : 26 FR, 9 NFR, 4 parcours de référence, contrat API v1 esquissé

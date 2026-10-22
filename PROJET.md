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
- **Kit pressenti** : `kit-php` · HTTP `vanilla` · persistance `cqrs` (ADR-004/005 retenus) · CI pinnée PHP 8.2 + MariaDB 10.6
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

- **Phase / étape** : Phase A · Conception BMAD · étape 4/5 (Scrum Master → epics & stories)
- **Prochaine action attendue** : le PO valide le backlog → Validateur (rapport PASS/CONCERNS/FAIL)
- **Rôle à jouer** : `pilote/roles/conception-bmad.md`

## Livrables de conception

| # | Livrable | Fichier | État |
|---|---|---|---|
| 1 | Product Brief | `docs/conception/01-product-brief.md` | ✅ v1.0 validé (gilmry, 2026-10-22) |
| 2 | PRD | `docs/conception/02-prd.md` | ✅ v1.0 validé (gilmry, 2026-10-22) |
| 3 | Architecture + ADR + plan vulnérabilités | `docs/conception/03-architecture.md` + `03b-plan-vulnerabilites.md` | ✅ v1.0 validé (gilmry, 2026-10-22) |
| 4 | Epics & stories | `docs/conception/04-epics-stories.md` | 🟡 v0.1 à valider (31 stories, ≈ 28 j) |
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

- **Point** : validation du backlog v0.1 + **découpage de release** (estimation ≈ 28 j vs target 15 j)
  - **Preuve jointe** : `docs/conception/04-epics-stories.md` (estimation chiffrée)
  - **Question (métier)** : mettre d'abord le site vitrine en ligne (R1 ≈ 18 j) puis la réservation (R2 ≈ 10 j), ou tout livrer d'un coup (≈ 28 j) ?
  - **Options** : A. deux releases (recommandé) · B. une seule release

### ✅ Tranchés

| Point | Décision (modalité) | Par | Le | ADR |
|---|---|---|---|---|
| Qui édite | Le gérant seul (un compte) | gilmry | 2026-10-22 | — (Brief §2) |
| Prise de rendez-vous | Réservation en ligne sur créneaux libres, dès le MVP | gilmry | 2026-10-22 | — (Brief §7) |
| Sort des réservations | Liste dans l'admin + e-mail au salon, purge automatique | gilmry | 2026-10-22 | — (Brief §7) |
| Langues | FR + NL + EN dès le MVP | gilmry | 2026-10-22 | — (Brief §14) |
| Product Brief v1.0 | Validé, hypothèses restantes acceptées | gilmry | 2026-10-22 | — |
| PRD v1.0 | Validé, hypothèses introduites acceptées | gilmry | 2026-10-22 | — |
| Outillage serveur | Léger (vanilla), réversible vers API Platform | gilmry | 2026-10-22 | ADR-004 |
| Stockage | SQL sur mesure (CQRS), pas d'ORM | gilmry | 2026-10-22 | ADR-005 |
| Environnements | Recette `test.<domaine>` puis production, envoi SFTP/FTPS par le PO | gilmry | 2026-10-22 | ADR-008 |
| Architecture v1.0 + plan de vulnérabilités | Validés ; responsable sécurité Gilles seul (sans suppléant, mesure compensatoire), signalement gilmry+abuse@gmail.com | gilmry | 2026-10-22 | — |

### Défauts annoncés (réversibles, non votés)

- Archétype stateful full-stack · CyFun Essential · substrat Docker · CI GitHub Actions (dépôt distant à créer par le PO).
- Correctif : la ligne « pile imposée par l'hébergement » a été retirée des tranchés — le mutualisé n'exclut pas Symfony ; c'est un choix proposé (ADR-004).
- Rendu des pages publiques côté serveur (PHP) + îlots Svelte pour réservation et admin → ADR-003.
- Design de base : maquette « Le Corbeau » ; thème ThemeForest non exploitable et sous licence, non copié.

## Journal (chronologie courte)

- 2026-10-22 — Q0 = nouveau ; projet créé, registre + Brief v0.1 (commit `10ce28c`)
- 2026-10-22 — réponses métier du PO → Brief v0.2 (Petit, 4 BC, ~20 stories, ~15 j)
- 2026-10-22 — Brief validé (v1.0) ; PRD v0.1 rédigé : 26 FR, 9 NFR, 4 parcours de référence, contrat API v1 esquissé (commit `10d4115`)
- 2026-10-22 — PRD validé (v1.0) ; architecture v0.1 (7 couches, 9 ADR, données et flux) + plan de vulnérabilités
- 2026-10-22 — PO tranche ADR-004 (léger), ADR-005 (SQL sur mesure), ADR-008 (avec site de test)
- 2026-10-22 — architecture v1.0 et plan de vulnérabilités validés
- 2026-10-22 — backlog v0.1 : 31 stories, ≈ 28 j (recalage ×1,9 vs prior du Brief), proposition R1/R2

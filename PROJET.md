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
- **Substrat d'exécution** : conteneur (Docker 29.7) pour PHP 8.4 + MySQL 8 ; Node 22 local pour compiler les îlots  <!-- sonde 0bis du 2026-10-22 : php, mysql, composer absents -->
- **Kit pressenti** : `kit-php` · HTTP `vanilla` · persistance `cqrs` (ADR-004/005 retenus) · CI pinnée PHP 8.4 + MySQL 8.0/8.4 (OVH)
- **Démarré le** : 2026-10-22
- **Dernière mise à jour** : 2026-10-22 (par : agent Claude Code)

## Répartition des rôles

| Rôle | Qui | Depuis |
|---|---|---|
| **PO** (tranche les modalités, valide sur preuve, signe) | gilmry | 2026-10-22 |
| **Dev** (exécute, assemble la preuve, ne tranche jamais) | gilmry + agent Claude Code | 2026-10-22 |

<!-- Deux chapeaux sur une même personne : la séparation reste ÉCRITE. -->

## Gantt / vagues

- **Gantt** : `docs/pilotage/gantt.md` v1.0 (signé le 2026-10-22 par gilmry) — scénario B, ratio 2
- **Vague courante** : V0 — Fondations (ouverte) · **barrière** : B0 (Sprint 0 vert, offre d'hébergement choisie)
- **Branches ouvertes** : —
- **Barreau** : Scrum (ADR-011)

## Position courante

- **Phase / étape** : Vague V0 · passe 1 terminée : 0.0 ✅ et 0.1 ✅ (branches, promotion à B0)
- **Prochaine action attendue** : passe 2 : Story 0.2 (contrat) ∥ Story 0.3 (triple harnais) ; en parallèle, le PO fait confirmer les 6 points par OVH puis commande
- **Rôle à jouer** : `pilote/roles/harnais.md` + `pilote/roles/gate-runner.md`

## Livrables de conception

| # | Livrable | Fichier | État |
|---|---|---|---|
| 1 | Product Brief | `docs/conception/01-product-brief.md` | ✅ v1.1 validé (gilmry, 2026-10-22, sortie BMAD) |
| 2 | PRD | `docs/conception/02-prd.md` | ✅ v1.1 validé (gilmry, 2026-10-22, sortie BMAD) |
| 3 | Architecture + ADR + plan vulnérabilités | `docs/conception/03-architecture.md` + `03b-plan-vulnerabilites.md` | ✅ v1.1 validé (gilmry, 2026-10-22, sortie BMAD) |
| 4 | Epics & stories | `docs/conception/04-epics-stories.md` | ✅ v1.1 validé (gilmry, 2026-10-22, sortie BMAD) |
| 5 | Validation | `docs/conception/05-validation.md` | ✅ **PASS 100/100**, signé (gilmry, 2026-10-22) |

Référence visuelle : `docs/design/reference-le-corbeau.md`.

## Gates (dernier statut)

| Gate | Statut | Quand | Commande |
|---|---|---|---|
| plancher (secrets · SBOM · conteneur · migrations si stateful) | 🟡 partiel : migrations réversibles (G2) 🟢 ; secrets/SBOM en 0.5 ; conteneur N/A | 2026-10-22 | `bash harness/run-verify.sh` |
| verify structurel | 🟢 G1 G2 H1 L1 (+ lint PHP 8.4) | 2026-10-22 | `bash harness/run-verify.sh` |
| contrat (anti-drift) | ⚪ | | |
| tests 4 couches | 🟢 unit (10 tests) · integration (migrations up→down→up, MySQL 8.4 et 8.0) | 2026-10-22 | `bash harness/run-phpunit.sh` · `bash harness/run-integration.sh` |
| E2E (correctness) | 🟢 smoke HTTP (4 vérifications) ; navigateur en 0.3 | 2026-10-22 | `bash harness/e2e-smoke.sh` |
| régression visuelle | ⚪ | | |
| doc vivante (preuve de valeur) | ⚪ | | non bloquant |
| jalon (SAST · SCA · qualité) | — | | rapport, non bloquant |

**Dernière gate review** : —

## Périmètres / backlog

| Périmètre / story | État | Couches vertes | Bascule | Commit / ADR | Tours | Tokens | Wall-clock |
|---|---|---|---|---|---|---|---|
| 0.0 Choisir et sonder l'hébergement (S) | ✅ fait, branche `story/0.0-hebergement` — OVH Perso retenu ; 6 points à confirmer auprès d'OVH par le PO | — | — | `nouveau(hebergement)` ×2 | 4 | ≈ 140 k (étude) | ≈ 20 min |
| 0.1 Socle reproductible (L) | ✅ fait, branche `story/0.1-socle` — promotion à arbitrer (B0) | socle (Http, Persistence) | — | `nouveau(socle)` ×2 | 14 | ≈ 60 k | ≈ 1 h |
| 0.2 Harnais de contrat (M) | ⚪ à faire | — | — | — | | | |
| 0.3 Parcours partagés et triple harnais (M) | ⚪ à faire | — | — | — | | | |
| 0.4 Paquet et hébergement simulé (M) | ⚪ à faire | — | — | — | | | |
| 0.5 CI, hooks, plancher (M) | ⚪ à faire | — | — | — | | | |
| 1.1 Installation sans CLI (M) | ⚪ à faire | — | — | — | | | |
| 1.2 Connexion 2FA (L) | ⚪ à faire | — | — | — | | | |
| 1.3 Session, CSRF, TOTP récent (M) | ⚪ à faire | — | — | — | | | |
| 1.4 Codes de secours, reprise d'accès (M) | ⚪ à faire | — | — | — | | | |
| 2.1 Accueil Le Corbeau (L) | ⚪ à faire | — | — | — | | | |
| 2.2 Trois langues (M) | ⚪ à faire | — | — | — | | | |
| 2.3 Carte (L) | ⚪ à faire | — | — | — | | | |
| 2.4 Équipe (M) | ⚪ à faire | — | — | — | | | |
| 2.5 Horaires et coordonnées (M) | ⚪ à faire | — | — | — | | | |
| 2.6 Textes des sections (M) | ⚪ à faire | — | — | — | | | |
| 2.7 Images (P3) (M) | ⚪ à faire | — | — | — | | | |
| 2.8 Légal + RGPD (M) | ⚪ à faire | — | — | — | | | |
| 3.1 Calcul des créneaux (M) | ⚪ à faire | — | — | — | | | |
| 3.2 Disponibilités (M) | ⚪ à faire | — | — | — | | | |
| 3.3 Absences et fermetures (M) | ⚪ à faire | — | — | — | | | |
| 3.4 API de lecture (S) | ⚪ à faire | — | — | — | | | |
| 3.5 Réserver (L) | ⚪ à faire | — | — | — | | | |
| 3.11 Garde-fous agenda (P4) (M) | ⚪ à faire | — | — | — | | | |
| 3.6 Sans préférence (S) | ⚪ à faire | — | — | — | | | |
| 3.7 Îlot de réservation (P1) (L) | ⚪ à faire | — | — | — | | | |
| 3.8 Annulation client (P2) (M) | ⚪ à faire | — | — | — | | | |
| 3.9 Planning salon (M) | ⚪ à faire | — | — | — | | | |
| 3.10 Purge (S) | ⚪ à faire | — | — | — | | | |
| 4.1 E-mails (M) | ⚪ à faire | — | — | — | | | |
| 4.2 Journal et relance (S) | ⚪ à faire | — | — | — | | | |
| 5.1 Recette puis production (M) | ⚪ à faire | — | — | — | | | |
| 5.2 Santé et sauvegarde (M) | ⚪ à faire | — | — | — | | | |

## Promotions

| Branche | Preuve complète (gate `promotion`) | Minutes d'arbitrage | Diff ouvert ? (pourquoi) | Décision | Le |
|---|---|---|---|---|---|

## Arbitrages

### 🔴 En attente (l'humain doit trancher une MODALITÉ)

- *(aucun)*

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
| Découpage de release | Une seule release (≈ 28 j) ; target recalée 15 → 28 j | gilmry | 2026-10-22 | — |
| Enveloppe après validation | ≈ 30 j acceptés (33 stories) | gilmry | 2026-10-22 | — |
| Barreau | Scrum, vagues V0–V5, revue = gate review de fin de vague | gilmry | 2026-10-22 | ADR-011 |
| Ratio de supervision | 2 chantiers en parallèle (scénario B, ≈ 19 j) | gilmry | 2026-10-22 | — |
| Gantt | Signé (`docs/pilotage/gantt.md` v1.0) | gilmry | 2026-10-22 | — |
| Versions de la pile | **PHP 8.4** (support de sécurité jusqu'au 31/12/2028 ; 8.2 finit le 31/12/2026) et **MariaDB 10.11 LTS** (10.6 sans support depuis 07/2026) — PHP n'a pas de LTS officielle | gilmry | 2026-10-22 | NFR-02 amendé |
| Hébergeur | **OVH Perso, 48 mois, datacenter GRA** — commande par le PO après confirmation des 6 points de `docs/hebergement.md` ; base **MySQL 8** (remplace MariaDB 10.11), CI sur MySQL 8.0 et 8.4 jusqu'à confirmation | gilmry | 2026-10-22 | ADR-010 (résidu accepté) |
| Sortie de BMAD | Conception validée (PASS 100/100), livrables v1.1 signés | gilmry | 2026-10-22 | — |
| Modalité de la bascule différée | Revue à chaque gate review ; déclencheurs : incident de dérive, > 60 routes, hébergeur rendant le poids indifférent | gilmry | 2026-10-22 | ADR-004 |
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
- 2026-10-22 — backlog validé, une seule release (≈ 28 j)
- 2026-10-22 — Validateur passage 1 : CONCERNS 98/100 ; reboucle PM/Architecte/Scrum Master → Brief/PRD/Archi/Stories v1.1 (28 FR, ADR-010, stories 0.0 et 3.11, ≈ 30 j)
- 2026-10-22 — Validateur passage 2 : CONCERNS 100/100 ; 3 points + 9 mineures corrigés (v1.1 revue) ; arbitrages ADR-004 et enveloppe 30 j posés
- 2026-10-22 — PO : modalité ADR-004 et enveloppe ≈ 30 j acceptées ; passage 3 du Validateur
- 2026-10-22 — Validateur passage 3 : CONCERNS 100/100 (perte du téléphone sans reprise d'accès) → FR-043 complétée, Story 1.4 passe en M (≈ 30 j inchangé)
- 2026-10-22 — Validateur passage 4 : **PASS 100/100** ; mineures M1 (exception /install/reset-2fa) et M2 (Mailer factice en 1.4) corrigées
- 2026-10-22 — sortie de BMAD validée par le PO ; plan de pilotage v0.1 (33 stories, 6 vagues, 2 barrières, scénarios ratio 1 / ratio 2)
- 2026-10-22 — PO : ratio 2, Scrum (ADR-011), Gantt signé ; ouverture de V0
- 2026-10-22 — PO : « prends la version LTS de PHP » → PHP 8.4 + MariaDB 10.11 LTS ; NFR-02, architecture, stories et images mis à jour
- 2026-10-22 — B0 : kit-php vert à froid (verify, unit, integration CQRS+Doctrine, e2e vanilla+API Platform, visuel) ; aucun résidu
- 2026-10-22 — Story 0.1 faite sur `story/0.1-socle` : CI socle verte depuis un clone neuf, sensibilité prouvée (7 dérives détectées), `./go` idempotent ; promotion arbitrée par le PO à la barrière B0
- 2026-10-22 — Story 0.0 : 5 offres conformes comparées ; PO retient **OVH Perso 48 mois (GRA)** → base MySQL 8 au lieu de MariaDB, conception v1.2 (mentions MariaDB → MySQL), ADR-010 en résidu accepté
- 2026-10-22 — socle passé sur MySQL 8 : CI verte sur 8.4 et 8.0, sensibilité reprouvée (commit `7419690` sur `story/0.1-socle`)

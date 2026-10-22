---
livrable: Rapport de validation croisée
persona: 05-validateur (BMAD, TOGAF Phase F)
projet: site-barbier
version: 0.1
sources:
  - 01-product-brief.md v1.0
  - 02-prd.md v1.0
  - 03-architecture.md v1.0 + 03b-plan-vulnerabilites.md v1.0
  - 04-epics-stories.md v1.0
redige_le: 2026-10-22
redige_par: agent validateur (Claude Code, pilote Foyer)
signature_humaine:
  nom: à signer
  role: PO
  date: à signer
  verdict: à signer
---

# Rapport de Validation croisée — site-barbier

*Livrable du Validateur · TOGAF Phase F. Verdict objectivé : on ne sort de BMAD qu'au PASS.
Archétype déclaré : **stateful full-stack** (toutes les sections de `archetypes.md` en scope) · CyFun® **Essential**
(section *Données et flux* obligatoire).*

## Statut global : **CONCERNS**

**Fidelity score** : **98/100** — score de traçabilité Brief→PRD→Architecture→Stories. Seuil PASS (≥ 80) atteint,
mais **12 incohérences « à corriger »** (aucune bloquante) doivent être levées avant la fabrication des stories
concernées (§10). Le pipeline reboucle vers PM, Architecte et Scrum Master ; il ne sort pas de BMAD en l'état.

**Critère explicite « contrat matérialisé »** (`skills/contrat-api.md`, barème du Validateur) :
**3/4 éléments mécanisés + élément 1 substitué sous ADR, destination non fixée** ; l'élément 3 nomme un
mécanisme (`StrictJson`) mais pas sa bibliothèque. → acceptable comme résidu déclaré, **à corriger** (incohérences 1 et 2).

> Calcul : (capacités Brief couvertes en PRD / total capacités) × 25 + (FRs couvertes en Architecture / total FRs) × 25 + (FRs couvertes en Stories / total FRs) × 25 + (4 classes couvertes / total 4×N) × 25

| Terme | Couvert / total | Points |
|---|---|---|
| Capacités Brief §7 → FR | 7 / 7 | 25,00 |
| FR → couches d'architecture (cas d'usage ou mécanisme nommé) | 24 / 26 | 23,08 |
| FR → stories | 26 / 26 | 25,00 |
| Classes 4×N couvertes (26 FR × 4) | 104 / 104 | 25,00 |
| **Total** | | **98,08 ≈ 98** |

**Critère appliqué (uniforme) pour FR → Architecture** : une FR est couverte si **chaque verbe de son « je veux »**
a une couche nommée **et** un cas d'usage (couche Application), un endpoint du contrat ou un mécanisme nommé dans une couche. Une FR décrite
seulement en prose, sans mécanisme, compte comme non couverte (règle de `contrat-api.md`).

| FR | Mécanisme d'architecture | Couvert | Story |
|---|---|---|---|
| FR-001 Site public | `RenderHomePage`, gabarits PHP (couche 4) | ✓ | 2.1 |
| FR-002 Langue | middleware langue (couche 3), `TranslatedText`, `app/i18n/*.php` | ✓ | 2.2 |
| FR-003 Carte | `RenderHomePage` (lecture carte) | ✓ | 2.3 |
| FR-004 Équipe | `RenderHomePage` | ✓ | 2.4 |
| FR-005 Horaires/coordonnées | `RenderHomePage` | ✓ | 2.5 |
| FR-006 Textes | `UpdateSection`, `PUT /admin/sections/{key}` | ✓ | 2.6 |
| FR-007 Images | `UploadMedia`, `POST/DELETE /admin/media` | ✓ | 2.7 |
| FR-008 Gérer la carte | `ManageCatalog`, `/admin/categories`, `/admin/services` | ✓ | 2.3 |
| FR-009 Barbiers | `ManageBarbers`, `/admin/barbers` | ✓ | 2.4 |
| FR-010 Horaires salon | `UpdateOpeningHours`, `UpdateSalonInfo`, `/admin/opening-hours`, `/salon` | ✓ | 2.5 |
| FR-011 Pages légales | `RenderLegalPage` (affichage) ; **édition** (« publier », « deux pages éditables ») : ni cas d'usage ni endpoint | **✗** | 2.8 |
| FR-020 Disponibilités | `SetAvailability`, `/admin/barbers/{id}/availability`, `AgendaLock` | ✓ | 3.2 |
| FR-021 Absences/fermetures | `AddAbsence`, `AddClosure`, `/admin/absences`, `/closures` | ✓ | 3.3 |
| FR-022 Créneaux libres | `ListBookableDays`, `ListFreeSlots`, `SlotCalculator` | ✓ | 3.1, 3.4 |
| FR-023 Réserver | `BookSlot`, `AgendaLock`, `booking_slot` (ADR-006) | ✓ | 3.5 |
| FR-024 Sans préférence | `BookSlot` + `NoPreferenceAssigner` | ✓ | 3.6 |
| FR-025 Annulation client | `CancelBookingByClient`, `POST /bookings/cancel` | ✓ | 3.8 |
| FR-026 Planning salon | `ListBookings`, `CancelBookingBySalon` | ✓ | 3.9 |
| FR-027 Purge | `PurgeExpiredBookings`, `GET_LOCK` | ✓ | 3.10 |
| FR-030 E-mail salon | `NotifySalon`, port `Mailer` (ADR-009) | ✓ | 4.1 |
| FR-031 E-mail client | `NotifyClient` | ✓ | 4.1 |
| FR-032 Journal et relance | `RetryFailedMails`, `GET/POST /admin/mail-log` | ✓ | 4.2 |
| FR-040 Connexion 2FA | `Login`, `VerifyTotp`, `/admin/session`, `/session/totp` | ✓ | 1.2 |
| FR-041 Session | sessions en base (`SessionHandlerInterface`), `Logout`, middleware CSRF | ✓ | 1.3 |
| FR-042 Installation/migrations | `InstallApplication`, `ApplyPendingMigrations`, `/admin/migrations` | ✓ | 1.1 (+ 5.1) |
| FR-043 Codes de secours | `UseRecoveryCode` (usage) ; **génération et régénération** : ni cas d'usage ni endpoint | **✗** | 1.4 |

Classes 4×N : les 26 FR du PRD portent chacune `@happy`, `@negative`, `@edge`, `@security` (vérifié FR par FR) ;
les 31 stories listent chacune leurs 4 classes. 104/104.

## 1. Cohérence DDD
- Glossaire (ubiquitous language) cohérent Brief→PRD→Archi : ☑ *(avec réserves mineures, incohérence 18)* —
  les termes du Brief §8 se retrouvent au PRD §4 et au mapping de l'architecture ; le PRD ajoute *Jeton d'annulation*
  et *Média* sans définition alors qu'il renvoie au Brief §8 pour les définitions.
- Bounded contexts stables : ☑ — 4 BC identiques du Brief §9 au PRD §5, à l'arborescence `src/Domain/{Vitrine,Agenda,Notification,Acces}` et aux epics.
- Invariants métier présents et codables : ☑ *(avec une contradiction de données, incohérence 6)* — invariants du
  Brief §10 portés par les constructeurs (`Money`, `Duration`, `TimeRange`, `Booking`) et par ADR-006 en base.
  Mais les données initiales de la maquette violent l'invariant `Duration` (multiple de 15).

## 2. Couverture SOLID
Chaque couche annonce ses principes (Domain SRP/DIP, Application ISP/OCP/DRY, Infra LSP/DIP) et les ports sont
déclarés côté domaine (13 ports nommés). ☑ — réserve : aucun cas d'usage de lecture publique de la carte et de
l'équipe pour l'API (`GET /services`, `GET /barbers`), incohérence 4.

## 3. Traçabilité
- Brief → PRD (toute capacité a ses exigences) : ☑ — 7/7 (table « Traçabilité Brief → PRD » vérifiée).
- PRD → Architecture (toute exigence a sa couche) : ☐ — 24/26 ; FR-011 (édition) et FR-043 (génération/régénération) sans mécanisme.
- PRD → Stories (toute exigence a ses stories) : ☑ — 26/26. Mais **une story sans FR** : Story 2.8 implémente le droit
  d'accès/effacement RGPD (NFR-06), qui n'a ni FR, ni matrice 4×N, ni cas d'usage, ni endpoint (incohérence 5).

**Cohérence des chiffres** : ☑ cohérent.
- Story par story : Sprint 0 = 1 + 4 × 0,75 = 4,00 j / 10 + 4 × 8 = 42 tours ; Accès 0,75 + 1 + 0,75 + 0,5 = 3,00 j / 32 ;
  Vitrine 2 × 1 + 6 × 0,75 = 6,50 j / 68 ; Agenda 2 × 1 + 5 × 0,75 + 3 × 0,5 = 7,25 j / 78 ; Notification 1,25 j / 14 ;
  Mise en ligne 1,25 j / 14.
- Sous-total 23,25 j / 248 tours ; + 20 % = 27,9 j ≈ 28 j / 297,6 ≈ 298 tours.
- Coût modèle : 0,4 × 0,85 + 0,04 × 2,55 = 0,442 € par tour × 298 ≈ 132 € (annoncé ≈ 130 €).
- Le prior du Brief (64 Mtok × 0,85 + 6,4 Mtok × 2,55 ≈ 70 €) utilise le même barème. Le ratio 28/15 ≈ 1,87 donne bien « ×1,9 ».
- R1 = 14,75 × 1,2 ≈ 17,7 j ≈ 18 j sur 19 stories ; R2 = 8,5 × 1,2 ≈ 10,2 j ≈ 10 j sur 12 stories. Compte : 5 + 4 + 8 + 10 + 2 + 2 = 31 stories.
- La décision « une seule release, target recalée à 28 j » est tracée dans `PROJET.md` (Arbitrages tranchés).

## 4. Architecture hexagonale
- Backend : dépendances vers l'intérieur : ☑ — Domain sans PDO/HTTP/`$_SESSION`, vérifié par gate H1 ; exceptions
  traduites en `problem+json` dans la seule couche Http ; pages publiques et API appellent les mêmes cas d'usage.
- Frontend (hexagonale light) : ☑ — îlots consommant uniquement le client généré, invariant « pas de `fetch(` hors
  `generated/` » en gate `verify` ; écart D2 (rendu PHP) tracé en ADR-003.

## 4bis. Contrat API matérialisé (critère explicite, `contrat-api.md`)

| Élément | Exigé | Livrables | Statut |
|---|---|---|---|
| 1. Annotation à la source (spec générée du code) | spec générée depuis le code | spec **écrite à la main** (ADR-002), validation des requêtes à l'exécution contre la spec | **substitué** — admis par `bootstrap-nouveau-projet.md` §Conditionnement *si* arbitrage humain en ADR avec résidu mesuré et **destination fixée** ; ADR-002 dit « éventuelle bascule » → destination non fixée (incohérence 1) |
| 2. Client généré | codegen | `gen-api.mjs` → `frontend/shared/generated/api.ts`, `prebuild`, gate `contrat` | ✓ nommé — mais le `gen-api.mjs` du kit traite OpenAPI **3.0.3**, la spec visée est **3.1** (incohérence 2) |
| 3. Désérialisation stricte | `deny_unknown_fields` | `StrictJson` + `additionalProperties: false` + « validateur JSON Schema embarqué » | ✓ mécanisme, **bibliothèque non nommée** ; absente du kit (incohérence 2) |
| 4. Contract tests CI | spec-sync + endpoints-exist | gate `contrat` (client régénéré ≡ commité, parité routes ↔ spec dans les deux sens) + gate `integration` (réponses validées) | ✓ — parité à construire (le kit ne fait que le diff `api.ts`) |

Sprint 0 inclut le harnais contrat : ☑ — Story 0.2, non optionnelle, avant toute story métier.
Contrat complet vis-à-vis des comportements spécifiés : ☐ — routes manquantes (incohérences 3 et 4).

## 4ter. Données et flux (obligatoire à Essential)
- Section présente, aux trois tables (données stockées, flux internes, flux externes) : ☑.
- Aucun flux externe du **produit** sans ligne : ☑. SMTP, boîtes de réception, hébergeur, sauvegardes de l'hébergeur,
  déploiement SFTP/FTPS et forge sont tous listés, avec l'ADR qui les autorise.
- Aucune donnée stockée sans ligne : ☐. Il manque les compteurs de limitation par IP hachée (donnée pseudonyme) et,
  si les corps d'e-mail sont conservés, leur contenu. Les flux d'approvisionnement du build (Packagist, npm,
  Docker Hub) ne sont pas listés. La source de l'état de support de PHP (Story 5.2) n'est pas précisée
  (incohérences 7 et 13).

## 5. Glossaire → code (mapping présent) : ☑
Le tableau « Glossaire DDD → mapping code » couvre tous les termes métier. Réserves mineures : `Catalog`, `Page` et
`Lang` sont mappés mais absents de la liste des agrégats de la couche Domain ; *Publication* n'a pas de mapping
(colonne `published`) (incohérence 18).

## 6. BDD + Documentation Vivante (flux critiques couverts) : ☑
Les parcours P1 à P4 sont nommés au PRD §9, comme artefacts uniques (`journeys/*.journey.ts`) importés par les trois
harnais (e2e, visuel, vitrine). L'invariant anti-dette est en gate `verify` (Story 0.3). Le choix des parcours
critiques (défaut : P1 et P3) est laissé au PO, conformément à `documentation-vivante.md`. Réserves :
- P4 est rattaché à la Story 3.9 (FR-026) alors que le PRD le lie à FR-021/022.
- Aucune story ne porte explicitement le rejeu de P3.
- P1 et P2 vérifient des e-mails (FR-031, FR-030) livrés par la Story 4.1, ordonnée après ou en parallèle
  (incohérences 10 et 20).

## 7. TDD (stratégie de tests + couverture cible) : ☑
La pyramide va de Unit (`SlotCalculator`, en tests de propriétés sur les changements d'heure) à BDD, Integration
(MariaDB 10.6 réelle, concurrence), E2E, visuel et vitrine. Couverture cible : domaine ≥ 95 %, application ≥ 90 %.
Le test de concurrence « 20 réservations simultanées = 1 confirmée » est en CI. La DoD impose les 4 classes, rouges
puis vertes.

## 7bis. Classes de tests 4×N (mécanisé)
- Matrice 4×N par FR présente dans le PRD (`@happy` + `@negative` + `@edge` + `@security`) : ☑ (26/26).
- 4 classes listées dans chaque story : ☑ (31/31, y compris Sprint 0).
- Aucune FR orpheline d'une classe : ☑. Réserve mineure : FR-026 `@edge`, « lisible sur tablette », n'est pas
  objectivable sans critère (golden ou seuil).

## 8. Readiness organisationnelle
- Scrum : ☑ (un flux, Petit ; DoD commune ; réserve d'émergence de 20 %).
- Nexus (si applicable) : — (non applicable, un seul flux).
- SAFe (si applicable) : — (non applicable).
- ITIL (pré-prod) : ☑ partiel. Recette `test.<domaine>` (ADR-008), check-list de mise en ligne (5.1) et page santé (5.2) existent. Mais le **sondage de l'offre d'hébergement**, prévu « avant le Sprint 0 » au Brief §15, n'est dans aucune story (incohérence 11).

## 9. « Agent IA Ready »
- Specs assez précises pour qu'un agent boucle sans deviner : ☐. Un agent devrait deviner sur les points suivants :
  - la durée des prestations de démonstration (incohérence 6) ;
  - le lien d'annulation à la relance (7) ;
  - le sort des disponibilités quand les horaires du salon sont réduits (8) ;
  - les routes absentes de la spec (3, 4) ;
  - le validateur JSON Schema (2) ;
  - les tests d'Epic 2 qui exigent des réservations pas encore construites (10).
- Critères d'acceptation testables : ☑ (Gherkin concret par FR et par story, valeurs chiffrées).
- Points irréversibles identifiés et marqués « validation humaine » : ☑. Sont marqués :
  - ADR-005, persistance (« irréversible en pratique », tranché par le PO) ;
  - ADR-008, mapping branche → environnement 🔴 ;
  - rupture du contrat `v1` ;
  - protection de branche (0.5) ;
  - première mise en production et première migration en production (5.1).

  ADR-004 est explicitement réversible.

## 10. Incohérences détectées

**Gravité → verdict.**
- **Bloquante** : empêche le Sprint 0. Une seule suffit pour FAIL.
- **À corriger** : à lever avant le démarrage de la story concernée. Avec un score ≥ 80, cela donne CONCERNS.
- **Mineure** : se corrige en vol, dans la story qui touche le sujet. N'empêche pas un PASS.

Bilan : 0 bloquante · 12 à corriger · 9 mineures → **CONCERNS**.

| # | Gravité | Persona | Fichier § | Incohérence |
|---|---|---|---|---|
| 1 | à corriger | Architecte (+ arbitrage PO) | `03-architecture.md` ADR-002, ADR-004 ; `01-product-brief.md` §14 | L'élément 1 du contrat (spec générée du code) est remplacé par une spec écrite à la main. Le cadre l'admet sous ADR **avec destination fixée** (`bootstrap-nouveau-projet.md` §Conditionnement : API Platform « point d'arrivée de tout projet » ; `contrat-api.md` : l'arbitrage porte « jamais sur le fait d'y arriver »). Or ADR-002 parle d'« éventuelle bascule », et la question au PO d'ADR-004 (« quitte à passer plus tard… si le projet grandit ») soumet la **destination** à l'arbitrage, pas la modalité. Le résidu n'est pas mesuré (pas d'indicateur au diagnostic, pas de déclencheur ni d'échéance de bascule). |
| 2 | à corriger | Architecte | `03-architecture.md` §Contrat API, Couche 3 ; `04` Story 0.2 | La bibliothèque du « validateur JSON Schema embarqué » derrière `StrictJson` n'est pas nommée (élément 3, ni version ni support d'OpenAPI 3.1). Le `gen-api.mjs` du kit traite OpenAPI 3.0.3 (nullable via `anyOf`), alors que la spec visée est en 3.1 (`type: [..., "null"]`, a priori non géré par `tsType` — à confirmer par l'Architecte). Le kit n'a ni `StrictJson` ni contrôle de parité routes ↔ spec (`harness/verify.php` C1 ne vérifie que la présence de la spec, `run-contract.sh` le diff `api.ts`). Story 0.2 doit donc construire tout cela, sans que ce soit dimensionné. |
| 3 | à corriger | Architecte + PM | `02-prd.md` §9bis ; `03-architecture.md` Couche 2 | La spec « écrite avant le code » n'a pas de route pour plusieurs comportements spécifiés : l'édition des pages légales (FR-011), la génération et la régénération des codes de secours (FR-043), l'enrôlement TOTP (FR-042, si `/install` est un îlot ; son mode de rendu n'est pas dit), la recherche, l'export et l'effacement RGPD (Story 2.8), le tableau de bord de santé (Story 5.2) et l'export manuel de la base (A.8.13). La parité routes ↔ spec forcera l'agent à inventer ces entrées. |
| 4 | à corriger | Scrum Master + Architecte | `04-epics-stories.md` Epic 3 ; `03-architecture.md` Couche 2 | Les endpoints publics `GET /api/v1/services` et `GET /api/v1/barbers`, consommés par l'îlot de réservation (3.7), n'ont ni story (3.4 ne couvre que `days` et `slots`) ni cas d'usage de lecture nommé. |
| 5 | à corriger | PM (+ Architecte) | `02-prd.md` §7 NFR-06 ; `03-architecture.md` §API · Sécurité & RGPD ; `04` Story 2.8 | Le droit d'accès et d'effacement RGPD n'existe qu'en NFR et en prose dans l'architecture (« à ajouter au backlog »). La Story 2.8 l'implémente sans FR, sans matrice 4×N au PRD, sans cas d'usage ni endpoint : c'est une story sans exigence source. |
| 6 | à corriger | PM (arbitrage PO) | `docs/design/reference-le-corbeau.md` §Contenu d'exemple ; `02-prd.md` FR-008 ; `03-architecture.md` Couche 1 ; `04` Story 2.1 | Deux prestations des données initiales ont une durée non multiple du pas de 15 min : « Rasage à l'ancienne » (40 min) et « Le Rituel du Corbeau » (100 min). Elles violent `Duration` (« multiple de 15 »), FR-008 `@negative` et le modèle de cellules `booking_slot` de 15 min (ADR-006). Or la Story 2.1 charge ces données. Il faut trancher : arrondir les durées, changer le pas, ou admettre des durées libres arrondies à la cellule. |
| 7 | à corriger | PM + Architecte | `02-prd.md` FR-027, FR-031, FR-032, §10 `mail_log` ; `03-architecture.md` §Données et flux | Le jeton d'annulation n'est « jamais stocké en clair » (seul son haché l'est). `mail_log` n'a pas de colonne de corps, mais FR-032 et la Story 4.2 parlent d'un corps « non conservé au-delà de la réservation » et FR-027 d'« e-mails stockés ». La relance d'une confirmation client en échec ne peut donc pas reconstruire le lien d'annulation : rien ne dit s'il faut stocker le corps, régénérer le jeton à la relance, etc. Si le corps (avec ses données personnelles) est conservé, il manque à « Données stockées ». |
| 8 | à corriger | PM | `02-prd.md` FR-010 `@edge`, FR-020 | Réduire les horaires du salon (FR-010) peut faire déborder les disponibilités des barbiers, qui doivent être « comprises dans les horaires » (FR-020, glossaire), sans que leur sort soit dit : refus, rognage automatique ou alerte. Par ailleurs, FR-010 `@edge` exige des réservations « déplacées/annulées », alors qu'aucune FR ne permet de déplacer une réservation. |
| 9 | à corriger | Architecte | `03-architecture.md` Couche 5, mapping ISO A.5.15 (PR.AA-05.3) | « Moindre privilège de l'utilisateur MariaDB (pas de `DROP`/`GRANT` hors migrations) » n'est pas mécanisable : les migrations s'exécutent depuis l'admin (FR-042, `ApplyPendingMigrations`) avec l'unique compte applicatif du mutualisé, qui détient donc les droits DDL. Il faut soit deux comptes, soit un résidu accepté en ADR avec mesure compensatoire. |
| 10 | à corriger | Scrum Master | `04-epics-stories.md` §Ordre et dépendances | Plusieurs stories de l'Epic 2 testent des comportements qui reposent sur l'Epic 3, ordonné après : 2.3 `@edge` (suppression d'une prestation avec réservations futures), 2.4 (désactivation d'un barbier avec réservations futures), 2.5 (réduction des horaires sous `AgendaLock`, fermeture exceptionnelle de FR-005), 2.8 (l'effacement libère `booking_slot`). Ils exigent `booking`, `booking_slot`, `AgendaLock` et les absences (3.2, 3.3, 3.5). De même, P1 (3.7) vérifie FR-031 alors que 4.1 vient après 3.7, et P2 (3.8) et l'annulation salon (3.9) attendent les e-mails de 4.1, ordonnée en parallèle. |
| 11 | à corriger | Scrum Master + Analyste | `01-product-brief.md` §15 ; `04-epics-stories.md` Sprint 0 ; `PROJET.md` | « Sonder l'offre réelle avant le Sprint 0 » n'est porté par aucune story ni action. Plusieurs points en dépendent : PHP 8.2, `.htaccess`, tâche planifiée (FR-027), SFTP/FTPS (ADR-008), sauvegarde (NFR-05), juridiction UE (Données et flux). Ce n'est pas bloquant pour le code (hébergement simulé), mais c'est bloquant pour 5.1. |
| 12 | à corriger | Architecte + Scrum Master | `03-architecture.md` Couche 5 A.8.13 ; `02-prd.md` NFR-05 | La sauvegarde repose sur une hypothèse (fournie par l'hébergeur) et sur un « export manuel depuis l'admin » qui n'a ni FR, ni story, ni endpoint. La couche plateforme CyFun (sauvegardes testées, `conformite.md` §2bis) n'est ni portée ni compensée par une mesure nommée. |
| 13 | mineure | Architecte | `03-architecture.md` §Données et flux | Plusieurs éléments manquent aux tables : les compteurs de limitation de débit par IP hachée (donnée pseudonyme, Couche 3) dans « Données stockées » ; les flux d'approvisionnement du build (Packagist, npm, Docker Hub) ; la source de l'« état de support » de PHP (Story 5.2), à préciser (une table embarquée, ou un flux externe à ajouter). |
| 14 | mineure | Architecte | `03-architecture.md` ADR-006 | La désactivation d'un barbier, la dépublication d'une prestation et le retrait d'une prestation d'un barbier ne passent pas par `AgendaLock`. Une réservation concurrente peut alors violer l'invariant « barbier actif, prestation publiée ». |
| 15 | mineure | Architecte | `03-architecture.md` Couche 7 | L'alerte « erreur d'envoi répétée » part par e-mail, c'est-à-dire par le SMTP même qui échoue. Il faut un canal de repli (bandeau dans l'admin). |
| 16 | mineure | PM | `02-prd.md` FR-042, §13 critère 3 | Le PRD parle de paquet « envoyé par FTP », alors qu'ADR-008 et la Story 5.1 imposent SFTP/FTPS, « jamais FTP en clair ». |
| 17 | mineure | Analyste | `01-product-brief.md` §14 « Pile » | Le Brief dit la pile « imposée par l'hébergement ». C'est contredit par ADR-004 (« pas exclue par l'hébergement ») et par le correctif inscrit dans `PROJET.md`. Le Brief v1.0 n'a pas été mis à jour. |
| 18 | mineure | PM + Architecte | `02-prd.md` §4 ; `03-architecture.md` Couche 1 et mapping | Le glossaire a des trous : *Jeton d'annulation* et *Média* n'ont de définition nulle part (le PRD renvoie au Brief §8, qui ne les contient pas) ; `Catalog`, `Page` et `Lang` sont mappés mais absents des agrégats du Domain ; *Publication* n'a pas de mapping. |
| 19 | mineure | PM + Architecte | `02-prd.md` FR-023, NFR-06 ; `03-architecture.md` §RGPD | La « case de consentement » obligatoire contredit la base légale déclarée (« exécution du service ») : un consentement conditionnant le service n'est pas une base valable. La case est à requalifier en « j'ai pris connaissance de la politique ». |
| 20 | mineure | Scrum Master | `04-epics-stories.md` Stories 1.1, 1.2, 2.1, §Stories transverses | L'enrôlement TOTP fait partie de `/install` selon FR-042, mais la Story 1.1 l'omet et la 1.2 le porte sans dire où (risque d'un compte éditeur sans second facteur entre les deux). Les couches de la Story 2.1 n'incluent pas Infra (lecture du contenu, données initiales). P4 est mal rattaché et P3 n'a pas de story porteuse (cf. §6). |
| 21 | mineure | PM | `02-prd.md` §2 O4, §12, FR-026 | L'objectif O4 (≥ 30 % de rendez-vous en ligne) n'a pas de dénominateur, car les rendez-vous pris par téléphone ne sont enregistrés nulle part. L'hypothèse « durée max 240 min » de FR-008 manque à la liste §12. FR-026 `@edge` (« lisible sur tablette ») n'est pas objectivable. |

## 11. Recommandations

Avant passage en Phase 2. On reboucle sur PM, Architecte et Scrum Master, puis on revalide.

1. **PO (arbitrages courts, en langage clair)** :
   - les durées 40 et 100 min (n° 6) ;
   - le sort des disponibilités quand les horaires sont réduits (n° 8) ;
   - le sort du lien d'annulation à la relance (n° 7) ;
   - la destination du contrat : API Platform fixée comme point d'arrivée, seule la *modalité* et le *moment* de la bascule restant à arbitrer (n° 1).
2. **PM — PRD v1.1** :
   - créer une FR « droits RGPD » (recherche, export, effacement) avec sa matrice 4×N ;
   - compléter FR-011 (édition) et FR-043 (génération et régénération) ;
   - ajouter au §9bis les routes manquantes (n° 3, 4) ;
   - harmoniser FTP → SFTP/FTPS ;
   - requalifier la case de consentement ;
   - définir *Jeton d'annulation* et *Média*.
3. **Architecte — Architecture v1.1** :
   - ADR-002 : destination, indicateur de résidu, déclencheur de bascule ;
   - nommer le validateur JSON Schema et la stratégie OpenAPI 3.1 du générateur (ou passer la spec en 3.0.3) ;
   - ajouter les cas d'usage manquants (`UpdateLegalPage`, `RegenerateRecoveryCodes`, `ListPublishedServices`, `ListBookableBarbers`, `ExportClientData` / `EraseClientData`, `ExportDatabase`) ;
   - traiter le moindre privilège en base par un ADR ;
   - compléter « Données et flux » (n° 7, 13) ;
   - étendre ADR-006 aux désactivations (n° 14) ;
   - ajouter un canal d'alerte de repli.
4. **Scrum Master — Backlog v1.1** :
   - ajouter une story ou action « sonder l'offre d'hébergement » avant Sprint 0 et une story de sauvegarde ;
   - dimensionner la construction de `StrictJson` et de la parité dans 0.2 ;
   - corriger les dépendances Epic 2 ↔ Epic 3 : avancer le schéma `booking` / `booking_slot` + `AgendaLock`, ou déplacer les `@edge` concernés dans les stories d'agenda ;
   - placer 4.1 avant le rejeu de P1 et P2 ;
   - attribuer P3 et P4 à une story porteuse ;
   - placer l'enrôlement TOTP dans 1.1 ;
   - recalculer l'estimation après ajouts.
5. **Analyste** : aligner le Brief §14 sur ADR-004 (la pile est un choix, pas une contrainte).
6. **Registre** : la ligne 5 de `PROJET.md` reste à mettre à jour (verdict CONCERNS, retour vers PM, Architecte et Scrum Master).

---
**Si FAIL/CONCERNS** : reboucler vers le(s) persona(s) concerné(s). **Si PASS** : le superviseur valide le passage au chef de projet (Phase 2).

---

*Dérivé du Manifeste Maury (CC BY-SA 4.0). Source : [`github.com/gilmry/manifest`](https://github.com/gilmry/manifest).*

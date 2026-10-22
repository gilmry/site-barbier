---
livrable: Rapport de validation croisée
persona: 05-validateur (BMAD, TOGAF Phase F)
projet: site-barbier
version: 0.2
sources:
  - 01-product-brief.md v1.1
  - 02-prd.md v1.1
  - 03-architecture.md v1.1 + 03b-plan-vulnerabilites.md v1.0
  - 04-epics-stories.md v1.1
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
Archétype déclaré : **stateful full-stack** (toutes les sections de `archetypes.md` en scope). Niveau CyFun® visé :
**Essential** (section *Données et flux* obligatoire).*

## Historique

| Passage | Livrables | Score | Verdict | Bilan |
|---|---|---|---|---|
| 1 (v0.1) | v1.0 (26 FR, 31 stories) | 98/100 | CONCERNS | 0 bloquante · 12 à corriger · 9 mineures |
| **2 (v0.2)** | v1.1 (28 FR, 33 stories ; commit `86a8c8b`) | **100/100** | **CONCERNS** | 0 bloquante · **3 à corriger** · **9 mineures** |

Les 21 points du passage 1 ont tous été vérifiés dans les textes v1.1 (§10bis).
- **18 sont levés.**
- **3 ne le sont qu'en partie.** Ils réapparaissent ci-dessous sous un nouveau numéro :
  - n° 10 (ordonnancement) → reste R2 ;
  - n° 16 (FTP) → reste R7 ;
  - n° 19 (consentement) → reste R4.
- **Les corrections ont introduit 2 incohérences nouvelles d'importance « à corriger »** : R1 (ADR-004) et R3 (e-mail dans l'URL).

## Statut global : **CONCERNS**

**Fidelity score : 100/100.** La traçabilité est complète.
- **Restent 3 incohérences à corriger** avant les stories concernées, toutes petites.
- **L'une conditionne la sortie elle-même** : la modalité de bascule d'ADR-004 doit être tranchée par le PO et inscrite au registre (R1).

**Critère explicite « contrat matérialisé »** (`skills/contrat-api.md`) : **4/4 au sens du cadre.**
- **Éléments 2 à 4 : mécanisés et nommés.**
  - élément 2 : `gen-api.mjs` adapté à OpenAPI 3.1 ;
  - élément 3 : `StrictJson`, avec `opis/json-schema` (JSON Schema 2020-12, le dialecte d'OpenAPI 3.1) ;
  - élément 4 : parité routes ↔ spec, et réponses validées en intégration.
- **Élément 1 : remplacé** par une spec écrite à la main, sous ADR-002. Le remplacement suit la voie que prévoit `bootstrap-nouveau-projet.md` §Conditionnement :
  - le résidu est mesuré : part des réponses validées, incidents de dérive ;
  - il est relevé à chaque gate review ;
  - la destination, API Platform, est fixée.
- **Réserve** : le choix de la *modalité* est attribué au PO sans trace de sa décision (R1).

> Calcul : (capacités Brief couvertes en PRD / total capacités) × 25 + (FRs couvertes en Architecture / total FRs) × 25 + (FRs couvertes en Stories / total FRs) × 25 + (4 classes couvertes / total 4×N) × 25

| Terme | Couvert / total | Points |
|---|---|---|
| Capacités Brief §7 → FR | 7 / 7 | 25 |
| FR → couches d'architecture | 28 / 28 | 25 |
| FR → stories | 28 / 28 | 25 |
| Classes 4×N couvertes (28 FR × 4) | 112 / 112 | 25 |
| **Total** | | **100** |

**Critère FR → Architecture**, inchangé depuis le passage 1 : une FR est couverte si chaque verbe de son « je veux » a
une couche nommée et, au choix, un cas d'usage, un endpoint du contrat ou un mécanisme nommé dans une couche.

| FR | Mécanisme d'architecture | ✓ | Story |
|---|---|---|---|
| FR-001…005 | `RenderHomePage`, gabarits PHP, middleware langue + `TranslatedText`/`Lang` (FR-002) | ✓ | 2.1–2.5 |
| FR-006 | `UpdateSection`, `PUT /admin/sections/{key}` | ✓ | 2.6 |
| FR-007 | `UploadMedia`, `/admin/media` | ✓ | 2.7 |
| FR-008 / 009 / 010 | `ManageCatalog` / `ManageBarbers` / `UpdateOpeningHours` + `UpdateSalonInfo` | ✓ | 2.3 / 2.4 / 2.5 (+ 3.11) |
| FR-011 | `RenderLegalPage` + **`UpdateLegalPage`**, `GET/PUT /admin/legal-pages/{key}` | ✓ (levé) | 2.8 |
| **FR-012** (nouvelle) | `FindClientBookings`, `ExportClientData`, `EraseClientData`, `/admin/privacy/*` | ✓ | 2.8 |
| FR-020 / 021 | `SetAvailability` / `AddAbsence`, `AddClosure` + `AgendaLock` | ✓ | 3.2 / 3.3 (+ 3.11) |
| FR-022 | `ListBookableServices`, `ListBookableBarbers`, `ListBookableDays`, `ListFreeSlots`, `SlotCalculator` | ✓ | 3.1, 3.4 |
| FR-023 / 024 | `BookSlot` (+ `NoPreferenceAssigner`), cellules de 5 min | ✓ | 3.5 / 3.6 |
| FR-025 / 026 / 027 | `CancelBookingByClient` / `ListBookings` + `CancelBookingBySalon` / `PurgeExpiredBookings` | ✓ | 3.8 / 3.9 / 3.10 |
| FR-030 / 031 / 032 | `NotifySalon` / `NotifyClient` / `RetryFailedMails` + `/admin/mail-log` | ✓ | 4.1 / 4.1 / 4.2 |
| FR-040 / 041 | `Login`, `VerifyTotp` / sessions en base, `Logout`, CSRF | ✓ | 1.2 / 1.3 |
| FR-042 | `InstallApplication`, `EnrollTotp`, `ConfirmTotpEnrollment`, `ApplyPendingMigrations` | ✓ | 1.1 + 1.2 |
| FR-043 | `ConfirmTotpEnrollment` (émission), `UseRecoveryCode`, **`RegenerateRecoveryCodes`** + routes | ✓ (levé) | 1.4 |
| **FR-044** (nouvelle) | `ExportBackup`, `GET /admin/backup` (dump en flux) | ✓ | 5.2 |

Classes 4×N : 28 FR × 4 classes présentes dans le PRD (FR-012 et FR-044 comprises) ; 33 stories × 4 classes.

## 1. Cohérence DDD
- **Glossaire (ubiquitous language) cohérent Brief→PRD→Archi : ☑.** Le PRD §4 définit désormais :
  - *Unité de durée* ;
  - *Jeton d'annulation* ;
  - *Média* ;
  - *Publication*.

  Le mapping de l'architecture les reprend.
- **Bounded contexts stables : ☑**, avec une réserve mineure (R6). FR-012 et ses cas d'usage sont rangés en *Vitrine*, alors qu'ils effacent des `Booking` sous `AgendaLock`, c'est-à-dire dans *Agenda*. FR-044 est rangée en *Accès*.
- **Invariants métier présents et codables : ☑.** `Duration` est en multiples de 5 min, ce qui rend les données initiales (40 et 100 min) conformes. Les débuts se font au quart d'heure, sur des cellules de 5 min, et `SlotCalculator` rogne aux horaires. Le Gherkin de 3.1 est vérifié par le calcul : 13:15 + 40 min = 13:55 ≤ 14:00 ; 13:30 + 40 min = 14:10 > 14:00.

## 2. Couverture SOLID
☑ — chaque couche annonce ses principes ; les lectures publiques ont leurs cas d'usage (`ListBookableServices`,
`ListBookableBarbers`) ; ports du domaine inchangés plus `RateLimiter`.

## 3. Traçabilité
- Brief → PRD (toute capacité a ses exigences) : ☑ — 7/7 ; la table du PRD intègre FR-012 et FR-044 en §7.6.
- PRD → Architecture (toute exigence a sa couche) : ☑ — 28/28.
- PRD → Stories (toute exigence a ses stories) : ☑ — 28/28 ; plus de story sans FR (2.8 ↔ FR-012, 5.2 ↔ FR-044).

**Cohérence des chiffres : ☑, recalculée story par story.**

| Bloc | Composition | Jours | Tours |
|---|---|---|---|
| Sprint 0 | 0.0 S, 0.1 L, 0.2–0.5 4 × M | 0,5 + 1 + 3 = 4,50 | 6 + 10 + 32 = 48 |
| Accès | 1.1 M, 1.2 L, 1.3 M, 1.4 S | 3,00 | 32 |
| Vitrine | 2.1 L, 2.3 L, six M | 2 + 4,5 = 6,50 | 20 + 48 = 68 |
| Agenda | 3.1–3.10, plus 3.11 M | 7,25 + 0,75 = 8,00 | 78 + 8 = 86 |
| Notification | | 1,25 | 14 |
| Mise en ligne | 5.1 M, 5.2 passée de S à M | 1,50 | 16 |

- Sous-total : 24,75 j / 264 tours.
- Avec l'émergence de 20 % : + 4,95 j / + 52,8 tours, soit 29,7 j ≈ 30 j et ≈ 317 tours.
- Coût modèle : 317 × 0,442 € = 140,1 € ≈ 140 €.
- Écart avec la v1.0 : + 1,5 j de sous-total (0.0 = 0,5 ; 3.11 = 0,75 ; 5.2 de S à M = 0,25). Rapport 29,7 / 27,9 ≈ + 6,5 %, annoncé « + 7 % ».
- Nombre de stories : 6 + 4 + 8 + 11 + 2 + 2 = 33.

La target de 30 j est notée « à confirmer par le PO » (le registre ne connaît encore que 28 j).

**Numérotation : ☑.** Les FR sont continues par module : 001–012, 020–027, 030–032, 040–044. Il y a 28 en-têtes `#### FR-`, et `§3` annonce bien « FR-001 → FR-044 (28 FR) ». Les 33 en-têtes de story sont uniques. La Story 3.11 est placée entre 3.5 et 3.6, dans l'ordre d'exécution, ce qui est voulu ; c'est un écart purement cosmétique.

## 4. Architecture hexagonale
- Backend : dépendances vers l'intérieur : ☑ (gate H1, exceptions traduites dans la seule couche Http).
- Frontend (hexagonale light) : ☑ (client généré seul, invariant `fetch(` ; D2 en ADR-003).

## 4bis. Contrat API matérialisé
| Élément | Mécanisme v1.1 | Statut |
|---|---|---|
| 1. Source de vérité | spec 3.1 écrite à la main ; ADR-002 : résidu mesuré en gate review, destination API Platform fixée | remplacé, conforme au §Conditionnement (réserve R1 sur la trace de la modalité) |
| 2. Client généré | `gen-api.mjs` du kit, adapté à 3.1 dans la Story 0.2 | ✓ |
| 3. Désérialisation stricte | `StrictJson` + `opis/json-schema`, `additionalProperties: false` vérifié par test | ✓ |
| 4. Contract tests CI | parité routes ↔ spec (nouvelle, 0.2), client régénéré ≡ commité, réponses validées | ✓ |

- **Sprint 0 inclut le harnais contrat : ☑.** La Story 0.2 annonce explicitement ce qui manque au kit : `StrictJson`, la parité et l'adaptation à OpenAPI 3.1.
- **Contrat complet vis-à-vis des comportements spécifiés : ☑.** Le PRD §9bis a reçu les routes manquantes :
  - TOTP et codes de secours ;
  - pages légales ;
  - droits RGPD ;
  - santé ;
  - sauvegarde.
- **Réserves** : sur l'URL de recherche RGPD (R3), et sur le « TOTP récent », dont le mécanisme n'est pas nommé (R5).

## 4ter. Données et flux (obligatoire à Essential)
- **Données stockées : ☑.** Les lignes suivantes ont été ajoutées :
  - compteurs de débit (IP hachée avec sel) ;
  - sauvegardes exportées ;
  - mention « aucun corps d'e-mail stocké ».

  Le PRD §10 a reçu les tables `booking_slot`, `rate_limit` et `session`.
- **Aucun flux externe sans ligne : ☑.** Les registres de build sont ajoutés, et la table de fin de support de PHP est embarquée (aucun appel à l'exécution).
- **Réserve mineure (R8)** :
  - l'export de sauvegarde garde des données personnelles au-delà de la conservation de 6 mois, sans mention dans la politique ;
  - la restauration dépend de la clé sodium, absente du dump.

## 5. Glossaire → code (mapping présent) : ☑
Le mapping comprend maintenant les entrées suivantes : *Publication* → `Service::published`, *Unité de durée* → `Duration`, *Limitation de débit* → `rate_limit`, et `Page`/`Catalog`/`Lang` au Domain. Réserve mineure (R9) : le mapping cite `section.page`, mais la table `section` du PRD §10 n'a pas de colonne `page`.

## 6. BDD + Documentation Vivante (flux critiques couverts) : ☑
Chaque parcours a désormais sa story porteuse :

| Parcours | Story porteuse | Dépendances vérifiées |
|---|---|---|
| P1 | 3.7 | après 4.1 |
| P2 | 3.8 | après 4.1 |
| P3 | 2.7 | après 1.2, 2.3 et 2.6 |
| P4 | 3.11 | après 3.3 et 3.5 |

## 7. TDD (stratégie de tests + couverture cible) : ☑
La stratégie et les cibles sont inchangées. Deux tests s'ajoutent : la restauration de sauvegarde (aller-retour export → import → P1 vert) et la concurrence entre une réservation et une dépublication ou une désactivation.

## 7bis. Classes de tests 4×N (mécanisé)
- Matrice 4×N par FR présente dans le PRD : ☑ (28/28).
- 4 classes listées dans chaque story : ☑ (33/33).
- Aucune FR orpheline d'une classe : ☑. FR-026 `@edge` est désormais objectivée : pas de défilement horizontal à 768 px.

## 8. Readiness organisationnelle
- Scrum : ☑ · Nexus : — (non applicable) · SAFe : — (non applicable).
- ITIL (pré-prod) : ☑. La Story 0.0 sonde l'offre d'hébergement avec des critères éliminatoires (sauvegarde et restauration, juridiction UE), et le choix de l'offre revient au PO. Viennent ensuite la recette, la check-list de mise en ligne et la page santé.

## 9. « Agent IA Ready »
- Specs assez précises pour qu'un agent boucle sans deviner : ☐, à trois points près :
  - l'ordre réel des dépendances de 3.2 et 3.3 (R2) ;
  - le mode de saisie du « TOTP récent » (R5) ;
  - le périmètre de la session avant l'enrôlement TOTP (R5).
- Critères d'acceptation testables : ☑.
- Points irréversibles identifiés et marqués « validation humaine » : ☑. Ils sont inchangés : ADR-005, ADR-008, la rupture du contrat `v1`, la protection de branche, la première mise en production et la première migration en production. S'y ajoute le choix de l'offre d'hébergement (0.0), qui engage un contrat. Réserve R1 : une modalité est attribuée au PO sans trace de sa décision.

## 10. Incohérences détectées (passage 2)

**Gravité → verdict** (inchangée) :
- **Bloquante** : empêche le Sprint 0 ; donne FAIL.
- **À corriger** : à lever avant le démarrage de la story concernée ; avec un score ≥ 80, donne CONCERNS.
- **Mineure** : à corriger en cours de route, n'empêche pas un PASS.

**Bilan : 0 bloquante · 3 à corriger · 9 mineures → CONCERNS.**

| # | Gravité | Persona | Fichier § | Incohérence |
|---|---|---|---|---|
| R1 | à corriger | Architecte → arbitrage PO | `03-architecture.md` ADR-004 ; `PROJET.md` Arbitrages | ADR-004 affirme que « le PO a tranché la modalité : bascule différée », avec trois déclencheurs (incident de dérive, plus de 60 routes, changement d'offre) et une date de réexamen. Or le PO n'a répondu qu'à la question d'origine (« Léger »), citée telle quelle. Le registre ne trace ni les déclencheurs ni la date. **Une décision humaine est attribuée sans preuve** (répondre-de). Il faut la soumettre au PO et l'inscrire aux Arbitrages tranchés. |
| R2 | à corriger | Scrum Master | `04-epics-stories.md` §Ordre (l. « Chaque story ne teste que ce que les stories précédentes ont livré ») ; Stories 2.5, 3.2, 3.3 | La règle annoncée est fausse sur trois points. **3.2** `@edge` (« refusée y compris si la réservation arrive au même instant », test de concurrence) et **3.3** (Gherkin « si une réservation existe ce jour, l'absence est refusée », et `@edge` absence ↔ réservation simultanées) sont ordonnées **avant 3.5**, qui livre `BookSlot`, `booking_slot` et le chemin d'écriture verrouillé. **2.5** `@edge` (« fermeture exceptionnelle à venir affichée ») précède **3.3**, qui crée les fermetures. 3.11 couvre déjà ces cas : il faut les retirer de 3.2 et 3.3 (et de 2.5), ou réordonner. |
| R3 | à corriger | PM + Architecte | `02-prd.md` §9bis (`GET /api/v1/admin/privacy/bookings?email=`) ; FR-012 `@security` | L'adresse e-mail du client passe dans la **query string**. Elle finit dans les journaux d'accès Apache de l'hébergeur, qui sont hors de la cartographie et hors purge. Cela contredit FR-012 `@security` (« journalisée sans l'adresse en clair ») et la ligne « Journal applicatif : aucune coordonnée en clair ». La recherche doit passer en `POST` avec un corps. |
| R4 | mineure | Architecte | `03-architecture.md` §API · Sécurité & RGPD | Le paragraphe RGPD n'a pas suivi la v1.1. Il garde « consentement explicite à la politique », ce qui contredit FR-023 v1.1 (« pas de case de consentement »). Il dit aussi « à ajouter au backlog comme story », alors que FR-012 et la Story 2.8 existent. |
| R5 | mineure | PM + Architecte | `02-prd.md` §9bis, FR-044 ; `03-architecture.md` §API · Sécurité, ADR-010 | Deux trous dans le modèle d'accès admin. (a) Les routes d'enrôlement TOTP sont « admin (pendant l'installation) », alors que l'architecture pose « Endpoints admin : session + TOTP + CSRF ». Le périmètre de la session ouverte avant l'enrôlement n'est pas spécifié ; il devrait se limiter aux seules routes d'enrôlement. (b) Le « TOTP récent » exigé pour `GET /admin/backup` (FR-044) et pour l'application des migrations (ADR-010) n'a aucun mécanisme nommé : pas de route de re-saisie, et un `GET` ne porte pas de code. De plus, `/admin/migrations` reste marqué « admin » au contrat. |
| R6 | mineure | PM + Architecte | `02-prd.md` §5 ; `03-architecture.md` Couche 2 | Le découpage en bounded contexts est incohérent sur deux points. FR-012 et `FindClientBookings`, `ExportClientData`, `EraseClientData` sont rangés en *Vitrine*, alors qu'ils lisent et effacent des `Booking` sous `AgendaLock` (ADR-006), donc dans *Agenda*. FR-044 (sauvegarde) est rangée en *Accès*, alors que sa capacité est §7.6. |
| R7 | mineure | PM (+ Analyste) | `02-prd.md` §12 (« mutualisé, FTP », « FTP sans SSH »), §13 critère 3 (« envoyé par FTP ») ; `01-product-brief.md` §15 | Des mentions de FTP subsistent, malgré « SFTP ou FTPS, FTP en clair exclu » (Brief §14 v1.1, ADR-008). |
| R8 | mineure | PM + Architecte | `02-prd.md` FR-012 `@edge`, FR-044, FR-011 ; `03-architecture.md` §Données stockées | Trois points restent à dire sur le cycle de vie des données. (a) L'effacement RGPD d'une réservation future envoie un e-mail d'annulation, puis efface le journal : un échec SMTP ne peut alors être ni constaté ni relancé. (b) Les sauvegardes exportées gardent des données de clients au-delà de 6 mois, sans mention dans la politique ni dans le registre de traitement. (c) La restauration « à l'identique » exige la clé sodium de `config.php`, qui ne figure pas dans le dump. |
| R9 | mineure | Architecte + PM | `03-architecture.md` Couche 6 (table des gates), ADR-010, mapping ; `02-prd.md` §10 | Deux écarts entre les textes. La règle de `verify` « aucune concaténation SQL », qui sert de mesure compensatoire dans ADR-010, est absente de la table des gates. Le mapping cite `section.page` pour les pages, mais la table `section` du PRD §10 n'a que `key` et `version`, ce qui laisse flou le stockage des pages légales. |
| R10 | mineure | Tous (Analyste, PM, Architecte, Scrum Master) | frontmatters 01–04 | Les versions sont en « 1.1 (à revalider) », mais `signature_humaine` affiche toujours `verdict: validé`, daté du 2026-10-22 et donc relatif à la v1.0. Le PRD a encore `source: 01-product-brief.md v1.0`. Il faut remettre la signature « à signer » jusqu'à la revalidation. |
| R11 | mineure | Scrum Master | `04-epics-stories.md` §Estimation, frontmatter ; `PROJET.md` | La target passe à ≈ 30 j, alors que le frontmatter (« ≈ 28 j ») et le registre (« target recalée 15 → 28 j ») n'ont pas changé. La confirmation du PO est annoncée mais pas tracée. |
| R12 | mineure | Scrum Master | `04-epics-stories.md` Story 1.1 | `/install` n'est désactivé qu'« une fois l'enrôlement confirmé (Story 1.2) », mais le `@security` de 1.1 exige « `/install` 404 après usage ». Livrée seule, la Story 1.1 ne peut pas être verte sur ce critère. Il faut déplacer la vérification en 1.2 ou la conditionner. |

## 10bis. Suivi des 21 points du passage 1

| n° (passage 1) | État en v1.1 | Preuve |
|---|---|---|
| 1 Contrat, destination | levé (réserve R1 sur la trace de la décision) | ADR-002 « Mesure du résidu » + « Destination » ; ADR-004 « Destination et modalité » ; Brief §14 erratum |
| 2 Validateur, OpenAPI 3.1 | levé | Couche 3 `opis/json-schema` ; Couche 4 adaptation de `gen-api.mjs` ; Story 0.2 |
| 3 Routes manquantes | levé (réserves R3, R5) | PRD §9bis, 8 nouvelles lignes |
| 4 `GET /services`, `/barbers` | levé | Couche 2 `ListBookable*` ; Story 3.4 |
| 5 RGPD sans FR | levé | FR-012 ; Story 2.8 |
| 6 Durées 40 et 100 min | levé | PRD §4 « Unité de durée » ; `Duration` multiple de 5 ; cellules de 5 min (ADR-006) ; Story 3.1 |
| 7 Jeton et relance | levé | FR-031/032 : corps recomposé, jeton renouvelé ; Couche 3 E-mail ; Story 4.2 |
| 8 Horaires ↔ disponibilités | levé | FR-010 `@edge` (rognage) ; `SlotCalculator` ; « pas de déplacement au MVP » |
| 9 Moindre privilège en base | levé | ADR-010 (deux comptes, sinon résidu accepté) ; 0.0 |
| 10 Ordonnancement | **partiel** → R2 | Epic 2 corrigé via 3.11 ; 4.1 avant 3.7 ; 2.8 après 4.2 et 3.11 ; restent 3.2, 3.3 et 2.5 |
| 11 Sondage de l'hébergement | levé | Story 0.0 |
| 12 Sauvegarde | levé (réserve R8) | FR-044 ; A.8.13 ; 0.0 éliminatoire ; restauration en CI |
| 13 Données et flux | levé | lignes `rate_limit`, sauvegardes, registres de build, table PHP embarquée |
| 14 ADR-006 étendu | levé | désactivation, dépublication, effacement RGPD sous verrou ; relecture sous verrou dans `BookSlot` |
| 15 Alerte SMTP | levé | Couche 7 : bandeau |
| 16 FTP | **partiel** → R7 | FR-042 et Brief §14 corrigés ; restent PRD §12, §13 et Brief §15 |
| 17 Brief §14 | levé | erratum v1.1 |
| 18 Glossaire | levé (réserve R9) | PRD §4 ; mapping |
| 19 Consentement | **partiel** → R4 | PRD FR-023 et §8 corrigés ; reste Architecture §RGPD |
| 20 TOTP, 2.1, P3/P4 | levé (réserve R12) | 1.1 → 1.2 ; couches de 2.1 avec Infra ; P3 → 2.7, P4 → 3.11 |
| 21 O4, 240 min, tablette | levé | O4 avec dénominateur déclaré ; PRD §12 ; 768 px |

## 11. Recommandations

Il faut reboucler une dernière fois. Ce sont de petites corrections ciblées, et le score est déjà au maximum.

1. **PO** : trancher en langage clair la modalité de bascule d'ADR-004 (déclencheurs et date de réexamen) et la target de ≈ 30 j. Inscrire les deux aux Arbitrages tranchés (R1, R11).
2. **Scrum Master** :
   - retirer de 3.2 et 3.3 les cas « réservation existante ou simultanée », et de 2.5 la « fermeture exceptionnelle », ou les réordonner (R2) ;
   - déplacer la vérification « `/install` 404 » en 1.2 (R12) ;
   - mettre à jour le frontmatter (R10, R11).
3. **PM** :
   - passer la recherche RGPD en `POST` avec un corps (R3) ;
   - nommer le mécanisme de « TOTP récent » et le périmètre de la session avant enrôlement (R5) ;
   - purger les dernières mentions de FTP (R7) ;
   - préciser l'ordre envoi/effacement de FR-012 et mentionner les sauvegardes exportées dans la politique (R8) ;
   - ranger FR-012 dans le module Agenda (R6).
4. **Architecte** :
   - mettre à jour §API · Sécurité & RGPD (R4) ;
   - aligner la table des gates sur ADR-010 et le stockage des pages légales sur le PRD §10 (R9) ;
   - documenter la dépendance de la restauration à la clé sodium (R8).
5. **Après correction**, un troisième passage peut se limiter à R1–R12. Si les trois « à corriger » sont levés, le verdict sera **PASS**, puis le superviseur validera le passage en Phase 2.
6. **Registre** : la ligne 5 de `PROJET.md` est à mettre à jour (passage 2 : CONCERNS, 100/100, 3 à corriger).

---
**Si FAIL/CONCERNS** : reboucler vers le(s) persona(s) concerné(s). **Si PASS** : le superviseur valide le passage au chef de projet (Phase 2).

---

*Dérivé du Manifeste Maury (CC BY-SA 4.0). Source : [`github.com/gilmry/manifest`](https://github.com/gilmry/manifest).*

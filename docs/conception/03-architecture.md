---
livrable: Architecture technique
persona: 03-architecte (BMAD, TOGAF Phases C-D)
projet: site-barbier
version: 1.1 (corrections du Validateur, à revalider)
source: 02-prd.md v1.1
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
annexe: 03b-plan-vulnerabilites.md
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: à revalider (v1.1, corrections du Validateur)
---

# Architecture technique — site-barbier

*Archétype **stateful full-stack** : les sept couches sont en scope. Niveau CyFun® visé : **Essential**.
Base : `kit-php` (dépôt Foyer), adapté à un hébergement mutualisé.*

## Vue d'ensemble

```
             ┌──────────────────────────────────────────────────────────────┐
             │ Frontend : gabarits PHP (pages publiques, rendu serveur)     │
             │            îlots Svelte 5 « booking » et « admin »           │
             │            → client TS GÉNÉRÉ depuis openapi/ (jamais d'URL) │
             │  ┌────────────────────────────────────────────────────────┐  │
             │  │ Http : front controller, routeur, StrictJson (valide    │  │
             │  │        contre la spec), session, CSRF, en-têtes, i18n   │  │
             │  │  ┌──────────────────────────────────────────────────┐  │  │
             │  │  │ Adapter : PDO/MariaDB (CQRS), SMTP, GD, horloge, │  │  │
             │  │  │           TOTP, sodium, fichiers, journal        │  │  │
             │  │  │  ┌────────────────────────────────────────────┐  │  │  │
             │  │  │  │ Application : use cases + ports + DTO      │  │  │  │
             │  │  │  │  ┌──────────────────────────────────────┐  │  │  │  │
             │  │  │  │  │ Domain (pur) : Vitrine · Agenda ·    │  │  │  │  │
             │  │  │  │  │ Notification · Acces — invariants    │  │  │  │  │
             │  │  │  │  └──────────────────────────────────────┘  │  │  │  │
             │  │  │  └────────────────────────────────────────────┘  │  │  │
             │  │  └──────────────────────────────────────────────────┘  │  │
             │  └────────────────────────────────────────────────────────┘  │
             └──────────────────────────────────────────────────────────────┘
                     dépendances toujours vers l'intérieur (gate H1)
```

Arborescence du dépôt (dérivée du kit) :

```
app/                      # tout le code serveur — HORS racine web
  src/Domain/{Vitrine,Agenda,Notification,Acces}/
  src/Application/{Vitrine,Agenda,Notification,Acces}/
  src/Adapter/{Persistence,Mail,Media,Security,Clock}/
  src/Http/               # routeur, contrôleurs, StrictJson, middleware
  templates/              # gabarits PHP des pages publiques
  i18n/{fr,nl,en}.php     # libellés d'interface
  migrations/NNNN_*.{up,down}.sql
  config.example.php      # config.php jamais commité (gate G1)
  vendor/                 # dépendances embarquées dans le paquet (pas installées sur le serveur)
www/                      # racine web : index.php (front controller), assets/, islands/, uploads/
openapi/site-barbier.openapi.json
frontend/{booking,admin}/ # sources des îlots (Vite) → www/islands/
journeys/                 # parcours de référence partagés (P1–P4)
harness/                  # gates (scripts Docker), visuel, vitrine, hébergement simulé
tests/{Unit,Application,Integration}/
```

## Couche 1 — Domain *(SOLID : SRP, DIP — racine : Ubuntu)*

Aucune dépendance (ni PDO, ni SQL, ni HTTP, ni `$_SESSION`) — vérifié par le gate H1 du kit.

| BC | Agrégats / value objects | Invariants codés dans les constructeurs |
|---|---|---|
| **Vitrine** | `Page` (accueil, pages légales), `Section`, `TranslatedText` (fr obligatoire, nl/en optionnels), `Lang` (fr/nl/en, liste blanche), `Catalog` (catégories ordonnées), `Service` (indicateur `published`), `Category`, `Barber`, `OpeningHours` (7 `DaySchedule`), `SalonInfo`, `MediaRef`, `Money` (centimes, ≥ 0), `Duration` (> 0, multiple de 5, ≤ 240) | FR obligatoire ; prix ≥ 0 ; durée valide ; fin > début |
| **Agenda** | `Booking` (états `confirmed` → `cancelled`), `TimeRange` (UTC, fin > début), `Slot`, `WeeklyAvailability`, `Absence` (barbier ou salon), `SlotPolicy` (débuts au quart d'heure, cellules de 5 min, délai 2 h, horizon 60 j), `SlotCalculator` (fonction pure), `NoPreferenceAssigner` (pure), `CancelToken` (haché), `RetentionPolicy` (6 mois) | réservation dans une disponibilité, hors absence, dans le futur ; annulation client ≥ 2 h avant ; pas de transition depuis `cancelled` ; prix/durée figés |
| **Notification** | `MailMessage`, `MailKind`, `MailAttempt` | destinataire valide ; aucun retour à la ligne dans les en-têtes |
| **Acces** | `Editor`, `PasswordPolicy` (≥ 12, liste noire), `TotpCode`, `RecoveryCode`, `LoginThrottle` | verrou progressif après 5 échecs ; code TOTP à usage unique |

Ports déclarés côté domaine : `BookingRepository`, `AgendaLock`, `ServiceRepository`, `BarberRepository`,
`ContentRepository`, `MediaStorage`, `Mailer`, `Clock`, `IdGenerator`, `TokenGenerator`, `PasswordHasher`,
`SecretBox`, `EditorRepository`.

`SlotCalculator` est **pur** : (horaires du salon, disponibilités, absences, réservations, prestation, maintenant, fuseau) → créneaux. Il **rogne** les disponibilités aux horaires du salon (une disponibilité qui déborde après une réduction d'horaires reste stockée mais n'ouvre aucun créneau ; l'admin la signale).
Toute la logique d'heure (Europe/Brussels, changements d'heure) y vit et se teste sans base.

## Couche 2 — Application *(ISP, OCP, DRY)*

Un cas d'usage par FR (ou groupe cohérent), orchestration sans règle métier :

- Vitrine : `RenderHomePage`, `UpdateSection`, `UploadMedia`, `ManageCatalog`, `ManageBarbers`, `UpdateOpeningHours`, `UpdateSalonInfo`, `RenderLegalPage`, `UpdateLegalPage`.
- Agenda : `ListBookableServices`, `ListBookableBarbers`, `ListBookableDays`, `ListFreeSlots`, `BookSlot`, `CancelBookingByClient`, `CancelBookingBySalon`, `SetAvailability`, `AddAbsence`, `AddClosure`, `ListBookings`, `PurgeExpiredBookings`, `FindClientBookings`, `ExportClientData`, `EraseClientData` (FR-012 : agissent sur `Booking`).
- Notification : `NotifySalon`, `NotifyClient`, `RetryFailedMails`.
- Acces : `Login`, `VerifyTotp`, `EnrollTotp`, `ConfirmTotpEnrollment` (émet les codes de secours), `UseRecoveryCode`, `RegenerateRecoveryCodes`, `Logout`, `InstallApplication`, `ApplyPendingMigrations`, `ExportBackup` (FR-044), `ReadHealth`.

DTO d'entrée/sortie typés ; les pages publiques et l'API appellent **les mêmes** cas d'usage.

## Couche 3 — Infrastructure backend *(LSP, DIP)*

- **Persistance** : CQRS SQL pur sur PDO/MariaDB 10.6 (InnoDB, `utf8mb4`), `*Queries` / `*Commands` séparés,
  mapping ligne↔entité isolé — **ADR-005**. Heures stockées en UTC (`DATETIME`), converties au bord.
- **Sérialisation de l'agenda** — **ADR-006** : toute écriture qui touche l'agenda d'un barbier passe par
  `AgendaLock` (`SELECT … FOR UPDATE` sur la ligne `barber`, en transaction), et les réservations occupent
  des cellules de 5 min dans `booking_slot` avec `UNIQUE(barber_id, slot_start)` (filet en base).
- **HTTP** : front controller unique `www/index.php` → routeur maison du kit (`vanilla`) — **ADR-004**.
  `StrictJson` valide chaque corps et chaque requête **contre la spec OpenAPI elle-même** (champ inconnu → 400),
  via **`opis/json-schema`** (Apache-2.0, JSON Schema 2020-12 — le dialecte d'OpenAPI 3.1), embarqué dans `vendor/`.
  `StrictJson` et la parité routes ↔ spec **n'existent pas dans le kit** : ils sont créés par la Story 0.2.
  Exceptions du domaine traduites en `problem+json` (400/401/403/404/409/422/429) dans cette seule couche.
- **Middleware** : en-têtes de sécurité (CSP stricte, HSTS, `nosniff`, `Referrer-Policy`, `frame-ancestors 'none'`),
  session, CSRF (jeton synchronisé), limitation de débit (compteurs en base, par IP hachée), langue.
- **Session** — FR-041 : sessions **en base** (gestionnaire `SessionHandlerInterface` sur PDO), jamais dans le
  `/tmp` partagé du mutualisé ; cookie `__Host-` `Secure; HttpOnly; SameSite=Strict`.
- **E-mail** : port `Mailer` → adaptateur SMTP authentifié (PHPMailer, embarqué) ; journal `mail_log` (type, état, erreur, tentatives — **jamais le corps**). Le corps est recomposé depuis la réservation à chaque envoi ; une (re)confirmation **renouvelle** le jeton d'annulation dans la même transaction que l'envoi journalisé.
- **Médias** : GD — vérification par contenu (`getimagesize` + signature), ré-encodage JPEG/WebP, max 1600 px,
  EXIF retiré, nom aléatoire ; `www/uploads/.htaccess` interdit toute exécution.
- **Sécurité** : `password_hash` (Argon2id si disponible, sinon bcrypt) ; TOTP RFC 6238 (implémentation
  locale testée sur les vecteurs de la RFC) ; secret TOTP chiffré par `sodium_crypto_secretbox` (clé dans `config.php`).
- **Comptes MariaDB** — ADR-010 : si l'hébergeur le permet, deux comptes (applicatif : `SELECT/INSERT/UPDATE/DELETE` ;
  migrations : DDL, identifiants séparés dans `config.php`, utilisé seulement par `/install` et l'action « appliquer les
  migrations ») ; sinon un compte unique, résidu accepté.
- **Sauvegarde** — FR-044 : `ExportBackup` écrit un dump SQL en flux (`php://output`), sans fichier intermédiaire.
- **Purge** — FR-027 : `PurgeExpiredBookings` appelé par la tâche planifiée de l'hébergeur si elle existe,
  sinon au premier passage du jour (verrou `GET_LOCK` pour éviter les doubles exécutions).

## Couche 4 — Frontend *(hexagonale light)*

- **Pages publiques** : gabarits PHP rendus côté serveur, échappement par défaut (`e()` obligatoire, gate
  `verify` : aucun `echo $` brut dans `templates/`). Design : `docs/design/reference-le-corbeau.md` (jetons CSS
  repris tels quels). **Polices auto-hébergées** (licence OFL) : aucune requête vers Google Fonts — **ADR-007**.
- **Îlots Svelte 5 (runes)**, compilés par Vite en CI et livrés en JS statique dans `www/islands/` :
  - `booking` : parcours ① prestation → ② barbier → ③ jour → ④ heure → ⑤ coordonnées (+ information RGPD) → ⑥ récapitulatif ;
  - `admin` : contenu, carte, équipe, horaires, agenda, réservations, journal des e-mails (FR seul).
- **Client généré** : `frontend/shared/generated/api.ts` produit par `gen-api.mjs` depuis la spec. Le script du kit
  cible OpenAPI 3.0.3 : la Story 0.2 l'adapte à 3.1 (types `["string","null"]` au lieu de `nullable`) ; un invariant interdit tout `fetch(`/URL d'endpoint hors `generated/` (gate `verify`).
- **i18n** : libellés serveur dans `app/i18n/*.php` ; l'îlot `booking` reçoit le dictionnaire de la langue de
  la page en JSON embarqué ; aucune chaîne en dur (gate `verify`).
- **Écart au moule (D2)** : le moule rend les pages en Astro statique ; ici les pages publiques sont rendues par
  PHP — **ADR-003**.

## Couche 5 — IaC (réduite au mutualisé)

Pas de serveur à piloter : l'hébergeur porte la plateforme. Ce que le projet porte comme « infrastructure as code » :

- **Paquet de déploiement reproductible** : `harness/package.sh` → `dist/site-barbier-<version>.zip`
  (`app/` + `www/` + `vendor/` + îlots compilés), même entrée = même archive (horodatages fixés), somme SHA-256 publiée.
- **Configuration versionnée** : `.htaccess` (réécriture vers le front controller, HTTPS forcé, refus de `app/`
  si elle doit vivre sous la racine), `php.ini`/`.user.ini` si l'hébergeur l'admet (`expose_php=Off`, sessions).
- **Contrôle d'environnement** : `/install` et la page « santé » de l'admin vérifient version PHP, extensions,
  droits d'écriture, HTTPS, migrations en attente.
- **Hébergement simulé** (gate `hebergement`) : `php:8.2-apache` + `mariadb:10.6`, racine web fixe, aucune CLI
  dans le conteneur applicatif ; on y dépose le paquet, on rejoue `/install` puis P1 — seule preuve de FR-042/NFR-02.

**Mapping ISO 27001 → mise en œuvre**

| Contrôle | Mise en œuvre |
|---|---|
| A.5.9 / A.8 Inventaire des actifs | SBOM CycloneDX (PHP + npm) attachés à chaque paquet ; section *Données et flux* |
| A.5.15 / A.8.2 Contrôle d'accès | éditeur unique, TOTP, verrou progressif, deux comptes MariaDB (DML / DDL) si l'hébergeur le permet — ADR-010 |
| A.8.24 Cryptographie | HTTPS/HSTS, secret TOTP chiffré (sodium), jetons hachés, mots de passe Argon2id/bcrypt |
| A.8.9 Configuration | `.htaccess` et `.user.ini` versionnés, vérifiés par le gate `hebergement` |
| A.8.15 Journalisation | journal applicatif hors racine web, sans donnée personnelle en clair, rotation |
| A.8.13 Sauvegarde | sauvegarde quotidienne de l'hébergeur **vérifiée au choix de l'offre** (Story 0.0, critère éliminatoire) + export SQL par le gérant (FR-044, rappel mensuel) ; restauration testée en CI |
| A.8.25-29 Développement sécurisé | cycle rouge/vert/bleu, 4 classes de tests, gates plancher, plan de vulnérabilités |

## Couche 6 — CI/CD

- **CI par défaut : GitHub Actions** (gabarits `foyer/tools/gates/github/` réinstanciés). Le dépôt distant
  n'est **pas** créé par l'agent (action externe) : à faire par le PO au moment voulu.
- **Mêmes commandes en local et en CI** (`harness/*.sh`, Docker) ; hook `pre-commit` (gitleaks, `verify`,
  `unit`) et `pre-push` (CI complète), DRY avec la CI.
- **Images épinglées** : `php:8.2-cli` / `php:8.2-apache` + `mariadb:10.6` + `node:22` (Vite, Playwright).

| Gate | Registre | Commande (cible) |
|---|---|---|
| `plancher` : secrets (Gitleaks), SBOM CycloneDX (`cyclonedx-php-composer` + `@cyclonedx/cyclonedx-npm`), migrations réversibles ; *conteneur : N/A, aucun image livrée* | bloquant | `bash harness/run-verify.sh` |
| `verify` : H1 pureté, échappement, **aucune concaténation SQL** (requêtes préparées seulement), pas d'URL tierce, pas de chaîne en dur, pas de `fetch` hors client généré | bloquant | idem |
| `unit` + `bdd` (domaine + application, nommés en Gherkin) | bloquant | `bash harness/run-phpunit.sh` |
| `contrat` : client régénéré = commité ; parité routes ↔ spec | bloquant | `bash harness/run-contract.sh` |
| `integration` (MariaDB 10.6 réelle, réponses validées contre la spec, tests de concurrence) | bloquant | `bash harness/run-integration.sh` |
| `e2e` (Playwright, P1–P4, axe a11y) | bloquant | `bash harness/run-e2e.sh` |
| `visuel` (goldens, tolérance 0,5 %) | bloquant à la bascule | `bash harness/run-visual.sh` |
| `hebergement` (paquet installé en hébergement simulé) | bloquant | `bash harness/run-hosting.sh` |
| `doc-vivante` (P1–P4 en cadence 1 action/1 s, vitrine) | non bloquant | `bash harness/run-demo.sh` |
| `promotion` (preuve complète avant fusion sur `main`) | bloquant | `bash harness/run-promotion.sh` |
| jalon : SAST Opengrep (SARIF), SCA `composer audit` + `npm audit`, PHPStan niveau 8, couverture | rapport | CI |

- **Déploiement** : le paquet est un artefact CI d'un commit de `main` ; l'envoi sur l'hébergement se fait en
  **SFTP ou FTPS** (jamais FTP en clair). Mapping branche → environnement : **ADR-008**, point irréversible 🔴.

## Couche 7 — Monitoring & Sécurité

- **Santé dans l'admin** (tableau de bord) : e-mails en échec, migrations en attente, date de la dernière purge,
  version déployée, espace disque des uploads.
- **Journal applicatif** (`app/var/log/`, hors racine) : erreurs, connexions admin (réussies/échouées), annulations,
  purges — identifiants hachés, aucune coordonnée client en clair ; rotation 30 jours.
- **Alerte** : e-mail au salon sur 5 échecs de connexion consécutifs. Une **panne d'envoi** ne peut pas s'annoncer par
  le SMTP en panne : elle s'affiche en bandeau rouge sur le tableau de bord et en tête de chaque page admin.
- **Hors MVP** : sonde de disponibilité externe (ajouterait un flux externe → mise à jour de *Données et flux*).
- **Plan de gestion des vulnérabilités** : `03b-plan-vulnerabilites.md`.

## Contrat API (OpenAPI) — matérialisé, pas décrit — **ADR-002**

Les quatre éléments de `skills/contrat-api.md`, nommés :

| Élément | Mécanisme concret |
|---|---|
| 1. Source de vérité | `openapi/site-barbier.openapi.json` (OpenAPI 3.1) **écrite à la main avant le code** (pas d'annotation PHP — élément **remplacé**, résidu mesuré : ADR-002) |
| 2. Client généré | `frontend/shared/scripts/gen-api.mjs` → `frontend/shared/generated/api.ts`, régénéré au `prebuild` ; jamais édité |
| 3. Désérialisation stricte | `StrictJson` valide requêtes et corps **contre la spec au moment de l'exécution** avec `opis/json-schema` ; `additionalProperties: false` partout (vérifié par un test sur la spec) |
| 4. Contract tests CI | gate `contrat` : client régénéré ≡ commité, **parité table de routes ↔ chemins de la spec** (dans les deux sens) ; gate `integration` : chaque réponse validée contre son schéma |

Version `v1` dans le chemin ; toute rupture = point irréversible (validation humaine).

## Glossaire DDD → mapping code

| Métier (FR) | Code | Table |
|---|---|---|
| Prestation / Catégorie / Carte | `Service` / `Category` / `Catalog` | `service` / `category` |
| Publication | `Service::published` ; enregistrement = publication immédiate | `service.published` |
| Barbier | `Barber` | `barber`, `barber_service` |
| Horaires | `OpeningHours`, `DaySchedule` | `opening_hours` |
| Disponibilité | `WeeklyAvailability` | `availability` |
| Absence / Fermeture exceptionnelle | `Absence` (`barberId` null = salon) | `absence` |
| Créneau | `Slot` | (calculé) / `booking_slot` |
| Réservation | `Booking` | `booking`, `booking_slot` |
| Jeton d'annulation | `CancelToken` | `booking.cancel_token_hash` |
| Texte traduit / Langue | `TranslatedText` / `Lang` | `translation` |
| Section / Page / Média | `Section` / `Page` / `MediaRef` | `section` / `section.page` / `media` |
| Unité de durée | `Duration` (multiple de 5 min) | `service.duration_min` |
| Éditeur | `Editor` | `editor`, `recovery_code`, `session` |
| Limitation de débit | `RateLimiter` (port) | `rate_limit` |

## Stratégie de tests

Pyramide : **Unit** (domaine pur, dont `SlotCalculator` en tests de propriétés sur les changements d'heure) →
**Application/BDD** (cas d'usage en Gherkin, fakes) → **Integration** (MariaDB réelle, HTTP, concurrence) →
**E2E** (Playwright sur P1–P4) → **visuel** + **vitrine** sur les mêmes parcours (`journeys/*.journey.ts`,
importés par les trois harnais — invariant anti-dette). Couverture cible : domaine ≥ 95 %, application ≥ 90 %.

## Classes de tests (4×N)

| Classe | BDD | Unitaire | Spécifique ici |
|---|---|---|---|
| `@happy` | parcours nominal | invariants, cas d'usage | P1–P4 |
| `@negative` | entrées invalides → message | exceptions domaine | désérialisation stricte (400) |
| `@edge` | bornes | propriétés | **concurrence sur MariaDB** : 20 réservations simultanées = 1 confirmée ; réservation ↔ absence ↔ changement d'horaires simultanés ; annulation ↔ nouvelle réservation ; **changements d'heure** fin mars / fin octobre |
| `@security` | abus, autorisation | injection | XSS stockée, CSRF, upload polyglotte, énumération de jetons, injection d'en-têtes e-mail, verrou de connexion, accès admin sans session |

## Données et flux *(obligatoire à Essential)*

**Données stockées**

| Donnée | Classification | Personnelle | Où | Chiffrée au repos |
|---|---|---|---|---|
| Contenu du site (textes, carte, équipe, horaires) | public | non (prénoms des barbiers publiés de leur plein gré) | MariaDB | non (public) |
| Photos | public | oui si visages (barbiers consentants) | `www/uploads/` | non (public) |
| Réservations (nom, téléphone, e-mail, rendez-vous, langue) | **confidentiel** | **oui** | MariaDB, 6 mois max | non applicatif — base de l'hébergeur ; accès restreint, purge ; à réexaminer en gate review |
| Compteurs de limitation de débit | interne | pseudonymisé (IP **hachée** avec sel secret) | MariaDB, purgés chaque jour | haché |
| Sauvegardes exportées par le gérant | **confidentiel** | **oui** | poste du gérant (hors produit), **6 mois au plus** (politique de confidentialité) | à la charge du gérant — avertissement à l'export ; restauration avec le `config.php` d'origine (clé sodium) |
| Jetons d'annulation | confidentiel | lié | MariaDB | **haché** (SHA-256) |
| Compte éditeur (e-mail, hash, secret TOTP, codes de secours) | **confidentiel** | oui | MariaDB | hash / **chiffré** (sodium) / hachés |
| Sessions admin | confidentiel | lié | MariaDB | identifiant seul, données minimales |
| Journal des e-mails | confidentiel | oui (destinataire) | MariaDB, purgé avec la réservation ; **aucun corps d'e-mail stocké** | non — destinataire masqué à l'affichage |
| Journal applicatif | interne | pseudonymisé | `app/var/log/` | non — aucune coordonnée en clair |
| Secrets (DB, SMTP, clé sodium, code d'installation) | **confidentiel** | non | `app/config.php` hors racine, jamais commité | fichier protégé (droits 600 si l'hébergeur le permet) |

**Flux internes**

| De → vers | Protocole | Données | Chiffré | Autorisé par |
|---|---|---|---|---|
| Navigateur → site (pages, îlots, API) | HTTPS 443 (HSTS) | toutes catégories ci-dessus selon l'écran | oui | ADR-004, FR-041 |
| PHP → MariaDB | socket local / TCP interne de l'hébergeur | toutes | selon l'hébergeur (réseau interne) | configuration hébergeur |

**Flux externes**

| Tiers · juridiction | Sens | Protocole | Données | Chiffré | Autorisé par |
|---|---|---|---|---|---|
| Serveur SMTP de l'hébergeur · UE `[hypothèse : hébergeur UE]` | sortant | SMTP + STARTTLS (587) | coordonnées et rendez-vous des clients | oui | ADR-009 |
| Boîtes de réception des clients et du salon | sortant (via SMTP) | e-mail | récapitulatif, lien d'annulation | selon le destinataire | FR-030/031 |
| Hébergeur mutualisé · UE | hébergement | — | tout (plateforme) | — | couche plateforme, contrat d'hébergement |
| Sauvegardes de l'hébergeur | sortant (plateforme) | — | base complète | selon l'hébergeur | couche plateforme — à vérifier au choix de l'offre |
| Poste du PO → hébergeur (déploiement) | sortant | **SFTP ou FTPS** | paquet + `config.php` | oui | ADR-008 |
| Forge (GitHub) | sortant | HTTPS/SSH | code source, aucune donnée client | oui | ADR-008 |
| Registres de build (Packagist, npm, Docker Hub, GitHub Actions) | entrant (build uniquement) | HTTPS | dépendances et images ; aucune donnée produit | oui | gates plancher (SBOM, SCA) |
| Calendrier de support PHP (php.net, données embarquées dans le paquet) | aucun appel à l'exécution | — | — | — | Story 5.2 : table de fin de support livrée avec chaque version |

Aucun autre tiers : pas d'analytics, pas de CDN, pas de polices distantes, pas de captcha tiers (ADR-007).

## API · Sécurité & RGPD

- Endpoints publics : lecture seule hors `POST /bookings` et `POST /bookings/cancel` ; aucun ne renvoie de donnée
  d'un autre client ; limitation de débit ; champ piège.
- Endpoints admin : session + TOTP + CSRF ; `noindex` ; actions sensibles = **TOTP récent** (< 10 min, FR-041) ; session pré-enrôlement limitée aux routes d'enrôlement.
- RGPD : base légale = exécution du service demandé (réservation) — **pas de consentement**, information avec lien
  vers la politique ; conservation 6 mois ; droits d'accès/effacement exécutés par le gérant (FR-012, adresse dans le
  corps d'un POST) ; sauvegardes exportées conservées 6 mois au plus par le gérant (FR-044) ; registre de traitement
  fourni au gérant (modèle).

## ADR (Architecture Decision Records)

### ADR-001 — Hexagonale + SOLID *(racine : écologie des savoirs)* — **retenu**
Décision : structure du kit (Domain → Application → Adapter → Http → Frontend), H1 en gate.
Contexte : un site de commerce local vivra des années, repris par d'autres. Alternatives écartées : CMS
(WordPress) — plus rapide à poser mais surface d'attaque large, mises à jour subies, pas de contrôle des invariants
d'agenda ; MVC « gros contrôleurs » — logique d'agenda dispersée, intestable sans base. Conséquence : un peu plus
de fichiers ; la règle de non-chevauchement est testable en isolation.

### ADR-002 — Contrat API matérialisé, spec écrite à la main — **retenu, résidu déclaré**
Décision : les 4 éléments du tableau *Contrat API* ci-dessus. Contexte : sans framework d'annotation, la spec ne
se génère pas depuis le code (`contrat-api.md` § « stack sans écosystème d'annotation ») : l'élément 1 est
**remplacé** (spec écrite à la main), les éléments 2 à 4 sont mécanisés.
**Résidu de risque** : la spec est un artefact maintenu à la main ; une route ou un champ de **réponse** peut
diverger. Mitigation : validation des requêtes **à l'exécution contre la spec** (la dérive d'entrée devient une
erreur 400 immédiate), parité routes↔spec en CI, validation des réponses en intégration.
**Mesure du résidu** (relevée à chaque gate review, ligne `jalon` du registre) : part des réponses d'intégration
validées contre leur schéma (cible 100 %) et nombre d'incidents de dérive constatés. **Destination** : la pile du
kit (API Platform), fixée par le cadre ; la **modalité** (quand basculer) est tranchée par le PO en ADR-004. Alternatives écartées : client écrit à la
main (double maintenance) ; pas de spec (aucun anti-drift).

### ADR-003 — Pages publiques rendues côté serveur (écart D2 au moule îlots-first) — **retenu**
Décision : gabarits PHP pour les pages publiques ; îlots Svelte seulement pour la réservation et l'admin.
Contexte : le contenu est édité en base et doit être visible **immédiatement** ; l'hébergement n'a pas de Node ;
un site Astro statique exigerait une recompilation et un nouvel envoi à chaque modification du gérant.
Alternatives écartées : Astro statique + rebuild (impossible sans Node serveur) ; tout en îlots (SEO et
performance dégradés, JS inutile). Conséquences : les goldens visuels portent sur le rendu serveur ;
l'échappement est garanti par un invariant `verify`. Règle de sobriété respectée : JS uniquement là où il y a
de l'interaction.

### ADR-004 — Adaptateur HTTP « vanilla » plutôt qu'API Platform — **retenu** (PO gilmry, 2026-10-22 : « Léger »)
Décision proposée : routeur maison du kit (`TODO_HTTP=vanilla` → ici seul adaptateur livré au MVP).
Contexte : la pile cible du kit est Symfony + API Platform (`bootstrap-nouveau-projet.md` §Conditionnement).
Elle **tourne** sur un mutualisé si `vendor/` est livré dans le paquet ; elle n'est donc **pas exclue** par
l'hébergement. On propose néanmoins vanilla pour : (a) un paquet de quelques centaines de fichiers au lieu de
plusieurs milliers à envoyer en SFTP ; (b) pas de cache Symfony inscriptible à gérer sur le serveur ;
(c) une empreinte mémoire et un temps de réponse meilleurs sur un hébergement d'entrée de gamme.
**Réversibilité** : le kit prouve qu'API Platform s'enfiche sur **les mêmes cas d'usage** (`src/Http/ApiPlatform/`)
sans toucher Domaine ni Application ; la bascule reste possible tant que le contrat v1 est respecté.
**Destination et modalité** : la destination (API Platform, `bootstrap-nouveau-projet.md`) n'est pas remise en cause.
Le PO a tranché « léger » (bascule **différée**) et, le 2026-10-22, les précisions de modalité suivantes :
réexamen à chaque gate review ; bascule déclenchée par le premier de ces événements — un incident de dérive du
contrat, plus de 60 routes, ou une offre d'hébergement qui rend le poids indifférent ; premier réexamen à la gate
review de fin de release.
Prix : contrat plus faible (résidu ADR-002). Alternative : API Platform dès le MVP — contrat mécanisé, paquet
lourd. Question posée au PO (modalité, langage clair) : « On garde un site léger et simple à envoyer, quitte à passer plus
tard sur l'outillage plus lourd si le projet grandit ? » → « Léger ».

### ADR-005 — Persistance CQRS SQL pur sur MariaDB — **retenu** (PO gilmry, 2026-10-22 : « Sur mesure »)
Décision proposée : `*Queries`/`*Commands` en PDO, migrations `up`/`down` SQL, pas d'ORM.
Contexte : modèle de ~15 tables, requêtes d'agenda précises (verrous, cellules) qu'un ORM masquerait ; Doctrine
ajouterait un cache de métadonnées inscriptible et un paquet lourd. La raison n'est **pas** « zéro Composer » :
le paquet embarque déjà PHPMailer et un validateur JSON Schema (SBOM et `composer audit` actifs).
Alternative : Doctrine (port identique, permutable selon le kit) — écartée pour le poids et l'opacité des
verrous. Irréversible en pratique une fois des données en production (migrations écrites pour ce mécanisme).

### ADR-006 — Une seule sérialisation de l'agenda — **retenu**
Décision : toute écriture qui modifie l'agenda d'un barbier (réserver, annuler, purger, poser une absence,
changer ses disponibilités, **le désactiver**, effacer une réservation au titre du RGPD) s'exécute en transaction
après `SELECT … FOR UPDATE` sur sa ligne `barber` ; les écritures à l'échelle du salon (fermeture exceptionnelle,
horaires du salon, **dépublication ou suppression d'une prestation**) verrouillent **toutes** les lignes `barber`
dans l'ordre des identifiants (pas d'interblocage). `BookSlot` relit l'état publié de la prestation et l'état actif
du barbier **sous le verrou**. Les réservations confirmées occupent leurs cellules de **5 min** dans `booking_slot` avec `UNIQUE(barber_id, slot_start)` : filet en base si un chemin de code oubliait
le verrou. Annulation et purge libèrent les cellules **dans la même transaction**. Contexte : MariaDB n'a pas de
contrainte d'exclusion d'intervalles ; les conflits réservation↔absence↔horaires (FR-010/020/021) doivent être
sérialisés avec les réservations, pas seulement les réservations entre elles. Alternatives écartées : verrou
applicatif fichier (fragile sur mutualisé) ; vérification sans verrou (course possible). Preuve : tests `@edge`
de concurrence sur MariaDB 10.6 en CI.

### ADR-007 — Aucun tiers côté navigateur — **retenu**
Polices Cinzel, Cormorant Garamond, UnifrakturMaguntia (OFL) auto-hébergées ; pas d'analytics, de CDN ni de
captcha tiers. Racine : sobriété et RGPD (aucune adresse IP de visiteur transmise à un tiers sans base légale).

### ADR-008 — Mapping branche → environnement — **retenu avec recette** (PO gilmry, 2026-10-22 : « Oui, site de test »)
Proposition : branches de travail → CI seulement ; `main` → paquet candidat ; **production** = dernier paquet de
`main` promu (gate `promotion` + accord du PO), envoyé par le PO en SFTP/FTPS ; **recette** facultative = un
sous-domaine du même hébergement (`test.<domaine>`) avec sa propre base, alimenté par le même paquet avant la
production. **Décision** : la recette est retenue — tout paquet passe par `test.<domaine>` avant la production.

### ADR-009 — E-mails par le SMTP authentifié de l'hébergeur — **retenu**
PHPMailer (LGPL-2.1, embarqué) en SMTP + STARTTLS avec le compte e-mail du domaine du salon ; SPF/DKIM à
configurer chez l'hébergeur (story de mise en ligne). La réservation ne dépend jamais du succès de l'envoi
(journal + relance, FR-032). Alternative écartée : service d'envoi tiers (nouveau flux externe, compte, coût).

### ADR-010 — Comptes MariaDB séparés quand l'hébergeur le permet — **retenu**
Décision : un compte applicatif limité au DML et un compte de migration (DDL), tous deux dans `config.php` ; le
second n'est chargé que par `/install` et l'action « appliquer les migrations » (session admin + TOTP récent).
Contexte : beaucoup d'offres mutualisées n'attribuent **qu'un** utilisateur par base ; le moindre privilège n'est
alors pas mécanisable. Dans ce cas : compte unique, **résidu accepté** (une injection SQL aurait des droits DDL),
compensé par les requêtes préparées partout (gate `verify` : aucune concaténation SQL) et les tests `@security`.
Vérifié au choix de l'offre (Story 0.0).

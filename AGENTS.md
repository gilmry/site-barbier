# AGENTS.md — site-barbier

Contrat agent du projet (lu par Claude Code, OpenCode, Codex…). Site trilingue (FR/NL/EN) d'un barbier
bruxellois avec réservation en ligne, hébergé sur un **mutualisé LAMP**. Archétype **stateful full-stack**,
niveau CyFun® **Essential**. Méthode : cadre Foyer (dépôt voisin `../foyer`), registre d'état **`PROJET.md`**
(à lire avant d'agir, à mettre à jour après chaque étape conclusive).

Conception validée (PASS 100/100) : `docs/conception/` — Brief, PRD (28 FR), Architecture (ADR-001…011),
plan de vulnérabilités, 33 stories. Plan : `docs/pilotage/gantt.md`.

## Pile

- **PHP 8.4** sans framework (ADR-004) · **MySQL 8** (hébergement OVH Perso), SQL pur CQRS (ADR-005). CI sur MySQL 8.0 et 8.4 jusqu'à confirmation de la version d'OVH (`docs/hebergement.md`).
- Pages publiques rendues côté serveur (ADR-003) ; îlots Svelte 5 pour la réservation et l'admin (à venir).
- Rien n'est installé sur le serveur : les dépendances sont embarquées dans le paquet (Story 0.4).

## Architecture et sa loi

```
app/src/Domain (pur) → app/src/Application (cas d'usage, ports) → app/src/Adapter (PDO, SMTP, GD…)
  → app/src/Http (routeur, traduction des erreurs) → gabarits PHP / îlots (client généré)
```

- Les dépendances pointent **vers l'intérieur**. Domaine et Application : aucun PDO, SQL, superglobale,
  ni classe d'Adapter/Http (gate `verify`, invariant H1).
- **Seule la couche Http** traduit les exceptions du domaine en codes HTTP (`problem+json`).
- `app/` vit **hors de la racine web** ; `www/` ne contient que `index.php` (contrôleur frontal), les
  assets et les îlots compilés (invariant L1).
- Ordre de construction d'une story : Domaine → Application → Adaptateurs → Contrat → Front.

## Commandes

Prérequis : Docker seulement (aucun PHP local). Images : `site-barbier-php:8.4` (construite depuis
`php:8.4-cli`), `mysql:8.4` (conteneur `barbier-mysql-8.4`, port 13308) et `mysql:8.0` (`barbier-mysql-8.0`, port 13309). Version de dev : `BARBIER_DB_VERSION=8.0 ./go`.

| Usage | Commande |
|---|---|
| Démarrer en local (image, base, migrations, serveur) | `./go` → http://127.0.0.1:8080 (`FOYER_PORT=8081 ./go` si le port est pris) |
| Préparer sans servir | `./go --no-serve` |
| Construire l'image | `bash docker/build.sh` |
| Migrations (dev) | `source harness/lib.sh && php_run "$BARBIER_PHP_IMAGE" php app/bin/migrate.php up\|down\|status` |

## Gates (noms stables, exit 0 = 🟢)

| Gate | Commande | Base requise | Depuis |
|---|---|---|---|
| `verify` / `plancher` structurel (lint PHP 8.4, G1 G2 H1 L1 C1 F1 J1, V1 gabarits échappés, S1 SQL préparé) | `bash harness/run-verify.sh` | non | 0.1 |
| `unit` + `bdd` | `bash harness/run-phpunit.sh` (un test : `--filter RouterTest`) — suite `bdd` ajoutée en 1.1 | non | 0.1 |
| `integration` (MySQL réelle, 8.4 puis 8.0 : migrations up → down → up, suite PHPUnit `Integration` avec réponses validées contre la spec) | `bash harness/run-integration.sh` (`BARBIER_DB_VERSIONS="8.4"` pour une seule) | oui (auto) | 0.1 |
| `e2e` (smoke HTTP via le contrôleur frontal) | `bash harness/e2e-smoke.sh` | oui (auto) | 0.1 |
| **tous** (miroir de `.github/workflows/ci.yml`) | `bash harness/ci.sh` | oui (auto) | 0.1 |
| preuve de sensibilité (chaque gate bloque une dérive) | `bash harness/prove-sensitivity.sh` | oui (auto) | 0.1 |
| `contrat` (parité routes ↔ spec, objets fermés, 400 déclaré, client TS à jour) | `bash harness/run-contract.sh` | non | 0.2 |
| `front` (îlots compilés `www/islands/` = sources `frontend/`) | `bash harness/run-front.sh` — recompiler : `bash harness/build-front.sh` | non | 1.1 |
| `e2e` navigateur (chaque parcours de `journeys/`, bureau + mobile) | `bash harness/run-e2e.sh` (`JOURNEY=<id>` pour un seul) | oui (auto) | 0.3 |
| `visuel` (goldens par parcours et taille d'écran, tolérance 0,5 %) | `bash harness/run-visual.sh` — `VISUAL_MODE=capture` pour réécrire **consciemment** les goldens | oui (auto) | 0.3 |
| `doc-vivante` (cadence 1 action/1 s, captures + vidéo, **non bloquant**) | `bash harness/run-demo.sh` → `harness/demo/vitrine/index.html` (non versionné, artefact CI) | oui (auto) | 0.3 |
| `hebergement` (paquet reproductible, sans fichier interdit, installé dans `php:8.4-apache`, durcissement `.htaccess`) | `bash harness/run-hosting.sh` | oui (auto) | 0.4 |
| paquet de déploiement | `bash harness/package.sh` → `dist/site-barbier-<version>.zip` (procédure : `docs/deploiement.md`) | non | 0.4 |
| `plancher` sécurité (Gitleaks arbre + historique, SBOM CycloneDX PHP + npm ; conteneur N/A) | `bash harness/run-plancher.sh` → `dist/sbom/` | non | 0.5 |
| `jalon` (**rapports non bloquants** : composer/npm audit, SAST SARIF, PHPStan 8) | `bash harness/run-jalon.sh` → `dist/reports/` | non | 0.5 |

Les gates s'ajoutent à cette table **dans la même PR** que la story qui les crée.

## Contrat API (ADR-002)

- Source de vérité : `openapi/site-barbier.openapi.json` (OpenAPI 3.1), **écrite à la main avant le code**.
- Le serveur valide chaque requête d'API contre la spec (`StrictJson`, `opis/json-schema`) : champ inconnu,
  type faux ou paramètre non déclaré → 400 `problem+json` avec `errors[]`.
- Routes : `$router->api(...)` (contrat, validées) ou `$router->page(...)` (pages rendues côté serveur).
- Client des îlots : `frontend/src/generated/api.ts`, généré par `(cd frontend && npm run gen:api)` —
  **jamais édité à la main** ; seul `frontend/src/shared/transport.ts` appelle le réseau (invariant F1).
- Toute nouvelle opération : spec d'abord (avec `operationId`, réponse 400, objets `additionalProperties:
  false`, paramètres de requête en chaînes), puis route, puis régénération du client.

## Parcours de référence (documentation vivante)

- Un parcours = un fichier `journeys/<id>.journey.mjs` (étapes en langage métier, `action`, `check`,
  `snapshot` facultatif). **Écrit une fois**, rejoué par les trois harnais (`harness/browser/run.mjs`).
- Une story à écran va **jusqu'à la preuve de valeur** : e2e vert, goldens capturés puis comparés, vitrine
  régénérée — sans attendre qu'on le demande.
- Un golden ne se réécrit que pour un changement de rendu **voulu** (`VISUAL_MODE=capture`), jamais pour
  faire passer un rouge.

## CI et hooks

- `.github/workflows/ci.yml` rejoue **les mêmes scripts** que `harness/ci.sh` (push `main`/`story/**`, PR,
  hebdomadaire). Artefacts : SBOM, vitrine, paquet, rapports de jalon (SARIF remonté à la forge).
- Hooks versionnés dans `.githooks/` (installés par `./go`) : `pre-commit` = Gitleaks + `verify` + `unit` ;
  `pre-push` = CI complète.
- Protection de la branche `main` (revue + CI verte obligatoires) : **à poser par le PO** à la création du
  dépôt distant (point irréversible).
- Signalement des failles : `SECURITY.md`.

## Accès et sécurité de l'administration (Epic 1)

- Installation : `/install` (contrôle d'environnement → migrations → compte → double facteur). Configuration
  serveur : `app/config.php` (`app_key` 32 octets base64, `install_code`, `db`, `env`).
- Sessions **en base** (étapes `pre_enrolment` → `password_ok` → `full`, ou `recovery`), cookie HttpOnly
  SameSite=Strict (Secure + `__Host-` en prod), jeton CSRF `X-CSRF-Token` sur toute écriture ; sans session,
  `X-Requested-With: barbier`.
- Actions sensibles : `AccessController::guard($r, [FULL], recentTotp: true)` (code TOTP de moins de 10 min).
- Réinitialisation d'urgence : déposer `app/var/reset-2fa.token` en SFTP, puis `/install/reset-2fa`.
- Dev/test : clé et code d'installation fixes dans `harness/lib.sh` (jamais en production).

## Vitrine (Epic 2)

- Pages publiques : gabarits PHP (`app/templates/`), URL `/{fr|nl|en}/…`, libellés dans `app/i18n/*.php`
  (repli FR). Sorties : `$e()` échappé, `$t()` libellé échappé, `$html()` **SafeHtml uniquement** (V1).
- Contenus éditables : table `translation` (FR obligatoire, repli FR) ; champs autorisés par section dans
  `Domain/Vitrine/SectionCatalog` ; texte enrichi sûr par `RichText` (gras, italique, liens https/tel/mailto).
- Contenu initial : migration `0004_contenu_initial` (maquette Le Corbeau, FR/NL/EN).
- Images : `GdImageProcessor` (type par contenu, ré-encodage WebP ≤ 1600 px, EXIF retiré) → `www/uploads/`.
- Polices auto-hébergées (`frontend/scripts/copy-fonts.mjs` → `www/assets/fonts/`), aucun tiers (ADR-007).
- Garde-fous agenda sur le contenu : port `Application/Vitrine/AgendaGuard` (implémenté en 3.11).

## Règles de l'agent

- **Un gate rouge = stop.** On corrige la cause ; on ne force pas, on ne « défère » pas un gate.
- **Ne jamais committer `app/config.php`** (secrets) ; aucun secret réel dans le dépôt.
- Toute migration `NNNN_nom.up.sql` a son `NNNN_nom.down.sql` qui la défait **entièrement** ; pas de
  `NOW()`, `CURRENT_TIMESTAMP`, `AUTO_INCREMENT` (G2) ; identifiants générés par l'application.
- Requêtes **préparées** uniquement (invariant à mécaniser en 0.2/1.x) ; heures stockées en **UTC**,
  affichées en `Europe/Brussels`.
- Tests d'abord (rouge → vert → bleu), **quatre classes** : `@happy`, `@negative`, `@edge`, `@security`.
- **Une branche par story** (`story/<n>-<nom>`), un commit par étape conclusive :
  `nouveau(<périmètre>): <étape>`. **Ne jamais fusionner sur `main` soi-même** : la promotion d'une branche
  est arbitrée par le PO sur preuve (gate `promotion`, à venir).
- Ne jamais modifier une dépendance embarquée (`vendor/`) : adapter de notre côté de la frontière.
- Points irréversibles (production, migration en production, protection de branche, choix d'hébergeur) :
  préparer la preuve, inscrire un 🔴 au registre, **demander au PO** — jamais trancher seul.

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
| `verify` / `plancher` structurel (lint PHP 8.4, G1 G2 H1 L1) | `bash harness/run-verify.sh` | non | 0.1 |
| `unit` + `bdd` | `bash harness/run-phpunit.sh` (un test : `--filter RouterTest`) — suite `bdd` ajoutée en 1.1 | non | 0.1 |
| `integration` (MySQL réelle, 8.4 puis 8.0 : up → down → up) | `bash harness/run-integration.sh` (`BARBIER_DB_VERSIONS="8.4"` pour une seule) | oui (auto) | 0.1 |
| `e2e` (smoke HTTP via le contrôleur frontal) | `bash harness/e2e-smoke.sh` | oui (auto) | 0.1 |
| **tous** | `bash harness/ci.sh` | oui (auto) | 0.1 |
| preuve de sensibilité (chaque gate bloque une dérive) | `bash harness/prove-sensitivity.sh` | oui (auto) | 0.1 |
| `contrat` (spec ↔ client généré ↔ routes) | à venir | | 0.2 |
| `e2e` navigateur, `visuel`, `doc-vivante` | à venir | | 0.3 |
| `hebergement` (paquet en hébergement simulé) | à venir | | 0.4 |
| `plancher` sécurité (secrets, SBOM, SCA) + CI distante | à venir | | 0.5 |

Les gates s'ajoutent à cette table **dans la même PR** que la story qui les crée.

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

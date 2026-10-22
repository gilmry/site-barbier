---
livrable: Gate review — fin de release 1 (vagues V0 à V4)
projet: site-barbier
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
branche: story/3.x-agenda (chaîne de branches story/0.1 → 0.2 → 0.3 → 0.4 → 0.5 → 1.x → 2.x → 3.x)
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: promue sur main (triage des rapports de jalon accepté)
---

# Gate review — fin de release 1

Tout le backlog est fabriqué sauf la **Story 5.1** (recette puis production), qui commence par des actions du
PO : commander l'offre OVH, créer `app/config.php` sur le serveur, envoyer le paquet en SFTP. C'est une suite
de points irréversibles, arbitrés par le PO.

## 1. Gates (exit code, mêmes commandes qu'en CI)

| Gate | Résultat | Preuve |
|---|---|---|
| `plancher` (Gitleaks arbre + historique, SBOM CycloneDX PHP + npm) | 🟢 | `bash harness/run-plancher.sh`, artefact `sbom` |
| `verify` (lint PHP 8.4, G1 G2 H1 L1 C1 F1 J1 V1 S1) | 🟢 | `bash harness/run-verify.sh` |
| `contrat` (51 opérations, parité routes ↔ spec, objets fermés, client à jour) | 🟢 | `bash harness/run-contract.sh` |
| `front` (îlots compilés = sources) | 🟢 | `bash harness/run-front.sh` |
| `unit` + `bdd` (59 tests, 535 assertions) | 🟢 | `bash harness/run-phpunit.sh` |
| `integration` (46 tests, 482 assertions, **MySQL 8.4 et 8.0**, réponses validées contre la spec, 20 processus concurrents) | 🟢 | `bash harness/run-integration.sh` |
| `e2e` HTTP + navigateur (8 parcours, bureau + mobile) | 🟢 | `bash harness/e2e-smoke.sh`, `bash harness/run-e2e.sh` |
| `visuel` (goldens bureau + mobile, 0,5 %) | 🟢 | `bash harness/run-visual.sh` |
| `hebergement` (paquet reproductible installé dans `php:8.4-apache`, durcissement `.htaccess`) | 🟢 | `bash harness/run-hosting.sh` |
| `doc-vivante` (vitrine filmée) | 🟢 non bloquant | `bash harness/run-demo.sh`, artefact `vitrine` |
| Preuve de sensibilité : 22 dérives artificielles, chacune bloquée par son gate | 🟢 | `bash harness/prove-sensitivity.sh` |

Parcours de référence (P1–P4 du PRD et parcours d'appui) : installation, connexion, accueil, **P3 édition**,
**P1 réservation**, **P4 agenda**, **P2 annulation**, socle. Parcours filmés « critiques » proposés : P1 et P3.

## 2. Rapports de jalon (non bloquants) — triage proposé

| Rapport | Résultat | Triage proposé |
|---|---|---|
| SCA Composer | 0 avis | — |
| SCA npm | 3 vulnérabilités | outils de **compilation** des îlots (Vite / esbuild), jamais livrés dans le paquet ni exécutés sur le serveur → non atteignables en production ; montée de version par Dependabot |
| SAST (règles p/php, p/secrets, SARIF) | 1 finding | **faux positif** : empreinte bcrypt factice (mot de passe sans compte) servant à la connexion à temps constant ; annoté `nosemgrep` |
| PHPStan niveau 8 | 133 remarques | précisions de **typage** (types de tableaux non détaillés, `PDOStatement\|false` alors que PDO est en mode exception, `match` sans défaut sur des actions écrites en dur) ; aucune ne révèle de défaut de comportement ; à réduire au fil des stories (objectif : niveau 8 propre à la release suivante) |

## 3. Autoévaluation CyFun® Essential — couche application (`skills/conformite.md` §2bis)

| Menace | Exigences | Mise en œuvre | Statut |
|---|---|---|---|
| Vol d'identifiants | PR.AA-03.2 ★B, PR.AA-01.4 E, PR.AA-02.2 E | double facteur TOTP obligatoire (RFC 6238, anti-rejeu), mot de passe ≥ 12 hors liste noire, verrou progressif + alerte e-mail, codes de secours, réinitialisation d'urgence par jeton déposé en SFTP | ✅ |
| Abus de droits | PR.AA-05.3 ★B, PR.AA-05.6 I | compte unique (choix PO), étapes de session, **TOTP récent** pour sauvegarde, migrations et codes de secours ; CSRF sur toute écriture | ✅ (porteur unique : mesure compensatoire du plan de vulnérabilités) |
| Faille applicative | ID.RA-08.1 ★I, PR.PS-06.3 E, ID.IM-03.9 E | plan de gestion des vulnérabilités, `SECURITY.md`, désérialisation stricte contre la spec, gabarits échappés (V1), SQL préparé (S1), CSP stricte, upload ré-encodé, tests `@security` dans chaque story, SAST/SCA hebdomadaires | ✅ |
| Exfiltration | ID.AM-03.3 ★E, PR.DS-02.2 E | flux cartographiés (Architecture, Données et flux), HTTPS + HSTS, SFTP/FTPS uniquement, aucun tiers côté navigateur | ✅ |
| Données au repos | PR.DS-01.x, GV.PO-01.2 I | secrets TOTP chiffrés (sodium), jetons et IP hachés (HMAC), mots de passe Argon2id/bcrypt, purge à 6 mois, sauvegarde exportée avec avertissement | ✅ (base de l'hébergeur non chiffrée applicativement : résidu ci-dessous) |

Seuils : aucun écart bloquant constaté sur la couche application. Les couches **plateforme** (OVH : sauvegardes,
séparation, pare-feu) et **organisation** (politiques, analyse de risque, réponse aux incidents) restent à
documenter par le porteur.

## 4. Résidus assumés (à réexaminer à chaque gate review)

| Résidu | Origine | Mesure / compensation |
|---|---|---|
| Spec OpenAPI écrite à la main | ADR-002 / ADR-004 | 100 % des réponses d'intégration validées contre leur schéma ; 0 incident de dérive ; bascule API Platform différée (déclencheurs : incident de dérive, > 60 routes — aujourd'hui 51 opérations sur 36 chemins) |
| Un seul utilisateur MySQL par base | ADR-010, offre OVH Perso | requêtes préparées partout (invariant S1), tests `@security` |
| Sauvegardes OVH « non contractuelles » | offre OVH Perso | export SQL mensuel par le gérant, restauration testée en CI (aller-retour export → import → site identique) |
| Données clients non chiffrées dans la base | mutualisé | accès restreint au compte DB, purge à 6 mois, données minimales |
| Porteur unique, sans suppléant | choix du PO | mesures compensatoires du plan de vulnérabilités (gates bloquants, Dependabot, rappels en gate review) |
| Version MySQL exacte d'OVH inconnue | Story 0.0 | CI sur 8.0 **et** 8.4 jusqu'à confirmation |

## 5. Points irréversibles en attente du PO (Story 5.1)

1. **Promotion** de la chaîne de branches vers `main` (arbitre de promotion, sur la preuve ci-dessus).
2. **Commande OVH Perso** après confirmation des 6 points de `docs/hebergement.md`.
3. **Dépôt distant** (GitHub) et **protection de la branche `main`** (CI verte + revue obligatoires).
4. **Recette** sur `test.<domaine>` puis **mise en production** (procédure `docs/deploiement.md`) ;
   SPF/DKIM du domaine ; tâche planifiée OVH `php app/bin/cron.php` chaque jour.
5. Contenus réels : nom, adresse, téléphone, e-mail, numéro BCE, photos, prix (tout est modifiable dans l'admin).

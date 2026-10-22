---
livrable: Plan de gestion des vulnérabilités
persona: 03-architecte (annexe de 03-architecture.md, couche 7)
projet: site-barbier
version: 1.0 (validé, contenu inchangé en v1.1)
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: validé (contenu inchangé lors des corrections v1.1)
---

# Plan de gestion des vulnérabilités — site-barbier

*Répond à CyFun® 2025 ID.RA-08.1 ★I, ID.RA-01.3 I, ID.RA-06.1 I, ID.RA-08.2 E (identifiants seuls).*

## 1. Périmètre

- **Couverts** : code applicatif PHP, gabarits, îlots Svelte, dépendances embarquées (Composer : PHPMailer,
  validateur JSON Schema ; npm : Svelte, Vite et leurs dépendances de build), `.htaccess` / `.user.ini`,
  paquet de déploiement.
- **Hors périmètre** : plateforme mutualisée (PHP, Apache, MariaDB, système) → portée par l'hébergeur, à
  vérifier au choix de l'offre (version de PHP maintenue, correctifs) ; poste du PO → documents d'organisation.
- **Responsable** : Gilles (gilmry), **seul porteur, sans suppléant** (choix du PO, 2026-10-22). Mesure compensatoire = les gates bloquent mécaniquement
  (secrets, SBOM, tests `@security`) sans dépendre d'une vigilance humaine, le bot de montée de version ouvre les
  PR seul, et l'agent rappelle les findings ouverts à chaque gate review. Absence de suppléant acceptée et
  réexaminée à chaque gate review (PR.AA-05.6).

## 2. Identifier

| Source | Mécanisme | Fréquence |
|---|---|---|
| Dépendances PHP | `composer audit` + SBOM `cyclonedx-php-composer` | chaque build + **hebdomadaire planifié** |
| Dépendances npm (build des îlots) | `npm audit` + SBOM `@cyclonedx/cyclonedx-npm` | chaque build + hebdomadaire |
| Montées de version | Dependabot (Composer, npm, GitHub Actions) | hebdomadaire |
| Code | Opengrep (SARIF) + PHPStan niveau 8 | chaque PR |
| Secrets | Gitleaks (poste et CI) | chaque commit / push |
| Image de conteneur | **N/A** — aucune image livrée (le paquet est un zip PHP) | — |
| Version de PHP de l'hébergeur | page « santé » de l'admin (alerte si version sans support de sécurité) | à chaque connexion admin |
| Signalement externe | §6 | à réception |

## 3. Analyser et évaluer

Sévérité retenue = sévérité outil ajustée par : **atteignable** (le code vulnérable est-il appelé ? ex. une faille
de Vite ne touche que le build, jamais le site en ligne) → **exposé** (public, admin authentifié, build seulement)
→ **exploité** (exploit public ou exploitation active : +1 cran). Décision écrite : issue (correction) ou ADR
(acceptation avec date de réexamen).

## 4. Atténuer — délais cibles

| Sévérité retenue | Délai |
|---|---|
| Critique ou exploitée activement | 7 jours |
| Haute | 30 jours |
| Moyenne | 90 jours ou prochaine gate review |
| Basse | backlog, revue en gate review |

Réponses : corriger · mitiger (désactiver la fonction, règle `.htaccess`) · accepter (ADR + date) · éviter
(retirer la dépendance). Une correction en production = nouveau paquet promu, envoyé en SFTP/FTPS.

## 5. Communiquer

- **Interne** : chaque gate review reprend findings ouverts, acceptations à réexaminer, délais dépassés.
- **Client (le salon)** : toute correction de sécurité déployée est notée dans le journal de versions du paquet.
- **CRA** : **non applicable** — site réalisé sur commande pour un seul client, non mis sur le marché comme produit ;
  à réexaminer si le site devenait un produit revendu à d'autres salons.
- **RGPD** : une fuite de données de clients se notifie à l'APD dans les 72 h (procédure dans les documents du salon).

## 6. Divulgation coordonnée

Publiée dans `SECURITY.md` à la racine du dépôt (Sprint 0) :
- **Canal** : signalement privé de la forge (GitHub Security Advisories) ou **gilmry+abuse@gmail.com** ; jamais d'issue publique.
- **Accusé de réception** : 5 jours ouvrés.
- **Divulgation** : après correction, ou à 90 jours au plus tard, d'un commun accord ; crédit au rapporteur s'il le souhaite.
- **Recours** : le CCB coordonne la CVD en Belgique.

## 7. Automatiser (Essential)

Dependabot ouvre les PR ; les findings SARIF remontent dans l'onglet sécurité de la forge ; un job hebdomadaire
planifié rejoue SCA + SBOM même sans commit ; le registre `PROJET.md` (ligne `jalon`) reporte le nombre de findings
ouverts et en retard.

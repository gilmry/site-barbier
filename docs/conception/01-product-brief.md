---
livrable: Product Brief
persona: 01-analyste (BMAD, TOGAF Phase A)
projet: site-barbier
version: 0.1 (brouillon, à valider)
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
signature_humaine:
  nom: <à signer>
  role: PO
  date: <AAAA-MM-JJ>
  verdict: <validé | à reprendre>
---

# Product Brief — Site web d'un barbier (site-barbier)

*Livrable de l'Analyste. Ce qui n'a pas été dit explicitement par le PO est marqué `[hypothèse à valider]`.*

## 1. Vision
Donner à un **barbier indépendant** un site vitrine soigné qu'il **met à jour lui-même** (textes,
images, tarifs, horaires) et par lequel ses clients **demandent un rendez-vous**, le tout sur un
**hébergement mutualisé bon marché**.

## 2. Stakeholders
- **Gérant du salon** — finance, décide, édite le contenu. `[hypothèse à valider : un seul éditeur]`
- **Barbiers** — présentés sur le site ; peuvent recevoir des demandes nominatives.
- **Clients et prospects** — consultent, demandent un rendez-vous.
- **Hébergeur mutualisé** — impose ses contraintes techniques (§14).
- **PO du projet** — gilmry.

## 3. Drivers business
- Être trouvable et crédible en ligne sans payer un abonnement de plateforme.
- Coût récurrent minimal (mutualisé LAMP « pas cher »).
- Autonomie : changer un prix ou une photo sans faire appel à un développeur.

## 4. Problème
Le salon n'a pas de présence en ligne qu'il maîtrise. Les solutions toutes faites coûtent un
abonnement ou imposent leur image ; un site figé devient vite faux (prix, horaires, équipe).

## 5. Proposition de valeur
Un site à l'identité forte (maquette « Le Corbeau », `../design/reference-le-corbeau.md`), rapide,
**éditable par le gérant**, hébergeable pour quelques euros par mois, sans dépendance à une plateforme.

## 6. Personas
| Persona | Objectifs | Frustrations actuelles |
|---|---|---|
| **Client** (mobile surtout) | Voir prestations/prix/horaires, demander un créneau vite | Appeler pendant que le barbier travaille ; infos périmées |
| **Gérant / éditeur** | Mettre à jour textes, images, tarifs, horaires ; voir les demandes | Dépendre d'un tiers pour la moindre modification |
| **Barbier** `[hypothèse à valider]` | Être présenté ; savoir qui l'a demandé | — |

## 7. Capacités métier requises
(ordonnées de la plus réversible à la moins réversible)
1. **Présenter le salon** : pages publiques (accueil, maison, tarifs, équipe, horaires & contact).
2. **Éditer le contenu** : textes et images des sections, prestations et prix, barbiers, horaires, coordonnées.
3. **Recevoir des demandes de rendez-vous** : formulaire public → demande enregistrée et/ou transmise.
   `[hypothèse à valider : demande à confirmer par téléphone, comme la maquette — pas de réservation en temps réel]`
4. **Protéger l'administration** : accès réservé à l'éditeur, avec double facteur (cf. §14).

## 8. Glossaire métier
| Terme | Définition |
|---|---|
| **Prestation** | Service vendu (ex. « Coupe classique ») : nom, catégorie, prix TTC, durée, description. |
| **Catégorie** | Regroupement de prestations sur la carte (*Cheveux*, *Barbe & rasage*). |
| **Carte** | L'ensemble ordonné des prestations publiées. |
| **Barbier** | Membre de l'équipe présenté sur le site : prénom, spécialité, bio, photo. |
| **Horaires** | Plages d'ouverture par jour de semaine ; un jour peut être *fermé*. |
| **Demande de rendez-vous** | Souhait d'un client (prestation, barbier ou « sans préférence », jour, heure, coordonnées, précisions). **Pas** un rendez-vous confirmé. |
| **Section** | Bloc éditable d'une page (titre, textes, image). |
| **Page** | Page publique composée de sections. |
| **Éditeur** | Personne autorisée à modifier le contenu (le gérant). |
| **Publication** | Moment où une modification devient visible publiquement. |

## 9. Bounded contexts pressentis
1. **Vitrine** (contenu éditable : pages, sections, médias, carte, équipe, horaires, coordonnées).
2. **Demandes de rendez-vous** (prise et suivi des demandes).
3. **Accès** (éditeur, authentification, double facteur).

## 10. Invariants métier critiques
- Une prestation a un nom non vide, un **prix ≥ 0** (en centimes) et une **durée > 0**.
- Une demande de rendez-vous **ne peut pas viser un jour fermé** ni une heure hors des horaires du jour.
- Une demande porte un **nom** et un **téléphone** (ou e-mail `[hypothèse à valider]`) non vides.
- Une demande ne vise qu'une **prestation publiée** et un barbier existant (ou « sans préférence »).
- Une image publiée est d'un **type autorisé** (JPEG/PNG/WebP) et d'une **taille bornée**.
- Seul un éditeur authentifié (double facteur) modifie le contenu ou consulte les demandes.

## 10bis. Flux multi-acteurs
| Capacité | Initiateur | Validateur | Consommateur | Workflow |
|---|---|---|---|---|
| Demande de RDV | Client | Gérant (au téléphone) | Gérant / barbier | Client remplit → demande enregistrée `[et/ou e-mail au salon — hypothèse]` → récapitulatif « appelez pour confirmer » → gérant traite la demande |
| Édition de contenu | Gérant | Gérant | Clients | Gérant modifie → publication → visible sur le site |

## 11. Fonctionnalités MVP
- Page d'accueil fidèle à la maquette (8 sections), responsive, sans écran ni musique.
- Administration : éditer textes et images des sections, la carte (CRUD prestations), l'équipe, les horaires, les coordonnées.
- Formulaire de demande de rendez-vous avec contrôle des jours/heures d'ouverture.
- Connexion éditeur avec double facteur.
- Mentions légales et politique de confidentialité (le site collecte nom et téléphone).

## 12. Fonctionnalités post-MVP
- Réservation **en temps réel** sur créneaux disponibles (agenda par barbier, conflits).
- Confirmation automatique au client (SMS/e-mail).
- Plusieurs éditeurs avec rôles distincts.
- Site multilingue (FR/NL/EN — Bruxelles) `[hypothèse à valider : FR seul au MVP]`.
- Galerie de réalisations, carte cadeau en ligne.

## 13. Dimensionnement projet
| Dimension | Valeur estimée |
|---|---|
| Bounded Contexts | 3 |
| Entités domain estimées | ~8 (Page, Section, Média, Prestation, Catégorie, Barbier, Horaires, Demande, Éditeur) |
| Endpoints API estimés | ~25 |
| Catégorie projet | **Micro** (1-3 BC) |

**Archétype** (défaut annoncé, `pilote/defaults.md`) : **stateful full-stack** — contenu et demandes persistés, écrans publics et d'administration.

## 14. Contraintes
- **Hébergement mutualisé LAMP bon marché** (exigence du PO, mot pour mot : « hébergement mutualisé pas cher LAMP ») :
  - déploiement par **FTP/SFTP**, sans SSH ni Composer ni CLI côté serveur `[hypothèse à valider selon l'offre]` → paquet **autonome** (aucune dépendance à installer sur le serveur) ;
  - **migrations de schéma sans CLI** (déclenchées depuis l'administration ou au premier passage) ;
  - **racine web imposée** (`www/` ou `public_html/`) → le code applicatif hors racine ou protégé par `.htaccess` ;
  - pile cible **PHP 8.2 + MariaDB** (le plus répandu en mutualisé) — les gates en CI tournent sur ce couple ;
  - **pas de Node côté serveur** : le JavaScript est compilé avant l'envoi.
- **Pile** : PHP sans framework + SQL pur (pas de Symfony/API Platform/Doctrine), imposé par l'hébergement → **ADR** d'exclusion de la pile cible du kit (`skills/bootstrap-nouveau-projet.md` §Conditionnement).
- **Rendu** : pages publiques rendues côté serveur par PHP depuis la base (le contenu est éditable), îlots Svelte limités au formulaire et à l'administration → **ADR** d'écart de rendu (D2).
- **Médias** : upload contrôlé (type, taille, renommage), **aucune exécution PHP** dans le dossier d'upload.
- **Conformité** : RGPD (nom + téléphone des clients) · **CyFun® Essential** (défaut : le produit conserve des données d'utilisateurs) → double facteur sur l'administration, moindre privilège, plan de gestion des vulnérabilités.
- **Langue** : français `[hypothèse à valider]`.
- **Design** : maquette « Le Corbeau » comme base ; le thème ThemeForest fourni n'est pas exploitable (cadre vide) et est sous licence — rien n'en est copié.

## 15. Risques
| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| L'offre mutualisée choisie est plus restrictive que prévu (PHP ancien, pas de `.htaccess`) | Moyenne | Élevé | Sonder l'offre réelle avant le Sprint 0 ; viser PHP 8.2 ; test de déploiement à blanc |
| Upload d'image utilisé pour déposer un script | Moyenne | Élevé | Liste blanche de types, ré-encodage, `.htaccess` sans exécution, gate `@security` |
| Spam sur le formulaire de demande | Élevée | Moyen | Champ piège + limitation de débit, sans service tiers |
| Vol du mot de passe éditeur | Moyenne | Élevé | Double facteur (TOTP), sessions durcies |
| Données de clients conservées sans fin | Moyenne | Moyen (RGPD) | Durée de conservation définie + purge |
| Déploiement FTP manuel source d'erreurs | Moyenne | Moyen | Paquet de déploiement unique, généré et vérifié par la CI |

## 16. Principes d'architecture
Hexagonale, DDD au centre, ordre des couches Foyer (Domaine → Application → Adaptateurs → Contrat → Front) ;
contrat API matérialisé (OpenAPI + client généré) pour les îlots ; migrations réversibles ; sobriété
(aucune dépendance serveur, pages légères) ; gates identiques en local et en CI.

## 17. Métriques de succès
- Le gérant modifie un prix et une photo **seul, en moins de 2 minutes**, sans aide.
- Page d'accueil : **< 1 s** de chargement sur mobile 4G, poids < 500 Ko hors photos.
- **≥ 1 demande de rendez-vous par semaine** via le site après un mois en ligne `[hypothèse à valider]`.
- Coût d'hébergement **≤ 5 €/mois** `[hypothèse à valider]`.

## 18. Estimation budgétaire préliminaire (point 0)
- **Échelle visée** : Scrum (un flux, Micro).
- **Volume** : ~12 stories (Sprint 0 habilitant + ~11 stories métier), taille moyenne M.
- **Coût superviseur** : ~12 × 0,75 j ≈ **9 j** wall-clock, fourchette large **6–14 j**.
- **Coût modèle** : ~12 stories × ~8 tours × ~0,4 Mtok in / 0,04 Mtok out par tour ≈ 40 Mtok in, 4 Mtok out → **≈ 45 €** (négligeable devant le superviseur).
- **Seed** : à froid (aucun projet passé à pile égale) ; structure héritée de `kit-php`.
- **Target de challenge** : **≤ 10 j** de supervision pour le MVP en ligne.

> Estimation = prior incertain, resserré story après story par le CSI.

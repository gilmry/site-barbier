---
livrable: Product Brief
persona: 01-analyste (BMAD, TOGAF Phase A)
projet: site-barbier
version: 1.1 (erratum du Validateur, à revalider)
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: à revalider (v1.1, corrections du Validateur) — v1.0 validé le 2026-10-22
---

# Product Brief — Site web d'un barbier (site-barbier)

*Livrable de l'Analyste. Ce qui n'a pas été dit explicitement par le PO est marqué `[hypothèse à valider]`.*

## 1. Vision
Donner à un **barbier indépendant** un site soigné, **en français, néerlandais et anglais**, qu'il
**met à jour lui-même** (textes, images, tarifs, horaires) et sur lequel ses clients **réservent
eux-mêmes un créneau libre** chez le barbier de leur choix, le tout sur un **hébergement mutualisé
bon marché**.

## 2. Stakeholders
- **Gérant du salon** — finance, décide, édite le contenu, gère les agendas. **Seul éditeur** (PO, 2026-10-22).
- **Barbiers** — présentés sur le site ; chacun a son agenda de réservations (tenu par le gérant).
- **Clients et prospects** — consultent (FR/NL/EN), réservent, annulent.
- **Hébergeur mutualisé** — impose ses contraintes techniques (§14).
- **PO du projet** — gilmry.

## 3. Drivers business
- Être trouvable et crédible en ligne sans payer un abonnement de plateforme.
- Coût récurrent minimal (mutualisé LAMP « pas cher »).
- Autonomie : changer un prix ou une photo sans faire appel à un développeur.

## 4. Problème
Le salon n'a pas de présence en ligne qu'il maîtrise. Les clients doivent appeler pendant que le
barbier travaille pour obtenir un rendez-vous. Les plateformes de réservation coûtent un abonnement
ou une commission et imposent leur image ; un site figé devient vite faux (prix, horaires, équipe).

## 5. Proposition de valeur
Un site à l'identité forte (maquette « Le Corbeau », `../design/reference-le-corbeau.md`), rapide,
**éditable par le gérant**, avec **réservation en ligne sans commission**, trilingue pour une clientèle
bruxelloise, hébergeable pour quelques euros par mois, sans dépendance à une plateforme.

## 6. Personas
| Persona | Objectifs | Frustrations actuelles |
|---|---|---|
| **Client** (mobile surtout, FR/NL/EN) | Voir prestations/prix/horaires, réserver un créneau en quelques gestes, pouvoir annuler | Appeler pendant que le barbier travaille ; infos périmées ; site dans une seule langue |
| **Gérant / éditeur** | Mettre à jour textes, images, tarifs, horaires ; ouvrir/fermer des créneaux ; voir le planning du jour | Dépendre d'un tiers ; doubles réservations ; agenda papier |
| **Barbier** | Être présenté ; connaître son planning | N'édite rien lui-même (le gérant tient son agenda) |

## 7. Capacités métier requises
(ordonnées de la plus réversible à la moins réversible)
1. **Présenter le salon** : pages publiques (accueil, maison, tarifs, équipe, horaires & contact).
2. **Éditer le contenu** : textes et images des sections, prestations et prix, barbiers, horaires, coordonnées.
3. **Présenter en trois langues** : chaque texte éditable existe en FR, NL et EN ; le visiteur choisit sa langue.
4. **Tenir les agendas** : disponibilités hebdomadaires de chaque barbier, absences et fermetures exceptionnelles.
5. **Réserver en ligne** : le client choisit prestation → barbier (ou « sans préférence ») → créneau **réellement libre** ;
   la réservation est enregistrée, le salon reçoit un **e-mail**, le client reçoit une confirmation avec un lien d'annulation
   `[hypothèse à valider : confirmation client par e-mail]`.
6. **Suivre les réservations** : liste et planning dans l'administration ; annulation par le salon ; **effacement automatique**
   après une durée de conservation `[hypothèse à valider : 6 mois après le rendez-vous]`.
7. **Protéger l'administration** : un seul compte éditeur, avec double facteur (cf. §14).

## 8. Glossaire métier
| Terme | Définition |
|---|---|
| **Prestation** | Service vendu (ex. « Coupe classique ») : nom, catégorie, prix TTC, durée, description. |
| **Catégorie** | Regroupement de prestations sur la carte (*Cheveux*, *Barbe & rasage*). |
| **Carte** | L'ensemble ordonné des prestations publiées. |
| **Barbier** | Membre de l'équipe présenté sur le site : prénom, spécialité, bio, photo. |
| **Horaires** | Plages d'ouverture par jour de semaine ; un jour peut être *fermé*. |
| **Disponibilité** | Plage hebdomadaire où un barbier reçoit (ex. mardi 10:00–19:00), comprise dans les horaires du salon. |
| **Absence** | Période où un barbier ne reçoit pas (congé, formation) ; une **fermeture exceptionnelle** vaut pour tout le salon. |
| **Créneau** | Début possible d'une prestation pour un barbier : la durée de la prestation tient entièrement dans sa disponibilité, sans chevaucher une réservation ni une absence. |
| **Réservation** | Créneau pris par un client pour une prestation chez un barbier, avec ses coordonnées. États : *confirmée*, *annulée* (par le client ou le salon). |
| **Langue** | FR, NL ou EN. Un **texte traduit** a une valeur par langue ; le FR est la langue de référence. |
| **Section** | Bloc éditable d'une page (titre, textes, image). |
| **Page** | Page publique composée de sections. |
| **Éditeur** | Personne autorisée à modifier le contenu (le gérant). |
| **Publication** | Moment où une modification devient visible publiquement. |

## 9. Bounded contexts pressentis
1. **Vitrine** (contenu éditable et traduit : pages, sections, médias, carte, équipe, horaires, coordonnées).
2. **Agenda & réservation** (disponibilités, absences, calcul des créneaux, réservations, annulations, conservation).
3. **Notification** (e-mails au salon et au client, dans la langue du client).
4. **Accès** (éditeur unique, authentification, double facteur).

## 10. Invariants métier critiques
- Une prestation a un nom non vide, un **prix ≥ 0** (en centimes) et une **durée > 0**.
- **Jamais deux réservations confirmées qui se chevauchent pour un même barbier** — y compris quand deux clients valident le même créneau au même instant.
- Une réservation tient **entièrement** dans une disponibilité du barbier, hors absence, hors fermeture, et **dans le futur** (délai minimal `[hypothèse : 2 h]`).
- La durée réservée est celle de la prestation au moment de la réservation (un changement de prix ou de durée ne modifie pas les réservations existantes).
- Une réservation porte un **nom**, un **téléphone** et un **e-mail** non vides `[hypothèse à valider : e-mail obligatoire pour la confirmation]`.
- Une réservation ne vise qu'une **prestation publiée** et un barbier actif ; « sans préférence » est résolu en un barbier précis au moment de la réservation.
- L'annulation par le client n'est possible que via son lien personnel et avant le rendez-vous `[hypothèse : jusqu'à 2 h avant]`.
- Les heures sont celles de **Bruxelles** (changements d'heure inclus).
- Un texte publié a toujours une valeur FR ; une traduction manquante retombe sur le FR (jamais de page vide).
- Une image publiée est d'un **type autorisé** (JPEG/PNG/WebP) et d'une **taille bornée**.
- Seul un éditeur authentifié (double facteur) modifie le contenu ou consulte les demandes.

## 10bis. Flux multi-acteurs
| Capacité | Initiateur | Validateur | Consommateur | Workflow |
|---|---|---|---|---|
| Réservation | Client | Système (règle de non-chevauchement) | Gérant / barbier | Client choisit prestation → barbier → créneau libre → saisit ses coordonnées → réservation confirmée → e-mail au salon + e-mail au client (avec lien d'annulation) → visible au planning |
| Annulation | Client ou gérant | Système (avant le rendez-vous) | L'autre partie | Lien personnel ou action admin → réservation annulée → créneau libéré → e-mail à l'autre partie |
| Agenda | Gérant | Gérant | Clients | Gérant saisit disponibilités/absences → créneaux recalculés ; une absence posée sur une réservation existante est **refusée** tant que la réservation n'est pas annulée |
| Édition de contenu | Gérant | Gérant | Clients | Gérant modifie → publication → visible sur le site |

## 11. Fonctionnalités MVP
- Site public fidèle à la maquette (8 sections), responsive, **en FR / NL / EN** (sélecteur de langue, adresses distinctes par langue).
- Administration : éditer textes (3 langues) et images des sections, la carte (prestations), l'équipe, les horaires, les coordonnées.
- **Agendas** : disponibilités hebdomadaires par barbier, absences, fermetures exceptionnelles.
- **Réservation en ligne** : parcours prestation → barbier → créneau libre → coordonnées → confirmation ; anti-double réservation.
- **E-mails** : au salon à chaque réservation/annulation ; au client (confirmation + lien d'annulation), dans sa langue.
- **Suivi** : planning et liste des réservations dans l'administration ; annulation ; purge automatique.
- Connexion éditeur unique avec double facteur.
- Mentions légales et politique de confidentialité (FR/NL/EN).

## 12. Fonctionnalités post-MVP
- Rappel automatique la veille (e-mail, puis SMS).
- Comptes barbiers (chacun gère son agenda) et rôles distincts.
- Acompte ou paiement en ligne ; gestion des absences répétées (« no-show »).
- Export de l'agenda (iCal) vers le téléphone du barbier.
- Galerie de réalisations, carte cadeau en ligne.

## 13. Dimensionnement projet
| Dimension | Valeur estimée |
|---|---|
| Bounded Contexts | 4 (Vitrine, Agenda & réservation, Notification, Accès) |
| Entités domain estimées | ~12 (Page, Section, Média, Texte traduit, Prestation, Catégorie, Barbier, Horaires, Disponibilité, Absence, Réservation, Éditeur) |
| Endpoints API estimés | ~35 |
| Catégorie projet | **Petit** (3-5 BC) |

**Archétype** (défaut annoncé, `pilote/defaults.md`) : **stateful full-stack** — contenu et demandes persistés, écrans publics et d'administration.

## 14. Contraintes
- **Hébergement mutualisé LAMP bon marché** (exigence du PO, mot pour mot : « hébergement mutualisé pas cher LAMP ») :
  - déploiement par **SFTP ou FTPS** (FTP en clair exclu), sans SSH ni Composer ni CLI côté serveur `[hypothèse à valider selon l'offre]` → paquet **autonome** (dépendances embarquées, rien à installer sur le serveur) ;
  - **migrations de schéma sans CLI** (déclenchées depuis l'administration ou au premier passage) ;
  - **racine web imposée** (`www/` ou `public_html/`) → le code applicatif hors racine ou protégé par `.htaccess` ;
  - pile cible **PHP 8.2 + MariaDB** (le plus répandu en mutualisé) — les gates en CI tournent sur ce couple ;
  - **pas de Node côté serveur** : le JavaScript est compilé avant l'envoi.
- **Pile** : *erratum v1.1* — le mutualisé **n'exclut pas** Symfony/API Platform (elles y tournent si `vendor/` est livré). PHP sans framework + SQL pur est un **choix proposé et tranché par le PO** pour la légèreté (ADR-004, ADR-005), la destination API Platform restant fixée par le cadre.
- **Rendu** : pages publiques rendues côté serveur par PHP depuis la base (le contenu est éditable), îlots Svelte limités au formulaire et à l'administration → **ADR** d'écart de rendu (D2).
- **E-mails** : envoyés par le serveur d'envoi (SMTP) de l'hébergeur ; risque de délivrabilité à traiter (SPF/DKIM du domaine).
- **Concurrence** : l'anti-double réservation doit tenir sur **MariaDB mutualisée** (transaction + contrainte en base), pas seulement dans le code.
- **Médias** : upload contrôlé (type, taille, renommage), **aucune exécution PHP** dans le dossier d'upload.
- **Conformité** : RGPD (nom, téléphone, e-mail et historique de rendez-vous des clients ; durée de conservation, droit d'effacement) · **CyFun® Essential** (défaut : le produit conserve des données d'utilisateurs) → double facteur sur l'administration, moindre privilège, plan de gestion des vulnérabilités.
- **Langues** : FR, NL, EN dès le MVP (PO, 2026-10-22) — interface, contenus éditables, e-mails, pages légales. Traductions fournies par le gérant `[hypothèse à valider : pas de traduction automatique]`.
- **Design** : maquette « Le Corbeau » comme base ; le thème ThemeForest fourni n'est pas exploitable (cadre vide) et est sous licence — rien n'en est copié.

## 15. Risques
| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| L'offre mutualisée choisie est plus restrictive que prévu (PHP ancien, pas de `.htaccess`) | Moyenne | Élevé | Sonder l'offre réelle avant le Sprint 0 ; viser PHP 8.2 ; test de déploiement à blanc |
| Upload d'image utilisé pour déposer un script | Moyenne | Élevé | Liste blanche de types, ré-encodage, `.htaccess` sans exécution, gate `@security` |
| Spam ou fausses réservations bloquant l'agenda | Élevée | Élevé | Champ piège, limitation de débit, confirmation par e-mail, annulation admin en un clic |
| Double réservation sous accès simultané | Moyenne | Élevé | Contrainte en base + transaction ; test `@edge` de concurrence en CI sur MariaDB |
| E-mails classés en spam ou non envoyés | Élevée | Élevé | SMTP authentifié du domaine, SPF/DKIM, la réservation reste valide même si l'e-mail échoue (journal + relance) |
| Erreurs d'heure aux changements d'heure | Moyenne | Moyen | Heures stockées avec fuseau `Europe/Brussels` ; tests `@edge` sur les deux dimanches de bascule |
| Traductions incomplètes | Élevée | Faible | Repli sur le FR ; indicateur « à traduire » dans l'admin |
| Vol du mot de passe éditeur | Moyenne | Élevé | Double facteur (TOTP), sessions durcies |
| Données de clients conservées sans fin | Moyenne | Moyen (RGPD) | Durée de conservation définie + purge |
| Déploiement SFTP/FTPS manuel source d'erreurs | Moyenne | Moyen | Paquet de déploiement unique, généré et vérifié par la CI |

## 16. Principes d'architecture
Hexagonale, DDD au centre, ordre des couches Foyer (Domaine → Application → Adaptateurs → Contrat → Front) ;
contrat API matérialisé (OpenAPI + client généré) pour les îlots ; migrations réversibles ; sobriété
(aucune dépendance serveur, pages légères) ; gates identiques en local et en CI.

## 17. Métriques de succès
- Le gérant modifie un prix et une photo **seul, en moins de 2 minutes**, sans aide.
- Un client réserve **en moins d'une minute** sur mobile, dans sa langue.
- **Zéro double réservation** sur la période.
- Part des rendez-vous pris en ligne **≥ 30 %** après trois mois `[hypothèse à valider]`.
- Page d'accueil : **< 1 s** de chargement sur mobile 4G, poids < 500 Ko hors photos.
- Coût d'hébergement **≤ 5 €/mois** `[hypothèse à valider]`.

## 18. Estimation budgétaire préliminaire (point 0)
- **Échelle visée** : Scrum (un flux, Petit).
- **Volume** : ~20 stories (Sprint 0 habilitant + ~19 stories métier ; la réservation et le multilingue en pèsent la moitié), taille moyenne M.
- **Coût superviseur** : ~20 × 0,75 j ≈ **15 j** wall-clock, fourchette large **10–24 j**.
- **Coût modèle** : ~20 stories × ~8 tours × ~0,4 Mtok in / 0,04 Mtok out par tour ≈ 64 Mtok in, 6,4 Mtok out → **≈ 70 €** (négligeable devant le superviseur).
- **Seed** : à froid (aucun projet passé à pile égale) ; structure héritée de `kit-php`.
- **Target de challenge** : **≤ 15 j** de supervision pour le MVP en ligne (≈ ×1,6 par rapport à une version « demande + appel, FR seul », du fait des choix du PO).

> Estimation = prior incertain, resserré story après story par le CSI.

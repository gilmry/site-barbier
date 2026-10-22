---
livrable: Compte rendu CSI — release 1
persona: csi-engineer (Foyer, boucle de pilotage continu)
projet: site-barbier
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
sources: historique git (horodatages des commits), PROJET.md, docs/revues/gate-review-release-1.md
signature_humaine:
  nom: <à signer>
  role: PO
  date: <AAAA-MM-JJ>
  verdict: <révisions de fourchette validées | à reprendre>
---

# Compte rendu CSI — release 1 du site du barbier

## En bref

**La release 1 a été conçue, fabriquée, vérifiée et promue en environ 5 heures d'une seule session**, d'environ
13 h 45 (début estimé : le premier commit, à 14 h 00, suit le cadrage) à 18 h 56. Elle avait été estimée à **≈ 30 jours de supervision** (≈ 19 jours avec deux chantiers en parallèle).
32 stories sur 33 sont livrées ; la mise en ligne (Story 5.1) est entre les mains du PO, avec un paquet prêt.

L'écart est énorme (de l'ordre de ×40) : la règle de l'abaque « S = 0,5 j, M = 0,75 j, L = 1 j par story »
mesure un rythme de développement humain supervisé, pas un agent qui code pendant que le PO tranche. Les
fourchettes doivent être recalées (§5), et ce projet fournit le premier point de mesure réel à cette pile.

## 1. Ce qui a été livré

| Domaine | Livré |
|---|---|
| Conception (BMAD) | Brief, PRD (28 exigences × 4 classes de tests), architecture (7 couches, 11 ADR), plan de vulnérabilités, 33 stories ; **PASS 100/100** après 4 passages du Validateur |
| Pilotage | WBS à dépendances déclarées, Gantt en passes, Scrum (ADR-011), ratio 2 |
| Fondations | socle PHP 8.4 / MySQL 8, contrat OpenAPI matérialisé, triple harnais (e2e, visuel, vitrine), paquet reproductible + hébergement simulé, CI GitHub + hooks + plancher sécurité |
| Accès | installation sans ligne de commande, double facteur, sessions en base, codes de secours, réinitialisation d'urgence |
| Vitrine | site trilingue FR/NL/EN fidèle à la maquette, édition des textes, carte, équipe, horaires, images |
| Agenda | réservation en ligne sans double réservation, annulation par lien, agenda du salon, garde-fous, e-mails + `.ics`, purge 6 mois, droits RGPD |
| Exploitation | tableau de bord, sauvegarde restaurable, mises à jour de la base depuis l'admin, alerte de verrouillage |

Volumes : 42 commits · ≈ 6 800 lignes PHP (application et gabarits) · ≈ 1 500 lignes Svelte/TS · ≈ 2 100
lignes de tests · ≈ 1 200 lignes de harnais · 39 chemins d'API (51 opérations) · 5 migrations · 8 parcours de
référence · 25 goldens visuels · ≈ 2 000 lignes de documentation.

## 2. Télémétrie par phase (wall-clock, d'après les commits)

| Phase | Plage | Durée | Ce qui a borné la durée |
|---|---|---|---|
| Cadrage (Q0, brief, réponses du PO) | ≈ 13 h 45 – 14 h 02 | ≈ 17 min (début estimé) | réponses du PO |
| Conception BMAD (PRD, architecture, stories) | 14 h 02 – 14 h 26 | ≈ 24 min | validations du PO (5) |
| Validation croisée (4 passages, 21 → 12 → 5 → 2 points) | 14 h 26 – 14 h 55 | ≈ 29 min | relectures du Validateur indépendant |
| Pilotage (WBS, Gantt, barreau) | 14 h 55 – 15 h 02 | ≈ 7 min | décisions du PO (3) |
| **V0 Fondations** (0.0 – 0.5) | 15 h 02 – 16 h 29 | ≈ 1 h 27 | 2 changements de pile demandés par le PO (PHP 8.4, OVH → MySQL), images Docker |
| **V1 Accès** (1.1 – 1.4) | 16 h 23 – 16 h 42 | ≈ 20 min | — |
| **V1/V2 Vitrine** (2.1 – 2.7) | 16 h 42 – 17 h 12 | ≈ 30 min | — |
| **V2–V4 Agenda, e-mails, RGPD, exploitation** (3.x, 4.x, 2.8, 5.2) | 17 h 12 – 18 h 11 | ≈ 1 h | tests de concurrence, parcours navigateur |
| Gate review, correction, vérification finale, promotion | 18 h 11 – 18 h 56 | ≈ 45 min | **temps machine** : 2 vérifications complètes sur clone neuf (≈ 25 min chacune) |
| **Total** | 13 h 45 – 18 h 56 | **≈ 5 h 10** | |

Décisions du PO : ≈ 25 (dont 11 arbitrages tracés au registre). Le temps de lecture et de réflexion du PO n'a
pas été mesuré (§6).

## 3. Écart au prior

| Estimation | Valeur | Réel | Écart |
|---|---|---|---|
| Point 0 du Brief (≈ 20 stories) | 6 – 14 j, cible ≤ 15 j | ≈ 5 h | ×20 à ×40 |
| Backlog BMAD, ratio 1 (33 stories) | ≈ 30 j | ≈ 5 h | ≈ ×48 (sur une base de 8 h/j) |
| Gantt, ratio 2 (19 passes) | ≈ 19 j | ≈ 5 h | ≈ ×30 |
| Coût modèle | ≈ 140 € (≈ 319 tours) | non mesuré précisément | — |
| Nombre de stories | ~20 (Brief) → 33 (BMAD) | 33 | prior du Brief sous-estimé de 65 % |

Ce qui est confirmé : le **volume** (33 stories, 4 bounded contexts) et la **forme de la courbe** (fondations
lourdes, puis l'agenda comme épopée la plus coûteuse). Ce qui est réfuté : la **durée** par story pour un agent.

## 4. Qualité : défauts arrêtés avant la production

Chaque défaut ci-dessous a été arrêté par un gate ou une relecture, jamais par un utilisateur.

| Défaut | Arrêté par | Gravité s'il était passé |
|---|---|---|
| Réservations « sans préférence » simultanées rejetées (instantané REPEATABLE READ pris avant le verrou) | test de concurrence (20 processus) | haute : réservations perdues aux heures de pointe |
| Route du lien d'annulation qui capturait des chemins d'API (HTML au lieu de `problem+json`, 405 masqué) | vérification finale sur clone neuf | moyenne |
| Durée réelle faussée la nuit du passage à l'heure d'hiver | conception du test de changement d'heure | moyenne (rare) |
| Transactions imbriquées dans les garde-fous de l'agenda | test d'intégration | haute : modification d'horaires impossible |
| Limite de débit qui comptait les tentatives refusées | test d'intégration | moyenne |
| Découpage SQL cassé par un « ; » dans un texte traduit | test unitaire du Migrator | haute : installation impossible |
| Défaut de reprise d'accès après perte du téléphone | Validateur (passage 3) | haute : gérant bloqué hors de l'admin |
| Suite de tests vide absente d'un clone neuf ; vitrine versionnée qui salissait le dépôt | vérification sur clone neuf | faible |
| Sonde de secret trop naïve (faible entropie) | vérification sur clone neuf | faible (gate décoratif) |

**22 dérives artificielles** ont chacune fait passer leur gate au rouge : aucun gate n'est décoratif.

## 5. Recalage proposé des fourchettes (à valider par le PO)

1. **Séparer deux horloges** au lieu de l'unique « jours de supervision » :
   - **temps agent** par story à cette pile : ≈ 5 – 10 min de fabrication (stories regroupées par épopée) ;
   - **temps de décision du PO** : ≈ 1 – 2 min par décision, ≈ 25 décisions pour un projet « Petit ».
2. **Compter le temps machine** des preuves comme un poste à part : une vérification complète (CI + sondes)
   sur clone neuf ≈ 25 min ; en prévoir **2** en fin de release (la première trouve encore quelque chose).
3. **Garder le chiffrage S/M/L** pour le travail humain (relecture, recette, mise en ligne), où il reste
   plausible : la Story 5.1 et la revue de la vitrine restent des tâches humaines.
4. **Relever le prior de volume du Brief** de ≈ 65 % pour un archétype stateful full-stack avec réservation
   (≈ 33 stories au lieu de ≈ 20).

## 6. Limites de la mesure (à corriger au prochain projet)

- **Tokens et tours non relevés par story** : le registre prévoyait ces colonnes, elles sont restées vides
  faute d'instrumentation. À faire : relever la consommation de la session à chaque fin de story.
- **Télémétrie par épopée, pas par story** : les stories 1.x, 2.x et 3.x ont été livrées en un commit par
  épopée (écart à la règle « un commit par étape conclusive »). Le grain fin est perdu pour l'estimation.
- **Temps humain non mesuré** : le PO a répondu vite ; un PO moins disponible allongerait surtout la conception
  et le pilotage, pas la fabrication.
- **Un seul point de mesure** : ce seed ne vaut qu'à stack égale (§7) ; il faut d'autres projets pour une
  fourchette fiable.

## 7. Seed pour les projets futurs (indexé par signature de stack)

| Signature | Valeur |
|---|---|
| Pile | PHP 8.4 sans framework, hexagonale (kit-php) · MySQL 8, SQL CQRS · îlots Svelte 5 + Vite · mutualisé OVH Perso |
| Archétype / taille | stateful full-stack · Petit (4 BC) · CyFun Essential · FR/NL/EN |
| Volume | 33 stories · 28 FR · 51 opérations d'API · 8 parcours de référence |
| Temps | ≈ 5 h de session (conception ≈ 1 h 10, fabrication ≈ 3 h 20, preuves et promotion ≈ 45 min) |
| Décisions du PO | ≈ 25 |
| Défauts arrêtés avant la production | 9 (dont 4 de gravité haute) |

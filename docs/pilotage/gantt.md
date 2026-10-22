---
livrable: Plan de pilotage (WBS, dépendances, Gantt en passes, vagues, coût, barreau)
persona: chef-de-projet (pilote/roles/pilotage.md)
projet: site-barbier
version: 1.0 (validé)
source: docs/conception/04-epics-stories.md v1.1 (PASS 100/100)
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: validé — scénario B (ratio 2), Scrum (ADR-011)
---

# Plan de pilotage — site-barbier

*L'axe du Gantt est la **passe d'agent** (un tour de boucle supervisé), jamais le calendrier : ce plan
n'est pas daté. Le graphe de dépendances est une **hypothèse falsifiable**, corrigée dès qu'une passe réelle
la contredit.*

## 1. WBS et dépendances déclarées

Les 33 stories du backlog, aucune non classée. Chaque dépendance porte sa raison.

| Story | Taille | Dépend de | Parce que |
|---|---|---|---|
| 0.0 Hébergement | S | — | décision du PO, menée en parallèle du socle |
| 0.1 Socle | L | — | point de départ |
| 0.2 Contrat | M | 0.1 | le harnais contrat se branche sur le socle et ses gates |
| 0.3 Triple harnais | M | 0.1 | Playwright rejoue un parcours sur l'app du socle |
| 0.4 Paquet + hébergement simulé | M | 0.0, 0.1 | la simulation reproduit l'offre retenue (versions, `.htaccess`, comptes MariaDB) |
| 0.5 CI + plancher | M | 0.2, 0.3 | la CI orchestre tous les gates déjà écrits |
| 1.1 Installation | M | **B0** | première story métier : après la barrière |
| 1.2 Connexion 2FA | L | 1.1 | l'enrôlement TOTP termine `/install` |
| 1.3 Session, TOTP récent | M | 1.2 | la session naît de la connexion |
| 1.4 Codes de secours | M | 1.3 | session « secours » et TOTP récent livrés en 1.3 |
| 2.1 Accueil | L | **B0** | rendu serveur sur données initiales, sans admin |
| 2.2 Trois langues | M | 2.1 | les gabarits existent |
| 2.3 Carte | L | 2.2, 1.3 | textes traduits ; édition derrière session + CSRF |
| 2.4 Équipe | M | 2.2, 1.3 | idem |
| 2.5 Horaires | M | 2.2, 1.3 | idem |
| 2.6 Textes des sections | M | 2.2, 1.3 | idem |
| 2.7 Images (porte P3) | M | 2.3, 2.6 | P3 = changer un prix (2.3) et la photo du hero |
| 3.1 Calcul des créneaux | M | **B0** | domaine pur, sans dépendance applicative |
| 3.2 Disponibilités | M | 3.1, 2.4, 2.5 | barbiers et horaires du salon doivent exister |
| 3.3 Absences, fermetures | M | 3.1, 2.4, 2.5 | idem |
| 3.4 API de lecture | S | 3.2, 3.3, 2.3 | prestations, barbiers, disponibilités, absences |
| 3.5 Réserver | L | 3.4 | réserve un créneau calculé |
| 3.11 Garde-fous (porte P4) | M | 3.5 | teste des réservations existantes et simultanées |
| 3.6 Sans préférence | S | 3.5 | s'appuie sur `BookSlot` |
| 4.1 E-mails | M | 3.5 | notifie une réservation |
| 3.7 Îlot de réservation (P1) | L | 3.6, 4.1, 2.2 | P1 vérifie l'e-mail du client dans sa langue |
| 3.8 Annulation client (P2) | M | 3.7, 4.1 | le lien vient de l'e-mail ; P2 suit P1 |
| 3.9 Planning salon | M | 3.5, 4.1 | l'annulation prévient le client |
| 4.2 Journal et relance | S | 4.1 | relance des envois journalisés |
| 2.8 Légal + RGPD | M | 4.1, 3.11 | effacement = e-mail d'annulation + libération sous verrou |
| 3.10 Purge | S | 3.5, 4.2 | purge réservations et journal des e-mails |
| 5.2 Santé + sauvegarde | M | 4.2, 1.3 | affiche les échecs d'envoi ; export = TOTP récent |
| 5.1 Recette puis production | M | toutes, 0.0 | paquet complet sur l'offre retenue — **point irréversible** |

## 2. Vagues et barrières

| Vague | Contenu | Barrière en sortie (rien ne la franchit avant fermeture) |
|---|---|---|
| **V0 — Fondations** | 0.0–0.5 | **B0** : clone neuf → `./go` → tous les gates verts, sensibilité prouvée, paquet installé en hébergement simulé, offre d'hébergement choisie |
| **V1 — Accès et vitrine** | 1.1–1.4, 2.1–2.7, 3.1 | P3 rejoué (e2e + visuel + vitrine) |
| **V2 — Moteur de réservation** | 3.2–3.5 | **B1** : 20 réservations simultanées = 1 confirmée sur MariaDB 10.11 |
| **V3 — Réservation complète** | 3.11, 3.6, 4.1, 3.7, 3.8, 3.9, 4.2 | P1, P2, P4 rejoués |
| **V4 — Conformité et exploitation** | 2.8, 3.10, 5.2 | gate review CyFun Essential (couche application) sans écart bloquant |
| **V5 — Mise en ligne** | 5.1 | promotion arbitrée par le PO, recette puis production |

## 3. Gantt en passes d'agent

Le parallélisme est plafonné par le **ratio de supervision** : combien de chantiers le PO peut suivre et
**en répondre** en même temps. Le PO est seul (pas de binôme) : deux scénarios.

### Scénario A — ratio 1 (un chantier à la fois)

33 passes, une story par passe, dans l'ordre de la table ci-dessus.
**Durée ≈ 25 j + 20 % d'émergence ≈ 30 j.**

### Scénario B — ratio 2 (deux chantiers en parallèle, sur deux branches) — **retenu par le PO (2026-10-22)**

| Passe | Chantier 1 | Chantier 2 | Durée de la passe |
|---|---|---|---|
| 1 | 0.1 Socle (L) | 0.0 Hébergement (S) | 1 |
| 2 | 0.2 Contrat (M) | 0.3 Triple harnais (M) | 0,75 |
| 3 | 0.4 Paquet (M) | 0.5 CI (M) | 0,75 |
| — | **barrière B0** | | |
| 4 | 1.1 Installation (M) | 2.1 Accueil (L) | 1 |
| 5 | 1.2 Connexion 2FA (L) | 2.2 Trois langues (M) | 1 |
| 6 | 1.3 Session (M) | 3.1 Créneaux (M) | 0,75 |
| 7 | 1.4 Codes de secours (M) | 2.3 Carte (L) | 1 |
| 8 | 2.4 Équipe (M) | 2.5 Horaires (M) | 0,75 |
| 9 | 2.6 Textes (M) | 3.2 Disponibilités (M) | 0,75 |
| 10 | 2.7 Images · P3 (M) | 3.3 Absences (M) | 0,75 |
| 11 | 3.4 API de lecture (S) | — | 0,5 |
| 12 | 3.5 Réserver (L) | — | 1 |
| — | **barrière B1** | | |
| 13 | 3.11 Garde-fous · P4 (M) | 3.6 Sans préférence (S) | 0,75 |
| 14 | 4.1 E-mails (M) | — | 0,75 |
| 15 | 3.7 Îlot · P1 (L) | 4.2 Journal (S) | 1 |
| 16 | 3.8 Annulation · P2 (M) | 3.9 Planning (M) | 0,75 |
| 17 | 2.8 Légal + RGPD (M) | 3.10 Purge (S) | 0,75 |
| 18 | 5.2 Santé + sauvegarde (M) | — | 0,75 |
| 19 | 5.1 Recette → production (M) | — | 0,75 |

**19 passes, ≈ 15,5 j + 20 % ≈ 19 j.** Chemin critique : 0.1 → 0.2/0.3 → 0.5 → 1.1 → 1.2 → 1.3 → 2.3 →
3.4 → 3.5 → 4.1 → 3.7 → 3.8 → 5.1. **Points de concours** : B0 (passe 3), 3.4 (attend 3.2, 3.3 et 2.3),
3.5 (B1), 4.1 (attendu par 3.7, 3.8, 3.9, 2.8). **Divergences** : après B0 (accès ∥ vitrine), après 3.5
(garde-fous ∥ sans préférence).

## 4. Coût

| | Scénario A (ratio 1) | Scénario B (ratio 2) |
|---|---|---|
| Durée (wall-clock) | ≈ 30 j | ≈ 19 j |
| Travail de supervision | ≈ 30 j | ≈ 30 j de relecture, **concentrés** sur ≈ 19 j (deux chantiers à suivre chaque jour) |
| Coût modèle | ≈ 319 tours ≈ 140 € | identique |
| Target de challenge (≈ 30 j, PO 2026-10-22) | tenue | tenue, avec marge |
| Risque *répondre-de* | faible | plus élevé : deux livraisons à relire par passe, seul, sans binôme |

> Le poste dominant est la supervision, pas le modèle (abaque §1). Le **bus factor est 1** dans les deux
> scénarios (PO seul) : risque accepté, compensé par le registre commité, les ADR et la doc vivante, qui
> permettent à un tiers de reprendre.

## 5. Barreau — ADR-011 — **retenu : Scrum** (PO gilmry, 2026-10-22)

**Décision : Scrum.** Un seul flux de travail, une seule personne qui tranche, au plus deux branches
ouvertes : Nexus (3 équipes Scrum ou plus sur un même produit) et SAFe (plusieurs trains) n'ont rien à
coordonner ici. Les « sprints » sont les vagues V0–V5 ; la revue de sprint est la gate review de fin de vague.
Rescaling à réexaminer seulement si un second développeur ou une seconde équipe rejoint le projet.
Alternatives écartées : Nexus, SAFe (surcoût de coordination sans objet).

## 6. Répartition des rôles

| Chapeau | Qui |
|---|---|
| PO (tranche, valide sur preuve, signe) | gilmry |
| Chef de projet / Scrum Master (tient ce plan, le registre) | agent, sous supervision de gilmry |
| Lead developer / développeurs (stories) | agent(s) Claude Code, une branche par story |
| Arbitre de promotion (juge ce qui sort des branches) | gilmry, sur preuve du gate `promotion` |
| Responsable sécurité | gilmry (seul, sans suppléant — mesure compensatoire au plan de vulnérabilités) |

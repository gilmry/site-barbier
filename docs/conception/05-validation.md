---
livrable: Rapport de validation croisée
persona: 05-validateur (BMAD, TOGAF Phase F)
projet: site-barbier
version: 0.3
sources:
  - 01-product-brief.md v1.1
  - 02-prd.md v1.1
  - 03-architecture.md v1.1 + 03b-plan-vulnerabilites.md
  - 04-epics-stories.md v1.1
  - PROJET.md (Arbitrages tranchés, commit cc5c6c0)
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
Archétype déclaré : **stateful full-stack** · Niveau CyFun® visé : **Essential**.*

## Historique

| Passage | Livrables | Score | Verdict | Bilan |
|---|---|---|---|---|
| 1 (v0.1) | v1.0 — 26 FR, 31 stories | 98/100 | CONCERNS | 12 points à corriger, 9 mineures |
| 2 (v0.2) | v1.1 — 28 FR, 33 stories (commit `86a8c8b`) | 100/100 | CONCERNS | 3 points à corriger, 9 mineures. Sur les 21 points du passage 1 : 18 levés, 3 partiellement levés |
| **3 (v0.3)** | v1.1 révisée (commits `5a1fadd`, `cc5c6c0`) | **100/100** | **CONCERNS** | Les 12 points du passage 2 sont levés. Il reste **1 point à corriger** (apparu au passage 3, latent depuis la v1.1) et 4 mineures |

## Statut global : **CONCERNS**

- **Fidelity score** : **100/100**.
- **Contrat matérialisé** : 4/4 au sens du cadre. Les éléments 2 à 4 sont mécanisés. L'élément 1 est remplacé sous ADR-002, avec un résidu mesuré, une destination fixée et une **modalité désormais tranchée par le PO et tracée au registre**.
- **Ce qui empêche le PASS** : il reste **un point à corriger** (N1). Après la perte du téléphone du gérant, aucun parcours ne permet de réenrôler le double facteur ni de régénérer les codes de secours. Pourtant, FR-044 fait reposer la restauration sur ce réenrôlement. Pour un éditeur **unique**, c'est un risque de blocage définitif de l'administration, et l'agent devrait inventer le parcours.

> Calcul : (capacités Brief couvertes en PRD / total capacités) × 25 + (FRs couvertes en Architecture / total FRs) × 25 + (FRs couvertes en Stories / total FRs) × 25 + (4 classes couvertes / total 4×N) × 25
>
> = (7/7) × 25 + (28/28) × 25 + (28/28) × 25 + (112/112) × 25 = **100**

Le critère FR → Architecture est inchangé : chaque verbe du « je veux » doit avoir une couche, et dans cette couche un cas d'usage, un endpoint ou un mécanisme nommé. Le détail FR par FR est en v0.2 ; il reste valable, car aucune FR n'a été ajoutée ni retirée.
- Le PRD v1.1 révisé ne fait que déplacer des cas d'usage : FR-012 passe en Agenda et FR-044 dans « Accès & administration ».
- Il ajoute aussi des mécanismes : le « TOTP récent », la session pré-enrôlement et `POST /privacy/search`.

N1 n'abaisse pas le score : FR-043 garde un mécanisme pour chacun de ses verbes (générer, utiliser, régénérer). Le défaut porte sur un **chemin d'usage** que les textes promettent mais ne rendent pas possible.

## 1. Cohérence DDD
- Glossaire cohérent Brief→PRD→Archi : ☑.
- Bounded contexts stables : ☑. FR-012 et ses cas d'usage relèvent désormais d'Agenda (PRD §5, Archi Couche 2). FR-044 est rattachée à « Accès & administration ».
- Invariants métier présents et codables : ☑.

## 2. Couverture SOLID : ☑

## 3. Traçabilité
- Brief → PRD : ☑ (7/7).
- PRD → Architecture : ☑ (28/28).
- PRD → Stories : ☑ (28/28). Réserve mineure N2 : le `@edge` de FR-020 n'est plus porté par aucune story.

**Cohérence des chiffres** : ☑. Aucune taille de story n'a changé depuis le passage 2 :
- 33 stories ;
- 24,75 j et 264 tours, puis 29,7 j (≈ 30 j) et ≈ 317 tours avec l'émergence de 20 % ;
- ≈ 140 €.

L'enveloppe de ≈ 30 j est **acceptée par le PO**. C'est tracé dans `PROJET.md` (Tranchés) et dans le frontmatter de `04`.

**Numérotation** : ☑. Les FR sont continues par module. La FR-012 est numérotée à la suite de la vitrine mais rattachée au module Agenda, ce que le PRD §5 explique. Les 33 identifiants de story sont uniques.

## 4. Architecture hexagonale
- Backend : dépendances vers l'intérieur : ☑.
- Frontend (hexagonale light) : ☑.

## 4bis. Contrat API matérialisé

| Élément | Statut |
|---|---|
| 1. Source de vérité | Remplacé (spec écrite à la main), conforme à `bootstrap-nouveau-projet.md` §Conditionnement. Le résidu est mesuré en gate review et la destination est API Platform. La **modalité a été tranchée par le PO le 2026-10-22** : réexamen à chaque gate review, trois déclencheurs (`PROJET.md` Tranchés, ADR-004). R1 est levé. |
| 2. Client généré | ✓ — `gen-api.mjs`, adapté à OpenAPI 3.1 en Story 0.2 |
| 3. Désérialisation stricte | ✓ — `StrictJson` + `opis/json-schema` |
| 4. Contract tests CI | ✓ — parité routes ↔ spec ; réponses validées en intégration |

- Sprint 0 inclut le harnais contrat : ☑ (Story 0.2).
- Contrat complet vis-à-vis des comportements spécifiés : ☐. Le réenrôlement TOTP d'un compte déjà enrôlé n'a pas de route (N1).

## 4ter. Données et flux (obligatoire à Essential) : ☑
La section est complète. Deux points ont été ajoutés depuis le passage 2 :
- les sauvegardes exportées sont conservées 6 mois au plus, et leur restauration exige le `config.php` d'origine ;
- la recherche RGPD transmet l'adresse dans le corps d'un POST, donc hors des journaux d'accès.

Aucun flux externe n'est sans ligne.

## 5. Glossaire → code (mapping présent) : ☑
La table `section` comporte désormais une colonne `page` (`home`, `legal-notice`, `privacy`), cohérente avec le mapping.

## 6. BDD + Documentation Vivante : ☑
Les parcours ont chacun leur story porteuse : P1 → 3.7, P2 → 3.8, P3 → 2.7, P4 → 3.11. Chaque story porteuse vient après les stories qu'elle vérifie.

## 7. TDD : ☑

## 7bis. Classes de tests 4×N
- Matrice 4×N par FR dans le PRD : ☑ (28/28). FR-041 porte un second `@edge`, consacré au TOTP récent et à la session pré-enrôlement.
- 4 classes listées dans chaque story : ☑ (33/33).
- Aucune FR orpheline d'une classe : ☑ au niveau du PRD. Côté stories, voir N2.

## 8. Readiness organisationnelle
- Scrum : ☑.
- Nexus et SAFe : non applicables.
- ITIL (pré-prod) : ☑. Couvert par la Story 0.0, la recette, la check-list de mise en ligne et la page santé.

## 9. « Agent IA Ready »
- Specs assez précises pour qu'un agent boucle sans deviner : ☐. Il reste un seul cas où l'agent devrait deviner : le parcours après la perte du téléphone (N1).
- Critères d'acceptation testables : ☑.
- Points irréversibles marqués « validation humaine » : ☑. Les décisions humaines attribuées dans les ADR ont désormais toutes leur ligne au registre (ADR-004 compris).

## 10. Incohérences détectées (passage 3)

**Gravité → verdict** (inchangée) :
- **Bloquante** : empêche le Sprint 0 ; donne FAIL.
- **À corriger** : à lever avant le démarrage de la story concernée ; avec un score ≥ 80, donne CONCERNS.
- **Mineure** : se corrige en cours de route ; n'empêche pas un PASS et passe en recommandation.

**Bilan : 0 bloquante · 1 à corriger · 4 mineures → CONCERNS.**

| # | Gravité | Persona | Fichier § | Incohérence |
|---|---|---|---|---|
| N1 | **à corriger** | PM + Architecte | `02-prd.md` FR-043 `@happy`, FR-044 `@happy`, §9bis (lignes `/admin/account/totp` et `/account/recovery-codes`) ; `03-architecture.md` Couche 2 (`EnrollTotp`) | **Aucun parcours de récupération après la perte du téléphone.** Le gérant peut se connecter avec un code de secours (`/session/recovery`). En revanche : (a) la régénération des codes exige « un code TOTP valide », impossible sans téléphone ; (b) les routes d'enrôlement ne sont ouvertes qu'à la **session pré-enrôlement** créée par `/install`. Un éditeur déjà enrôlé ne peut donc jamais réenrôler son TOTP : il épuise ses 10 codes, puis il est bloqué. Or FR-043 promet de « ne pas perdre l'accès si je perds mon téléphone », et FR-044 affirme que « l'éditeur ré-enrôle son double facteur via ses codes de secours ». Ce mécanisme n'existe pas. Il manque aussi de dire si une session ouverte par code de secours vaut « TOTP récent ». |
| N2 | mineure | Scrum Master | `04-epics-stories.md` Stories 3.2 et 3.11 | Le correctif R2 a déplacé les conflits avec les réservations de la 3.2 vers la 3.11. Mais la 3.11 ne cite pas le cas FR-020 `@edge`, « modification des disponibilités d'un barbier qui exclut une réservation future → refusée avec la liste », ni ce même cas en concurrence. Son Gherkin ne vise que la suppression, la désactivation, la réduction d'horaires et l'absence. Son en-tête omet aussi FR-021, alors qu'elle porte le refus d'une absence posée sur un jour réservé. |
| N3 | mineure | Scrum Master | `04-epics-stories.md` Story 1.3 | Le « TOTP récent », ajouté à FR-041, n'est porté explicitement par aucune story. La 1.3 (FR-041) ne le liste pas ; la 5.2 (export de sauvegarde), la 1.4 (régénération) et la 5.1 (migrations) le **consomment**. Il faut le livrer en 1.3. |
| N4 | mineure | Architecte | `03b-plan-vulnerabilites.md` frontmatter | Le frontmatter indique `version: 1.0 (validé)` et `verdict: à revalider (v1.1, corrections du Validateur)`, alors que le contenu n'a pas changé depuis la v1.0. Il faut aligner les deux champs. |
| N5 | mineure | (registre — superviseur) | `PROJET.md` §Livrables de conception | Les lignes 1 à 4 affichent « ✅ v1.0 validé », alors que les livrables sont en v1.1 « à revalider ». La ligne 5 est à mettre à jour avec le résultat de ce passage. |

## 10bis. Suivi des 12 points du passage 2

| Point | État | Preuve |
|---|---|---|
| R1 ADR-004, modalité attribuée sans trace | levé | `PROJET.md` Tranchés « Modalité de la bascule différée » (gilmry, 2026-10-22) ; ADR-004 « Destination et modalité » |
| R2 Ordonnancement 2.5 / 3.2 / 3.3 | levé (réserve N2) | 2.5 renvoie à 3.3 et 3.11 ; 3.2 et 3.3 renvoient leurs conflits à 3.11 ; 3.3 porte l'affichage FR-005 |
| R3 E-mail dans l'URL | levé | PRD §9bis `POST /privacy/search` ; FR-012 `@security` ; Archi §RGPD |
| R4 §RGPD obsolète | levé | Archi §API · Sécurité & RGPD : « pas de consentement », renvoi à FR-012 |
| R5 TOTP récent, session pré-enrôlement | levé (réserves N1, N3) | FR-041 `@edge` (suite) ; §9bis ; Archi §API · Sécurité |
| R6 Bounded contexts de FR-012 et FR-044 | levé | PRD §5 ; Archi Couche 2 |
| R7 FTP | levé | plus aucune occurrence de « FTP » seul dans 01 à 04 |
| R8 Effacement, sauvegardes, clé sodium | levé (réserve N1 sur la phrase « ré-enrôle ») | FR-012 `@edge` ; FR-044 `@happy`/`@security` ; Données stockées |
| R9 Gate SQL, `section.page` | levé | table des gates `verify` ; PRD §10 `section` |
| R10 Frontmatters | levé (réserve N4) | `signature_humaine.verdict` « à revalider » ; PRD `source` en v1.1 |
| R11 Enveloppe ≈ 30 j | levé | `PROJET.md` Tranchés « Enveloppe après validation » ; `04` §Estimation |
| R12 `/install` 404 | levé | 1.1 `@security` renvoie à 1.2 ; 1.2 `@security` vérifie le 404 |

## 11. Recommandations

1. **PM + Architecte (N1, avant la Story 1.4)** : spécifier le parcours de récupération. Proposition :
   - une session ouverte par code de secours donne accès à une route de **réenrôlement TOTP**. Elle prendrait la forme `POST /api/v1/admin/account/totp/reset`, utiliserait le cas d'usage `EnrollTotp` en mode réenrôlement, et ouvrirait une session limitée, comme la session pré-enrôlement ;
   - la confirmation du nouveau secret invalide l'ancien et régénère les codes de secours ;
   - FR-043 doit recevoir un `@edge` « téléphone perdu ».

   Mettre ensuite §9bis, FR-044 et la Story 1.4 en cohérence.
2. **Mineures, à traiter en cours de route** :
   - N2 : ajouter à la 3.11 le cas FR-020 et la mention de FR-021 ;
   - N3 : livrer le « TOTP récent » dans la Story 1.3 ;
   - N4 : aligner le frontmatter de `03b` ;
   - N5 : mettre à jour les lignes de livrables du registre.
3. **Passage 4** : il peut se limiter à N1, et aux mineures si elles ont été traitées. Si N1 est levé sans nouvelle incohérence bloquante ou à corriger, le verdict sera **PASS**. Le PO revalide alors les livrables v1.1, puis le superviseur valide la sortie de BMAD vers la Phase 2.

---
**Si FAIL/CONCERNS** : reboucler vers le(s) persona(s) concerné(s). **Si PASS** : le superviseur valide le passage au chef de projet (Phase 2).

---

*Dérivé du Manifeste Maury (CC BY-SA 4.0). Source : [`github.com/gilmry/manifest`](https://github.com/gilmry/manifest).*

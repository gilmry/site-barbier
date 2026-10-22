---
livrable: Rapport de validation croisée
persona: 05-validateur (BMAD, TOGAF Phase F)
projet: site-barbier
version: 1.0 (PASS, signé)
sources:
  - 01-product-brief.md v1.1
  - 02-prd.md v1.1
  - 03-architecture.md v1.1 + 03b-plan-vulnerabilites.md v1.0 (contenu inchangé)
  - 04-epics-stories.md v1.1
  - PROJET.md (commit 32b72e6)
redige_le: 2026-10-22
redige_par: agent validateur (Claude Code, pilote Foyer)
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: validé (sortie BMAD, PASS 100/100)
---

# Rapport de Validation croisée — site-barbier

*Livrable du Validateur · TOGAF Phase F. Verdict objectivé : on ne sort de BMAD qu'au PASS.
Archétype déclaré : **stateful full-stack** · Niveau CyFun® visé : **Essential**.*

## Historique

| Passage | Livrables | Score | Verdict | Bilan |
|---|---|---|---|---|
| 1 (v0.1) | v1.0 (26 FR, 31 stories) | 98/100 | CONCERNS | 12 à corriger, 9 mineures |
| 2 (v0.2) | v1.1 (28 FR, 33 stories), commit `86a8c8b` | 100/100 | CONCERNS | 3 à corriger, 9 mineures |
| 3 (v0.3) | v1.1 révisée, commits `5a1fadd` et `cc5c6c0` | 100/100 | CONCERNS | 1 à corriger : aucune reprise d'accès après la perte du téléphone ; 4 mineures |
| **4 (v0.4)** | v1.1 révisée, commit `32b72e6` | **100/100** | **PASS** | N1 à N5 levés ; **0 bloquante · 0 à corriger · 2 mineures** (en recommandations) |

## Statut global : **PASS**

**Fidelity score** : **100/100**. Il dépasse le seuil de 80, et il ne reste aucune incohérence bloquante ni « à corriger ».

**Critère explicite « contrat matérialisé »** (`skills/contrat-api.md`) : **4/4 au sens du cadre.**
- Les éléments 2 à 4 sont mécanisés : `gen-api.mjs` adapté à OpenAPI 3.1, `StrictJson` avec `opis/json-schema`, et la parité routes ↔ spec.
- L'élément 1 est remplacé sous ADR-002 : spec écrite à la main, résidu mesuré, destination API Platform.
- La modalité de bascule a été tranchée par le PO et tracée dans `PROJET.md`.

> Calcul : (capacités Brief couvertes en PRD / total capacités) × 25 + (FRs couvertes en Architecture / total FRs) × 25 + (FRs couvertes en Stories / total FRs) × 25 + (4 classes couvertes / total 4×N) × 25
>
> = (7/7) × 25 + (28/28) × 25 + (28/28) × 25 + (112/112) × 25 = **100**

Le critère FR → Architecture n'a pas changé depuis le passage 1 : chaque verbe du « je veux » doit avoir une couche, et un cas d'usage, un endpoint ou un mécanisme nommé. Le détail FR par FR figure en v0.2. Ce passage n'ajoute aucune FR. Il complète FR-043 avec les cas d'usage `ResetTotp` et `EmergencyResetTotp`, et les routes `totp/reset`, `totp/reset/confirm` et `/install/reset-2fa`.

## 1. Cohérence DDD
- Glossaire (ubiquitous language) cohérent Brief→PRD→Archi : ☑
- Bounded contexts stables : ☑ (FR-012 en Agenda, FR-044 en « Accès & administration »)
- Invariants métier présents et codables : ☑

## 2. Couverture SOLID : ☑

## 3. Traçabilité
- Brief → PRD : ☑ (7/7)
- PRD → Architecture : ☑ (28/28)
- PRD → Stories : ☑ (28/28). FR-020 `@edge` et FR-021 sont désormais portées par la Story 3.11 : Gherkin, `@happy` et en-tête (N2 levé).

**Cohérence des chiffres : ☑, recalculée.**
- **Accès** : 1.1 M + 1.2 L + 1.3 M + 1.4 M (passée de S à M) = 0,75 + 1 + 0,75 + 0,75 = 3,25 j. Tours : 8 + 10 + 8 + 8 = 34.
- **Sous-total** : 4,50 + 3,25 + 6,50 + 8,00 + 1,25 + 1,50 = 25,00 j. Tours : 48 + 34 + 68 + 86 + 14 + 16 = 266.
- **Émergence (20 %)** : + 5,00 j / + 53,2 tours.
- **Total** : 30,0 j / ≈ 319 tours.
- **Coût modèle** : 319 × 0,442 € = 141 €, soit ≈ 140 €.
- **Écarts annoncés** : « + 1,75 j » est juste (23,25 → 25,00 j). « + 7 % » aussi (30 / 27,9).
- **Enveloppe** : ≈ 30 j, acceptée par le PO et tracée dans le registre ; elle reste inchangée.
- **Stories** : 33. **Numérotation** : ☑.

## 4. Architecture hexagonale
- Backend : dépendances vers l'intérieur : ☑
- Frontend (hexagonale light) : ☑

## 4bis. Contrat API matérialisé
Les 4 éléments sont en place (voir le statut global). Sprint 0 inclut le harnais contrat : ☑ (Story 0.2).

Le contrat est complet vis-à-vis des comportements spécifiés : ☑. La reprise d'accès est couverte en trois chemins :
- **session « secours »** : elle ne vaut pas « TOTP récent » et n'ouvre que la consultation et `POST /account/totp/reset` (+ `/confirm`), qui invalident l'ancien secret et les anciens codes ;
- **réinitialisation d'urgence** : un fichier jeton déposé par SFTP/FTPS, plus le mot de passe, ouvrent `/install/reset-2fa`, avec journalisation et notification ;
- **restauration de sauvegarde** : FR-044 renvoie désormais à la réinitialisation d'urgence.

## 4ter. Données et flux (obligatoire à Essential) : ☑
La section est inchangée et reste complète. Le fichier jeton d'urgence est transitoire (`app/var/`, hors racine web) et il est supprimé après usage. Aucun nouveau flux externe : la notification passe par le SMTP déjà cartographié.

## 5. Glossaire → code (mapping présent) : ☑
## 6. BDD + Documentation Vivante (flux critiques couverts) : ☑ (P1 → 3.7, P2 → 3.8, P3 → 2.7, P4 → 3.11)

## 7. TDD (stratégie de tests + couverture cible) : ☑

## 7bis. Classes de tests 4×N (mécanisé)
- Matrice 4×N par FR présente dans le PRD : ☑ (28/28 ; FR-043 `@edge` couvre « téléphone perdu » et « plus aucun code »).
- 4 classes listées dans chaque story : ☑ (33/33).
- Aucune FR orpheline d'une classe : ☑.

## 8. Readiness organisationnelle
- Scrum : ☑ · Nexus : — (non applicable) · SAFe : — (non applicable) · ITIL (pré-prod) : ☑ (0.0, recette, check-list de mise en ligne, page santé)

## 9. « Agent IA Ready »
- Les specs sont assez précises pour qu'un agent boucle sans deviner : ☑. Les deux imprécisions restantes sont mineures et se lèvent dans la story qui les touche (§11).
- Critères d'acceptation testables : ☑
- Points irréversibles identifiés et marqués « validation humaine » : ☑. Il s'agit d'ADR-005, d'ADR-008, de la rupture du contrat `v1`, de la protection de branche, de la première mise en production, de la première migration en production et du choix de l'offre d'hébergement. La modalité d'ADR-004 est tracée.

## 10. Incohérences détectées (passage 4)

**Gravité → verdict.**
- **Bloquante** : empêche le Sprint 0 et donne FAIL.
- **À corriger** : à lever avant la story concernée ; avec un score ≥ 80, donne CONCERNS.
- **Mineure** : se corrige en cours de story, n'empêche pas le PASS et part en recommandation.

**Bilan : 0 bloquante · 0 à corriger · 2 mineures → PASS.**

| # | Gravité | Persona | Fichier § | Incohérence |
|---|---|---|---|---|
| M1 | mineure | PM + Architecte | `02-prd.md` FR-042 (« /install est désactivé »), §8 arborescence (« désactivé après usage »), §9bis (`/install/reset-2fa`) ; `04` Story 1.2 `@security` (« `/install` répond 404 ») | La réinitialisation d'urgence vit sous `/install/reset-2fa`, alors que `/install` est déclaré désactivé (404) après l'enrôlement. Il faut écrire l'exception, par exemple « `/install/*` répond 404, sauf `/install/reset-2fa` lorsqu'un fichier jeton est présent ». Sinon le test 404 de 1.2 et le parcours d'urgence de 1.4 se contredisent. La route est par ailleurs rendue côté serveur mais listée dans le tableau de la spec API : il faut l'exclure de la parité routes ↔ spec, comme les pages publiques. |
| M2 | mineure | Scrum Master | `04-epics-stories.md` Story 1.4 `@security`, §Ordre | La Story 1.4 vérifie que l'événement d'urgence est « notifié par e-mail ». L'adaptateur SMTP (`Mailer`) n'est livré qu'en 4.1, bien plus tard, ce qui contredit la règle « chaque story ne teste que ce que les précédentes ont livré ». Gravité mineure : le parcours de reprise n'en dépend pas. Il suffit de vérifier la notification avec un `Mailer` factice en 1.4 et de la rejouer avec le vrai SMTP en 4.1. |

## 10bis. Suivi des 5 points du passage 3

| Point | État | Preuve |
|---|---|---|
| N1 Reprise d'accès après perte du téléphone | levé (réserve M1) | FR-043 `@happy`/`@edge` (session « secours », `totp/reset`, réinitialisation d'urgence) ; §9bis (3 lignes) ; Archi Couche 2 `ResetTotp`, `EmergencyResetTotp` ; FR-044 renvoie à FR-043 ; Story 1.4 (Gherkin « téléphone perdu ») |
| N2 FR-020 `@edge`, FR-021 dans 3.11 | levé | 3.11 : en-tête, Gherkin (« modifie sa disponibilité »), `@happy` |
| N3 TOTP récent livré | levé | Story 1.3 `@happy` + `@edge` (10 min 01 s), avant 1.4, 5.1 et 5.2 |
| N4 Frontmatter du plan | levé | `03b` : version 1.0, verdict « validé (contenu inchangé) » |
| N5 Registre | levé | `PROJET.md` lignes 1–4 « 🟡 v1.1 à revalider », ligne 5 passage 3 |

## 11. Recommandations

**Avant le passage en Phase 2**

1. **Le PO revalide les livrables v1.1.** Les frontmatters de 01, 02, 03 et 04 portent « à revalider ». Il signe ce rapport (`signature_humaine`). La sortie de BMAD est une décision engageante, que **le superviseur valide**.
2. **Mettre à jour le registre.** La ligne 5 de `PROJET.md` doit indiquer « passage 4 : PASS 100/100 ». Il faut aussi ajouter l'entrée de journal et faire passer les lignes 1 à 4 à ✅ v1.1 après la signature.

**Mineures, à traiter dans la story concernée**

3. **M1** (PM + Architecte, avant ou pendant 1.2 et 1.4) : écrire l'exception `/install/reset-2fa` à la désactivation de `/install`, et l'exclure de la parité routes ↔ spec.
4. **M2** (Scrum Master, 1.4 et 4.1) : notification d'urgence avec un `Mailer` factice en 1.4, rejouée avec le SMTP réel en 4.1.

---
**Si FAIL/CONCERNS** : reboucler vers le(s) persona(s) concerné(s). **Si PASS** : le superviseur valide le passage au chef de projet (Phase 2).

---

*Dérivé du Manifeste Maury (CC BY-SA 4.0). Source : [`github.com/gilmry/manifest`](https://github.com/gilmry/manifest).*

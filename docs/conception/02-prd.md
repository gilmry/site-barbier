---
livrable: PRD
persona: 02-product-manager (BMAD, TOGAF Phases B-C)
projet: site-barbier
version: 1.0 (validé)
source: 01-product-brief.md v1.0 (validé 2026-10-22)
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: validé (hypothèses introduites acceptées telles quelles)
---

# PRD — site-barbier

*Chaque exigence se rattache à une capacité du Brief (§7.n). Archétype **stateful full-stack** :
toutes les sections s'appliquent. Les valeurs non tranchées par le PO restent marquées `[hypothèse]`
— elles ont été acceptées en bloc à la validation du Brief.*

## 1. Résumé exécutif

Site trilingue (FR/NL/EN) d'un barbier bruxellois, hébergé sur un mutualisé LAMP. Trois usages :
**présenter** le salon (contenu éditable par le gérant), **réserver** un créneau réellement libre
chez un barbier, **administrer** (contenu, agendas, réservations) derrière un accès à double facteur.
MVP = les 4 bounded contexts du Brief ; aucune dépendance à un service tiers payant.

## 2. Objectifs produit (mesurables)

| # | Objectif | Mesure | Cible |
|---|---|---|---|
| O1 | Autonomie du gérant | temps pour changer un prix + une photo, sans aide | < 2 min |
| O2 | Réservation rapide | temps médian du parcours de réservation sur mobile | < 60 s |
| O3 | Fiabilité de l'agenda | réservations qui se chevauchent pour un même barbier | 0 |
| O4 | Adoption | part des rendez-vous pris en ligne à 3 mois | ≥ 30 % `[hypothèse]` |
| O5 | Sobriété | poids de la page d'accueil hors photos · chargement 4G | < 500 Ko · < 1 s |
| O6 | Coût | hébergement | ≤ 5 €/mois `[hypothèse]` |

## 3. Périmètre

**MVP** : FR-001 → FR-043 ci-dessous.

**Hors scope MVP** (Brief §12) : rappel la veille, comptes barbiers, paiement/acompte, gestion des
no-shows, export iCal, galerie, carte cadeau en ligne, traduction automatique, SMS.

## 4. Glossaire DDD (figé depuis le Brief §8)

Prestation · Catégorie · Carte · Barbier · Horaires · **Disponibilité** · **Absence** ·
**Fermeture exceptionnelle** · **Créneau** · **Réservation** (*confirmée* | *annulée*) ·
**Jeton d'annulation** · Section · Page · Média · **Texte traduit** · Langue (FR réf., NL, EN) ·
Éditeur · Publication. Définitions : Brief §8. Ajout PRD :

| Terme | Définition |
|---|---|
| **Pas de créneau** | Granularité des débuts de créneau : 15 min `[hypothèse]`. |
| **Délai minimal** | Une réservation commence au plus tôt 2 h après l'instant de réservation `[hypothèse]`. |
| **Horizon** | On réserve au plus 60 jours à l'avance `[hypothèse]`. |
| **Durée de conservation** | 6 mois après la date du rendez-vous, puis effacement des données du client. |

## 5. Bounded contexts → modules

| BC (Brief §9) | Module | Préfixe FR |
|---|---|---|
| Vitrine | `Vitrine` | FR-001…FR-011 |
| Agenda & réservation | `Agenda` | FR-020…FR-027 |
| Notification | `Notification` | FR-030…FR-032 |
| Accès | `Acces` | FR-040…FR-043 |

## 6. Exigences fonctionnelles

### Module Vitrine

#### FR-001 — Afficher le site public
- **En tant que** client **je veux** voir la page d'accueil (8 sections de la maquette) **afin de** découvrir le salon.
- **Gherkin** : `Étant donné un contenu publié, Quand j'ouvre /fr/, Alors je vois en-tête, hero, maison, carte, équipe, réservation, horaires et pied de page avec les textes publiés.`
- **4×N** :
  - `@happy` — page rendue côté serveur avec le contenu publié ; navigation par ancres.
  - `@negative` — URL inconnue → page 404 traduite ; base injoignable → page d'erreur sobre (500), sans trace technique.
  - `@edge` — section vide (ex. aucun barbier actif) → section masquée, pas de cadre vide ; écran 320 px sans défilement horizontal ; `prefers-reduced-motion` coupe l'animation.
  - `@security` — tout texte édité est échappé à l'affichage (XSS stockée impossible) ; en-têtes CSP, `X-Content-Type-Options`, `Referrer-Policy` présents.
- **Capacité** : §7.1

#### FR-002 — Choisir sa langue
- **En tant que** client **je veux** lire le site en FR, NL ou EN **afin de** comprendre dans ma langue.
- **Gherkin** : `Étant donné que je suis sur /fr/, Quand je choisis NL, Alors j'arrive sur /nl/ à la même section et tous les textes sont en néerlandais.`
- **4×N** :
  - `@happy` — préfixe d'URL `/fr/`, `/nl/`, `/en/` ; `hreflang` et `lang` corrects ; `/` redirige selon `Accept-Language` (défaut FR).
  - `@negative` — préfixe inconnu (`/de/`) → 404 ; `Accept-Language` illisible → FR.
  - `@edge` — traduction NL/EN manquante → repli sur le FR pour ce texte seulement, page jamais vide.
  - `@security` — le paramètre de langue n'est jamais injecté tel quel dans le HTML ni dans un chemin de fichier (liste blanche).
- **Capacité** : §7.3

#### FR-003 — Afficher la carte
- **En tant que** client **je veux** voir les prestations par catégorie avec prix et durée **afin de** choisir.
- **Gherkin** : `Étant donné 8 prestations publiées en 2 catégories, Quand j'ouvre la section Tarifs, Alors je vois les 2 catégories dans l'ordre défini et chaque prestation avec nom, prix TTC, description et durée.`
- **4×N** :
  - `@happy` — ordre de catégories et de prestations respecté ; prix formaté selon la langue (`28 €`, `€ 28`).
  - `@negative` — prestation dépubliée → absente de la carte et du parcours de réservation.
  - `@edge` — prix 0 € affiché « offert » traduit ; catégorie sans prestation publiée → masquée ; nom très long → retour à la ligne sans casser la ligne pointillée.
  - `@security` — échappement des noms et descriptions.
- **Capacité** : §7.1

#### FR-004 — Afficher l'équipe
- **En tant que** client **je veux** voir les barbiers (photo ou monogramme, spécialité, bio) **afin de** choisir avec qui réserver.
- **Gherkin** : `Étant donné 3 barbiers actifs, Quand j'ouvre la section Équipe, Alors je vois 3 cartes dans l'ordre défini.`
- **4×N** :
  - `@happy` — photo si présente, sinon monogramme (initiale du prénom).
  - `@negative` — barbier inactif → absent de l'équipe et du choix de barbier.
  - `@edge` — 1 seul barbier → la grille reste lisible ; prénom avec accent ou tiret → monogramme correct.
  - `@security` — `alt` des photos échappé ; pas d'URL de photo arbitraire (uniquement médias gérés, FR-007).
- **Capacité** : §7.1

#### FR-005 — Afficher horaires et coordonnées
- **En tant que** client **je veux** voir les horaires de la semaine, le jour courant, l'adresse et le téléphone **afin de** venir ou appeler.
- **Gherkin** : `Étant donné que nous sommes jeudi à Bruxelles, Quand j'ouvre Horaires, Alors la ligne jeudi est marquée « Aujourd'hui » et le lundi est affiché « Fermé ».`
- **4×N** :
  - `@happy` — 7 jours, jours fermés grisés, bouton « Copier » le numéro, lien `tel:`.
  - `@negative` — presse-papiers refusé → le numéro est sélectionné (repli).
  - `@edge` — fermeture exceptionnelle dans les 7 jours → mention « fermé exceptionnellement le … » ; visiteur dans un autre fuseau → le jour courant reste celui de Bruxelles.
  - `@security` — coordonnées échappées.
- **Capacité** : §7.1

#### FR-006 — Éditer les textes des sections
- **En tant que** gérant **je veux** modifier les textes de chaque section dans les 3 langues **afin de** tenir le site à jour.
- **Gherkin** : `Étant donné que je suis connecté, Quand je modifie l'accroche du hero en FR et NL puis j'enregistre, Alors le site public affiche les nouveaux textes immédiatement dans ces langues.`
- **4×N** :
  - `@happy` — formulaire par section, onglets FR/NL/EN, aperçu ; indicateur « à traduire » quand NL/EN manque.
  - `@negative` — FR vide → refus (le FR est obligatoire) ; texte au-delà de la longueur maximale → refus avec message.
  - `@edge` — deux onglets d'admin ouverts → la seconde sauvegarde sur une version périmée est refusée (verrou optimiste) au lieu d'écraser.
  - `@security` — accès sans session → 401 ; jeton CSRF obligatoire ; HTML brut interdit (texte simple + mise en forme limitée : gras, italique, liens `https:`/`tel:`/`mailto:`).
- **Capacité** : §7.2, §7.3

#### FR-007 — Gérer les images
- **En tant que** gérant **je veux** remplacer les images (hero, barbiers, maison) **afin de** montrer le vrai salon.
- **Gherkin** : `Étant donné que je suis connecté, Quand j'envoie une photo JPEG de 4 Mo, Alors elle est redimensionnée, enregistrée et affichée à la place de l'ancienne, avec un texte alternatif dans les 3 langues.`
- **4×N** :
  - `@happy` — JPEG/PNG/WebP acceptés, ré-encodés et redimensionnés (largeur max 1600 px), métadonnées EXIF retirées.
  - `@negative` — type non autorisé (SVG, PDF, `.php`) → refus ; fichier > 8 Mo `[hypothèse]` → refus clair.
  - `@edge` — image très petite (< 200 px) → avertissement ; remplacement d'une image utilisée → l'ancienne est supprimée seulement si plus aucune section ne la référence.
  - `@security` — type vérifié par contenu (pas par extension) ; nom de fichier généré ; dossier d'upload sans exécution de scripts ; fichier polyglotte (image + PHP) neutralisé par le ré-encodage.
- **Capacité** : §7.2

#### FR-008 — Gérer la carte
- **En tant que** gérant **je veux** créer, modifier, ordonner, publier/dépublier les prestations et catégories **afin de** tenir mes prix à jour.
- **Gherkin** : `Étant donné la prestation « Coupe classique » à 28 €, Quand je la passe à 30 €, Alors la carte affiche 30 € et les réservations déjà prises gardent leur prix et leur durée d'origine.`
- **4×N** :
  - `@happy` — CRUD + ordre par glisser ou flèches ; nom/description en 3 langues.
  - `@negative` — prix négatif, durée nulle ou non multiple du pas de créneau → refus.
  - `@edge` — suppression d'une prestation ayant des réservations futures → refusée (proposer « dépublier ») ; durée max 240 min `[hypothèse]`.
  - `@security` — admin seulement, CSRF ; prix stocké en centimes entiers (pas de flottant).
- **Capacité** : §7.2

#### FR-009 — Gérer les barbiers
- **En tant que** gérant **je veux** ajouter, modifier, activer/désactiver un barbier et indiquer les prestations qu'il réalise **afin de** refléter l'équipe.
- **Gherkin** : `Étant donné Léa active, Quand je la désactive, Alors elle disparaît du site et du choix de barbier, et ses réservations futures me sont listées pour que je les traite.`
- **4×N** :
  - `@happy` — prénom, spécialité et bio (3 langues), photo, prestations réalisées.
  - `@negative` — prénom vide → refus.
  - `@edge` — désactivation avec réservations futures → avertissement + liste, les réservations restent valides tant qu'elles ne sont pas annulées ; un barbier sans prestation → absent du parcours de réservation.
  - `@security` — admin seulement, CSRF.
- **Capacité** : §7.2, §7.4

#### FR-010 — Gérer les horaires du salon et les coordonnées
- **En tant que** gérant **je veux** fixer les horaires par jour et les coordonnées **afin que** le site et les créneaux soient justes.
- **Gherkin** : `Étant donné le jeudi 10:00–21:00, Quand je le passe à 10:00–19:00, Alors le site l'affiche et aucun créneau ne commence de sorte à finir après 19:00 le jeudi.`
- **4×N** :
  - `@happy` — une plage par jour ou « fermé » ; adresse, téléphone, e-mail du salon, accès (3 langues).
  - `@negative` — fin ≤ début → refus ; téléphone au format invalide → refus.
  - `@edge` — réduction d'horaires qui exclut des réservations futures → refusée tant que ces réservations ne sont pas déplacées/annulées (liste fournie).
  - `@security` — admin seulement, CSRF.
- **Capacité** : §7.2, §7.4

#### FR-011 — Pages légales
- **En tant que** gérant **je veux** publier mentions légales et politique de confidentialité en 3 langues **afin de** respecter la loi.
- **Gherkin** : `Quand j'ouvre /nl/privacy, Alors je lis la politique en néerlandais, avec la durée de conservation (6 mois) et le contact pour exercer mes droits.`
- **4×N** :
  - `@happy` — deux pages éditables, liées en pied de page et dans le formulaire de réservation ; modèle initial fourni.
  - `@negative` — page non rédigée dans une langue → repli FR.
  - `@edge` — la durée de conservation affichée est **lue de la configuration**, jamais recopiée à la main.
  - `@security` — échappement ; même règles de mise en forme que FR-006.
- **Capacité** : §7.6, Brief §14 (RGPD)

### Module Agenda

#### FR-020 — Définir les disponibilités des barbiers
- **En tant que** gérant **je veux** fixer pour chaque barbier ses plages hebdomadaires **afin que** seuls ses vrais horaires soient réservables.
- **Gherkin** : `Étant donné le salon ouvert le mardi 10:00–19:00, Quand je donne à Samir le mardi 12:00–19:00, Alors aucun créneau de Samir ne commence avant 12:00 le mardi.`
- **4×N** :
  - `@happy` — plusieurs plages par jour possibles (pause midi) ; par défaut = horaires du salon.
  - `@negative` — plage hors des horaires du salon → refus ; plages qui se chevauchent pour un même jour → refus.
  - `@edge` — modification qui exclut des réservations futures → refusée avec la liste ; plage de 15 min (le pas) acceptée.
  - `@security` — admin seulement, CSRF.
- **Capacité** : §7.4

#### FR-021 — Absences et fermetures exceptionnelles
- **En tant que** gérant **je veux** poser une absence pour un barbier ou fermer le salon un jour donné **afin de** bloquer les réservations.
- **Gherkin** : `Étant donné aucune réservation pour Antoine le 12 novembre, Quand je pose une absence ce jour, Alors aucun créneau d'Antoine n'est proposé le 12 novembre.`
- **4×N** :
  - `@happy` — absence par barbier (date ou plage date-heure) ; fermeture pour tout le salon, avec motif public facultatif (3 langues).
  - `@negative` — fin avant début → refus ; date passée → refus.
  - `@edge` — absence qui recouvre une réservation existante → **refusée** avec la liste (règle du Brief §10bis) ; absence sur plusieurs semaines.
  - `@security` — admin seulement, CSRF.
- **Capacité** : §7.4

#### FR-022 — Proposer les créneaux libres
- **En tant que** client **je veux** voir, pour une prestation et un barbier (ou « sans préférence ») et un jour, les heures réellement disponibles **afin de** choisir sans appeler.
- **Gherkin** : `Étant donné Samir disponible le mardi 12:00–19:00 et réservé 14:00–15:00, Quand je demande une coupe aux ciseaux (60 min) chez Samir ce mardi, Alors on me propose 12:00, 12:15, …, 13:00, 15:00, …, 18:00 et ni 13:15 ni 18:15.`
- **4×N** :
  - `@happy` — créneaux au pas de 15 min où la durée entière tient dans une disponibilité, hors réservation confirmée, absence et fermeture ; « sans préférence » = union des barbiers qui réalisent la prestation.
  - `@negative` — prestation inexistante/dépubliée ou barbier inactif → 404 ; date mal formée → 400.
  - `@edge` — jour courant : rien avant *maintenant + 2 h* ; au-delà de l'horizon (60 j) → liste vide ; **dimanches de changement d'heure** (fin mars, fin octobre) calculés juste ; jour fermé → liste vide avec motif.
  - `@security` — endpoint public en lecture seule, ne révèle **aucune** donnée de client (ni nom ni motif des créneaux pris) ; limitation de débit.
- **Capacité** : §7.5

#### FR-023 — Réserver un créneau
- **En tant que** client **je veux** réserver le créneau choisi en donnant nom, téléphone et e-mail **afin d'** avoir un rendez-vous ferme.
- **Gherkin** : `Étant donné le créneau mardi 15:00 libre chez Samir, Quand je le réserve pour une coupe aux ciseaux avec mes coordonnées, Alors la réservation est confirmée, je vois un récapitulatif dans ma langue et le créneau 15:00–16:00 n'est plus proposé.`
- **4×N** :
  - `@happy` — réservation *confirmée* ; récapitulatif (prestation, barbier, date/heure de Bruxelles, prix) ; e-mails FR-030 et FR-031 déclenchés.
  - `@negative` — champ obligatoire vide, e-mail ou téléphone invalide, case de consentement à la politique de confidentialité non cochée → refus champ par champ ; créneau devenu indisponible → 409 avec proposition de rafraîchir.
  - `@edge` — **deux clients valident le même créneau au même instant → exactement une réservation confirmée, l'autre reçoit 409** (garanti en base, testé sur MariaDB) ; créneau qui chevauche partiellement une autre réservation → 409 ; prix/durée figés au moment de la réservation.
  - `@security` — champ piège + limitation de débit par IP (ex. 5 réservations/heure `[hypothèse]`) ; désérialisation stricte (champ inconnu → 400) ; données personnelles jamais renvoyées par un autre endpoint public ; journal sans données personnelles en clair.
- **Capacité** : §7.5

#### FR-024 — Réserver « sans préférence »
- **En tant que** client **je veux** réserver sans choisir de barbier **afin d'** avoir le premier créneau possible.
- **Gherkin** : `Étant donné Samir et Léa libres le mardi à 15:00, Quand je réserve « sans préférence » à 15:00, Alors la réservation est attribuée à un barbier précis et le récapitulatif affiche son prénom.`
- **4×N** :
  - `@happy` — attribution au barbier libre ayant le moins de réservations ce jour (répartition équitable), à égalité ordre de l'équipe.
  - `@negative` — plus aucun barbier libre au moment de valider → 409.
  - `@edge` — un seul barbier libre → attribué à lui ; concurrence avec une réservation nominative sur le même barbier → l'invariant de non-chevauchement tranche.
  - `@security` — idem FR-023.
- **Capacité** : §7.5

#### FR-025 — Annuler par le client
- **En tant que** client **je veux** annuler via le lien reçu par e-mail **afin de** libérer le créneau.
- **Gherkin** : `Étant donné ma réservation de demain 15:00, Quand j'ouvre mon lien d'annulation et je confirme, Alors la réservation passe à « annulée », le créneau redevient libre et le salon est prévenu.`
- **4×N** :
  - `@happy` — page de confirmation (récapitulatif) puis annulation ; e-mail au salon (FR-030).
  - `@negative` — jeton inconnu → 404 générique ; réservation déjà annulée → message « déjà annulée ».
  - `@edge` — moins de 2 h avant le rendez-vous → annulation en ligne refusée, téléphone du salon affiché ; rendez-vous passé → refus.
  - `@security` — jeton aléatoire ≥ 128 bits, stocké **haché** ; ouvrir le lien n'annule pas (confirmation par POST, protège des pré-chargeurs de liens) ; pas d'énumération (même réponse pour jeton inconnu et expiré).
- **Capacité** : §7.5

#### FR-026 — Suivre et annuler côté salon
- **En tant que** gérant **je veux** voir le planning par jour et par barbier, la liste des réservations, et annuler **afin de** gérer le salon.
- **Gherkin** : `Étant donné 6 réservations aujourd'hui, Quand j'ouvre le planning du jour, Alors je les vois par barbier et par heure avec nom et téléphone du client.`
- **4×N** :
  - `@happy` — vue jour (colonnes par barbier) et liste filtrable (date, barbier, état) ; annulation avec message facultatif → e-mail au client (FR-031).
  - `@negative` — annuler une réservation déjà annulée → sans effet, message.
  - `@edge` — jour sans réservation → vue vide explicite ; 40 réservations sur un jour → lisible sur tablette.
  - `@security` — admin seulement ; données personnelles uniquement dans l'admin ; CSRF sur l'annulation.
- **Capacité** : §7.6

#### FR-027 — Effacer automatiquement les données échues
- **En tant que** gérant **je veux** que les réservations soient effacées 6 mois après le rendez-vous **afin de** respecter le RGPD sans y penser.
- **Gherkin** : `Étant donné une réservation dont le rendez-vous date de 6 mois et 1 jour, Quand la purge s'exécute, Alors la réservation et les coordonnées du client n'existent plus et un compteur anonyme du mois est conservé.`
- **4×N** :
  - `@happy` — purge quotidienne : tâche planifiée de l'hébergeur si disponible, sinon déclenchée au premier passage de la journée `[hypothèse]`.
  - `@negative` — purge interrompue → reprise au passage suivant sans doublon ni perte (idempotente).
  - `@edge` — exactement 6 mois → conservée jusqu'au lendemain ; réservations annulées purgées selon la même règle.
  - `@security` — la purge ne peut pas être déclenchée par un visiteur pour un autre effet (pas de paramètre) ; aucune donnée personnelle ne survit dans les journaux ou les e-mails stockés.
- **Capacité** : §7.6

### Module Notification

#### FR-030 — Prévenir le salon
- **En tant que** gérant **je veux** recevoir un e-mail à chaque réservation et annulation **afin d'** être au courant sans ouvrir l'admin.
- **Gherkin** : `Quand un client réserve, Alors l'adresse du salon reçoit un e-mail (en français) avec prestation, barbier, date/heure, nom, téléphone et e-mail du client.`
- **4×N** :
  - `@happy` — envoi via le SMTP authentifié de l'hébergeur.
  - `@negative` — SMTP en échec → la réservation **reste confirmée**, l'envoi est journalisé « en échec » (FR-032).
  - `@edge` — adresse du salon non configurée → avertissement permanent dans l'admin.
  - `@security` — en-têtes construits sans injection (retours à la ligne refusés dans les champs client) ; pas de contenu HTML client non échappé.
- **Capacité** : §7.5, §7.6

#### FR-031 — Confirmer au client
- **En tant que** client **je veux** recevoir un e-mail de confirmation dans ma langue avec un lien d'annulation **afin de** garder la trace de mon rendez-vous.
- **Gherkin** : `Étant donné que j'ai réservé sur /nl/, Quand la réservation est confirmée, Alors je reçois un e-mail en néerlandais avec le récapitulatif, l'adresse du salon et mon lien d'annulation.`
- **4×N** :
  - `@happy` — e-mails de confirmation et d'annulation (par le salon) dans la langue de réservation ; pièce `.ics` jointe `[hypothèse]`.
  - `@negative` — échec SMTP → journalisé, relançable (FR-032) ; la page de récapitulatif reste la preuve.
  - `@edge` — traduction d'un gabarit manquante → repli FR.
  - `@security` — protection contre l'injection d'en-têtes ; le lien contient le jeton en clair une seule fois (jamais stocké en clair) ; expéditeur = domaine du salon (SPF/DKIM).
- **Capacité** : §7.5

#### FR-032 — Journal et relance des e-mails
- **En tant que** gérant **je veux** voir les e-mails en échec et les relancer **afin qu'** aucun client ne reste sans confirmation.
- **Gherkin** : `Étant donné 2 e-mails en échec, Quand je clique « relancer », Alors ils sont renvoyés et passent à « envoyé » en cas de succès.`
- **4×N** :
  - `@happy` — liste des envois (date, destinataire masqué, type, état) ; relance unitaire ou groupée.
  - `@negative` — relance en échec → reste « en échec » avec l'erreur.
  - `@edge` — relance d'un e-mail dont la réservation a été purgée → impossible, entrée retirée.
  - `@security` — admin seulement ; le corps des e-mails n'est pas conservé au-delà de la réservation.
- **Capacité** : §7.6

### Module Accès

#### FR-040 — Se connecter avec double facteur
- **En tant que** gérant **je veux** me connecter avec mot de passe + code à 6 chiffres d'une appli d'authentification **afin que** personne d'autre ne modifie le site.
- **Gherkin** : `Étant donné mon compte avec TOTP activé, Quand je saisis mon e-mail, mon mot de passe puis un code valide, Alors j'accède à l'administration.`
- **4×N** :
  - `@happy` — mot de passe haché (Argon2id ou bcrypt), TOTP (RFC 6238, tolérance ±30 s).
  - `@negative` — mot de passe ou code faux → message unique non révélateur ; code déjà utilisé → refus (anti-rejeu).
  - `@edge` — horloge du téléphone décalée de 30 s → accepté ; de 2 min → refusé.
  - `@security` — **verrouillage progressif** après 5 échecs (délai croissant) ; pas d'énumération de compte ; mot de passe ≥ 12 caractères, vérifié contre une liste de mots de passe courants embarquée.
- **Capacité** : §7.7

#### FR-041 — Session sûre
- **En tant que** gérant **je veux** une session qui expire et une déconnexion **afin qu'** un poste laissé ouvert ne reste pas exposé.
- **Gherkin** : `Étant donné une session inactive depuis 30 min, Quand j'agis dans l'admin, Alors on me redemande de me connecter.`
- **4×N** :
  - `@happy` — cookie `Secure; HttpOnly; SameSite=Strict`, identifiant régénéré à la connexion, déconnexion.
  - `@negative` — cookie altéré → session refusée.
  - `@edge` — expiration d'inactivité 30 min, absolue 12 h `[hypothèse]`.
  - `@security` — CSRF sur toute écriture ; HTTPS imposé (HSTS) ; l'admin n'est pas indexée (`noindex`).
- **Capacité** : §7.7

#### FR-042 — Installation et mises à jour sans ligne de commande
- **En tant que** gérant (ou son prestataire) **je veux** installer le site et appliquer les mises à jour de la base depuis le navigateur **afin de** n'avoir besoin que d'un accès FTP.
- **Gherkin** : `Étant donné un paquet envoyé par FTP et une base vide, Quand j'ouvre /install avec le code d'installation, Alors les migrations s'appliquent, je crée le compte éditeur et j'enrôle mon TOTP, puis /install est désactivé.`
- **4×N** :
  - `@happy` — assistant : vérification de l'environnement (version PHP, extensions, droits d'écriture) → migrations → compte → TOTP ; mises à jour ultérieures : migrations en attente appliquées depuis l'admin.
  - `@negative` — PHP < 8.2 ou extension manquante → arrêt avec message clair ; migration en échec → transaction annulée, état inchangé.
  - `@edge` — rejouer l'installation après succès → impossible ; migration déjà appliquée → ignorée (idempotent).
  - `@security` — code d'installation à usage unique (présent dans la configuration envoyée par FTP) ; configuration et code applicatif hors racine web ou interdits par `.htaccess` ; aucune page n'affiche les secrets.
- **Capacité** : §7.7, Brief §14

#### FR-043 — Codes de secours
- **En tant que** gérant **je veux** des codes de secours **afin de** ne pas perdre l'accès si je perds mon téléphone.
- **Gherkin** : `Étant donné mes 10 codes de secours, Quand j'en utilise un à la place du code TOTP, Alors je suis connecté et ce code n'est plus valable.`
- **4×N** :
  - `@happy` — 10 codes générés à l'enrôlement, affichés une seule fois, stockés hachés ; régénération possible.
  - `@negative` — code de secours déjà utilisé → refus.
  - `@edge` — dernier code utilisé → l'admin exige d'en régénérer.
  - `@security` — mêmes limites de tentatives que FR-040.
- **Capacité** : §7.7

## 7. Exigences non-fonctionnelles

| # | Exigence | Mesure |
|---|---|---|
| NFR-01 Performance | accueil rendu serveur < 200 ms (TTFB, hébergeur nominal) ; < 500 Ko hors photos ; Lighthouse perf mobile ≥ 90 | test de charge léger + Lighthouse en CI |
| NFR-02 Compatibilité hébergement | PHP **8.2** + **MariaDB 10.6** ; extensions : `pdo_mysql`, `gd` ou `imagick`, `mbstring`, `intl`, `openssl` ; aucune dépendance installée sur le serveur | gates CI sur cette pile exacte |
| NFR-03 Sécurité | CyFun Essential (couche application, `skills/conformite.md` §2bis) ; OWASP ASVS niveau 2 sur l'admin | gates `@security` + rapports de jalon (SAST, SCA) |
| NFR-04 Accessibilité | WCAG 2.1 AA (contraste sur fond noir vérifié, navigation clavier, libellés de formulaire) | axe en E2E |
| NFR-05 Disponibilité | celle de l'hébergeur ; aucun point unique ajouté par l'app ; sauvegarde quotidienne de la base `[hypothèse : fournie par l'hébergeur]` | — |
| NFR-06 RGPD | finalité et durée affichées ; consentement explicite ; effacement à 6 mois ; droit d'accès/effacement traité par le gérant depuis l'admin | FR-011, FR-027 |
| NFR-07 Sobriété | aucun traceur, aucune police tierce chargée côté client sans auto-hébergement `[défaut : polices auto-hébergées]` ; JS uniquement dans les îlots | gate `verify` (pas d'URL tierce dans le HTML) |
| NFR-08 i18n | 100 % des libellés d'interface dans des fichiers de traduction FR/NL/EN ; aucun texte en dur dans les gabarits | gate `verify` |
| NFR-09 Temps | toutes les heures calculées en `Europe/Brussels`, stockées en UTC | tests `@edge` changement d'heure |

## 8. Frontend UX

**Parcours client (mobile d'abord)** : accueil → « Prendre rendez-vous » → ① prestation → ② barbier ou
« sans préférence » → ③ jour (calendrier des 60 prochains jours, jours sans créneau grisés) →
④ heure → ⑤ coordonnées + consentement → ⑥ récapitulatif. Retour possible à chaque étape sans perte.

**Parcours gérant** : connexion (mot de passe → code) → tableau de bord (réservations du jour,
e-mails en échec, textes « à traduire ») → Contenu (sections, images, carte, équipe, horaires,
coordonnées, pages légales) → Agenda (disponibilités, absences, fermetures) → Réservations (planning, liste).

**Arborescence**
```
/{fr|nl|en}/                     accueil (8 sections) — rendu serveur PHP
/{fr|nl|en}/reserver             parcours de réservation — îlot Svelte « booking »
/{fr|nl|en}/annuler/{jeton}      confirmation d'annulation — rendu serveur
/{fr|nl|en}/mentions-legales     (nl: /juridische-informatie, en: /legal)
/{fr|nl|en}/confidentialite      (nl: /privacy, en: /privacy)
/admin/                          administration — îlot Svelte « admin », FR seul
/install                         assistant d'installation (désactivé après usage)
```

**i18n** : langue dans l'URL ; FR langue de référence et de repli ; libellés d'interface en fichiers
de traduction versionnés ; contenus éditables en champs traduits en base ; l'admin est en français
`[hypothèse : le gérant est francophone]`.

## 9bis. Contrat API (OpenAPI) — consommé par les îlots

Source de vérité : `openapi/site-barbier.openapi.json` (OpenAPI 3.1), écrite **avant** le code ;
client TypeScript généré pour les îlots ; désérialisation stricte (champ inconnu → 400) ;
contract tests en CI (gate `contrat`). Erreurs au format `application/problem+json`.
Version `v1` dans le chemin.

| Méthode | Chemin | Accès | Rôle |
|---|---|---|---|
| GET | `/api/v1/services?lang=` | public | prestations publiées réservables |
| GET | `/api/v1/barbers?service=&lang=` | public | barbiers actifs réalisant la prestation |
| GET | `/api/v1/availability/days?service=&barber=&from=&to=` | public | jours ayant ≥ 1 créneau |
| GET | `/api/v1/availability/slots?service=&barber=&date=` | public | créneaux libres d'un jour (`barber=any` admis) |
| POST | `/api/v1/bookings` | public, limité | réserver (201 / 400 / 409 / 429) |
| POST | `/api/v1/bookings/cancel` | public (jeton) | annuler par le client (200 / 404 / 422) |
| POST | `/api/v1/admin/session` · `/session/totp` · DELETE `/session` | — | connexion en deux temps, déconnexion |
| GET/PUT | `/api/v1/admin/sections/{key}` | admin | textes traduits d'une section (version pour verrou optimiste) |
| POST/DELETE | `/api/v1/admin/media` · `/media/{id}` | admin | images |
| GET/POST/PUT/DELETE | `/api/v1/admin/categories[/{id}]` · `/services[/{id}]` | admin | carte |
| GET/POST/PUT | `/api/v1/admin/barbers[/{id}]` | admin | équipe |
| GET/PUT | `/api/v1/admin/opening-hours` · `/salon` | admin | horaires, coordonnées |
| GET/PUT | `/api/v1/admin/barbers/{id}/availability` | admin | disponibilités |
| GET/POST/DELETE | `/api/v1/admin/absences[/{id}]` · `/closures[/{id}]` | admin | absences, fermetures |
| GET | `/api/v1/admin/bookings?date=&barber=&state=` | admin | planning / liste |
| POST | `/api/v1/admin/bookings/{id}/cancel` | admin | annulation salon |
| GET/POST | `/api/v1/admin/mail-log` · `/mail-log/retry` | admin | journal et relance |
| GET/POST | `/api/v1/admin/migrations` | admin | migrations en attente / appliquer |

Les pages publiques (FR-001…FR-005, FR-011, FR-025) sont rendues côté serveur et ne passent pas par
l'API ; elles appellent **les mêmes cas d'usage** (couche Application).

## 9. Documentation Vivante

**Flux critiques** (chacun = 1 scénario BDD = 1 parcours partagé rejoué par E2E, visuel et vitrine) :

| # | Parcours de référence | FR couvertes |
|---|---|---|
| P1 | **Réserver** : visiteur NL → carte → réserver coupe chez Samir → récapitulatif → créneau disparu | 001, 002, 003, 022, 023, 031 |
| P2 | **Annuler** : lien reçu → confirmation → créneau libéré → e-mail au salon | 025, 030 |
| P3 | **Éditer** : connexion 2FA → changer un prix et la photo du hero → visible sur le site | 040, 006, 007, 008 |
| P4 | **Tenir l'agenda** : poser une absence → créneaux disparus → absence refusée sur jour réservé | 021, 022 |

Le PO désignera, à l'étape d'arbitrage du bootstrap, lesquels sont « critiques » et filmés (défaut proposé : P1 et P3).

## 10. Modèle de données (entités → tables MariaDB)

*Le gabarit dit PostgreSQL ; ici **MariaDB 10.6** imposée par l'hébergement (Brief §14).*

| Entité | Table | Colonnes clés | Contraintes |
|---|---|---|---|
| Section | `section` | `key`, `version` | PK `key` |
| Texte traduit | `translation` | `owner_type`, `owner_id`, `field`, `lang`, `value` | UNIQUE (owner_type, owner_id, field, lang) ; `lang ∈ {fr,nl,en}` |
| Média | `media` | `id`, `path`, `mime`, `width`, `height` | — |
| Catégorie | `category` | `id`, `position` | — |
| Prestation | `service` | `id`, `category_id`, `price_cents`, `duration_min`, `published`, `position` | `price_cents ≥ 0`, `duration_min > 0` |
| Barbier | `barber` | `id`, `first_name`, `photo_media_id`, `active`, `position` | — |
| Prestation × barbier | `barber_service` | `barber_id`, `service_id` | PK composite |
| Horaires | `opening_hours` | `weekday`, `opens`, `closes` | `closes > opens` |
| Disponibilité | `availability` | `barber_id`, `weekday`, `starts`, `ends` | dans les horaires |
| Absence / fermeture | `absence` | `id`, `barber_id` (NULL = salon), `starts_at`, `ends_at` | `ends_at > starts_at` |
| Réservation | `booking` | `id`, `barber_id`, `service_id`, `starts_at`, `ends_at`, `price_cents`, `duration_min`, `state`, `lang`, `client_name`, `client_phone`, `client_email`, `cancel_token_hash`, `created_at` | **non-chevauchement garanti en base** (verrou par barbier en transaction — mécanisme précis tranché par l'Architecte en ADR) |
| Envoi d'e-mail | `mail_log` | `id`, `booking_id`, `kind`, `state`, `error`, `attempts` | — |
| Éditeur | `editor` | `id`, `email`, `password_hash`, `totp_secret_enc`, `failed_attempts`, `locked_until` | un seul enregistrement au MVP |
| Code de secours | `recovery_code` | `editor_id`, `code_hash`, `used_at` | — |
| Statistique anonyme | `booking_stat` | `month`, `count`, `cancelled` | — |
| Migrations | `schema_migration` | `version`, `applied_at` | — |

Migrations `NNNN_*.up.sql` / `*.down.sql` réversibles, SQL portable MariaDB.

## 11. Intégrations externes

| Intégration | Usage | Données envoyées |
|---|---|---|
| SMTP de l'hébergeur | e-mails salon + client | nom, téléphone, e-mail, détails du rendez-vous |
| (aucune autre) | — | pas d'analytics, pas de CDN, pas de captcha tiers |

## 12. Contraintes et hypothèses

- Contraintes : Brief §14 (mutualisé, FTP, PHP 8.2 + MariaDB, pas de Node serveur, CyFun Essential, RGPD, FR/NL/EN).
- Hypothèses acceptées à la validation du Brief : e-mail client obligatoire · conservation 6 mois ·
  délai minimal 2 h, annulation jusqu'à 2 h avant · traductions saisies par le gérant · FTP sans SSH · ≤ 5 €/mois.
- Hypothèses introduites ici (à confirmer à la validation du PRD) : pas de 15 min · horizon 60 jours ·
  image ≤ 8 Mo · 5 réservations/heure/IP · session 30 min / 12 h · admin en français · fichier `.ics` joint ·
  purge « au premier passage du jour » si pas de tâche planifiée · répartition « sans préférence » équitable.

## 13. Critères de succès MVP

1. Les 4 parcours de référence (P1–P4) passent en E2E sur PHP 8.2 + MariaDB 10.6.
2. Test de concurrence : 20 réservations simultanées sur un même créneau → exactement 1 confirmée.
3. Un paquet de déploiement unique, envoyé par FTP sur un hébergement de test, s'installe via `/install`.
4. Objectifs O1, O2, O5 mesurés et atteints avant mise en ligne.
5. Gate review CyFun Essential (couche application) sans écart bloquant.

## Traçabilité Brief → PRD

| Capacité Brief §7 | FR |
|---|---|
| 7.1 Présenter | 001, 003, 004, 005 |
| 7.2 Éditer | 006, 007, 008, 009, 010 |
| 7.3 Trois langues | 002, 006 (+ tous les libellés, NFR-08) |
| 7.4 Agendas | 009, 010, 020, 021 |
| 7.5 Réserver | 022, 023, 024, 025, 030, 031 |
| 7.6 Suivre | 011, 026, 027, 030, 032 |
| 7.7 Protéger l'admin | 040, 041, 042, 043 |

Aucune FR orpheline ; les 26 FR portent leurs 4 classes de tests.

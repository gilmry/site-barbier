---
livrable: Epics & User Stories
persona: 04-scrum-master (BMAD, TOGAF Phase E)
projet: site-barbier
version: 1.1 (corrections du Validateur, à revalider)
source: 03-architecture.md v1.1
redige_le: 2026-10-22
redige_par: agent (Claude Code, pilote Foyer)
signature_humaine:
  nom: gilmry
  role: PO
  date: 2026-10-22
  verdict: à revalider (v1.1, corrections du Validateur) — v1.0 validé le 2026-10-22 (une seule release, ≈ 28 j ; ≈ 30 j en v1.1, accepté par le PO le 2026-10-22)
---

# Epics & User Stories — site-barbier

*Une story = un tour du cercle (rouge → vert → bleu). Taille S = 0,5 j · M = 0,75 j · L = 1 j de supervision.
Tours estimés : S ≈ 6 · M ≈ 8 · L ≈ 10. Les 4 classes de tests détaillées des FR sont dans le PRD ; chaque
story ci-dessous reprend leur Gherkin principal et nomme ce que chaque classe vérifie **dans cette story**.*

**DoD commune** : 4 classes rouges puis vertes dans les couches concernées · gates `plancher`, `verify`, `unit`,
`contrat`, `integration` verts · si écran : `e2e` + `visuel` verts et parcours de référence rejoué en vitrine ·
registre `PROJET.md` à jour (tours, tokens, wall-clock) · un commit par étape conclusive.

---

## Sprint 0 — Fondations *(story habilitante — précède tout, bloque le reste)*

### Story 0.0 — Choisir et sonder l'offre d'hébergement
- **En tant que** PO **je veux** une grille de critères vérifiés sur l'offre envisagée **afin de** ne pas découvrir une impossibilité au moment de la mise en ligne.
- **Gherkin** : `Étant donné une offre mutualisée candidate, Quand l'agent déroule la grille, Alors chaque critère est noté conforme / non conforme / à contourner, et un critère éliminatoire non conforme fait écarter l'offre.`
- **4×N** : `@happy` grille : PHP 8.2+ et extensions (`pdo_mysql`, `gd`, `mbstring`, `intl`, `openssl`, `sodium`), MariaDB 10.6+ (un ou deux comptes — ADR-010), SFTP/FTPS, `.htaccess`, `.user.ini`, tâche planifiée, SMTP authentifié + SPF/DKIM, sous-domaine de test, **sauvegarde quotidienne et restauration** (éliminatoire), juridiction UE (éliminatoire), HTTPS gratuit, prix ≤ 5 €/mois · `@negative` critère éliminatoire absent → offre écartée, motif tracé · `@edge` pas de tâche planifiée → contournement « premier passage du jour » (FR-027) ; un seul compte MariaDB → résidu ADR-010 · `@security` aucun identifiant d'hébergement dans le dépôt ; la grille remplie est versionnée (`docs/hebergement.md`). Le **choix final** de l'offre est une décision du PO.
- **Couches** : IaC · **Taille** : S · **Tours** : 6

### Story 0.1 — Socle reproductible depuis le kit
- **En tant que** développeur **je veux** cloner le dépôt et lancer une commande **afin d'** avoir l'app et tous les gates verts « à vide ».
- **Gherkin** : `Étant donné un clone neuf et Docker, Quand je lance ./go, Alors l'app répond sur localhost, MariaDB 10.6 tourne et bash harness/ci.sh passe au vert ; relancer ./go ne produit aucun diff.`
- **4×N** : `@happy` socle `kit-php` instancié, domaine Todo retiré, images épinglées PHP 8.2 + MariaDB 10.6 · `@negative` Docker absent → message clair (seul blocage légitime) · `@edge` relance idempotente, port occupé → `FOYER_PORT` · `@security` **preuve de sensibilité** : un drift artificiel fait passer chaque gate au rouge, puis restauré ; `config.php` ignoré (G1).
- **Couches** : IaC · CI · **Taille** : L · **Tours** : 10

### Story 0.2 — Harnais de contrat matérialisé *(non optionnel, `contrat-api.md`)*
- **En tant que** développeur **je veux** que la spec OpenAPI soit la source de vérité vérifiée en CI **afin qu'** aucun écran ne dérive du serveur.
- **Gherkin** : `Étant donné la spec v1 squelette, Quand j'ajoute une route au routeur sans l'ajouter à la spec, Alors le gate contrat est rouge.`
- **4×N** : `@happy` `gen-api.mjs` (repris du kit et **adapté à OpenAPI 3.1**) génère `api.ts` ; `StrictJson` (**nouveau**, `opis/json-schema`) valide contre la spec ; parité routes ↔ spec (**nouvelle**, le kit ne l'a pas) · `@negative` champ inconnu → 400 `problem+json` · `@edge` route documentée absente du code → rouge ; client édité à la main → rouge ; schéma sans `additionalProperties: false` → rouge · `@security` invariant : aucun `fetch(` hors `generated/` (gate `verify`).
- **Couches** : Http · Frontend · CI · **Taille** : M · **Tours** : 8

### Story 0.3 — Parcours partagés et triple harnais
- **En tant que** PO **je veux** que chaque parcours de référence soit rejoué en test, en comparaison visuelle et en vitrine filmée **afin de** voir la valeur sans dette de doc.
- **Gherkin** : `Étant donné journeys/smoke.journey.ts, Quand la CI tourne, Alors e2e est vert, les goldens visuels sont comparés et une vitrine HTML + vidéo est publiée en artefact.`
- **4×N** : `@happy` Playwright (node 22) rejoue le parcours à la vitesse et en cadence 1 action/1 s · `@negative` assertion fausse → e2e rouge, vitrine produite quand même (non bloquante) · `@edge` rendu stabilisé (animations coupées) pour des goldens sans bruit · `@security` invariant anti-dette : un harnais qui n'importe pas le parcours partagé → `verify` rouge.
- **Couches** : Frontend · CI · **Taille** : M · **Tours** : 8

### Story 0.4 — Paquet de déploiement et hébergement simulé
- **En tant que** PO **je veux** un paquet unique installable par SFTP **afin de** mettre en ligne sans ligne de commande.
- **Gherkin** : `Étant donné un commit, Quand harness/package.sh s'exécute, Alors un zip reproductible (SHA-256 identique à entrée égale) est produit ; déposé dans php:8.2-apache + mariadb:10.6 à racine fixe, sa page de santé répond 200.`
- **4×N** : `@happy` zip `app/` + `www/` + `vendor/` + îlots, `.htaccess` versionné · `@negative` extension PHP manquante dans le conteneur → page de santé en erreur explicite · `@edge` `app/` placée sous la racine web → interdite par `.htaccess` (403 vérifié) · `@security` aucun fichier de test, `.git`, `config.php` ni source TS dans le paquet ; `uploads/` sans exécution PHP (requête sur un `.php` déposé → non exécuté).
- **Couches** : IaC · CI · **Taille** : M · **Tours** : 8

### Story 0.5 — CI, hooks et plancher sécurité
- **En tant que** responsable sécurité **je veux** que les mêmes gates tournent en local et en CI **afin qu'** aucun secret ni dépendance vulnérable ne passe.
- **Gherkin** : `Étant donné un secret factice commité, Quand je pousse, Alors le hook pre-commit et la CI échouent sur Gitleaks.`
- **4×N** : `@happy` workflows GitHub Actions (gabarits Foyer), hooks `pre-commit`/`pre-push`, SBOM CycloneDX PHP + npm, `composer audit`/`npm audit`, Opengrep, PHPStan 8, Dependabot, job hebdomadaire · `@negative` dépendance avec avis critique → rapport de jalon (non bloquant) remonté au registre · `@edge` dépôt sans commit pendant un mois → job planifié tourne quand même · `@security` `SECURITY.md` (signalement gilmry+abuse@gmail.com) ; **protection de branche** préparée mais posée par le PO (point irréversible, dépôt distant à créer par lui).
- **Couches** : CI · Monitoring · **Taille** : M · **Tours** : 8

---

## Epic 1 — Accès · **Must** *(prérequis de toute édition)*

### Story 1.1 — Installer et migrer depuis le navigateur (FR-042)
- **Gherkin** : `Étant donné une base vide et le paquet déposé, Quand j'ouvre /install avec le code d'installation, Alors les migrations s'appliquent, je crée le compte éditeur et je suis conduit à l'enrôlement du double facteur (Story 1.2) ; /install est désactivé une fois l'enrôlement confirmé.`
- **4×N** : `@happy` contrôle d'environnement → migrations → compte · `@negative` PHP < 8.2 → arrêt clair ; migration en échec → rollback · `@edge` recréer un compte une fois le compte créé → refus ; migration déjà appliquée → ignorée · `@security` code à usage unique, aucun secret affiché ; la session créée n'est qu'une session pré-enrôlement (la désactivation de `/install` est vérifiée en 1.2).
- **Couches** : Domain · Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 1.2 — Connexion mot de passe + double facteur (FR-040)
- **Gherkin** : `Étant donné mon compte avec TOTP enrôlé, Quand je saisis e-mail, mot de passe puis un code valide, Alors j'accède à l'admin.`
- **4×N** : `@happy` **enrôlement TOTP** (QR + secret, confirmé par un premier code — porte l'étape finale de `/install`), connexion en deux temps · `@negative` mauvais mot de passe/code → message unique ; code rejoué → refus · `@edge` décalage ±30 s accepté, 2 min refusé ; vecteurs RFC 6238 · `@security` verrou progressif après 5 échecs, pas d'énumération, liste noire de mots de passe, secret chiffré (sodium) ; `/install` répond 404 une fois l'enrôlement confirmé ; session pré-enrôlement limitée aux routes d'enrôlement.
- **Couches** : Domain · Application · Infra · Frontend · **Taille** : L · **Tours** : 10

### Story 1.3 — Session sûre, CSRF, en-têtes (FR-041)
- **Gherkin** : `Étant donné une session inactive depuis 30 min, Quand j'agis dans l'admin, Alors on me redemande de me connecter.`
- **4×N** : `@happy` sessions en base, cookie `__Host-`, déconnexion · `@negative` cookie altéré → refus · `@edge` expirations 30 min / 12 h · `@security` CSRF sur toute écriture, CSP stricte, HSTS, `noindex` sur l'admin, identifiant régénéré à la connexion.
- **Couches** : Infra · Http · **Taille** : M · **Tours** : 8

### Story 1.4 — Codes de secours (FR-043)
- **Gherkin** : `Quand j'utilise un de mes 10 codes de secours au lieu du TOTP, Alors je suis connecté et ce code n'est plus valable.`
- **4×N** : `@happy` génération, affichage unique, stockage haché · `@negative` code utilisé → refus · `@edge` dernier code → régénération exigée · `@security` mêmes limites de tentatives que 1.2.
- **Couches** : Domain · Application · Frontend · **Taille** : S · **Tours** : 6

---

## Epic 2 — Vitrine · **Must**

### Story 2.1 — Page d'accueil au design Le Corbeau (FR-001)
- **Gherkin** : `Étant donné le contenu initial, Quand j'ouvre /fr/, Alors je vois les 8 sections de la maquette, rendues par le serveur, sans requête vers un tiers.`
- **4×N** : `@happy` gabarits PHP + jetons CSS de la maquette, polices auto-hébergées, données initiales de la maquette · `@negative` URL inconnue → 404 traduite ; base injoignable → 500 sobre · `@edge` section vide masquée, 320 px sans défilement horizontal, `prefers-reduced-motion` · `@security` échappement obligatoire (`verify`), en-têtes de sécurité ; golden visuel posé (référence de rendu).
- **Couches** : Application · Infra · Frontend · **Taille** : L · **Tours** : 10

### Story 2.2 — Trois langues (FR-002, NFR-08)
- **Gherkin** : `Étant donné que je suis sur /fr/, Quand je choisis NL, Alors j'arrive sur /nl/ et tous les libellés sont en néerlandais.`
- **4×N** : `@happy` préfixes `/fr|nl|en/`, `hreflang`, redirection de `/` selon `Accept-Language` · `@negative` `/de/` → 404 · `@edge` traduction manquante → repli FR texte par texte · `@security` langue en liste blanche ; aucune chaîne en dur (`verify`).
- **Couches** : Domain (`TranslatedText`) · Http · Frontend · **Taille** : M · **Tours** : 8

### Story 2.3 — La carte : affichage et gestion (FR-003, FR-008)
- **Gherkin** : `Étant donné « Coupe classique » à 28 €, Quand je la passe à 30 € dans l'admin, Alors la carte affiche 30 € dans les 3 langues.`
- **4×N** : `@happy` CRUD catégories/prestations, ordre, publication, formatage du prix par langue ; données initiales de la maquette (dont 40 et 100 min) · `@negative` prix négatif, durée nulle ou non multiple de 5 → refus · `@edge` 0 € « offert » ; durée 240 min acceptée, 245 refusée (le refus de suppression avec réservations futures est porté par la Story 3.11) · `@security` admin + CSRF, centimes entiers, échappement.
- **Couches** : Domain · Application · Infra · Frontend (îlot admin) · **Taille** : L · **Tours** : 10

### Story 2.4 — L'équipe (FR-004, FR-009)
- **Gherkin** : `Étant donné Léa active, Quand je la désactive, Alors elle disparaît du site et du choix de barbier.`
- **4×N** : `@happy` CRUD barbiers, prestations réalisées, monogramme ou photo · `@negative` prénom vide → refus · `@edge` un seul barbier ; prénom accentué → monogramme correct (l'avertissement sur réservations futures est porté par la Story 3.11) · `@security` admin + CSRF, `alt` échappé.
- **Couches** : Domain · Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 2.5 — Horaires et coordonnées (FR-005, FR-010)
- **Gherkin** : `Étant donné que nous sommes jeudi à Bruxelles, Quand j'ouvre Horaires, Alors jeudi est marqué « Aujourd'hui » ; si je réduis le jeudi à 19:00 dans l'admin, le site l'affiche.`
- **4×N** : `@happy` 7 jours, fermé/ouvert, copier le numéro, coordonnées traduites · `@negative` fin ≤ début, téléphone invalide → refus · `@edge` jour courant au fuseau de Bruxelles (l'affichage des fermetures exceptionnelles est porté par la 3.3, le refus d'une réduction qui exclut des réservations par la 3.11) · `@security` admin + CSRF.
- **Couches** : Domain · Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 2.6 — Textes des sections (FR-006)
- **Gherkin** : `Quand je modifie l'accroche du hero en FR et NL puis j'enregistre, Alors le site public les affiche immédiatement.`
- **4×N** : `@happy` onglets FR/NL/EN, indicateur « à traduire » · `@negative` FR vide, texte trop long → refus · `@edge` deux onglets ouverts → verrou optimiste (409 sur version périmée) · `@security` mise en forme limitée (gras, italique, liens `https:`/`tel:`/`mailto:`), XSS stockée impossible.
- **Couches** : Domain · Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 2.7 — Images (FR-007)
- **Gherkin** : `Quand j'envoie une photo JPEG de 4 Mo pour le hero, Alors elle est redimensionnée, nettoyée de ses métadonnées et affichée.`
- **4×N** : `@happy` JPEG/PNG/WebP, ré-encodage, 1600 px max, alt en 3 langues · `@negative` SVG/PDF/`.php`, > 8 Mo → refus · `@edge` image < 200 px → avertissement ; image encore référencée → non supprimée · `@security` type vérifié par contenu, fichier polyglotte neutralisé, nom aléatoire, aucune exécution dans `uploads/`.
- **Couches** : Domain · Application · Infra (GD) · Frontend · **Taille** : M · **Tours** : 8

### Story 2.8 — Pages légales et droits RGPD (FR-011, FR-012) *(ordonnée après 4.1 et 3.11)*
- **Gherkin** : `Quand j'ouvre /nl/privacy, Alors je lis la politique en néerlandais avec la durée de conservation lue dans la configuration ; dans l'admin, je retrouve les réservations d'un e-mail et je peux les exporter ou les effacer.`
- **4×N** : `@happy` deux pages éditables + modèles initiaux FR/NL/EN, recherche par e-mail → export JSON / effacement · `@negative` page non traduite → repli FR ; e-mail sans résultat → message · `@edge` durée affichée = configuration (jamais recopiée) ; effacement d'une réservation future → e-mail d'annulation (4.1) puis libération des cellules sous `AgendaLock` (ADR-006) · `@security` admin + CSRF, export généré en mémoire, jamais écrit sur le serveur ; action journalisée sans l'adresse en clair.
- **Couches** : Application · Infra · Frontend · **Taille** : M · **Tours** : 8

---

## Epic 3 — Agenda & réservation · **Must**

### Story 3.1 — Calcul des créneaux, domaine pur (FR-022 domaine)
- **Gherkin** : `Étant donné Samir disponible mardi 12:00–19:00 et réservé 14:00–15:00, Quand je calcule les créneaux d'une prestation de 60 min, Alors j'obtiens 12:00…13:00 et 15:00…18:00 au quart d'heure ; pour un rasage de 40 min, 13:15 est proposé (fin 13:55) mais pas 13:30.`
- **4×N** : `@happy` `SlotCalculator` pur (horaires du salon, disponibilités rognées aux horaires, absences, réservations, politique, maintenant) · `@negative` durée > disponibilité → aucun créneau · `@edge` **tests de propriétés** : aucun créneau ne chevauche une réservation ; délai 2 h ; horizon 60 j ; **dimanches de changement d'heure** (fin mars, fin octobre) · `@security` aucune donnée client dans le résultat (type `Slot` sans identité).
- **Couches** : Domain · **Taille** : M · **Tours** : 8

### Story 3.2 — Disponibilités des barbiers (FR-020)
- **Gherkin** : `Quand je donne à Samir le mardi 12:00–19:00, Alors aucun créneau de Samir ne commence avant 12:00 le mardi.`
- **4×N** : `@happy` plusieurs plages par jour, défaut = horaires du salon · `@negative` hors horaires, chevauchement → refus · `@edge` plage de 15 min acceptée ; disponibilité qui déborde des horaires du salon → rognée au calcul (`SlotCalculator`) · `@security` admin + CSRF. (Les conflits avec des réservations existantes ou simultanées sont portés par la 3.11, après `BookSlot`.)
- **Couches** : Domain · Application · Infra (AgendaLock) · Frontend · **Taille** : M · **Tours** : 8

### Story 3.3 — Absences et fermetures exceptionnelles (FR-021)
- **Gherkin** : `Quand je pose une absence pour Antoine le 12 novembre, Alors aucun créneau d'Antoine n'est proposé ce jour ; quand je ferme le salon le 24 décembre, le site affiche « fermé exceptionnellement le 24 décembre » dans la section Horaires.`
- **4×N** : `@happy` absence barbier, fermeture salon avec motif traduit · `@negative` fin avant début, date passée → refus · `@edge` absence sur plusieurs semaines ; fermeture salon verrouille tous les barbiers dans l'ordre des identifiants (pas d'interblocage, test de deux fermetures simultanées) ; fermeture dans les 7 jours affichée sur le site (FR-005) · `@security` admin + CSRF. (Absence refusée sur un jour réservé, et course absence ↔ réservation : Story 3.11.)
- **Couches** : Domain · Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 3.4 — API publique de réservation en lecture (FR-022 ; prérequis FR-023/024)
- **Gherkin** : `Quand j'appelle GET /api/v1/services puis /barbers?service=… puis /availability/slots?service=…&barber=any&date=…, Alors je reçois les prestations publiées, les barbiers qui les réalisent et leurs créneaux libres.`
- **4×N** : `@happy` `ListBookableServices`, `ListBookableBarbers`, `days` et `slots`, `barber=any` · `@negative` prestation dépubliée / barbier inactif → 404 ; date invalide → 400 · `@edge` jour fermé → liste vide + motif ; au-delà de l'horizon → vide · `@security` aucune donnée client exposée, limitation de débit, réponse validée contre la spec.
- **Couches** : Application · Http · **Taille** : S · **Tours** : 6

### Story 3.5 — Réserver un créneau, sans double réservation (FR-023)
- **Gherkin** : `Étant donné mardi 15:00 libre chez Samir, Quand je réserve une coupe aux ciseaux, Alors la réservation est confirmée et 15:00–16:00 n'est plus proposé ; si 20 clients valident ce créneau en même temps, un seul est confirmé.`
- **4×N** : `@happy` `BookSlot` en transaction (`AgendaLock` + cellules de 5 min `booking_slot`), prix/durée figés, jeton d'annulation haché · `@negative` champs invalides → 400 champ par champ (pas de case de consentement : information RGPD seulement) ; créneau pris → 409 · `@edge` **20 réservations simultanées = 1 confirmée** ; chevauchement partiel → 409 ; réservation ↔ annulation simultanées · `@security` champ piège, 5 réservations/h/IP, désérialisation stricte, aucune donnée personnelle dans les journaux.
- **Couches** : Domain · Application · Infra · Http · **Taille** : L · **Tours** : 10

### Story 3.11 — Garde-fous de l'agenda sur le contenu (FR-008, FR-009, FR-010, FR-020 ; parcours P4) *(après 3.5)*
- **En tant que** gérant **je veux** que mes modifications de carte, d'équipe et d'horaires ne cassent jamais une réservation existante **afin de** ne pas laisser un client sans rendez-vous.
- **Gherkin** : `Étant donné une réservation future de Léa, Quand je supprime sa prestation, la désactive, réduis les horaires qui la couvrent ou pose une absence ce jour, Alors chaque action est refusée (ou avertie pour la désactivation) avec la liste des réservations concernées.`
- **4×N** : `@happy` suppression → refus et proposition de dépublier ; dépublication et désactivation → acceptées, réservations conservées, liste affichée ; réduction d'horaires → refus avec la liste ; disponibilité qui déborde → signalée « hors horaires » · `@negative` aucune réservation concernée → action acceptée · `@edge` chaque action **en concurrence avec une réservation** sur MariaDB → l'une des deux l'emporte, jamais d'état incohérent (verrous ADR-006 dans l'ordre des identifiants) · `@security` admin + CSRF. **P4 rejoué** : absence posée → créneaux disparus → absence refusée sur un jour réservé.
- **Couches** : Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 3.6 — Réserver « sans préférence » (FR-024)
- **Gherkin** : `Étant donné Samir et Léa libres à 15:00, Quand je réserve « sans préférence », Alors la réservation est attribuée à celui qui a le moins de rendez-vous ce jour.`
- **4×N** : `@happy` `NoPreferenceAssigner` pur, égalité → ordre de l'équipe · `@negative` plus aucun libre → 409 · `@edge` un seul libre ; course avec une réservation nominative → l'invariant tranche · `@security` idem 3.5.
- **Couches** : Domain · Application · **Taille** : S · **Tours** : 6

### Story 3.7 — Îlot de réservation, parcours P1 (FR-022/023/024, UX §8)
- **Gherkin** : `Étant donné un visiteur sur /nl/, Quand il choisit une coupe, Samir, mardi, 15:00 puis saisit ses coordonnées, Alors il voit le récapitulatif en néerlandais et le créneau a disparu.`
- **4×N** : `@happy` îlot Svelte 5, 6 étapes, retour arrière sans perte, client généré uniquement · `@negative` 409 → message et rafraîchissement des créneaux ; erreurs champ par champ traduites · `@edge` mobile 320 px ; calendrier 60 j avec jours sans créneau grisés ; navigation clavier (axe) · `@security` aucun `fetch` hors client généré, aucune donnée stockée dans le navigateur au-delà de la session. **P1 rejoué en e2e, visuel et vitrine.**
- **Couches** : Frontend · **Taille** : L · **Tours** : 10

### Story 3.8 — Annulation par le client (FR-025, P2)
- **Gherkin** : `Étant donné ma réservation de demain 15:00, Quand j'ouvre mon lien et je confirme, Alors elle est annulée et le créneau redevient libre.`
- **4×N** : `@happy` page de confirmation (rendu serveur, langue de la réservation) puis POST · `@negative` jeton inconnu → 404 générique ; déjà annulée → message · `@edge` < 2 h avant → refus + téléphone du salon ; cellules libérées dans la même transaction · `@security` GET n'annule pas, jeton ≥ 128 bits haché, pas d'énumération. **P2 rejoué.**
- **Couches** : Domain · Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 3.9 — Planning et annulation côté salon (FR-026)
- **Gherkin** : `Étant donné 6 réservations aujourd'hui, Quand j'ouvre le planning, Alors je les vois par barbier et par heure ; si j'en annule une avec un message, le client est prévenu.`
- **4×N** : `@happy` vue jour par barbier, liste filtrable, annulation · `@negative` annuler une annulée → sans effet · `@edge` jour vide explicite, 40 réservations sans défilement horizontal à 768 px · `@security` données personnelles uniquement dans l'admin, CSRF.
- **Couches** : Application · Infra · Frontend · **Taille** : M · **Tours** : 8

### Story 3.10 — Purge automatique à 6 mois (FR-027)
- **Gherkin** : `Étant donné une réservation dont le rendez-vous date de 6 mois et 1 jour, Quand la purge s'exécute, Alors la réservation et ses e-mails journalisés n'existent plus et le compteur du mois est incrémenté.`
- **4×N** : `@happy` tâche planifiée de l'hébergeur ou premier passage du jour · `@negative` purge interrompue → reprise idempotente · `@edge` exactement 6 mois → conservée jusqu'au lendemain ; `GET_LOCK` évite deux exécutions · `@security` non déclenchable avec paramètre par un visiteur ; aucune donnée personnelle survivante (journal compris).
- **Couches** : Domain · Application · Infra · **Taille** : S · **Tours** : 6

---

## Epic 4 — Notification · **Must**

### Story 4.1 — E-mails au salon et au client (FR-030, FR-031)
- **Gherkin** : `Quand un client réserve sur /nl/, Alors le salon reçoit un e-mail en français et le client un e-mail en néerlandais avec son lien d'annulation et un fichier .ics.`
- **4×N** : `@happy` PHPMailer SMTP + STARTTLS, gabarits FR/NL/EN, `.ics` ; corps recomposé depuis la réservation, jamais stocké · `@negative` SMTP en échec → réservation maintenue, envoi journalisé « en échec » · `@edge` gabarit non traduit → repli FR ; adresse du salon absente → avertissement admin · `@security` aucun retour à la ligne accepté dans les en-têtes, contenu échappé, jeton en clair uniquement dans l'e-mail. Test avec un serveur SMTP de capture (Mailpit) en intégration.
- **Couches** : Domain · Application · Infra · **Taille** : M · **Tours** : 8

### Story 4.2 — Journal et relance des e-mails (FR-032)
- **Gherkin** : `Étant donné 2 e-mails en échec, Quand je clique « relancer », Alors ils passent à « envoyé » en cas de succès.`
- **4×N** : `@happy` liste (destinataire masqué), relance unitaire/groupée ; relance d'une confirmation = nouveau jeton d'annulation (l'ancien lien cesse de fonctionner) · `@negative` relance en échec → reste en échec avec l'erreur · `@edge` réservation purgée → entrée retirée · `@security` admin + CSRF, corps non conservé au-delà de la réservation.
- **Couches** : Application · Infra · Frontend · **Taille** : S · **Tours** : 6

---

## Epic 5 — Mise en ligne et exploitation · **Must** (fin de release)

### Story 5.1 — Recette puis production (ADR-008)
- **Gherkin** : `Étant donné un paquet promu, Quand le PO l'envoie en SFTP sur test.<domaine> puis lance /install, Alors P1–P4 passent en recette ; après son accord, le même paquet va en production.`
- **4×N** : `@happy` check-list de mise en ligne, SPF/DKIM du domaine, test de délivrabilité · `@negative` recette en échec → pas de production · `@edge` mise à jour d'une version existante : migrations en attente appliquées depuis l'admin · `@security` envoi SFTP/FTPS uniquement, `config.php` créé sur le serveur et jamais dans le paquet, HTTPS vérifié. **Points irréversibles** (première mise en production, première migration en production) : arbitrage du PO.
- **Couches** : IaC · **Taille** : M · **Tours** : 8

### Story 5.2 — Santé, journal, alertes et sauvegarde (couche 7, FR-044)
- **Gherkin** : `Quand j'ouvre le tableau de bord, Alors je vois e-mails en échec, migrations en attente, dernière purge, dernière sauvegarde exportée, version déployée, version de PHP et son état de support ; « Télécharger une sauvegarde » me donne un fichier SQL qui restaure le site.`
- **4×N** : `@happy` page santé (table de fin de support PHP embarquée), journal applicatif hors racine avec rotation 30 j ; export SQL en flux, rappel si aucun export depuis 30 j · `@negative` journal non inscriptible → alerte dans l'admin ; export interrompu → aucun fichier partiel · `@edge` PHP sans support de sécurité → bandeau rouge ; panne SMTP → bandeau (pas d'e-mail) ; aller-retour export → import → P1 vert en CI · `@security` alerte e-mail après 5 échecs de connexion ; export exige un TOTP récent ; identifiants hachés dans le journal.
- **Couches** : Infra · Frontend · Monitoring · **Taille** : M · **Tours** : 8

---

## Stories transverses

- **Documentation vivante** : P1 porté par 3.7, P2 par 3.8, **P3 par 2.7** (dernière story de la chaîne d'édition 2.3 → 2.6 → 2.7), **P4 par 3.11** — chacun rejoué en e2e + visuel + vitrine. Parcours filmés « critiques » : défaut **P1 et P3**, confirmés par le PO au bootstrap.
- **i18n** : portée par 2.2 puis par chaque story à écran (libellés FR/NL/EN dans la même PR).
- **Émergence** : réserve de **20 %**.
- **Scaling / ITIL** : non activées (un seul flux, pas d'équipe d'exploitation) ; 5.2 tient lieu de supervision.

## Ordre et dépendances

```
0.0 → 0.1 → 0.2 → 0.3 → 0.4 → 0.5      (Sprint 0 : barrière, rien ne commence avant)
                   ↓
1.1 → 1.2 → 1.3 → 1.4                   (accès : prérequis de toute édition)
                   ↓
2.1 → 2.2 → { 2.3, 2.4, 2.5 } → 2.6 → 2.7 (P3)
                   ↓
3.1 → { 3.2, 3.3 } → 3.4 → 3.5 → 3.11 (P4) → 3.6 → 4.1 → 3.7 (P1) → { 3.8 (P2), 3.9 } → 4.2 → 2.8 → 3.10
                                                                                                  ↓
                                                                                            5.2 → 5.1
```
Chaque story ne teste que ce que les stories **précédentes** ont livré : les garde-fous agenda des stories de
vitrine sont regroupés en 3.11 (après 3.5), et les e-mails (4.1) précèdent les parcours qui les vérifient.

## Estimation chiffrée

| Couche dominante | Stories | Σ jours | Σ tours |
|---|---|---|---|
| Fondations (IaC / CI / harnais) | 0.0–0.5 | 4,50 | 48 |
| Accès | 1.1–1.4 | 3,00 | 32 |
| Vitrine | 2.1–2.8 | 6,50 | 68 |
| Agenda & réservation | 3.1–3.11 | 8,00 | 86 |
| Notification | 4.1–4.2 | 1,25 | 14 |
| Mise en ligne / monitoring | 5.1–5.2 | 1,50 | 16 |
| **Sous-total (33 stories)** | | **24,75** | **264** |
| Émergence (20 %) | | 4,95 | 53 |
| **Total** | | **≈ 30 j** | **≈ 317 tours** |

- **Coût superviseur** : ≈ 30 j de supervision (v1.1 : +1,5 j pour les stories 0.0, 3.11 et l'export de sauvegarde).
- **Coût modèle** : 317 tours × (0,4 Mtok × 0,85 € + 0,04 Mtok × 2,55 €) ≈ 317 × 0,44 € ≈ **140 €**.
- **Comparaison à la target** : la target du Brief (≤ 15 j) a été recalée par le PO à **28 j** (une seule release,
  proposition R1/R2 écartée) ; les corrections du Validateur la portent à **≈ 30 j** (+7 %). Le Brief comptait ~20 stories ;
  le découpage en compte 33, car la réservation fiable (verrous, concurrence, changements d'heure), le double facteur
  et l'installation sans ligne de commande pèsent plus que prévu.

**Décision du PO (2026-10-22) : une seule release, ≈ 28 j**, puis enveloppe de **≈ 30 j acceptée** après les corrections du Validateur.

# Référence de design — maquette « Le Corbeau »

> Source : maquette HTML fournie par le PO le 2026-10-22 (`Le Corbeau Barbier.html`, non versionnée
> ici — le PO peut la déposer dans `docs/design/` pour en garder l'original). C'est la **base visuelle**
> du site. Les contenus (nom, adresse, téléphone, barbiers, prix) sont des **exemples** : ils deviennent
> du contenu éditable.
>
> Seconde pièce fournie : un aperçu ThemeForest « Salon — Barbershop & Tattoo Studio WordPress Theme ».
> Le fichier ne contient qu'un cadre (iframe) dont la page source n'est pas jointe : aucun design
> exploitable. C'est de plus un **thème payant sous licence** : inspiration d'ambiance au plus, rien
> n'en est copié.

## Identité visuelle

- **Thème unique sombre** (noir assumé), filets argent, losange comme séparateur de sections.
- Jetons de couleur : `--noir #0a0a0b` · `--charbon #141416` · `--graphite #232326` · `--fer #4a4b50`
  · `--argent #a9abb0` · `--argent-clair #d4d5d8` · `--os #f1f0ec` · `--blanc #ffffff`.
- Polices (Google Fonts) : **UnifrakturMaguntia** (titres, logo), **Cormorant Garamond** (texte),
  **Cinzel** (petites capitales, libellés, boutons).
- Signature : **enseigne de barbier animée** (bande rayée verticale à gauche du hero), désactivée si
  `prefers-reduced-motion`.
- Boutons : plein blanc / fantôme bordé, capitales espacées.
- Responsive : une colonne sous 860 px, navigation masquée sous 860 px, formulaire une colonne sous 560 px.

## Sections de la page d'accueil (dans l'ordre)

1. **En-tête collant** : logo, navigation (La maison · Tarifs · Barbiers · Horaires), bouton « Réserver ».
2. **Hero** : surtitre (« Barbier · Bruxelles · Sur rendez-vous »), nom, accroche, deux actions,
   trois faits courts (rasage, jours d'ouverture, nombre de fauteuils).
3. **La maison** : trois paragraphes (lettrine) + encadré « Les règles de la maison » (liste numérotée romaine).
4. **Tarifs** (« La carte ») : deux catégories (*Cheveux*, *Barbe & rasage*), chaque prestation =
   nom · prix · description · durée. Note de bas (carte cadeau).
5. **Équipe** (« Trois barbiers ») : carte par barbier = monogramme (ou photo) · spécialité · prénom · bio.
6. **Réservation** : formulaire nom · téléphone · prestation · barbier (ou sans préférence) · jour ·
   heure · précisions ; refus des jours fermés ; récapitulatif « appelez pour confirmer ».
7. **Horaires & adresse** : tableau des 7 jours (jour courant mis en avant, jours fermés grisés),
   adresse, téléphone (bouton copier), accès.
8. **Pied de page** : logo + devise.

## Contenu d'exemple (à reprendre comme données initiales)

| Catégorie | Prestation | Prix | Durée |
|---|---|---|---|
| Cheveux | Coupe classique | 28 € | 45 min |
| Cheveux | Coupe aux ciseaux | 34 € | 60 min |
| Cheveux | Dégradé à blanc | 32 € | 45 min |
| Cheveux | Enfant, moins de 12 ans | 18 € | 30 min |
| Barbe & rasage | Taille de barbe | 22 € | 30 min |
| Barbe & rasage | Rasage à l'ancienne | 35 € | 40 min |
| Barbe & rasage | Coupe & barbe | 45 € | 75 min |
| Barbe & rasage | Le Rituel du Corbeau | 65 € | 100 min |

Barbiers : Antoine (fondateur, rasage lame droite), Samir (coupes aux ciseaux), Léa (barbes & dégradés).
Horaires : lundi et dimanche fermés · mar–mer 10:00–19:00 · jeu–ven 10:00–21:00 · sam 09:00–17:00.

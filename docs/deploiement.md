# Déploiement sur OVH Perso — procédure du PO

*Aucune ligne de commande côté serveur : un paquet, un envoi SFTP, une page d'installation. Toujours la
recette (`test.<domaine>`) d'abord, la production ensuite, avec le même paquet (ADR-008).*

## 0. Une fois : préparer l'offre

1. Commander OVH Perso (datacenter **GRA**), après confirmation des points de `docs/hebergement.md`.
2. Espace client OVH :
   - **PHP 8.4** : fourni par le fichier `.ovhconfig` du paquet (rien à faire) ;
   - **bases** : créer deux bases MySQL, `recette` et `production` ; noter hôte, nom, utilisateur, mot de passe ;
   - **multisite** : `test.<domaine>` → dossier `recette/www`, `<domaine>` → dossier `www` (racine) ;
   - **e-mail** : créer `reservations@<domaine>` (MX Plan) ; activer **DKIM** ; vérifier l'enregistrement **SPF** ;
   - **SFTP** : activer « FTP-SSH » et noter l'identifiant.

## 1. Obtenir le paquet

Artefact `paquet` de la CI du commit promu sur `main`, ou en local : `bash harness/package.sh`
→ `dist/site-barbier-<version>.zip` + `.sha256`. Vérifier : `sha256sum -c dist/site-barbier-<version>.zip.sha256`.

## 2. Envoyer en SFTP (jamais en FTP non chiffré)

Décompresser le zip **sur ton poste**, puis envoyer son contenu :
- recette : dans `recette/` (là où se trouve `recette/www/`) ;
- production : à la racine (là où se trouve `www/`).

`app/` se retrouve **à côté** de `www/`, hors de la partie publique : c'est voulu.

## 3. Configurer (`app/config.php`, créé sur le serveur, jamais dans le dépôt)

Copier `app/config.example.php` en `app/config.php` et remplacer les `getenv(...)` par les valeurs :

| Clé | Valeur |
|---|---|
| `env` | `prod` |
| `app_key` | 32 octets aléatoires en base64 — générer sur ton poste : `php -r 'echo base64_encode(random_bytes(32)), "\n";'` ou `openssl rand -base64 32`. **À garder précieusement** (sans elle, il faut réactiver le double facteur). |
| `install_code` | une phrase de 12 caractères au moins, connue de toi seul (sert une fois) |
| `base_url` | `https://test.<domaine>` (recette) ou `https://<domaine>` (production) |
| `db` | les accès de la base correspondante |
| `smtp` | `host` `ssl0.ovh.net`, `port` 587, `secure` `tls`, `username`/`from` `reservations@<domaine>`, `password` celui de la boîte |

## 4. Installer

Ouvrir `https://test.<domaine>/install` : contrôle de l'hébergement (tout doit être vert), création des
tables, compte du gérant, activation du double facteur, codes de secours à imprimer.

## 5. Tâche planifiée (purge à 6 mois)

Espace client OVH → Hébergement → Tâches planifiées – Cron : commande `app/bin/cron.php`, langage PHP 8.4,
chaque jour à 03:00. (Sans elle, la purge se fait au premier passage du jour.)

## 6. Recette

Sur `test.<domaine>` : réserver un créneau (e-mail reçu ?), l'annuler par le lien, modifier un prix, envoyer une
photo, télécharger une sauvegarde depuis le tableau de bord. Puis remplacer les contenus d'exemple (adresse,
téléphone, e-mail, numéro BCE, photos).

## 7. Production

Même paquet, à la racine, avec son propre `app/config.php` et sa base. Refaire `/install`.

## 8. Mises à jour suivantes

Nouveau paquet promu → recette → production : envoyer en SFTP **sans toucher à `app/config.php` ni à
`www/uploads/`** ; si le tableau de bord annonce « Mise à jour de la base en attente », cliquer « Mettre à jour
la base » (code TOTP demandé).

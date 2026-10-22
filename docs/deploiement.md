# Déploiement sur OVH Perso — procédure du PO

*Aucune ligne de commande côté serveur : un paquet, un envoi SFTP, une page d'installation.*

## 1. Obtenir le paquet

Le paquet est produit par la CI (artefact du commit promu sur `main`) ou en local :

```bash
bash harness/package.sh      # → dist/site-barbier-<version>.zip + .sha256
```

Vérifier l'empreinte : `sha256sum -c dist/site-barbier-<version>.zip.sha256`.

## 2. Envoyer en SFTP (jamais en FTP non chiffré)

1. Activer SFTP dans l'espace client OVH (hébergement → FTP-SSH).
2. Décompresser le zip **sur ton poste**, puis envoyer son contenu à la **racine** de l'espace
   (là où se trouve déjà `www/`) : `app/`, `www/`, `openapi/`, `.ovhconfig`, `VERSION`.
3. `app/` se retrouve **à côté** de `www/`, hors de la partie publique : c'est voulu.

## 3. Configurer

Copier `app/config.example.php` en `app/config.php` **sur le serveur** et y renseigner les accès à la base
(espace client OVH → Bases de données). Ce fichier n'est jamais dans le dépôt ni dans le paquet.

## 4. Installer

Ouvrir `https://<domaine>/install` (Story 1.1) : vérification de l'environnement, création des tables,
création du compte du gérant, activation du double facteur.

## 5. Recette puis production (ADR-008)

Toujours `test.<domaine>` d'abord (même paquet, sa propre base), parcours vérifiés, puis la production.

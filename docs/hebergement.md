# Choix de l'hébergement — grille de la Story 0.0

*Étude menée le 2026-10-22 sur les sites des hébergeurs (prix et versions vérifiés ce jour-là ; « ? » = non
vérifié). Prix TTC calculés à la TVA belge de 21 % ; si le salon facture avec un numéro de TVA, le prix HT
compte. Cible : PHP 8.4, MariaDB 10.11+ (ou MySQL 8), ≤ 5 € TTC/mois. **Éliminatoires** : sauvegardes
quotidiennes restaurables (9), juridiction UE (10).*

## Offres conformes aux critères éliminatoires

| Offre | Prix TTC | PHP | Base | SFTP | .htaccess / ini | cron | SMTP | Sous-domaine + base | Sauvegardes | UE | HTTPS | Hors www |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **alwaysdata Small** | **6,05 €/mois** (5 € HT, annuel) | ✓ 8.4, 8.5 | ✓ MariaDB 10.11/11.4/11.8, bases illimitées, **droits par utilisateur** | SSH ✓, SFTP ? | ? | ✓ | ✓ 465/587, SPF/DMARC/DKIM | ✓ illimités | ✓ quotidiennes, **7 j**, restauration en libre-service | ✓ France | ? | ? (probable) |
| **OVH Perso** (datacenter GRA) | **4,83 €/mois sur 48 mois** payés d'avance (191,52 € HT) ; 7,25 € sur 12 mois | ✓ 8.4, 8.5 | MySQL 8 (non officiel), 5 bases, **un seul utilisateur par base** | ✓ SFTP, pas de SSH | .htaccess ✓, **pas de php.ini** (`.ovhconfig`) | ✓ | ✓ DKIM, 200 mails/h | ✓ 15 sites | ✓ quotidiennes, 30 j, « non contractuelles », restauration ? | ✓ France (GRA) | ✓ | ✓ |
| o2switch Grow | 8,47 € | ? (8.4 à confirmer) | MariaDB **10.6** (fin de vie) | ✓ | php.ini ✓ | ✓ | ✓ | ✓ | ✓ 30 j | ✓ | ✓ | ? |
| Combell Business (BE) | 7,25 € la 1re année, **23,58 €** ensuite | ✓ | MySQL 8, 5 bases | ? | ? | ✓ | ? | ✓ | ✓ 14 j | ✓ Bruxelles | ✓ | ? |
| LWS Perso | 4,83 € (déduit) | ✓ 8.4 | **une seule base** → pas de site de test | FTPS | ? | ✓ | ? | ✗ | ? | ✓ | ✓ | ? |

**Écartées** : Hostinger Premium (sauvegardes hebdomadaires), Infomaniak (Suisse, hors UE), PlanetHoster
(société canadienne), OVH Pro (> budget), one.com et Gandi (prix ou offre non vérifiables).

## Conséquences pour le projet selon l'offre

| | alwaysdata Small | OVH Perso 48 mois |
|---|---|---|
| Budget ≤ 5 € TTC | dépassé de ~1 €/mois (pile 5 € HT) | tenu, mais 4 ans payés d'avance |
| Pile testée en CI | MariaDB 10.11 (déjà en place) | **passer la CI sur MySQL 8.0** |
| ADR-010 (moindre privilège base) | **deux comptes possibles** | résidu accepté : un seul compte |
| Sauvegardes | 7 j chez l'hébergeur + export mensuel du gérant (FR-044) indispensable | 30 j, mais non contractuelles |
| Points à vérifier avant commande | SFTP, `.htaccess`, `.user.ini`, Let's Encrypt, racine hors www | tarif 48 mois à la commande, prix belge, procédure de restauration, version MySQL |
| Extensions `intl` et `sodium` | à vérifier | à vérifier |

**Décision du PO** : *à prendre* (point irréversible : 0.4 reproduit l'offre retenue, 5.1 y déploie).

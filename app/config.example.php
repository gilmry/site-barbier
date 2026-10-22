<?php

declare(strict_types=1);

/**
 * Gabarit de configuration. Sur l'hébergement : copier en app/config.php (jamais versionné, gate G1)
 * et renseigner les vraies valeurs. En développement et en test, les variables d'environnement suffisent.
 * Aucun secret réel ici.
 *
 *  app_key       32 octets aléatoires en base64 (chiffrement des secrets TOTP, empreintes de jetons).
 *                Générer : php -r 'echo base64_encode(random_bytes(32)), "\n";'
 *                La perdre oblige à réactiver le double facteur (FR-044).
 *  install_code  code à usage unique (12 caractères au moins) demandé par /install.
 *  env           prod (défaut : cookies Secure, HTTPS exigé) | test | dev.
 *  base_url      adresse publique (https://www.lecorbeau.be) pour les liens des e-mails ; sinon l'hôte de la requête.
 *  smtp          compte e-mail du domaine chez l'hébergeur (OVH : ssl0.ovh.net, port 587, tls) — ADR-009.
 *  retention_months  durée de conservation des réservations (défaut 6, affichée dans la politique).
 */
return [
    'env' => getenv('BARBIER_ENV') ?: 'prod',
    'app_key' => getenv('APP_KEY') ?: '',
    'install_code' => getenv('INSTALL_CODE') ?: '',
    'base_url' => getenv('BASE_URL') ?: '',
    'retention_months' => 6,
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: '',
        'port' => (int) (getenv('SMTP_PORT') ?: 587),
        'secure' => getenv('SMTP_SECURE') ?: 'tls',
        'username' => getenv('SMTP_USER') ?: '',
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'from' => getenv('SMTP_FROM') ?: '',
        'from_name' => 'Le Corbeau',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: 'barbier',
        'username' => getenv('DB_USER') ?: 'barbier',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];

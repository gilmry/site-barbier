<?php

declare(strict_types=1);

/**
 * Gabarit de configuration. Sur l'hébergement : copier en app/config.php (jamais versionné, gate G1)
 * et renseigner les vraies valeurs. En développement, les variables d'environnement suffisent.
 * Aucun secret réel ici.
 */
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: 'barbier',
        'username' => getenv('DB_USER') ?: 'barbier',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];

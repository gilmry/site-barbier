<?php

declare(strict_types=1);

/**
 * Routeur du serveur PHP intégré (développement, e2e) — reproduit la règle du .htaccess :
 * un fichier existant de www/ est servi tel quel, tout le reste va au contrôleur frontal.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = dirname(__DIR__) . '/www' . $path;
if ($path !== '/' && is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}
require dirname(__DIR__) . '/www/index.php';
return true;

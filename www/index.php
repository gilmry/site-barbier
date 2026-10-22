<?php

declare(strict_types=1);

/**
 * Contrôleur frontal unique. Le code applicatif vit dans ../app, hors de la racine web.
 * Toute requête qui ne vise pas un fichier existant arrive ici (.htaccess, harness/dev-router.php).
 */
use Barbier\Http\Request;
use Barbier\Kernel;

$app = dirname(__DIR__) . '/app';
try {
    require $app . '/src/bootstrap.php';
    $config = require (is_file($app . '/config.php') ? $app . '/config.php' : $app . '/config.example.php');
    (new Kernel($config))->handle(Request::fromGlobals())->send();
} catch (Throwable) {
    http_response_code(500);
    header('Content-Type: application/problem+json; charset=utf-8');
    echo '{"type":"about:blank","title":"Erreur interne","status":500}';
}

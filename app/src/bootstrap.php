<?php

declare(strict_types=1);

/**
 * Autoloader PSR-4 minimal : préfixe Barbier\ → app/src/. Les dépendances embarquées (vendor/,
 * stories suivantes) ont leur propre autoloader, chargé s'il existe.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Barbier\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$vendor = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
}

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/src/bootstrap.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Barbier\\Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $path = dirname(__DIR__) . '/tests/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

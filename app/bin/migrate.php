<?php

declare(strict_types=1);

/**
 * Outil de développement et de CI : `php app/bin/migrate.php up|down|status`.
 * Sur l'hébergement (sans ligne de commande), les migrations passent par /install et l'admin (FR-042).
 */
use Barbier\Adapter\Persistence\Database;
use Barbier\Adapter\Persistence\Migrator;

require dirname(__DIR__) . '/src/bootstrap.php';
$app = dirname(__DIR__);
$config = require (is_file($app . '/config.php') ? $app . '/config.php' : $app . '/config.example.php');
$migrator = new Migrator(Database::connect($config['db']), $app . '/migrations');

$cmd = $argv[1] ?? 'status';
$result = match ($cmd) {
    'up' => $migrator->up(),
    'down' => $migrator->downAll(),
    'status' => $migrator->applied(),
    default => null,
};
if ($result === null) {
    fwrite(STDERR, "usage: php app/bin/migrate.php up|down|status\n");
    exit(2);
}
fwrite(STDOUT, $cmd . ': ' . ($result === [] ? '(rien)' : implode(', ', $result)) . "\n");

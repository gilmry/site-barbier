<?php

declare(strict_types=1);

/**
 * Gate `integration` — migrations sur MariaDB réelle : base vide → tout up → tout down (base vide
 * à nouveau) → tout up. Prouve G2 sur le vrai moteur, pas seulement par convention de nommage.
 */
use Barbier\Adapter\Persistence\Database;
use Barbier\Adapter\Persistence\Migrator;

require dirname(__DIR__, 2) . '/app/src/bootstrap.php';
$pdo = Database::connect([
    'host' => getenv('DB_HOST') ?: '127.0.0.1', 'port' => (int) getenv('DB_PORT'),
    'database' => (string) getenv('DB_NAME'), 'username' => (string) getenv('DB_USER'), 'password' => (string) getenv('DB_PASSWORD'),
]);
$migrator = new Migrator($pdo, dirname(__DIR__, 2) . '/app/migrations');
$tables = static fn (): array => $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY 1')->fetchAll(PDO::FETCH_COLUMN);

$fail = 0;
$check = static function (bool $ok, string $label) use (&$fail): void {
    echo ($ok ? '  ✓ ' : '  ✗ ') . $label . "\n";
    $fail += $ok ? 0 : 1;
};

$available = array_keys($migrator->available());
$check($tables() === [], 'base de test vide au départ');
$check($migrator->up() === $available, "up applique toutes les migrations dans l'ordre (" . implode(', ', $available) . ')');
$check($migrator->applied() === $available, 'schema_migration reflète les migrations appliquées');
$check($migrator->up() === [], 'up rejoué : rien à faire (idempotent)');
$migrator->downAll();
$check($tables() === [], 'down de tout : aucune table ne subsiste (chaque down défait son up)');
$check($migrator->up() === $available, 'up après down : le cycle est rejouable');

echo $fail === 0 ? "\nintegration (migrations): 🟢\n" : "\nintegration (migrations): 🔴 ($fail échec(s))\n";
exit($fail === 0 ? 0 : 1);

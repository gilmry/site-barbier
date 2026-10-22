<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use PDO;

/** Fabrique de connexion PDO/MySQL : exceptions, requêtes préparées natives, UTC. */
final class Database
{
    /** @param array{host?:string, port?:int, database?:string, username?:string, password?:string} $db */
    public static function connect(array $db): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $db['host'] ?? 'localhost',
            (int) ($db['port'] ?? 3306),
            $db['database'] ?? '',
        );
        $pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");

        return $pdo;
    }
}

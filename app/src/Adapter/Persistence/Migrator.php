<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use PDO;
use RuntimeException;

/**
 * Applique / révertit les migrations app/migrations/NNNN_nom.{up,down}.sql dans l'ordre.
 * La migration 0001 crée la table de suivi elle-même ; sa réversion la supprime en dernier.
 * MariaDB valide implicitement le DDL : une migration en échec s'arrête net et n'est pas inscrite.
 */
final class Migrator
{
    public function __construct(private readonly PDO $pdo, private readonly string $dir)
    {
    }

    /** @return list<string> versions appliquées par cet appel */
    public function up(): array
    {
        $done = $this->applied();
        $ran = [];
        foreach ($this->available() as $version => $name) {
            if (in_array($version, $done, true)) {
                continue;
            }
            $this->run($this->file($version, $name, 'up'));
            $stmt = $this->pdo->prepare('INSERT INTO schema_migration (version, name, applied_at) VALUES (?, ?, UTC_TIMESTAMP())');
            $stmt->execute([$version, $name]);
            $ran[] = $version;
        }

        return $ran;
    }

    /** Révertit les migrations appliquées, de la plus récente à la plus ancienne. @return list<string> */
    public function downAll(): array
    {
        $available = $this->available();
        $reverted = [];
        foreach (array_reverse($this->applied()) as $version) {
            if (!isset($available[$version])) {
                throw new RuntimeException("Migration appliquée introuvable sur disque : $version");
            }
            if ($version !== '0001') {
                $this->pdo->prepare('DELETE FROM schema_migration WHERE version = ?')->execute([$version]);
            }
            $this->run($this->file($version, $available[$version], 'down'));
            $reverted[] = $version;
        }

        return $reverted;
    }

    /** @return list<string> */
    public function applied(): array
    {
        $exists = $this->pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'schema_migration'"
        )->fetchColumn();
        if ((int) $exists === 0) {
            return [];
        }

        return array_map('strval', $this->pdo->query('SELECT version FROM schema_migration ORDER BY version')->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return array<string,string> version → nom, triés */
    public function available(): array
    {
        $out = [];
        foreach (glob($this->dir . '/*.up.sql') ?: [] as $file) {
            if (preg_match('/^(\d{4})_([a-z0-9_]+)\.up\.sql$/', basename($file), $m) === 1) {
                $out[$m[1]] = $m[2];
            }
        }
        ksort($out, SORT_STRING);

        return $out;
    }

    private function file(string $version, string $name, string $direction): string
    {
        return $this->dir . "/{$version}_{$name}.{$direction}.sql";
    }

    private function run(string $file): void
    {
        $sql = (string) file_get_contents($file);
        foreach (self::statements($sql) as $statement) {
            $this->pdo->exec($statement);
        }
    }

    /** Découpe un fichier SQL en instructions (pas de « ; » dans les littéraux de nos migrations). @return list<string> */
    private static function statements(string $sql): array
    {
        $sql = (string) preg_replace('/--[^\r\n]*/', '', $sql);

        return array_values(array_filter(array_map('trim', explode(';', $sql)), static fn (string $s): bool => $s !== ''));
    }
}

<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use Barbier\Application\Exploitation\BackupExporter;
use PDO;

/**
 * Sauvegarde SQL (FR-044) : schéma (SHOW CREATE TABLE) + données (INSERT par lots), dans l'ordre qui respecte
 * les clés étrangères à la réimportation (FOREIGN_KEY_CHECKS désactivé le temps du chargement).
 * Les sessions et compteurs de débit ne sont pas sauvegardés (données éphémères).
 */
final class PdoBackupExporter implements BackupExporter
{
    private const SKIP_DATA = ['session', 'rate_limit'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function export(): string
    {
        $tables = $this->pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
        $out = ["-- Sauvegarde site-barbier — " . gmdate('c') . ' (UTC)', '-- Contient des données personnelles : à conserver 6 mois au plus, en lieu sûr.', 'SET NAMES utf8mb4;', 'SET FOREIGN_KEY_CHECKS = 0;', ''];
        foreach ($tables as $table) {
            $t = (string) $table;
            if (preg_match('/^[a-z_]+$/', $t) !== 1) {
                continue;
            }
            $create = $this->pdo->query('SHOW CREATE TABLE `' . $t . '`')->fetch(PDO::FETCH_NUM);
            $out[] = 'DROP TABLE IF EXISTS `' . $t . '`;';
            $out[] = $create[1] . ';';
            if (!in_array($t, self::SKIP_DATA, true)) {
                $rows = $this->pdo->query('SELECT * FROM `' . $t . '`')->fetchAll(PDO::FETCH_ASSOC);
                foreach (array_chunk($rows, 100) as $chunk) {
                    $values = array_map(fn (array $r): string => '(' . implode(', ', array_map(fn ($v): string => $v === null ? 'NULL' : $this->pdo->quote((string) $v), $r)) . ')', $chunk);
                    $out[] = 'INSERT INTO `' . $t . '` (`' . implode('`, `', array_keys($chunk[0])) . '`) VALUES' . "\n" . implode(",\n", $values) . ';';
                }
            }
            $out[] = '';
        }
        $out[] = 'SET FOREIGN_KEY_CHECKS = 1;';

        return implode("\n", $out) . "\n";
    }
}

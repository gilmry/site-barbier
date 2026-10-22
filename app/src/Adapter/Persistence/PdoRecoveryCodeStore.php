<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use Barbier\Domain\Acces\RecoveryCodeStore;
use PDO;

final class PdoRecoveryCodeStore implements RecoveryCodeStore
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function replace(string $editorId, array $hashes): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM recovery_code WHERE editor_id = ?')->execute([$editorId]);
            $ins = $this->pdo->prepare('INSERT INTO recovery_code (editor_id, code_hash) VALUES (?, ?)');
            foreach ($hashes as $h) {
                $ins->execute([$editorId, $h]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function consume(string $editorId, string $hash, \DateTimeImmutable $now): bool
    {
        $st = $this->pdo->prepare('UPDATE recovery_code SET used_at = ? WHERE editor_id = ? AND code_hash = ? AND used_at IS NULL');
        $st->execute([$now->format('Y-m-d H:i:s'), $editorId, $hash]);

        return $st->rowCount() === 1;
    }

    public function remaining(string $editorId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM recovery_code WHERE editor_id = ? AND used_at IS NULL');
        $st->execute([$editorId]);

        return (int) $st->fetchColumn();
    }
}

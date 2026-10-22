<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use Barbier\Domain\Acces\Editor;
use Barbier\Domain\Acces\EditorRepository;
use PDO;

final class PdoEditorRepository implements EditorRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM editor')->fetchColumn();
    }

    public function find(string $id): ?Editor
    {
        $st = $this->pdo->prepare('SELECT * FROM editor WHERE id = ?');
        $st->execute([$id]);

        return $this->map($st->fetch());
    }

    public function findByEmail(string $email): ?Editor
    {
        $st = $this->pdo->prepare('SELECT * FROM editor WHERE email = ?');
        $st->execute([Editor::normalizeEmail($email)]);

        return $this->map($st->fetch());
    }

    public function save(Editor $e): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO editor (id, email, password_hash, totp_secret_sealed, totp_confirmed, last_totp_step, failed_attempts, locked_until, created_at)
             VALUES (:id, :email, :ph, :ts, :tc, :ls, :fa, :lu, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE email = VALUES(email), password_hash = VALUES(password_hash), totp_secret_sealed = VALUES(totp_secret_sealed),
               totp_confirmed = VALUES(totp_confirmed), last_totp_step = VALUES(last_totp_step), failed_attempts = VALUES(failed_attempts),
               locked_until = VALUES(locked_until)'
        );
        $st->bindValue(':id', $e->id);
        $st->bindValue(':email', Editor::normalizeEmail($e->email));
        $st->bindValue(':ph', $e->passwordHash);
        $st->bindValue(':ts', $e->totpSecretSealed, $e->totpSecretSealed === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $st->bindValue(':tc', $e->totpConfirmed ? 1 : 0, PDO::PARAM_INT);
        $st->bindValue(':ls', $e->lastTotpStep, $e->lastTotpStep === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $st->bindValue(':fa', $e->failedAttempts, PDO::PARAM_INT);
        $st->bindValue(':lu', $e->lockedUntil?->format('Y-m-d H:i:s'));
        $st->execute();
    }

    /** @param array<string,mixed>|false $row */
    private function map(array|false $row): ?Editor
    {
        if ($row === false) {
            return null;
        }

        return new Editor(
            (string) $row['id'],
            (string) $row['email'],
            (string) $row['password_hash'],
            $row['totp_secret_sealed'] === null ? null : (string) $row['totp_secret_sealed'],
            (bool) $row['totp_confirmed'],
            $row['last_totp_step'] === null ? null : (int) $row['last_totp_step'],
            (int) $row['failed_attempts'],
            $row['locked_until'] === null ? null : new \DateTimeImmutable((string) $row['locked_until'], new \DateTimeZone('UTC')),
        );
    }
}

<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use Barbier\Domain\Notification\MailLog;
use PDO;

final class PdoMailLog implements MailLog
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(string $id, ?string $bookingId, string $kind, string $recipient, string $state, ?string $error): void
    {
        $this->pdo->prepare('INSERT INTO mail_log (id, booking_id, kind, recipient, state, error, attempts, created_at, sent_at) VALUES (?, ?, ?, ?, ?, ?, 1, UTC_TIMESTAMP(), ?)')
            ->execute([$id, $bookingId, $kind, $recipient, $state, $error === null ? null : mb_substr($error, 0, 250), $state === 'sent' ? gmdate('Y-m-d H:i:s') : null]);
    }

    public function update(string $id, string $state, ?string $error): void
    {
        $this->pdo->prepare('UPDATE mail_log SET state = ?, error = ?, attempts = attempts + 1, sent_at = ? WHERE id = ?')
            ->execute([$state, $error === null ? null : mb_substr($error, 0, 250), $state === 'sent' ? gmdate('Y-m-d H:i:s') : null, $id]);
    }

    public function recent(int $limit = 100): array
    {
        $st = $this->pdo->prepare('SELECT * FROM mail_log ORDER BY created_at DESC, id LIMIT ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();

        return array_map([$this, 'row'], $st->fetchAll());
    }

    public function find(string $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM mail_log WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();

        return $r === false ? null : $this->row($r);
    }

    public function failedCount(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM mail_log WHERE state = 'failed'")->fetchColumn();
    }

    /** @param array<string,mixed> $r @return array{id:string, bookingId:?string, kind:string, recipient:string, state:string, error:?string, attempts:int, createdAt:string, sentAt:?string} */
    private function row(array $r): array
    {
        return ['id' => (string) $r['id'], 'bookingId' => $r['booking_id'] === null ? null : (string) $r['booking_id'], 'kind' => (string) $r['kind'],
            'recipient' => (string) $r['recipient'], 'state' => (string) $r['state'], 'error' => $r['error'] === null ? null : (string) $r['error'],
            'attempts' => (int) $r['attempts'], 'createdAt' => (string) $r['created_at'], 'sentAt' => $r['sent_at'] === null ? null : (string) $r['sent_at']];
    }
}

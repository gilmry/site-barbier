<?php

declare(strict_types=1);

namespace Barbier\Domain\Notification;

/** Journal des envois (FR-032) : type, destinataire, état, erreur, tentatives — jamais le corps. */
interface MailLog
{
    public function record(string $id, ?string $bookingId, string $kind, string $recipient, string $state, ?string $error): void;

    public function update(string $id, string $state, ?string $error): void;

    /** @return list<array{id:string, bookingId:?string, kind:string, recipient:string, state:string, error:?string, attempts:int, createdAt:string, sentAt:?string}> */
    public function recent(int $limit = 100): array;

    /** @return array{id:string, bookingId:?string, kind:string, recipient:string, state:string, error:?string, attempts:int, createdAt:string, sentAt:?string}|null */
    public function find(string $id): ?array;

    public function failedCount(): int;
}

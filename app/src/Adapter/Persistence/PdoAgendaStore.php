<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Agenda\Booking;
use Barbier\Domain\Agenda\ClientContact;
use Barbier\Domain\Agenda\SlotTaken;
use Barbier\Domain\Agenda\TimeRange;
use PDO;
use PDOException;

final class PdoAgendaStore implements AgendaStore
{
    private const F = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function transactional(callable $fn): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $fn();
        }
        $this->pdo->beginTransaction();
        try {
            $result = $fn();
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function lockBarbers(array $barberIds): void
    {
        $ids = array_values(array_unique($barberIds));
        sort($ids, SORT_STRING); // ordre fixe : pas d'interblocage entre deux écritures concurrentes
        $st = $this->pdo->prepare('SELECT id FROM barber WHERE id = ? FOR UPDATE');
        foreach ($ids as $id) {
            $st->execute([$id]);
            $st->fetchAll();
        }
    }

    public function lockAllBarbers(): void
    {
        $this->pdo->query('SELECT id FROM barber ORDER BY id FOR UPDATE')->fetchAll();
    }

    public function availability(string $barberId): array
    {
        $st = $this->pdo->prepare('SELECT weekday, starts, ends FROM availability WHERE barber_id = ? ORDER BY weekday, starts');
        $st->execute([$barberId]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[(int) $r['weekday']][] = ['starts' => substr((string) $r['starts'], 0, 5), 'ends' => substr((string) $r['ends'], 0, 5)];
        }

        return $out;
    }

    public function setAvailability(string $barberId, array $ranges): void
    {
        $this->pdo->prepare('DELETE FROM availability WHERE barber_id = ?')->execute([$barberId]);
        $ins = $this->pdo->prepare('INSERT INTO availability (id, barber_id, weekday, starts, ends) VALUES (UUID(), ?, ?, ?, ?)');
        foreach ($ranges as $r) {
            $ins->execute([$barberId, $r['weekday'], $r['starts'] . ':00', $r['ends'] . ':00']);
        }
    }

    public function busy(string $barberId, TimeRange $window): array
    {
        $out = [];
        $st = $this->pdo->prepare("SELECT starts_at, ends_at FROM booking WHERE barber_id = ? AND state = 'confirmed' AND starts_at < ? AND ends_at > ?");
        $st->execute([$barberId, $window->end->format(self::F), $window->start->format(self::F)]);
        foreach ($st->fetchAll() as $r) {
            $out[] = TimeRange::utc((string) $r['starts_at'], (string) $r['ends_at']);
        }
        $st = $this->pdo->prepare('SELECT starts_at, ends_at FROM absence WHERE (barber_id = ? OR barber_id IS NULL) AND starts_at < ? AND ends_at > ?');
        $st->execute([$barberId, $window->end->format(self::F), $window->start->format(self::F)]);
        foreach ($st->fetchAll() as $r) {
            $out[] = TimeRange::utc((string) $r['starts_at'], (string) $r['ends_at']);
        }

        return $out;
    }

    public function countConfirmedByBarber(TimeRange $window): array
    {
        $st = $this->pdo->prepare("SELECT barber_id, COUNT(*) AS n FROM booking WHERE state = 'confirmed' AND starts_at >= ? AND starts_at < ? GROUP BY barber_id");
        $st->execute([$window->start->format(self::F), $window->end->format(self::F)]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[(string) $r['barber_id']] = (int) $r['n'];
        }

        return $out;
    }

    public function insertBooking(Booking $b, string $cancelTokenHash, array $cells): void
    {
        $this->pdo->prepare('INSERT INTO booking (id, barber_id, service_id, starts_at, ends_at, price_cents, duration_min, state, lang, client_name, client_phone, client_email, cancel_token_hash, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())')
            ->execute([$b->id, $b->barberId, $b->serviceId, $b->range->start->format(self::F), $b->range->end->format(self::F), $b->priceCents, $b->durationMin,
                $b->state, $b->lang, $b->client->name, $b->client->phone, $b->client->email, $cancelTokenHash]);
        $ins = $this->pdo->prepare('INSERT INTO booking_slot (barber_id, slot_start, booking_id) VALUES (?, ?, ?)');
        try {
            foreach ($cells as $cell) {
                $ins->execute([$b->barberId, $cell, $b->id]);
            }
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                throw new SlotTaken(); // filet en base : la transaction appelante est annulée
            }
            throw $e;
        }
    }

    public function find(string $id): ?Booking
    {
        $st = $this->pdo->prepare('SELECT * FROM booking WHERE id = ?');
        $st->execute([$id]);

        return $this->map($st->fetch());
    }

    public function findByTokenHash(string $hash): ?Booking
    {
        $st = $this->pdo->prepare('SELECT * FROM booking WHERE cancel_token_hash = ?');
        $st->execute([$hash]);

        return $this->map($st->fetch());
    }

    public function markCancelled(Booking $b): void
    {
        $this->pdo->prepare('UPDATE booking SET state = ?, cancelled_by = ? WHERE id = ?')->execute([$b->state, $b->cancelledBy, $b->id]);
        $this->pdo->prepare('DELETE FROM booking_slot WHERE booking_id = ?')->execute([$b->id]);
    }

    public function replaceToken(string $bookingId, string $newHash): void
    {
        $this->pdo->prepare('UPDATE booking SET cancel_token_hash = ? WHERE id = ?')->execute([$newHash, $bookingId]);
    }

    public function futureBookings(\DateTimeImmutable $now, ?string $barberId = null, ?string $serviceId = null): array
    {
        $st = $this->pdo->prepare("SELECT b.*, br.first_name FROM booking b JOIN barber br ON br.id = b.barber_id
            WHERE b.state = 'confirmed' AND b.starts_at > ? AND (? IS NULL OR b.barber_id = ?) AND (? IS NULL OR b.service_id = ?) ORDER BY b.starts_at");
        $st->execute([$now->format(self::F), $barberId, $barberId, $serviceId, $serviceId]);

        return array_map([$this, 'row'], $st->fetchAll());
    }

    public function overlapping(?string $barberId, TimeRange $range): array
    {
        $st = $this->pdo->prepare("SELECT b.*, br.first_name FROM booking b JOIN barber br ON br.id = b.barber_id
            WHERE b.state = 'confirmed' AND b.starts_at < ? AND b.ends_at > ? AND (? IS NULL OR b.barber_id = ?) ORDER BY b.starts_at");
        $st->execute([$range->end->format(self::F), $range->start->format(self::F), $barberId, $barberId]);

        return array_map([$this, 'row'], $st->fetchAll());
    }

    public function listBookings(TimeRange $window, ?string $barberId = null, ?string $state = null): array
    {
        $st = $this->pdo->prepare("SELECT b.*, br.first_name,
              (SELECT t.value FROM translation t WHERE t.owner_type = 'service' AND t.owner_id = b.service_id AND t.field = 'name' AND t.lang = 'fr') AS service_name
            FROM booking b JOIN barber br ON br.id = b.barber_id
            WHERE b.starts_at >= ? AND b.starts_at < ? AND (? IS NULL OR b.barber_id = ?) AND (? IS NULL OR b.state = ?) ORDER BY b.starts_at, br.position");
        $st->execute([$window->start->format(self::F), $window->end->format(self::F), $barberId, $barberId, $state, $state]);

        return array_map([$this, 'row'], $st->fetchAll());
    }

    public function addAbsence(string $id, ?string $barberId, TimeRange $range): void
    {
        $this->pdo->prepare('INSERT INTO absence (id, barber_id, starts_at, ends_at) VALUES (?, ?, ?, ?)')
            ->execute([$id, $barberId, $range->start->format(self::F), $range->end->format(self::F)]);
    }

    public function deleteAbsence(string $id): bool
    {
        $st = $this->pdo->prepare('DELETE FROM absence WHERE id = ?');
        $st->execute([$id]);

        return $st->rowCount() === 1;
    }

    public function absences(\DateTimeImmutable $from): array
    {
        $st = $this->pdo->prepare('SELECT * FROM absence WHERE ends_at > ? ORDER BY starts_at');
        $st->execute([$from->format(self::F)]);

        return array_map(static fn (array $r): array => ['id' => (string) $r['id'], 'barberId' => $r['barber_id'] === null ? null : (string) $r['barber_id'],
            'startsAt' => (string) $r['starts_at'], 'endsAt' => (string) $r['ends_at']], $st->fetchAll());
    }

    public function closures(TimeRange $window): array
    {
        $st = $this->pdo->prepare('SELECT * FROM absence WHERE barber_id IS NULL AND starts_at < ? AND ends_at > ? ORDER BY starts_at');
        $st->execute([$window->end->format(self::F), $window->start->format(self::F)]);

        return array_map(static fn (array $r): array => ['id' => (string) $r['id'], 'barberId' => null, 'startsAt' => (string) $r['starts_at'], 'endsAt' => (string) $r['ends_at']], $st->fetchAll());
    }

    public function recordStat(string $month, int $booked, int $cancelled): void
    {
        $this->pdo->prepare('INSERT INTO booking_stat (month, booked, cancelled) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE booked = booked + VALUES(booked), cancelled = cancelled + VALUES(cancelled)')
            ->execute([$month, $booked, $cancelled]);
    }

    public function purgeBefore(\DateTimeImmutable $cutoffUtc): int
    {
        $st = $this->pdo->prepare('DELETE FROM booking WHERE starts_at < ?');
        $st->execute([$cutoffUtc->format(self::F)]);

        return $st->rowCount();
    }

    public function byEmail(string $email): array
    {
        $st = $this->pdo->prepare('SELECT b.*, br.first_name FROM booking b JOIN barber br ON br.id = b.barber_id WHERE b.client_email = ? ORDER BY b.starts_at');
        $st->execute([mb_strtolower(trim($email))]);

        return array_map([$this, 'row'], $st->fetchAll());
    }

    public function delete(string $bookingId): void
    {
        $this->pdo->prepare('DELETE FROM booking WHERE id = ?')->execute([$bookingId]);
    }

    public function hit(string $bucket, string $keyHash, \DateTimeImmutable $windowStart): int
    {
        $this->pdo->prepare('INSERT INTO rate_limit (bucket, key_hash, window_start, hits) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE hits = hits + 1')
            ->execute([$bucket, $keyHash, $windowStart->format(self::F)]);
        $st = $this->pdo->prepare('SELECT hits FROM rate_limit WHERE bucket = ? AND key_hash = ? AND window_start = ?');
        $st->execute([$bucket, $keyHash, $windowStart->format(self::F)]);

        return (int) $st->fetchColumn();
    }

    public function hits(string $bucket, string $keyHash, \DateTimeImmutable $windowStart): int
    {
        $st = $this->pdo->prepare('SELECT hits FROM rate_limit WHERE bucket = ? AND key_hash = ? AND window_start = ?');
        $st->execute([$bucket, $keyHash, $windowStart->format(self::F)]);

        return (int) $st->fetchColumn();
    }

    public function purgeRateLimits(\DateTimeImmutable $before): void
    {
        $this->pdo->prepare('DELETE FROM rate_limit WHERE window_start < ?')->execute([$before->format(self::F)]);
    }

    public function state(string $key): ?string
    {
        $st = $this->pdo->prepare('SELECT state_value FROM app_state WHERE state_key = ?');
        $st->execute([$key]);
        $v = $st->fetchColumn();

        return $v === false ? null : (string) $v;
    }

    public function setState(string $key, string $value): void
    {
        $this->pdo->prepare('INSERT INTO app_state (state_key, state_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE state_value = VALUES(state_value)')->execute([$key, $value]);
    }

    /** @param array<string,mixed> $r @return array<string,mixed> */
    private function row(array $r): array
    {
        return ['id' => (string) $r['id'], 'barberId' => (string) $r['barber_id'], 'barberName' => (string) ($r['first_name'] ?? ''), 'serviceId' => (string) $r['service_id'],
            'serviceName' => isset($r['service_name']) ? (string) $r['service_name'] : null, 'startsAt' => (string) $r['starts_at'], 'endsAt' => (string) $r['ends_at'],
            'priceCents' => (int) $r['price_cents'], 'durationMin' => (int) $r['duration_min'], 'state' => (string) $r['state'], 'lang' => (string) $r['lang'],
            'clientName' => (string) $r['client_name'], 'clientPhone' => (string) $r['client_phone'], 'clientEmail' => (string) $r['client_email'],
            'cancelledBy' => $r['cancelled_by'] === null ? null : (string) $r['cancelled_by'], 'createdAt' => (string) $r['created_at']];
    }

    /** @param array<string,mixed>|false $r */
    private function map(array|false $r): ?Booking
    {
        if ($r === false) {
            return null;
        }

        return new Booking((string) $r['id'], (string) $r['barber_id'], (string) $r['service_id'], TimeRange::utc((string) $r['starts_at'], (string) $r['ends_at']),
            (int) $r['price_cents'], (int) $r['duration_min'], (string) $r['lang'], new ClientContact((string) $r['client_name'], (string) $r['client_phone'], (string) $r['client_email']),
            (string) $r['state'], $r['cancelled_by'] === null ? null : (string) $r['cancelled_by']);
    }
}

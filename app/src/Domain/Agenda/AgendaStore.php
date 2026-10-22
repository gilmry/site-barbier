<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

/**
 * Port de l'agenda. Les écritures qui touchent l'agenda d'un barbier s'exécutent dans `transactional()`
 * après `lockBarbers()` (ou `lockAllBarbers()` pour les changements à l'échelle du salon), dans l'ordre des
 * identifiants : une seule sérialisation pour réserver, annuler, purger, poser une absence, changer des
 * disponibilités, désactiver un barbier ou dépublier une prestation (ADR-006).
 */
interface AgendaStore
{
    /** @template T @param callable():T $fn @return T */
    public function transactional(callable $fn): mixed;

    /** @param list<string> $barberIds */
    public function lockBarbers(array $barberIds): void;

    public function lockAllBarbers(): void;

    /** @return array<int, list<array{starts:string, ends:string}>> weekday → plages ; jours absents = horaires du salon */
    public function availability(string $barberId): array;

    /** @param list<array{weekday:int, starts:string, ends:string}> $ranges */
    public function setAvailability(string $barberId, array $ranges): void;

    /** Réservations confirmées et absences (du barbier ou du salon) qui touchent l'intervalle. @return list<TimeRange> */
    public function busy(string $barberId, TimeRange $window): array;

    /** @return array<string,int> barberId → réservations confirmées dans l'intervalle */
    public function countConfirmedByBarber(TimeRange $window): array;

    /** @param list<string> $cells @throws SlotTaken */
    public function insertBooking(Booking $b, string $cancelTokenHash, array $cells): void;

    public function find(string $id): ?Booking;

    public function findByTokenHash(string $hash): ?Booking;

    /** Enregistre l'annulation et libère les cellules (même transaction). */
    public function markCancelled(Booking $b): void;

    public function replaceToken(string $bookingId, string $newHash): void;

    /** Réservations confirmées futures qui sortiraient des règles. @return list<array<string,mixed>> */
    public function futureBookings(\DateTimeImmutable $now, ?string $barberId = null, ?string $serviceId = null): array;

    /** Réservations confirmées qui chevauchent un intervalle (barbier, ou tous si null). @return list<array<string,mixed>> */
    public function overlapping(?string $barberId, TimeRange $range): array;

    /** @return list<array<string,mixed>> réservations d'une période, pour le planning */
    public function listBookings(TimeRange $window, ?string $barberId = null, ?string $state = null): array;

    public function addAbsence(string $id, ?string $barberId, TimeRange $range): void;

    public function deleteAbsence(string $id): bool;

    /** @return list<array{id:string, barberId:?string, startsAt:string, endsAt:string}> absences qui finissent après $from */
    public function absences(\DateTimeImmutable $from): array;

    /** @return list<array{id:string, barberId:?string, startsAt:string, endsAt:string}> fermetures du salon dans l'intervalle */
    public function closures(TimeRange $window): array;

    public function recordStat(string $month, int $booked, int $cancelled): void;

    /** Purge (FR-027) : réservations dont le rendez-vous est antérieur à $cutoffUtc (cellules et journal en cascade). */
    public function purgeBefore(\DateTimeImmutable $cutoffUtc): int;

    /** @return list<array<string,mixed>> réservations d'une adresse e-mail (FR-012) */
    public function byEmail(string $email): array;

    /** Efface une réservation (FR-012) : cellules et journal en cascade. */
    public function delete(string $bookingId): void;

    /** Incrémente un compteur de débit ; @return int nombre de passages dans la fenêtre */
    public function hit(string $bucket, string $keyHash, \DateTimeImmutable $windowStart): int;

    /** Lit un compteur de débit sans l'incrémenter. */
    public function hits(string $bucket, string $keyHash, \DateTimeImmutable $windowStart): int;

    public function purgeRateLimits(\DateTimeImmutable $before): void;

    public function state(string $key): ?string;

    public function setState(string $key, string $value): void;
}

<?php

declare(strict_types=1);

namespace Barbier\Application\Agenda;

use Barbier\Application\Notification\NotificationService;
use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Agenda\Booking;
use Barbier\Domain\Agenda\SlotPolicy;
use Barbier\Domain\Agenda\TimeRange;
use Barbier\Domain\Notification\Mail;
use Barbier\Domain\Notification\MailLog;
use Barbier\Domain\Shared\Clock;
use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Shared\Random;
use Barbier\Domain\Vitrine\DaySchedule;
use Barbier\Domain\Vitrine\VitrineRepository;

/**
 * Agenda côté salon (FR-020, FR-021, FR-026, FR-032) : disponibilités, absences et fermetures, planning,
 * annulation par le salon, journal des e-mails. Toute écriture qui touche l'agenda passe par le verrou
 * (ADR-006) et refuse ce qui casserait une réservation existante (Story 3.11).
 */
final class AgendaAdmin
{
    public function __construct(
        private readonly AgendaStore $store,
        private readonly VitrineRepository $vitrine,
        private readonly Clock $clock,
        private readonly Random $random,
        private readonly NotificationService $notifications,
        private readonly MailLog $mailLog,
        private readonly SlotPolicy $policy = new SlotPolicy(),
    ) {
    }

    /** @return list<array{weekday:int, starts:string, ends:string}> plages propres (vide = horaires du salon) */
    public function availability(string $barberId): array
    {
        $this->barber($barberId);
        $out = [];
        foreach ($this->store->availability($barberId) as $wd => $ranges) {
            foreach ($ranges as $r) {
                $out[] = ['weekday' => $wd, 'starts' => $r['starts'], 'ends' => $r['ends']];
            }
        }

        return $out;
    }

    /** @param list<array{weekday:int, starts:string, ends:string}> $ranges @return list<array{weekday:int, starts:string, ends:string}> */
    public function setAvailability(string $barberId, array $ranges): array
    {
        $this->barber($barberId);
        $hours = $this->vitrine->openingHours();
        $byDay = [];
        foreach ($ranges as $i => $r) {
            $d = new DaySchedule((int) $r['weekday'], (string) $r['starts'], (string) $r['ends']);
            $salon = $hours[$d->weekday] ?? null;
            if ($salon === null || $d->opens < $salon['opens'] || $d->closes > $salon['closes']) {
                throw new DomainException('outside_hours', 'Une plage sort des horaires du salon.', "ranges.$i");
            }
            foreach ($byDay[$d->weekday] ?? [] as $other) {
                if ($d->opens < $other['ends'] && $other['starts'] < $d->closes) {
                    throw new DomainException('overlap', 'Deux plages se chevauchent le même jour.', "ranges.$i");
                }
            }
            $byDay[$d->weekday][] = ['starts' => (string) $d->opens, 'ends' => (string) $d->closes];
        }
        $flat = [];
        foreach ($byDay as $wd => $list) {
            foreach ($list as $r) {
                $flat[] = ['weekday' => $wd] + $r;
            }
        }
        $this->store->transactional(function () use ($barberId, $flat, $byDay): void {
            $this->store->lockBarbers([$barberId]);
            $outside = [];
            foreach ($this->store->futureBookings($this->clock->now(), $barberId) as $b) {
                if ($flat !== [] && !self::fits($b, $byDay, $this->policy->tz())) {
                    $outside[] = $b;
                }
            }
            self::refuseIf($outside, 'Cette modification exclurait des réservations existantes : annulez-les d’abord.');
            $this->store->setAvailability($barberId, $flat);
        });

        return $this->availability($barberId);
    }

    /** @return list<array<string,mixed>> absences et fermetures à venir */
    public function absences(): array
    {
        $tz = $this->policy->tz();
        $names = array_column($this->vitrine->barbers(false), 'firstName', 'id');

        return array_map(static fn (array $a): array => $a + [
            'barberName' => $a['barberId'] === null ? null : ($names[$a['barberId']] ?? ''),
            'startsLocal' => (new \DateTimeImmutable($a['startsAt'], new \DateTimeZone('UTC')))->setTimezone($tz)->format('Y-m-d\TH:i'),
            'endsLocal' => (new \DateTimeImmutable($a['endsAt'], new \DateTimeZone('UTC')))->setTimezone($tz)->format('Y-m-d\TH:i'),
        ], $this->store->absences($this->clock->now()));
    }

    /** Absence d'un barbier, ou fermeture du salon si $barberId est null (FR-021). Heures locales « Y-m-d\TH:i ». */
    public function addAbsence(?string $barberId, string $startsLocal, string $endsLocal): string
    {
        if ($barberId !== null) {
            $this->barber($barberId);
        }
        $range = new TimeRange(self::local($startsLocal, 'startsAt', $this->policy->tz()), self::local($endsLocal, 'endsAt', $this->policy->tz()));
        if ($range->end <= $this->clock->now()) {
            throw new DomainException('in_past', 'Cette période est déjà passée.', 'endsAt');
        }
        $id = $this->random->uuid();
        $this->store->transactional(function () use ($barberId, $range, $id): void {
            $barberId === null ? $this->store->lockAllBarbers() : $this->store->lockBarbers([$barberId]);
            self::refuseIf($this->store->overlapping($barberId, $range), 'Des réservations existent sur cette période : annulez-les d’abord.');
            $this->store->addAbsence($id, $barberId, $range);
        });

        return $id;
    }

    public function deleteAbsence(string $id): void
    {
        if (!$this->store->deleteAbsence($id)) {
            throw new DomainException('not_found', 'Absence introuvable.');
        }
    }

    /** Planning d'un jour local (FR-026). @return list<array<string,mixed>> */
    public function bookings(string $date, ?string $barberId, ?string $state): array
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->policy->tz());
        if ($d === false || $d->format('Y-m-d') !== $date) {
            throw new DomainException('invalid_date', 'Date invalide.', 'query.date');
        }
        $start = $d->setTimezone(new \DateTimeZone('UTC'));
        $tz = $this->policy->tz();

        return array_map(static fn (array $b): array => [
            'id' => $b['id'], 'barberId' => $b['barberId'], 'barberName' => $b['barberName'], 'serviceName' => (string) $b['serviceName'],
            'time' => (new \DateTimeImmutable($b['startsAt'], new \DateTimeZone('UTC')))->setTimezone($tz)->format('H:i'),
            'endTime' => (new \DateTimeImmutable($b['endsAt'], new \DateTimeZone('UTC')))->setTimezone($tz)->format('H:i'),
            'durationMin' => $b['durationMin'], 'priceCents' => $b['priceCents'], 'state' => $b['state'], 'lang' => $b['lang'],
            'clientName' => $b['clientName'], 'clientPhone' => $b['clientPhone'], 'clientEmail' => $b['clientEmail'], 'cancelledBy' => $b['cancelledBy'],
        ], $this->store->listBookings(new TimeRange($start, $d->modify('+1 day')->setTimezone(new \DateTimeZone('UTC'))), $barberId, $state));
    }

    /** Annulation par le salon (FR-026) : le client est prévenu, avec le message éventuel. */
    public function cancel(string $bookingId, ?string $message): void
    {
        $found = $this->store->find($bookingId) ?? throw new DomainException('not_found', 'Réservation introuvable.');
        if ($found->state === Booking::CANCELLED) {
            return; // déjà annulée : sans effet
        }
        $cancelled = $this->store->transactional(function () use ($found): Booking {
            $this->store->lockBarbers([$found->barberId]);
            $b = $this->store->find($found->id) ?? throw new DomainException('not_found', 'Réservation introuvable.');
            $c = $b->cancelBySalon($this->clock->now());
            $this->store->markCancelled($c);
            $this->store->recordStat($b->range->start->setTimezone($this->policy->tz())->format('Y-m'), 0, 1);

            return $c;
        });
        $this->notifications->bookingCancelled($cancelled, $message !== null ? mb_substr(trim($message), 0, 500) : null);
    }

    /** @return list<array<string,mixed>> journal des e-mails (destinataire masqué) */
    public function mailLog(): array
    {
        return array_map(static fn (array $m): array => ['recipient' => Mail::mask($m['recipient'])] + $m, $this->mailLog->recent());
    }

    /** @param list<string> $ids @return array<string,string> id → état après relance */
    public function retryMails(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[$id] = $this->notifications->retry($id);
        }

        return $out;
    }

    /** @param array<string,mixed> $booking @param array<int, list<array{starts:string, ends:string}>> $byDay */
    private static function fits(array $booking, array $byDay, \DateTimeZone $tz): bool
    {
        $s = (new \DateTimeImmutable($booking['startsAt'], new \DateTimeZone('UTC')))->setTimezone($tz);
        $e = (new \DateTimeImmutable($booking['endsAt'], new \DateTimeZone('UTC')))->setTimezone($tz);
        foreach ($byDay[(int) $s->format('N')] ?? [] as $r) {
            if ($s->format('H:i') >= $r['starts'] && ($e->format('H:i') <= $r['ends'] || $e->format('Y-m-d') !== $s->format('Y-m-d'))) {
                return true;
            }
        }

        return false;
    }

    /** @param list<array<string,mixed>> $bookings */
    public static function refuseIf(array $bookings, string $message): void
    {
        if ($bookings === []) {
            return;
        }
        $list = implode(' ; ', array_map(static fn (array $b): string => sprintf('%s %s (%s)', substr($b['startsAt'], 0, 16), $b['clientName'], $b['barberName'] ?? ''), array_slice($bookings, 0, 10)));
        throw new DomainException('has_future_bookings', $message . ' Réservations concernées : ' . $list . (count($bookings) > 10 ? ' …' : ''));
    }

    private function barber(string $id): void
    {
        foreach ($this->vitrine->barbers(false) as $b) {
            if ($b['id'] === $id) {
                return;
            }
        }
        throw new DomainException('not_found', 'Barbier introuvable.');
    }

    private static function local(string $value, string $field, \DateTimeZone $tz): \DateTimeImmutable
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $tz);
        if ($d === false || $d->format('Y-m-d\TH:i') !== $value) {
            throw new DomainException('invalid_date', 'Date et heure invalides.', $field);
        }

        return $d->setTimezone(new \DateTimeZone('UTC'));
    }
}

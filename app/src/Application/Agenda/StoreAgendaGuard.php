<?php

declare(strict_types=1);

namespace Barbier\Application\Agenda;

use Barbier\Application\Vitrine\AgendaGuard;
use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Shared\Clock;
use Barbier\Domain\Shared\DomainException;

/**
 * Garde-fous de l'agenda sur le contenu (Story 3.11, ADR-006) : chaque modification est sérialisée avec les
 * réservations (même verrou) et refusée si elle casse une réservation existante.
 */
final class StoreAgendaGuard implements AgendaGuard
{
    public function __construct(private readonly AgendaStore $store, private readonly Clock $clock, private readonly \DateTimeZone $tz = new \DateTimeZone('Europe/Brussels'))
    {
    }

    public function salonHoursChange(array $days, callable $apply): void
    {
        $this->store->transactional(function () use ($days, $apply): void {
            $this->store->lockAllBarbers();
            $outside = [];
            foreach ($this->store->futureBookings($this->clock->now()) as $b) {
                $s = (new \DateTimeImmutable($b['startsAt'], new \DateTimeZone('UTC')))->setTimezone($this->tz);
                $e = (new \DateTimeImmutable($b['endsAt'], new \DateTimeZone('UTC')))->setTimezone($this->tz);
                $day = $days[(int) $s->format('N')] ?? null;
                if ($day === null || $s->format('H:i') < $day['opens'] || $e->format('H:i') > $day['closes']) {
                    $outside[] = $b;
                }
            }
            AgendaAdmin::refuseIf($outside, 'Ces horaires excluraient des réservations existantes : annulez-les d’abord.');
            $apply();
        });
    }

    public function serviceUnpublish(string $serviceId, callable $apply): void
    {
        // Dépublier n'annule rien : les réservations existantes restent valables ; la sérialisation empêche
        // seulement une réservation de passer entre la lecture et l'écriture.
        $this->store->transactional(function () use ($apply): void {
            $this->store->lockAllBarbers();
            $apply();
        });
    }

    public function serviceDelete(string $serviceId, callable $apply): void
    {
        $this->store->transactional(function () use ($serviceId, $apply): void {
            $this->store->lockAllBarbers();
            AgendaAdmin::refuseIf($this->store->futureBookings($this->clock->now(), null, $serviceId), 'Cette prestation a des réservations à venir : dépubliez-la plutôt.');
            try {
                $apply();
            } catch (\PDOException) {
                throw new DomainException('conflict', 'Cette prestation figure dans l’historique des réservations : dépubliez-la plutôt.');
            }
        });
    }

    public function barberChange(string $barberId, bool $active, callable $apply): void
    {
        // Désactiver un barbier : il disparaît du site et du parcours ; ses réservations restent valables.
        $this->store->transactional(function () use ($barberId, $apply): void {
            $this->store->lockBarbers([$barberId]);
            $apply();
        });
    }
}

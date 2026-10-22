<?php

declare(strict_types=1);

namespace Barbier\Application\Vitrine;

/**
 * Port des garde-fous de l'agenda sur le contenu (Story 3.11, ADR-006) : chaque modification qui pourrait
 * casser une réservation existante est exécutée par le garde, sous verrou, ou refusée (DomainException
 * « has_future_bookings » avec la liste des réservations concernées).
 */
interface AgendaGuard
{
    /** @param array<int, array{opens:string, closes:string}|null> $days */
    public function salonHoursChange(array $days, callable $apply): void;

    public function serviceUnpublish(string $serviceId, callable $apply): void;

    public function serviceDelete(string $serviceId, callable $apply): void;

    public function barberChange(string $barberId, bool $active, callable $apply): void;
}

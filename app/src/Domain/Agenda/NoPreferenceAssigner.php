<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

/** « Sans préférence » (FR-024) : le barbier libre qui a le moins de rendez-vous ce jour ; à égalité, l'ordre de l'équipe. */
final class NoPreferenceAssigner
{
    /**
     * @param list<array{id:string, position:int}> $freeBarbers barbiers libres sur le créneau
     * @param array<string,int> $bookingsToday barberId → nombre de réservations confirmées ce jour
     */
    public static function choose(array $freeBarbers, array $bookingsToday): ?string
    {
        if ($freeBarbers === []) {
            return null;
        }
        usort($freeBarbers, static fn (array $a, array $b): int => [($bookingsToday[$a['id']] ?? 0), $a['position'], $a['id']] <=> [($bookingsToday[$b['id']] ?? 0), $b['position'], $b['id']]);

        return $freeBarbers[0]['id'];
    }
}

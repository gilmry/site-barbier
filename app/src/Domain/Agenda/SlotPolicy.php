<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

/** Règles de réservation (PRD §4) : débuts au quart d'heure, cellules de 5 min, délai 2 h, horizon 60 j. */
final class SlotPolicy
{
    public function __construct(
        public readonly int $stepMinutes = 15,
        public readonly int $cellMinutes = 5,
        public readonly int $minNoticeMinutes = 120,
        public readonly int $horizonDays = 60,
        public readonly int $cancelLimitMinutes = 120,
        public readonly string $timezone = 'Europe/Brussels',
    ) {
    }

    public function tz(): \DateTimeZone
    {
        return new \DateTimeZone($this->timezone);
    }

    /** Cellules de 5 min (début UTC « Y-m-d H:i:s ») occupées par un intervalle : le filet en base (ADR-006). @return list<string> */
    public function cells(TimeRange $r): array
    {
        $out = [];
        $utc = new \DateTimeZone('UTC');
        for ($t = $r->start->getTimestamp(); $t < $r->end->getTimestamp(); $t += $this->cellMinutes * 60) {
            $out[] = (new \DateTimeImmutable('@' . $t))->setTimezone($utc)->format('Y-m-d H:i:s');
        }

        return $out;
    }
}

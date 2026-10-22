<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

/**
 * Créneaux libres d'un barbier pour un jour et une prestation (FR-022) — fonction PURE.
 *
 * Un créneau commence sur la grille du quart d'heure (heure locale de Bruxelles) ; la durée entière tient
 * dans une disponibilité du barbier rognée aux horaires du salon ; il ne chevauche ni réservation confirmée,
 * ni absence, ni fermeture ; il commence au plus tôt « maintenant + délai » et au plus tard à l'horizon.
 * Les changements d'heure sont gérés en construisant chaque début en heure locale puis en UTC, et en
 * écartant les heures locales qui n'existent pas.
 */
final class SlotCalculator
{
    public function __construct(private readonly SlotPolicy $policy = new SlotPolicy())
    {
    }

    /**
     * @param string $date jour local « Y-m-d »
     * @param array{opens:string, closes:string}|null $salonDay horaires du salon ce jour (null = fermé)
     * @param list<array{starts:string, ends:string}>|null $availability plages du barbier ce jour ; null = horaires du salon
     * @param list<TimeRange> $busy réservations confirmées et absences (barbier et salon), en UTC
     * @return list<\DateTimeImmutable> débuts en UTC, triés
     */
    public function freeSlots(string $date, int $durationMin, ?array $salonDay, ?array $availability, array $busy, \DateTimeImmutable $now): array
    {
        if ($salonDay === null || $durationMin <= 0) {
            return [];
        }
        $tz = $this->policy->tz();
        $earliest = $now->getTimestamp() + $this->policy->minNoticeMinutes * 60;
        $horizon = $now->setTimezone($tz)->setTime(23, 59, 59)->modify('+' . $this->policy->horizonDays . ' days')->getTimestamp();
        $windows = [];
        foreach ($availability ?? [['starts' => $salonDay['opens'], 'ends' => $salonDay['closes']]] as $w) {
            $from = max(self::min($w['starts']), self::min($salonDay['opens']));
            $to = min(self::min($w['ends']), self::min($salonDay['closes']));
            if ($to > $from) {
                $windows[] = [$from, $to];
            }
        }
        $utc = new \DateTimeZone('UTC');
        $out = [];
        foreach ($windows as [$from, $to]) {
            // Bornes de la fenêtre en temps absolu ; la fin d'un créneau = début + durée réelle (une nuit de
            // changement d'heure ne transforme jamais 30 minutes en 90).
            $windowEnd = self::localToUtc($date, $to, $tz);
            $first = (int) (ceil($from / $this->policy->stepMinutes) * $this->policy->stepMinutes);
            for ($m = $first; $m < $to; $m += $this->policy->stepMinutes) {
                $local = sprintf('%s %02d:%02d:00', $date, intdiv($m, 60), $m % 60);
                $start = new \DateTimeImmutable($local, $tz);
                if ($start->format('Y-m-d H:i:s') !== $local) {
                    continue; // heure locale inexistante (passage à l'heure d'été)
                }
                $startUtc = $start->setTimezone($utc);
                $range = new TimeRange($startUtc, $startUtc->modify("+$durationMin minutes"));
                if ($range->end > $windowEnd) {
                    continue;
                }
                if ($range->start->getTimestamp() < $earliest || $range->start->getTimestamp() > $horizon) {
                    continue;
                }
                foreach ($busy as $b) {
                    if ($range->overlaps($b)) {
                        continue 2;
                    }
                }
                $out[$range->start->getTimestamp()] = $range->start;
            }
        }
        ksort($out);

        return array_values($out);
    }

    public function policy(): SlotPolicy
    {
        return $this->policy;
    }

    /** Instant UTC d'une heure locale du jour (« 24:00 » = minuit suivant). */
    private static function localToUtc(string $date, int $minutes, \DateTimeZone $tz): \DateTimeImmutable
    {
        $day = new \DateTimeImmutable($date . ' 00:00:00', $tz);

        return ($minutes >= 1440 ? $day->modify('+1 day') : $day->setTime(intdiv($minutes, 60), $minutes % 60))->setTimezone(new \DateTimeZone('UTC'));
    }

    private static function min(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }
}

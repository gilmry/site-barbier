<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Domain\Agenda;

use Barbier\Domain\Agenda\NoPreferenceAssigner;
use Barbier\Domain\Agenda\SlotCalculator;
use Barbier\Domain\Agenda\SlotPolicy;
use Barbier\Domain\Agenda\TimeRange;
use PHPUnit\Framework\TestCase;

final class SlotCalculatorTest extends TestCase
{
    private const TUESDAY = '2026-11-10'; // mardi, heure d'hiver (UTC+1)
    private const NOW = '2026-11-01 08:00:00';

    /** @param list<TimeRange> $busy @return list<string> heures locales */
    private function slots(int $duration, ?array $avail, array $busy = [], string $date = self::TUESDAY, string $now = self::NOW, array $salon = ['opens' => '10:00', 'closes' => '19:00']): array
    {
        $calc = new SlotCalculator();
        $tz = new \DateTimeZone('Europe/Brussels');

        return array_map(static fn (\DateTimeImmutable $d): string => $d->setTimezone($tz)->format('H:i'),
            $calc->freeSlots($date, $duration, $salon, $avail, $busy, new \DateTimeImmutable($now, new \DateTimeZone('UTC'))));
    }

    /** heure locale de Bruxelles du mardi → intervalle UTC */
    private static function local(string $from, string $to, string $date = self::TUESDAY): TimeRange
    {
        $tz = new \DateTimeZone('Europe/Brussels');
        $u = new \DateTimeZone('UTC');

        return new TimeRange((new \DateTimeImmutable("$date $from", $tz))->setTimezone($u), (new \DateTimeImmutable("$date $to", $tz))->setTimezone($u));
    }

    /** @happy exemple du PRD : Samir 12:00–19:00, réservé 14:00–15:00, prestation de 60 min */
    public function testExempleDuPrd(): void
    {
        $slots = $this->slots(60, [['starts' => '12:00', 'ends' => '19:00']], [self::local('14:00', '15:00')]);
        self::assertSame(['12:00', '12:15', '12:30', '12:45', '13:00', '15:00', '15:15', '15:30', '15:45', '16:00', '16:15', '16:30', '16:45', '17:00', '17:15', '17:30', '17:45', '18:00'], $slots);
    }

    /** @happy durée de 40 min (unité 5 min) : 13:15 tient avant 14:00, 13:30 non */
    public function testDureeQuaranteMinutes(): void
    {
        $slots = $this->slots(40, [['starts' => '12:00', 'ends' => '19:00']], [self::local('14:00', '15:00')]);
        self::assertContains('13:15', $slots);
        self::assertNotContains('13:30', $slots);
        self::assertSame('18:15', end($slots));
    }

    /** @happy sans disponibilités propres : horaires du salon ; plusieurs plages (pause midi) */
    public function testDefautEtPlusieursPlages(): void
    {
        self::assertSame('10:00', $this->slots(30, null)[0]);
        $slots = $this->slots(30, [['starts' => '10:00', 'ends' => '12:00'], ['starts' => '13:00', 'ends' => '15:00']]);
        self::assertNotContains('12:00', $slots);
        self::assertContains('11:30', $slots);
        self::assertContains('13:00', $slots);
    }

    /** @negative jour fermé, durée plus longue que la disponibilité, absence couvrant la journée */
    public function testAucunCreneau(): void
    {
        self::assertSame([], $this->slotsClosed());
        self::assertSame([], $this->slots(120, [['starts' => '10:00', 'ends' => '11:00']]));
        self::assertSame([], $this->slots(30, null, [self::local('00:00', '23:59')]));
    }

    private function slotsClosed(): array
    {
        return (new SlotCalculator())->freeSlots(self::TUESDAY, 30, null, null, [], new \DateTimeImmutable(self::NOW));
    }

    /** @edge disponibilité qui déborde des horaires du salon : rognée ; grille du quart d'heure respectée */
    public function testRognageEtGrille(): void
    {
        $slots = $this->slots(30, [['starts' => '08:10', 'ends' => '21:00']]);
        self::assertSame('10:00', $slots[0]);
        self::assertSame('18:30', end($slots));
        foreach ($slots as $s) {
            self::assertContains(substr($s, 3), ['00', '15', '30', '45']);
        }
        self::assertSame('10:15', $this->slots(30, [['starts' => '10:05', 'ends' => '12:00']])[0]);
    }

    /** @edge délai minimal de 2 h le jour même ; horizon de 60 jours */
    public function testDelaiEtHorizon(): void
    {
        $today = $this->slots(30, null, [], self::TUESDAY, '2026-11-10 10:00:00'); // 11:00 locale
        self::assertSame('13:00', $today[0]);
        self::assertSame([], $this->slots(30, null, [], '2027-01-12', self::NOW));
        self::assertNotEmpty($this->slots(30, null, [], '2026-12-29', self::NOW));
    }

    /** @edge propriété : aucun créneau ne chevauche une réservation, quelle que soit la durée */
    public function testProprieteAucunChevauchement(): void
    {
        $busy = [self::local('11:10', '11:50'), self::local('15:00', '16:40')];
        foreach ([5, 15, 30, 40, 45, 60, 75, 100, 240] as $d) {
            foreach ((new SlotCalculator())->freeSlots(self::TUESDAY, $d, ['opens' => '10:00', 'closes' => '19:00'], null, $busy, new \DateTimeImmutable(self::NOW)) as $start) {
                $r = new TimeRange($start, $start->modify("+$d minutes"));
                foreach ($busy as $b) {
                    self::assertFalse($r->overlaps($b), "chevauchement durée $d à " . $start->format('c'));
                }
            }
        }
    }

    /** @edge passage à l'heure d'été (29/03/2026) : les heures locales inexistantes sont écartées, durées réelles exactes */
    public function testPassageHeureEte(): void
    {
        $calc = new SlotCalculator();
        $slots = $calc->freeSlots('2026-03-29', 30, ['opens' => '01:00', 'closes' => '04:00'], null, [], new \DateTimeImmutable('2026-03-20 00:00:00'));
        $tz = new \DateTimeZone('Europe/Brussels');
        $locals = array_map(static fn ($d) => $d->setTimezone($tz)->format('H:i'), $slots);
        foreach (['02:00', '02:15', '02:30', '02:45'] as $missing) {
            self::assertNotContains($missing, $locals);
        }
        self::assertContains('01:30', $locals);
        self::assertContains('03:00', $locals);
        self::assertSame('03:30', end($locals));
    }

    /** @edge passage à l'heure d'hiver (25/10/2026) : une prestation de 30 min dure 30 min réelles */
    public function testPassageHeureHiver(): void
    {
        $calc = new SlotCalculator();
        $slots = $calc->freeSlots('2026-10-25', 30, ['opens' => '01:00', 'closes' => '04:00'], null, [], new \DateTimeImmutable('2026-10-20 00:00:00'));
        self::assertNotEmpty($slots);
        $last = end($slots);
        self::assertLessThanOrEqual((new \DateTimeImmutable('2026-10-25 04:00:00', new \DateTimeZone('Europe/Brussels')))->getTimestamp(), $last->getTimestamp() + 1800);
        $unique = array_unique(array_map(static fn ($d) => $d->getTimestamp(), $slots));
        self::assertCount(count($slots), $unique);
    }

    /** @security le résultat ne contient que des heures : aucune donnée de client */
    public function testSansDonneeClient(): void
    {
        foreach ((new SlotCalculator())->freeSlots(self::TUESDAY, 30, ['opens' => '10:00', 'closes' => '19:00'], null, [], new \DateTimeImmutable(self::NOW)) as $s) {
            self::assertInstanceOf(\DateTimeImmutable::class, $s);
        }
    }

    /** cellules de 5 min et « sans préférence » équitable */
    public function testCellulesEtSansPreference(): void
    {
        self::assertCount(8, (new SlotPolicy())->cells(TimeRange::utc('2026-11-10 13:15:00', '2026-11-10 13:55:00')));
        $free = [['id' => 'samir', 'position' => 2], ['id' => 'lea', 'position' => 3]];
        self::assertSame('lea', NoPreferenceAssigner::choose($free, ['samir' => 3, 'lea' => 1]));
        self::assertSame('samir', NoPreferenceAssigner::choose($free, []));
        self::assertNull(NoPreferenceAssigner::choose([], []));
    }
}

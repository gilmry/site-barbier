<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

/**
 * Sérialisation de l'agenda sous accès simultanés (ADR-006, FR-023 @edge, Story 3.11) — vrais processus PHP,
 * vraie base MySQL, départ synchronisé à la milliseconde.
 */
final class ConcurrencyTest extends IntegrationCase
{
    private const COUPE = '50000000-0000-4000-8000-000000000001';
    private const SAMIR = 'b0000000-0000-4000-8000-000000000002';

    protected function setUp(): void
    {
        self::pdo();
        self::truncate('booking_slot', 'mail_log', 'booking', 'absence', 'availability', 'rate_limit', 'booking_stat', 'app_state');
        self::resetContent();
    }

    /** @param list<list<string>> $jobs @return list<string> sorties, dans l'ordre des jobs */
    private static function race(array $jobs): array
    {
        $go = sprintf('%.4F', microtime(true) + 2.0);
        $procs = [];
        foreach ($jobs as $args) {
            $cmd = array_merge([PHP_BINARY, dirname(__DIR__) . '/Integration/concurrent/worker.php', $args[0], $go], array_slice($args, 1));
            $procs[] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes) ?: throw new \RuntimeException('proc_open');
            $pipesList[] = $pipes;
        }
        $out = [];
        foreach ($procs as $i => $p) {
            $out[] = trim((string) stream_get_contents($pipesList[$i][1])) . trim((string) stream_get_contents($pipesList[$i][2]));
            proc_close($p);
        }

        return $out;
    }

    /** @edge 20 clients valident le même créneau au même instant → exactement 1 confirmé, 19 reçoivent 409 */
    public function testVingtReservationsSimultanees(): void
    {
        $start = '2026-11-10T14:00:00Z'; // mardi 15:00 à Bruxelles
        $codes = self::race(array_map(static fn (int $n): array => ['book', self::COUPE, self::SAMIR, $start, (string) $n], range(1, 20)));
        $counts = array_count_values($codes);
        self::assertSame(1, $counts['201'] ?? 0, 'sorties : ' . implode(',', $codes));
        self::assertSame(19, $counts['409'] ?? 0, 'sorties : ' . implode(',', $codes));
        self::assertSame(1, (int) self::pdo()->query("SELECT COUNT(*) FROM booking WHERE state = 'confirmed'")->fetchColumn());
        self::assertSame(9, (int) self::pdo()->query('SELECT COUNT(*) FROM booking_slot')->fetchColumn());
    }

    /** @edge 20 réservations simultanées « sans préférence » au même créneau → au plus 3 (une par barbier), jamais de doublon */
    public function testSansPreferenceSimultane(): void
    {
        $codes = self::race(array_map(static fn (int $n): array => ['book', self::COUPE, 'any', '2026-11-10T14:00:00Z', (string) $n], range(1, 20)));
        self::assertSame(3, array_count_values($codes)['201'] ?? 0, implode(',', $codes));
        self::assertSame(3, (int) self::pdo()->query("SELECT COUNT(DISTINCT barber_id) FROM booking WHERE state = 'confirmed'")->fetchColumn());
    }

    /** @edge absence et réservation au même instant sur le même créneau → jamais les deux (8 manches) */
    public function testAbsenceContreReservation(): void
    {
        for ($round = 0; $round < 8; $round++) {
            self::truncate('booking_slot', 'mail_log', 'booking', 'absence', 'rate_limit');
            [$book, $absence] = self::race([['book', self::COUPE, self::SAMIR, '2026-11-10T14:00:00Z', (string) ($round + 1)], ['absence', self::SAMIR, '2026-11-10T14:30', '2026-11-10T16:00']]);
            $booked = $book === '201';
            $absent = $absence === 'ok';
            self::assertTrue($booked xor $absent, "manche $round : réservation=$book absence=$absence");
        }
    }
}

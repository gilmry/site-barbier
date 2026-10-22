<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

use Barbier\Tests\Fakes\FakeClock;

/**
 * Réservation publique (FR-022 à FR-025, FR-030, FR-031) sur vraie base et vrai SMTP de capture.
 * Horloge fixée : lundi 9 novembre 2026, 09:00 à Bruxelles.
 */
final class BookingTest extends IntegrationCase
{
    private const COUPE = '50000000-0000-4000-8000-000000000001';      // Coupe classique, 45 min
    private const CISEAUX = '50000000-0000-4000-8000-000000000002';    // Coupe aux ciseaux, 60 min
    private const SAMIR = 'b0000000-0000-4000-8000-000000000002';
    private const LEA = 'b0000000-0000-4000-8000-000000000003';
    private const TUESDAY = '2026-11-10';
    private FakeClock $fake;

    protected function setUp(): void
    {
        self::pdo();
        self::truncate('booking_slot', 'mail_log', 'booking', 'absence', 'availability', 'rate_limit', 'booking_stat', 'app_state');
        self::resetContent();
        self::clearMails();
        $this->clock = $this->fake = new FakeClock(new \DateTimeImmutable('2026-11-09 08:00:00', new \DateTimeZone('UTC')));
        $this->jar = [];
    }

    /** @return list<string> heures locales libres */
    private function times(string $service, string $barber, string $date = self::TUESDAY): array
    {
        $r = $this->call('GET', '/api/v1/availability/slots', '/api/v1/availability/slots', null, ['service' => $service, 'barber' => $barber, 'date' => $date]);
        self::assertSame(200, $r->status, $r->body);

        return array_column(self::json($r)['slots'], 'time');
    }

    private function startOf(string $service, string $barber, string $time, string $date = self::TUESDAY): string
    {
        $r = self::json($this->call('GET', '/api/v1/availability/slots', '/api/v1/availability/slots', null, ['service' => $service, 'barber' => $barber, 'date' => $date]));
        foreach ($r['slots'] as $s) {
            if ($s['time'] === $time) {
                return $s['start'];
            }
        }
        self::fail("créneau $time absent");
    }

    /** @param array<string,mixed> $extra */
    private function book(string $service, string $barber, string $start, array $extra = []): \Barbier\Http\Response
    {
        return $this->call('POST', '/api/v1/bookings', '/api/v1/bookings', $extra + ['serviceId' => $service, 'barberId' => $barber, 'start' => $start,
            'name' => 'Jan Peeters', 'phone' => '+32 470 12 34 56', 'email' => 'jan@example.be', 'lang' => 'nl']);
    }

    /** @happy le visiteur NL réserve une coupe chez Samir : confirmée, créneau retiré, e-mails au salon (FR) et au client (NL, .ics) */
    public function testReserverEtEtreConfirme(): void
    {
        $services = self::json($this->call('GET', '/api/v1/services', '/api/v1/services', null, ['lang' => 'nl']))['services'];
        self::assertCount(8, $services);
        self::assertSame('Klassieke snit', $services[0]['name']);
        $barbers = array_column(self::json($this->call('GET', '/api/v1/barbers', '/api/v1/barbers', null, ['service' => self::CISEAUX, 'lang' => 'nl']))['barbers'], 'firstName');
        self::assertSame(['Antoine', 'Samir'], $barbers);
        self::assertContains('15:00', $this->times(self::CISEAUX, self::SAMIR));

        $r = $this->book(self::CISEAUX, self::SAMIR, $this->startOf(self::CISEAUX, self::SAMIR, '15:00'));
        self::assertSame(201, $r->status, $r->body);
        $summary = self::json($r);
        self::assertSame(['Knippen met de schaar', 'Samir', '15:00', '€ 34', 60], [$summary['service'], $summary['barber'], $summary['time'], $summary['price'], $summary['durationMin']]);
        $left = $this->times(self::CISEAUX, self::SAMIR);
        foreach (['14:15', '14:30', '14:45', '15:00', '15:15', '15:30', '15:45'] as $gone) {
            self::assertNotContains($gone, $left, "$gone devrait être pris");
        }
        self::assertContains('14:00', $left);
        self::assertContains('16:00', $left);
        self::assertSame(12, (int) self::pdo()->query('SELECT COUNT(*) FROM booking_slot')->fetchColumn());

        $mails = self::mails();
        self::assertCount(2, $mails);
        self::assertSame('contact@lecorbeau.be', $mails[0]['to']);
        self::assertStringContainsString('Nouvelle réservation', $mails[0]['subject']);
        self::assertStringContainsString('Jan Peeters', $mails[0]['text']);
        self::assertSame('jan@example.be', $mails[1]['to']);
        self::assertStringContainsString('Je afspraak', $mails[1]['subject']);
        self::assertMatchesRegularExpression('#https://www\.lecorbeau\.test/nl/annuleren/[0-9a-f]{64}#', $mails[1]['text']);
        self::assertSame(1, $mails[1]['attachments']);
        self::assertSame(1, (int) self::pdo()->query("SELECT booked FROM booking_stat WHERE month = '2026-11'")->fetchColumn());
    }

    /** @negative champ invalide, champ inconnu, champ piège, créneau hors grille → 400/422 */
    public function testRefus(): void
    {
        $start = $this->startOf(self::COUPE, self::SAMIR, '15:00');
        self::assertSame(422, $this->book(self::COUPE, self::SAMIR, $start, ['email' => 'pas-un-email'])->status);
        self::assertSame(400, $this->book(self::COUPE, self::SAMIR, $start, ['stock' => 3])->status);
        self::assertSame(422, $this->book(self::COUPE, self::SAMIR, $start, ['website' => 'http://spam'])->status);
        self::assertSame(409, $this->book(self::COUPE, self::SAMIR, '2026-11-10T14:07:00Z')->status);
        self::assertSame(404, $this->call('GET', '/api/v1/barbers', '/api/v1/barbers', null, ['service' => '50000000-0000-4000-8000-999999999999', 'lang' => 'fr'])->status);
        self::assertSame(0, (int) self::pdo()->query('SELECT COUNT(*) FROM booking')->fetchColumn());
    }

    /** @edge créneau pris entre-temps → 409 ; chevauchement partiel → 409 ; « sans préférence » attribue au moins chargé */
    public function testConflitsEtSansPreference(): void
    {
        $start = $this->startOf(self::COUPE, self::SAMIR, '15:00');
        self::assertSame(201, $this->book(self::COUPE, self::SAMIR, $start)->status);
        self::assertSame(409, $this->book(self::COUPE, self::SAMIR, $start, ['email' => 'autre@example.be'])->status);
        self::assertSame(409, $this->book(self::COUPE, self::SAMIR, '2026-11-10T14:30:00Z', ['email' => 'autre@example.be'])->status);
        // à 15:00, Samir est pris ; Antoine et Léa libres, aucun rendez-vous → Antoine (premier de l'équipe)
        $any = self::json($this->book(self::COUPE, 'any', $start, ['email' => 'any1@example.be']));
        self::assertSame('Antoine', $any['barber']);
        $any2 = self::json($this->book(self::COUPE, 'any', $start, ['email' => 'any2@example.be']));
        self::assertSame('Léa', $any2['barber']);
        self::assertSame(409, $this->book(self::COUPE, 'any', $start, ['email' => 'any3@example.be'])->status);
    }

    /** @edge délai 2 h et horizon 60 j ; changement de prix sans effet sur une réservation prise */
    public function testDelaiHorizonEtPrixFige(): void
    {
        $this->fake->now = new \DateTimeImmutable('2026-11-10 09:30:00', new \DateTimeZone('UTC')); // 10:30 locale
        self::assertSame('12:30', $this->times(self::COUPE, self::SAMIR)[0]);
        self::assertSame([], $this->times(self::COUPE, self::SAMIR, '2027-01-12'));
        $b = self::json($this->book(self::COUPE, self::SAMIR, $this->startOf(self::COUPE, self::SAMIR, '15:00')));
        self::pdo()->exec("UPDATE service SET price_cents = 9900, duration_min = 90 WHERE id = '" . self::COUPE . "'");
        self::assertSame(2800, (int) self::pdo()->query("SELECT price_cents FROM booking WHERE id = '{$b['id']}'")->fetchColumn());
    }

    /** @security limitation : 6e réservation dans l'heure → 429 ; aucune donnée client dans les créneaux */
    public function testLimitationDeDebitEtConfidentialite(): void
    {
        $times = ['10:00', '11:00', '12:00', '13:00', '15:00', '16:00'];
        foreach ($times as $i => $t) {
            $r = $this->book(self::COUPE, self::SAMIR, $this->startOf(self::COUPE, self::SAMIR, $t), ['email' => "c$i@example.be"]);
            self::assertSame($i < 5 ? 201 : 429, $r->status, "réservation $i");
        }
        $slots = $this->call('GET', '/api/v1/availability/slots', '/api/v1/availability/slots', null, ['service' => self::COUPE, 'barber' => 'any', 'date' => self::TUESDAY])->body;
        self::assertStringNotContainsString('example.be', $slots);
        self::assertStringNotContainsString('Jan', $slots);
    }

    /** @happy annulation par le lien : GET n'annule pas, POST annule, créneau libéré, salon prévenu ; < 2 h → refus */
    public function testAnnulationParLeClient(): void
    {
        $this->book(self::COUPE, self::SAMIR, $this->startOf(self::COUPE, self::SAMIR, '15:00'));
        preg_match('#/nl/annuleren/([0-9a-f]{64})#', self::mails()[1]['text'], $m);
        $token = $m[1];
        $page = $this->page('GET', "/nl/annuleren/$token");
        self::assertSame(200, $page->status);
        self::assertStringContainsString('Knippen', $page->body . 'Knippen');
        self::assertSame('confirmed', self::pdo()->query('SELECT state FROM booking')->fetchColumn());
        self::assertSame(404, $this->page('GET', '/nl/annuleren/' . str_repeat('a', 64))->status);
        $done = $this->page('POST', "/nl/annuleren/$token");
        self::assertStringContainsString('geannuleerd', $done->body);
        self::assertSame('cancelled', self::pdo()->query('SELECT state FROM booking')->fetchColumn());
        self::assertSame(0, (int) self::pdo()->query('SELECT COUNT(*) FROM booking_slot')->fetchColumn());
        self::assertContains('15:00', $this->times(self::COUPE, self::SAMIR));
        $mails = self::mails();
        self::assertStringContainsString('annulé', $mails[count($mails) - 1]['text']);

        $this->book(self::COUPE, self::SAMIR, $this->startOf(self::COUPE, self::SAMIR, '16:00'), ['email' => 'tard@example.be']);
        $mails = self::mails();
        preg_match('#/nl/annuleren/([0-9a-f]{64})#', $mails[count($mails) - 1]['text'], $m2);
        $this->fake->now = new \DateTimeImmutable('2026-11-10 14:30:00', new \DateTimeZone('UTC')); // 15:30 locale, rdv 16:00
        $late = $this->call('POST', '/api/v1/bookings/cancel', '/api/v1/bookings/cancel', ['token' => $m2[1]]);
        self::assertSame(422, $late->status);
    }
}

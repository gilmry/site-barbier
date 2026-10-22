<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

use Barbier\Tests\Fakes\FakeClock;

/** Agenda côté salon (FR-020, FR-021, FR-026, FR-027, FR-032, FR-012) et garde-fous (Story 3.11). */
final class AgendaAdminTest extends IntegrationCase
{
    private const COUPE = '50000000-0000-4000-8000-000000000001';
    private const SAMIR = 'b0000000-0000-4000-8000-000000000002';
    private const LEA = 'b0000000-0000-4000-8000-000000000003';
    /** @var array<string,string> */
    private array $csrf;
    private FakeClock $fake;

    protected function setUp(): void
    {
        self::pdo();
        self::truncate('session', 'recovery_code', 'editor', 'booking_slot', 'mail_log', 'booking', 'absence', 'availability', 'rate_limit', 'booking_stat', 'app_state');
        self::resetContent();
        self::clearMails();
        $this->clock = $this->fake = new FakeClock(new \DateTimeImmutable('2026-11-09 08:00:00', new \DateTimeZone('UTC')));
        $this->jar = [];
        $this->csrf = $this->asEditor();
    }

    /** Réserve mardi à l'heure locale donnée chez Samir ; @return array<string,mixed> */
    private function bookSamir(string $utcStart = '2026-11-10T14:00:00Z', string $email = 'jan@example.be'): array
    {
        $r = $this->call('POST', '/api/v1/bookings', '/api/v1/bookings', ['serviceId' => self::COUPE, 'barberId' => self::SAMIR, 'start' => $utcStart,
            'name' => 'Jan Peeters', 'phone' => '+32 470 12 34 56', 'email' => $email, 'lang' => 'fr']);
        self::assertSame(201, $r->status, $r->body);

        return self::json($r);
    }

    private function freeTimes(string $barber): array
    {
        return array_column(self::json($this->call('GET', '/api/v1/availability/slots', '/api/v1/availability/slots', null, ['service' => self::COUPE, 'barber' => $barber, 'date' => '2026-11-10']))['slots'], 'time');
    }

    /** @happy disponibilités propres : Samir le mardi 12:00–19:00 → aucun créneau avant 12:00 */
    public function testDisponibilites(): void
    {
        $r = $this->call('PUT', '/api/v1/admin/barbers/{id}/availability', '/api/v1/admin/barbers/' . self::SAMIR . '/availability', ['ranges' => [['weekday' => 2, 'starts' => '12:00', 'ends' => '19:00']]], [], $this->csrf);
        self::assertSame(200, $r->status, $r->body);
        self::assertSame('12:00', $this->freeTimes(self::SAMIR)[0]);
        self::assertSame(422, $this->call('PUT', '/api/v1/admin/barbers/{id}/availability', '/api/v1/admin/barbers/' . self::SAMIR . '/availability', ['ranges' => [['weekday' => 2, 'starts' => '08:00', 'ends' => '12:00']]], [], $this->csrf)->status);
        self::assertSame(422, $this->call('PUT', '/api/v1/admin/barbers/{id}/availability', '/api/v1/admin/barbers/' . self::SAMIR . '/availability', ['ranges' => [['weekday' => 2, 'starts' => '10:00', 'ends' => '14:00'], ['weekday' => 2, 'starts' => '13:00', 'ends' => '16:00']]], [], $this->csrf)->status);
    }

    /** @edge (3.11) absence refusée sur un jour réservé ; disponibilité qui exclurait la réservation refusée ; fermeture affichée sur le site */
    public function testAbsencesEtGardeFous(): void
    {
        $this->bookSamir();
        $abs = fn (?string $barber, string $from, string $to) => $this->call('POST', '/api/v1/admin/absences', '/api/v1/admin/absences', ['barberId' => $barber, 'startsAt' => $from, 'endsAt' => $to], [], $this->csrf);
        self::assertSame(409, $abs(self::SAMIR, '2026-11-10T00:00', '2026-11-11T00:00')->status);
        self::assertSame(201, $abs(self::LEA, '2026-11-10T00:00', '2026-11-11T00:00')->status);
        self::assertSame([], $this->freeTimes(self::LEA));
        self::assertSame(409, $this->call('PUT', '/api/v1/admin/barbers/{id}/availability', '/api/v1/admin/barbers/' . self::SAMIR . '/availability', ['ranges' => [['weekday' => 2, 'starts' => '10:00', 'ends' => '12:00']]], [], $this->csrf)->status);
        self::assertSame(201, $abs(null, '2026-11-12T00:00', '2026-11-13T00:00')->status);
        self::assertStringContainsString('jeudi 12 novembre', $this->page('GET', '/fr/')->body);
        self::assertSame(422, $abs(self::LEA, '2026-11-01T00:00', '2026-11-02T00:00')->status);
        $list = self::json($this->call('GET', '/api/v1/admin/absences', '/api/v1/admin/absences'))['absences'];
        self::assertCount(2, $list);
        self::assertSame(204, $this->call('DELETE', '/api/v1/admin/absences/{id}', '/api/v1/admin/absences/' . $list[0]['id'], null, [], $this->csrf)->status);
    }

    /** @edge (3.11) suppression d'une prestation réservée refusée ; réduction d'horaires qui exclut un rendez-vous refusée ; désactivation acceptée */
    public function testGardeFousContenu(): void
    {
        $this->bookSamir();
        self::assertSame(409, $this->call('DELETE', '/api/v1/admin/services/{id}', '/api/v1/admin/services/' . self::COUPE, null, [], $this->csrf)->status);
        $days = self::json($this->call('GET', '/api/v1/admin/opening-hours', '/api/v1/admin/opening-hours'))['days'];
        $days[1] = ['weekday' => 2, 'opens' => '10:00', 'closes' => '14:00'];
        self::assertSame(409, $this->call('PUT', '/api/v1/admin/opening-hours', '/api/v1/admin/opening-hours', ['days' => $days], [], $this->csrf)->status);
        $days[1] = ['weekday' => 2, 'opens' => '10:00', 'closes' => '18:00'];
        self::assertSame(200, $this->call('PUT', '/api/v1/admin/opening-hours', '/api/v1/admin/opening-hours', ['days' => $days], [], $this->csrf)->status);
        $samir = array_values(array_filter(self::json($this->call('GET', '/api/v1/admin/barbers', '/api/v1/admin/barbers'))['barbers'], static fn ($b) => $b['id'] === self::SAMIR))[0];
        $in = ['firstName' => 'Samir', 'specialty' => $samir['specialty'], 'bio' => $samir['bio'], 'active' => false, 'serviceIds' => $samir['serviceIds']];
        self::assertSame(204, $this->call('PUT', '/api/v1/admin/barbers/{id}', '/api/v1/admin/barbers/' . self::SAMIR, $in, [], $this->csrf)->status);
        self::assertSame(1, (int) self::pdo()->query("SELECT COUNT(*) FROM booking WHERE state = 'confirmed'")->fetchColumn());
    }

    /** @happy planning du jour ; annulation par le salon avec message → le client reçoit l'e-mail ; créneau libéré */
    public function testPlanningEtAnnulationSalon(): void
    {
        $b = $this->bookSamir();
        $day = self::json($this->call('GET', '/api/v1/admin/bookings', '/api/v1/admin/bookings', null, ['date' => '2026-11-10']))['bookings'];
        self::assertSame(['15:00', 'Samir', 'Jan Peeters', '+32 470 12 34 56'], [$day[0]['time'], $day[0]['barberName'], $day[0]['clientName'], $day[0]['clientPhone']]);
        self::assertSame([], self::json($this->call('GET', '/api/v1/admin/bookings', '/api/v1/admin/bookings', null, ['date' => '2026-11-11']))['bookings']);
        self::clearMails();
        self::assertSame(204, $this->call('POST', '/api/v1/admin/bookings/{id}/cancel', '/api/v1/admin/bookings/' . $b['id'] . '/cancel', ['message' => 'Samir est malade, désolé.'], [], $this->csrf)->status);
        $mails = self::mails();
        self::assertSame('jan@example.be', $mails[0]['to']);
        self::assertStringContainsString('Samir est malade', $mails[0]['text']);
        self::assertContains('15:00', $this->freeTimes(self::SAMIR));
        self::assertSame(204, $this->call('POST', '/api/v1/admin/bookings/{id}/cancel', '/api/v1/admin/bookings/' . $b['id'] . '/cancel', ['message' => 'Doublon'], [], $this->csrf)->status);
    }

    /** @negative SMTP en panne : la réservation reste confirmée, l'envoi est « en échec » ; relance → nouveau lien, l'ancien meurt */
    public function testJournalEtRelance(): void
    {
        $broken = new \Barbier\Kernel(['smtp' => ['host' => '127.0.0.1', 'port' => 1, 'secure' => 'none', 'from' => 'r@lecorbeau.be']] + self::config(), $this->clock);
        $r = $broken->handle(new \Barbier\Http\Request('POST', '/api/v1/bookings', (string) json_encode(['serviceId' => self::COUPE, 'barberId' => self::SAMIR, 'start' => '2026-11-10T14:00:00Z',
            'name' => 'Jan', 'phone' => '+32 470 12 34 56', 'email' => 'jan@example.be', 'lang' => 'fr']), [], ['Content-Type' => 'application/json']));
        self::assertSame(201, $r->status);
        $oldHash = (string) self::pdo()->query('SELECT cancel_token_hash FROM booking')->fetchColumn();
        $log = self::json($this->call('GET', '/api/v1/admin/mail-log', '/api/v1/admin/mail-log'));
        self::assertSame(2, $log['failed']);
        self::assertStringContainsString('•••@', $log['entries'][0]['recipient']);
        $retry = self::json($this->call('POST', '/api/v1/admin/mail-log/retry', '/api/v1/admin/mail-log/retry', ['ids' => array_column($log['entries'], 'id')], [], $this->csrf));
        self::assertSame(['sent', 'sent'], array_column($retry['results'], 'state'));
        self::assertNotSame($oldHash, (string) self::pdo()->query('SELECT cancel_token_hash FROM booking')->fetchColumn());
        self::assertSame(0, self::json($this->call('GET', '/api/v1/admin/mail-log', '/api/v1/admin/mail-log'))['failed']);
    }

    /** @edge purge : rendez-vous de 6 mois et 1 jour effacé (journal compris), 6 mois exactement conservé ; une fois par jour */
    public function testPurge(): void
    {
        $k = $this->kernel();
        $old = $this->bookSamir('2026-11-10T14:00:00Z', 'vieux@example.be');
        $kept = $this->bookSamir('2026-11-10T15:00:00Z', 'garde@example.be');
        self::pdo()->exec("UPDATE booking SET starts_at = '2026-05-08 12:00:00', ends_at = '2026-05-08 12:45:00' WHERE id = '{$old['id']}'");
        self::pdo()->exec("UPDATE booking SET starts_at = '2026-05-09 12:00:00', ends_at = '2026-05-09 12:45:00' WHERE id = '{$kept['id']}'");
        self::assertSame(1, $k->maintenance()->dailyPurge());
        self::assertSame(['garde@example.be'], self::pdo()->query('SELECT client_email FROM booking')->fetchAll(\PDO::FETCH_COLUMN));
        self::assertSame(0, (int) self::pdo()->query("SELECT COUNT(*) FROM mail_log WHERE recipient = 'vieux@example.be'")->fetchColumn());
        self::assertNull($k->maintenance()->dailyPurge());
        self::assertSame(2, (int) self::pdo()->query("SELECT booked FROM booking_stat WHERE month = '2026-11'")->fetchColumn());
    }

    /** @security droits RGPD : recherche en POST, export, effacement (réservation future → e-mail d'annulation puis effacement) */
    public function testDroitsRgpd(): void
    {
        $this->bookSamir('2026-11-10T14:00:00Z', 'jan@example.be');
        self::clearMails();
        $found = self::json($this->call('POST', '/api/v1/admin/privacy/search', '/api/v1/admin/privacy/search', ['email' => 'JAN@example.be'], [], $this->csrf));
        self::assertCount(1, $found['bookings']);
        $export = $this->call('POST', '/api/v1/admin/privacy/export', '/api/v1/admin/privacy/export', ['email' => 'jan@example.be'], [], $this->csrf);
        self::assertSame('+32 470 12 34 56', self::json($export)['bookings'][0]['phone']);
        $erase = self::json($this->call('POST', '/api/v1/admin/privacy/erase', '/api/v1/admin/privacy/erase', ['email' => 'jan@example.be', 'confirmWithoutEmail' => false], [], $this->csrf));
        self::assertSame(['erased' => 1, 'notified' => 1, 'notifyFailed' => 0], $erase);
        self::assertSame(0, (int) self::pdo()->query('SELECT COUNT(*) FROM booking')->fetchColumn());
        self::assertSame(0, (int) self::pdo()->query('SELECT COUNT(*) FROM booking_slot')->fetchColumn());
        self::assertSame('jan@example.be', self::mails()[0]['to']);
        self::assertSame(422, $this->call('POST', '/api/v1/admin/privacy/search', '/api/v1/admin/privacy/search', ['email' => 'pas-un-email'], [], $this->csrf)->status);
        $this->jar = [];
        self::assertSame(401, $this->call('POST', '/api/v1/admin/privacy/search', '/api/v1/admin/privacy/search', ['email' => 'jan@example.be'], [], $this->csrf)->status);
    }
}

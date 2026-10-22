<?php

declare(strict_types=1);

namespace Barbier\Application\Notification;

use Barbier\Domain\Acces\Crypto;
use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Agenda\Booking;
use Barbier\Domain\Notification\Mail;
use Barbier\Domain\Notification\Mailer;
use Barbier\Domain\Notification\MailLog;
use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Shared\Random;
use Barbier\Domain\Vitrine\Lang;
use Barbier\Domain\Vitrine\Money;
use Barbier\Domain\Vitrine\TranslatedText;
use Barbier\Domain\Vitrine\VitrineRepository;

/**
 * E-mails de réservation (FR-030 à FR-032). Le corps n'est jamais stocké : il est recomposé depuis la
 * réservation à chaque envoi. Un échec d'envoi n'annule jamais la réservation : il est journalisé et
 * relançable ; relancer une confirmation génère un NOUVEAU jeton d'annulation (l'ancien lien meurt).
 */
final class NotificationService
{
    public const SALON_BOOKED = 'salon_booked';
    public const SALON_CANCELLED = 'salon_cancelled';
    public const CLIENT_CONFIRMED = 'client_confirmed';
    public const CLIENT_CANCELLED = 'client_cancelled';

    public function __construct(
        private readonly Mailer $mailer,
        private readonly MailLog $log,
        private readonly Texts $texts,
        private readonly VitrineRepository $vitrine,
        private readonly AgendaStore $agenda,
        private readonly Crypto $crypto,
        private readonly Random $random,
        private readonly string $baseUrl,
    ) {
    }

    /** Après une réservation : e-mail au salon (FR) et confirmation au client (sa langue, .ics joint). */
    public function bookingConfirmed(Booking $b, string $cancelToken): void
    {
        $this->deliver($b, self::SALON_BOOKED, null);
        $this->deliver($b, self::CLIENT_CONFIRMED, $cancelToken);
    }

    /** Après une annulation : on prévient l'autre partie. @return bool true si l'e-mail est parti */
    public function bookingCancelled(Booking $b, ?string $message = null): bool
    {
        return $b->cancelledBy === 'client' ? $this->deliver($b, self::SALON_CANCELLED, null) : $this->deliver($b, self::CLIENT_CANCELLED, null, $message);
    }

    /** Relance d'un envoi en échec (FR-032). @return string nouvel état */
    public function retry(string $logId): string
    {
        $entry = $this->log->find($logId) ?? throw new DomainException('not_found', 'Envoi introuvable.');
        if ($entry['state'] === 'sent') {
            return 'sent';
        }
        $booking = $entry['bookingId'] !== null ? $this->agenda->find($entry['bookingId']) : null;
        if ($booking === null) {
            throw new DomainException('not_found', 'La réservation de cet envoi n’existe plus.');
        }
        $token = null;
        if ($entry['kind'] === self::CLIENT_CONFIRMED) {
            $token = bin2hex($this->random->bytes(32)); // nouveau lien : l'ancien jeton ne vaut plus rien
            $this->agenda->replaceToken($booking->id, $this->crypto->tokenHash($token));
        }
        try {
            $this->mailer->send($this->compose($booking, $entry['kind'], $token, null));
            $this->log->update($logId, 'sent', null);

            return 'sent';
        } catch (\Throwable $e) {
            $this->log->update($logId, 'failed', $e->getMessage());

            return 'failed';
        }
    }

    private function deliver(Booking $b, string $kind, ?string $token, ?string $message = null): bool
    {
        $id = $this->random->uuid();
        try {
            $mail = $this->compose($b, $kind, $token, $message);
        } catch (DomainException $e) {
            $this->log->record($id, $b->id, $kind, $this->recipient($b, $kind), 'failed', $e->getMessage());

            return false;
        }
        try {
            $this->mailer->send($mail);
            $this->log->record($id, $b->id, $kind, $mail->to, 'sent', null);

            return true;
        } catch (\Throwable $e) {
            $this->log->record($id, $b->id, $kind, $mail->to, 'failed', $e->getMessage());

            return false;
        }
    }

    private function recipient(Booking $b, string $kind): string
    {
        return in_array($kind, [self::SALON_BOOKED, self::SALON_CANCELLED], true) ? $this->vitrine->salon()['email'] : $b->client->email;
    }

    private function compose(Booking $b, string $kind, ?string $token, ?string $message): Mail
    {
        $toSalon = in_array($kind, [self::SALON_BOOKED, self::SALON_CANCELLED], true);
        $lang = $toSalon ? 'fr' : $b->lang;
        $l = Lang::tryFrom($lang) ?? Lang::FR;
        $salon = $this->vitrine->salon();
        $service = '';
        foreach ($this->vitrine->catalog(false) as $c) {
            foreach ($c['services'] as $s) {
                if ($s['id'] === $b->serviceId) {
                    $service = (new TranslatedText($s['name'] ?: ['fr' => '—'], 'name', 1000, false))->get($l);
                }
            }
        }
        $barber = '';
        foreach ($this->vitrine->barbers(false) as $br) {
            if ($br['id'] === $b->barberId) {
                $barber = $br['firstName'];
            }
        }
        $slugBook = $this->texts->get($lang, 'slug.book');
        $params = [
            'salon' => $salon['name'], 'service' => $service, 'barber' => $barber, 'when' => $this->texts->when($lang, $b->range->start),
            'duration' => (string) $b->durationMin, 'price' => (new Money($b->priceCents))->format($l),
            'name' => $b->client->name, 'phone' => $b->client->phone, 'email' => $b->client->email, 'lang' => strtoupper($b->lang),
            'address' => str_replace("\n", ', ', (new TranslatedText($salon['address'] ?: ['fr' => ''], 'address', 1000, false))->get($l)),
            'salonPhone' => $salon['phone'],
            'cancelUrl' => $token === null ? '' : rtrim($this->baseUrl, '/') . '/' . $lang . '/' . $this->texts->get($lang, 'slug.cancel') . '/' . $token,
            'bookUrl' => rtrim($this->baseUrl, '/') . '/' . $lang . '/' . $slugBook,
            'message' => $message !== null && trim($message) !== '' ? $this->texts->get($lang, 'mail.message', ['message' => trim($message)]) . "\n" : '',
        ];
        $subject = $this->texts->get($lang, "mail.$kind.subject", $params);
        $text = $this->texts->get($lang, "mail.$kind.body", $params);
        $to = $toSalon ? $salon['email'] : $b->client->email;
        $attachment = $kind === self::CLIENT_CONFIRMED ? ['name' => 'rendez-vous.ics', 'mime' => 'text/calendar; charset=utf-8; method=PUBLISH',
            'content' => self::ics($b, $this->texts->get($lang, 'mail.ics.summary', $params), $params['address'], $salon['email'])] : null;

        return new Mail($to, $subject, $text, $toSalon ? $b->client->email : $salon['email'], $attachment);
    }

    /** Fichier calendrier (RFC 5545) : à ajouter à l'agenda du téléphone. */
    private static function ics(Booking $b, string $summary, string $location, string $organizer): string
    {
        $esc = static fn (string $v): string => str_replace(["\\", ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $v);
        $f = 'Ymd\THis\Z';

        return implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//site-barbier//reservation//FR', 'METHOD:PUBLISH', 'BEGIN:VEVENT',
            'UID:' . $b->id . '@site-barbier', 'DTSTAMP:' . gmdate($f), 'DTSTART:' . $b->range->start->format($f), 'DTEND:' . $b->range->end->format($f),
            'SUMMARY:' . $esc($summary), 'LOCATION:' . $esc($location), 'ORGANIZER:mailto:' . $organizer, 'END:VEVENT', 'END:VCALENDAR', '',
        ]);
    }
}

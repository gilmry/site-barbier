<?php

declare(strict_types=1);

namespace Barbier\Application\Agenda;

use Barbier\Application\Notification\NotificationService;
use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Agenda\Booking;
use Barbier\Domain\Shared\Clock;
use Barbier\Domain\Shared\DomainException;

/**
 * Droits d'accès et d'effacement des clients (FR-012). L'adresse voyage dans le corps des requêtes (jamais
 * dans l'URL). Effacer une réservation future prévient d'abord le client ; si cet e-mail échoue, l'effacement
 * exige une confirmation explicite (aucune relance possible une fois le journal effacé).
 */
final class PrivacyService
{
    public function __construct(private readonly AgendaStore $store, private readonly NotificationService $notifications, private readonly Clock $clock)
    {
    }

    /** @return list<array<string,mixed>> */
    public function search(string $email): array
    {
        if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('invalid_email', 'Adresse e-mail invalide.', 'email');
        }

        return $this->store->byEmail($email);
    }

    /** Export JSON des données d'une adresse (droit d'accès). @return array<string,mixed> */
    public function export(string $email): array
    {
        $rows = $this->search($email);

        return ['email' => mb_strtolower(trim($email)), 'exportedAt' => $this->clock->now()->format('c'), 'bookings' => array_map(static fn (array $b): array => [
            'service' => $b['serviceId'], 'barber' => $b['barberName'], 'startsAt' => $b['startsAt'], 'state' => $b['state'], 'name' => $b['clientName'],
            'phone' => $b['clientPhone'], 'email' => $b['clientEmail'], 'lang' => $b['lang'], 'createdAt' => $b['createdAt']], $rows)];
    }

    /** @return array{erased:int, notified:int, notifyFailed:int} */
    public function erase(string $email, bool $confirmWithoutEmail): array
    {
        $rows = $this->search($email);
        $now = $this->clock->now();
        $notified = 0;
        $failed = 0;
        foreach ($rows as $row) {
            if ($row['state'] === Booking::CONFIRMED && $row['startsAt'] > $now->format('Y-m-d H:i:s')) {
                $booking = $this->store->find($row['id']);
                if ($booking !== null) {
                    $cancelled = $booking->cancelBySalon($now);
                    $this->store->transactional(function () use ($cancelled): void {
                        $this->store->lockBarbers([$cancelled->barberId]);
                        $this->store->markCancelled($cancelled);
                    });
                    $this->notifications->bookingCancelled($cancelled, null) ? $notified++ : $failed++;
                }
            }
        }
        if ($failed > 0 && !$confirmWithoutEmail) {
            throw new DomainException('notify_failed', "L'e-mail d'annulation n'a pas pu partir : prévenez le client par téléphone, puis confirmez l'effacement.");
        }
        foreach ($rows as $row) {
            $this->store->transactional(function () use ($row): void {
                $this->store->lockBarbers([$row['barberId']]);
                $this->store->delete($row['id']);
            });
        }

        return ['erased' => count($rows), 'notified' => $notified, 'notifyFailed' => $failed];
    }
}

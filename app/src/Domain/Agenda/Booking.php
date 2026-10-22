<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

use Barbier\Domain\Shared\DomainException;

/** Réservation (FR-023 à FR-026) : confirmée → annulée (par le client ou le salon), jamais l'inverse. */
final class Booking
{
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';

    public function __construct(
        public readonly string $id,
        public readonly string $barberId,
        public readonly string $serviceId,
        public readonly TimeRange $range,
        public readonly int $priceCents,
        public readonly int $durationMin,
        public readonly string $lang,
        public readonly ClientContact $client,
        public readonly string $state = self::CONFIRMED,
        public readonly ?string $cancelledBy = null,
    ) {
        if ($range->minutes() !== $durationMin) {
            throw new DomainException('invalid_booking', 'La durée réservée doit être celle de la prestation.');
        }
        if (!in_array($state, [self::CONFIRMED, self::CANCELLED], true)) {
            throw new DomainException('invalid_booking', 'État de réservation inconnu.');
        }
    }

    /** Annulation par le client (FR-025) : au plus tard 2 h avant le rendez-vous. */
    public function cancelByClient(\DateTimeImmutable $now, SlotPolicy $policy): self
    {
        if ($this->state === self::CANCELLED) {
            throw new DomainException('already_cancelled', 'Cette réservation est déjà annulée.');
        }
        if ($this->range->start->getTimestamp() - $now->getTimestamp() < $policy->cancelLimitMinutes * 60) {
            throw new DomainException('too_late', 'Il est trop tard pour annuler en ligne : appelez le salon.');
        }

        return $this->cancelled('client');
    }

    /** Annulation par le salon (FR-026) : possible tant que le rendez-vous n'est pas passé. */
    public function cancelBySalon(\DateTimeImmutable $now): self
    {
        if ($this->state === self::CANCELLED) {
            throw new DomainException('already_cancelled', 'Cette réservation est déjà annulée.');
        }

        return $this->cancelled('salon');
    }

    private function cancelled(string $by): self
    {
        return new self($this->id, $this->barberId, $this->serviceId, $this->range, $this->priceCents, $this->durationMin, $this->lang, $this->client, self::CANCELLED, $by);
    }
}

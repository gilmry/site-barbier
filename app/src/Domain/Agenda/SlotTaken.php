<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

use Barbier\Domain\Shared\DomainException;

/** Créneau pris entre-temps (FR-023) : traduit en 409, le client choisit un autre créneau. */
final class SlotTaken extends DomainException
{
    public function __construct()
    {
        parent::__construct('slot_taken', 'Ce créneau vient d’être réservé. Choisissez-en un autre.');
    }
}

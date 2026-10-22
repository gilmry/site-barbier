<?php

declare(strict_types=1);

namespace Barbier\Domain\Shared;

/** Horloge (port) : le domaine ne lit jamais l'heure système directement. Toujours en UTC. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}

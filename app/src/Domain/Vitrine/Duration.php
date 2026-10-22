<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

use Barbier\Domain\Shared\DomainException;

/** Durée d'une prestation : multiple de 5 min, de 5 à 240 min (PRD §4, unité de durée). */
final class Duration
{
    public const MAX = 240;

    public function __construct(public readonly int $minutes)
    {
        if ($minutes <= 0 || $minutes > self::MAX || $minutes % 5 !== 0) {
            throw new DomainException('invalid_duration', 'La durée doit être un multiple de 5 minutes, de 5 à 240.', 'duration_min');
        }
    }

    public function format(): string
    {
        return $this->minutes . ' min';
    }
}

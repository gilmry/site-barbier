<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

use Barbier\Domain\Shared\DomainException;

/** Horaires d'un jour (ISO : 1 = lundi … 7 = dimanche). opens/closes « HH:MM » ou null (fermé). */
final class DaySchedule
{
    public function __construct(public readonly int $weekday, public readonly ?string $opens, public readonly ?string $closes)
    {
        if ($weekday < 1 || $weekday > 7) {
            throw new DomainException('invalid_weekday', 'Jour de semaine invalide.', 'weekday');
        }
        if (($opens === null) !== ($closes === null)) {
            throw new DomainException('invalid_hours', "Indiquez l'ouverture et la fermeture, ou « fermé ».", "hours.$weekday");
        }
        if ($opens !== null) {
            if (!self::valid($opens) || !self::valid((string) $closes) || $closes <= $opens) {
                throw new DomainException('invalid_hours', 'La fermeture doit suivre l’ouverture (HH:MM).', "hours.$weekday");
            }
        }
    }

    public function isClosed(): bool
    {
        return $this->opens === null;
    }

    public static function minutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

    private static function valid(string $t): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t) === 1 || $t === '24:00';
    }
}

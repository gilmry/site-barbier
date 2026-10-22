<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

/** Conservation (FR-027) : effacement le lendemain des 6 mois révolus après la date du rendez-vous. */
final class RetentionPolicy
{
    public function __construct(public readonly int $months = 6)
    {
    }

    /** Un rendez-vous dont le jour local est STRICTEMENT antérieur à cette date est purgé. */
    public function cutoffDate(\DateTimeImmutable $now, \DateTimeZone $tz): string
    {
        return $now->setTimezone($tz)->setTime(0, 0)->modify("-{$this->months} months")->format('Y-m-d');
    }
}

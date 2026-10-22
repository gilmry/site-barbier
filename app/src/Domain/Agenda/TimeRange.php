<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

use Barbier\Domain\Shared\DomainException;

/** Intervalle [début, fin[ en UTC. */
final class TimeRange
{
    public function __construct(public readonly \DateTimeImmutable $start, public readonly \DateTimeImmutable $end)
    {
        if ($end <= $start) {
            throw new DomainException('invalid_range', 'La fin doit suivre le début.', 'ends_at');
        }
    }

    public static function utc(string $start, string $end): self
    {
        $tz = new \DateTimeZone('UTC');

        return new self(new \DateTimeImmutable($start, $tz), new \DateTimeImmutable($end, $tz));
    }

    public function overlaps(self $o): bool
    {
        return $this->start < $o->end && $o->start < $this->end;
    }

    public function minutes(): int
    {
        return intdiv($this->end->getTimestamp() - $this->start->getTimestamp(), 60);
    }
}

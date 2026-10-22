<?php

declare(strict_types=1);

namespace Barbier\Tests\Fakes;

use Barbier\Domain\Shared\Clock;

final class FakeClock implements Clock
{
    public function __construct(public \DateTimeImmutable $now = new \DateTimeImmutable('2026-10-22 10:00:00', new \DateTimeZone('UTC')))
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(int $seconds): void
    {
        $this->now = $this->now->modify("+$seconds seconds");
    }
}

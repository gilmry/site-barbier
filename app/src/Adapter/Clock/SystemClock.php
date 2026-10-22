<?php

declare(strict_types=1);

namespace Barbier\Adapter\Clock;

use Barbier\Domain\Shared\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}

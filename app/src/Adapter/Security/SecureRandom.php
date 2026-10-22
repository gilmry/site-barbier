<?php

declare(strict_types=1);

namespace Barbier\Adapter\Security;

use Barbier\Domain\Shared\Random;

final class SecureRandom implements Random
{
    public function bytes(int $length): string
    {
        return random_bytes($length);
    }

    public function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}

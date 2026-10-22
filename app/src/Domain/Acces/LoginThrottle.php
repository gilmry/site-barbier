<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

/**
 * Verrou progressif (FR-040 @security) : à partir de 5 échecs consécutifs, attente de 1 min, puis
 * doublée à chaque échec supplémentaire, plafonnée à 1 h. Un succès remet le compteur à zéro.
 */
final class LoginThrottle
{
    public const FREE_ATTEMPTS = 5;
    public const BASE_DELAY = 60;
    public const MAX_DELAY = 3600;

    public static function lockSeconds(int $failedAttempts): int
    {
        if ($failedAttempts < self::FREE_ATTEMPTS) {
            return 0;
        }

        return min(self::MAX_DELAY, self::BASE_DELAY * (2 ** min(10, $failedAttempts - self::FREE_ATTEMPTS)));
    }
}

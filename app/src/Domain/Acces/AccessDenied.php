<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

use Barbier\Domain\Shared\DomainException;

/**
 * Refus d'accès au message volontairement unique (pas d'énumération de compte, FR-040 @security).
 * `retryAfter` > 0 quand le compte est verrouillé.
 */
final class AccessDenied extends DomainException
{
    public function __construct(string $reason, public readonly int $retryAfter = 0)
    {
        parent::__construct($reason, $retryAfter > 0
            ? "Trop d'essais. Réessayez dans " . (int) ceil($retryAfter / 60) . ' min.'
            : 'Identifiants ou code incorrects.');
    }
}

<?php

declare(strict_types=1);

namespace Barbier\Domain\Shared;

/**
 * Violation d'une règle métier. `code` est stable (traduit en HTTP par la seule couche Http),
 * `field` désigne le champ fautif quand il y en a un.
 */
class DomainException extends \DomainException
{
    public function __construct(public readonly string $reason, string $message, public readonly ?string $field = null)
    {
        parent::__construct($message);
    }
}

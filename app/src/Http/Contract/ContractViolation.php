<?php

declare(strict_types=1);

namespace Barbier\Http\Contract;

use RuntimeException;

/** Requête non conforme au contrat : traduite en 400 problem+json, champ par champ. */
final class ContractViolation extends RuntimeException
{
    /** @param list<array{field:string, message:string}> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Requête non conforme au contrat');
    }
}

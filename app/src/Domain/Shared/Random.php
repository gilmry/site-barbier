<?php

declare(strict_types=1);

namespace Barbier\Domain\Shared;

/** Source d'aléa cryptographique (port). */
interface Random
{
    public function bytes(int $length): string;

    /** Identifiant unique (UUID v4) pour les entités : aucune clé auto-incrémentée (G2). */
    public function uuid(): string;
}

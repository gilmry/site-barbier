<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

/** Primitives cryptographiques (port) : hachage de mot de passe, coffre à secrets, empreinte de jeton. */
interface Crypto
{
    public function hashPassword(string $password): string;

    public function verifyPassword(string $password, string $hash): bool;

    /** Chiffre un secret (TOTP) avec la clé de l'application. */
    public function seal(string $plain): string;

    public function open(string $sealed): ?string;

    /** Empreinte HMAC-SHA256 (clé de l'application) d'un jeton à forte entropie : codes, jetons, IP. */
    public function tokenHash(string $token): string;
}

<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

use Barbier\Domain\Shared\DomainException;

/** Politique de mot de passe (FR-040 @security) : ≥ 12 caractères, hors liste des mots de passe courants, sans l'e-mail. */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /** @param list<string> $commonPasswords liste embarquée (minuscules) */
    public function __construct(private readonly array $commonPasswords)
    {
    }

    public function assertAcceptable(string $password, string $email): void
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            throw new DomainException('weak_password', 'Le mot de passe doit faire au moins 12 caractères.', 'password');
        }
        if (mb_strlen($password) > 200) {
            throw new DomainException('weak_password', 'Le mot de passe est trop long (200 caractères au plus).', 'password');
        }
        $lower = mb_strtolower($password);
        if (in_array($lower, $this->commonPasswords, true)) {
            throw new DomainException('weak_password', 'Ce mot de passe est trop courant.', 'password');
        }
        $local = mb_strtolower(explode('@', $email)[0]);
        if ($local !== '' && mb_strlen($local) >= 4 && str_contains($lower, $local)) {
            throw new DomainException('weak_password', "Le mot de passe ne doit pas contenir l'adresse e-mail.", 'password');
        }
    }
}

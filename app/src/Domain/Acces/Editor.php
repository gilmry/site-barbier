<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

use Barbier\Domain\Shared\DomainException;

/** L'éditeur (le gérant, compte unique au MVP). Invariants dans le constructeur. */
final class Editor
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly ?string $totpSecretSealed = null,
        public readonly bool $totpConfirmed = false,
        public readonly ?int $lastTotpStep = null,
        public readonly int $failedAttempts = 0,
        public readonly ?\DateTimeImmutable $lockedUntil = null,
    ) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
            throw new DomainException('invalid_email', 'Adresse e-mail invalide.', 'email');
        }
        if ($passwordHash === '') {
            throw new DomainException('invalid_editor', 'Empreinte de mot de passe manquante.');
        }
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function isLocked(\DateTimeImmutable $now): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > $now;
    }

    public function withFailure(\DateTimeImmutable $now): self
    {
        $failed = $this->failedAttempts + 1;
        $lock = LoginThrottle::lockSeconds($failed);

        return new self($this->id, $this->email, $this->passwordHash, $this->totpSecretSealed, $this->totpConfirmed,
            $this->lastTotpStep, $failed, $lock > 0 ? $now->modify("+$lock seconds") : null);
    }

    public function withSuccess(): self
    {
        return new self($this->id, $this->email, $this->passwordHash, $this->totpSecretSealed, $this->totpConfirmed, $this->lastTotpStep, 0, null);
    }

    public function withPendingTotp(string $sealedSecret): self
    {
        return new self($this->id, $this->email, $this->passwordHash, $sealedSecret, false, null, $this->failedAttempts, $this->lockedUntil);
    }

    public function withConfirmedTotp(int $step): self
    {
        return new self($this->id, $this->email, $this->passwordHash, $this->totpSecretSealed, true, $step, 0, null);
    }

    public function withTotpStep(int $step): self
    {
        return new self($this->id, $this->email, $this->passwordHash, $this->totpSecretSealed, $this->totpConfirmed, $step, 0, null);
    }
}

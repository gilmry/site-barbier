<?php

declare(strict_types=1);

namespace Barbier\Http;

/** Session d'administration courante (lue en base à partir du cookie). */
final class AdminSession
{
    public const PRE_ENROLMENT = 'pre_enrolment'; // après /install ou mot de passe sans TOTP confirmé
    public const PASSWORD_OK = 'password_ok';     // mot de passe vérifié, code TOTP attendu
    public const FULL = 'full';                   // mot de passe + TOTP
    public const RECOVERY = 'recovery';           // ouverte par code de secours : réinitialisation du TOTP seulement

    public function __construct(
        public readonly string $idHash,
        public readonly string $editorId,
        public readonly string $stage,
        public readonly string $csrf,
        public readonly ?\DateTimeImmutable $totpVerifiedAt,
    ) {
    }

    /** TOTP récent (FR-041) : code vérifié depuis moins de 10 minutes. */
    public function hasRecentTotp(\DateTimeImmutable $now): bool
    {
        return $this->stage === self::FULL && $this->totpVerifiedAt !== null && $now->getTimestamp() - $this->totpVerifiedAt->getTimestamp() < 600;
    }
}

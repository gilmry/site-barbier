<?php

declare(strict_types=1);

namespace Barbier\Application\Acces;

use Barbier\Domain\Acces\AccessDenied;
use Barbier\Domain\Acces\Crypto;
use Barbier\Domain\Acces\Editor;
use Barbier\Domain\Acces\EditorRepository;
use Barbier\Domain\Acces\PasswordPolicy;
use Barbier\Domain\Acces\RecoveryCodes;
use Barbier\Domain\Acces\RecoveryCodeStore;
use Barbier\Domain\Acces\Totp;
use Barbier\Domain\Notification\SecurityNotifier;
use Barbier\Domain\Shared\Clock;
use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Shared\Random;

/**
 * Cas d'usage de l'accès (FR-040 à FR-043, FR-042 pour l'installation). Orchestration seulement :
 * les règles vivent dans le domaine (Totp, LoginThrottle, PasswordPolicy, Editor). La session elle-même
 * est tenue par la couche Http, qui applique le résultat de chaque cas d'usage.
 */
final class AccessService
{
    public const ISSUER = 'Site du salon';
    /** Empreinte bcrypt valide d'un mot de passe sans compte : vérifiée quand l'e-mail est inconnu. */
    private const DUMMY_HASH = '$2y$12$tExlM0IgzBl2lstOTS40reuQKi1W86sgZBR/.pATfPnfk9nf1Oxbi';

    public function __construct(
        private readonly EditorRepository $editors,
        private readonly RecoveryCodeStore $codes,
        private readonly Crypto $crypto,
        private readonly PasswordPolicy $policy,
        private readonly Clock $clock,
        private readonly Random $random,
        private readonly ?SecurityNotifier $notifier = null,
    ) {
    }

    public function isInstalled(): bool
    {
        return $this->editors->count() > 0;
    }

    /** FR-042 : création du compte unique, protégée par le code d'installation de config.php. */
    public function install(string $email, string $password, string $passwordAgain, string $givenCode, string $expectedCode): string
    {
        if ($this->isInstalled()) {
            throw new DomainException('already_installed', 'Le site est déjà installé.');
        }
        if (strlen($expectedCode) < 12 || !hash_equals($expectedCode, $givenCode)) {
            throw new DomainException('bad_install_code', "Code d'installation incorrect.", 'install_code');
        }
        if ($password !== $passwordAgain) {
            throw new DomainException('password_mismatch', 'Les deux mots de passe diffèrent.', 'password_again');
        }
        $email = Editor::normalizeEmail($email);
        $this->policy->assertAcceptable($password, $email);
        $editor = new Editor($this->random->uuid(), $email, $this->crypto->hashPassword($password));
        $this->editors->save($editor);

        return $editor->id;
    }

    /**
     * Étape 1 de la connexion. @return array{editorId:string, enrolled:bool}
     * Le compte est verrouillé progressivement après 5 échecs (mot de passe ou code confondus).
     */
    public function checkPassword(string $email, string $password): array
    {
        $now = $this->clock->now();
        $editor = $this->editors->findByEmail($email);
        if ($editor === null) {
            $this->crypto->verifyPassword($password, self::DUMMY_HASH); // temps constant : pas d'énumération par la durée
            throw new AccessDenied('invalid_credentials');
        }
        $this->assertNotLocked($editor, $now);
        if (!$this->crypto->verifyPassword($password, $editor->passwordHash)) {
            $this->fail($editor, $now);
        }

        return ['editorId' => $editor->id, 'enrolled' => $editor->totpConfirmed];
    }

    /** Étape 2 : code de l'appli d'authentification (anti-rejeu par pas déjà utilisé). */
    public function verifyTotp(string $editorId, string $code): void
    {
        $now = $this->clock->now();
        $editor = $this->require($editorId);
        $this->assertNotLocked($editor, $now);
        $secret = $editor->totpConfirmed && $editor->totpSecretSealed !== null ? $this->crypto->open($editor->totpSecretSealed) : null;
        $step = $secret === null ? null : Totp::verify($secret, trim($code), $now->getTimestamp(), $editor->lastTotpStep);
        if ($step === null) {
            $this->fail($editor, $now);
        }
        $this->editors->save($editor->withTotpStep($step));
    }

    /** Connexion par code de secours (FR-043) : ouvre une session « secours ». @return int codes restants */
    public function useRecoveryCode(string $editorId, string $code): int
    {
        $now = $this->clock->now();
        $editor = $this->require($editorId);
        $this->assertNotLocked($editor, $now);
        if (!$this->codes->consume($editor->id, $this->crypto->tokenHash(RecoveryCodes::normalize($code)), $now)) {
            $this->fail($editor, $now);
        }
        $this->editors->save($editor->withSuccess());

        return $this->codes->remaining($editor->id);
    }

    /**
     * Démarre un (ré)enrôlement : nouveau secret, en attente de confirmation par un premier code. L'ancien
     * secret est remplacé. La couche Http n'y donne accès qu'en session pré-enrôlement, « secours » ou
     * avec un TOTP récent.
     *
     * @return array{secret:string, uri:string}
     */
    public function startEnrollment(string $editorId): array
    {
        $editor = $this->require($editorId);
        $secret = $this->random->bytes(20);
        $this->editors->save($editor->withPendingTotp($this->crypto->seal($secret)));

        return ['secret' => Totp::base32($secret), 'uri' => Totp::provisioningUri($secret, $editor->email, self::ISSUER)];
    }

    /** Confirme l'enrôlement avec un premier code ; invalide tous les anciens codes de secours. @return list<string> */
    public function confirmEnrollment(string $editorId, string $code): array
    {
        $now = $this->clock->now();
        $editor = $this->require($editorId);
        $this->assertNotLocked($editor, $now);
        $secret = $editor->totpSecretSealed !== null && !$editor->totpConfirmed ? $this->crypto->open($editor->totpSecretSealed) : null;
        if ($secret === null) {
            throw new DomainException('no_pending_enrollment', "Aucun enrôlement en cours : recommencez l'activation.");
        }
        $step = Totp::verify($secret, trim($code), $now->getTimestamp(), null);
        if ($step === null) {
            $this->fail($editor, $now);
        }
        $this->editors->save($editor->withConfirmedTotp($step));

        return $this->regenerateRecoveryCodes($editor->id);
    }

    /** @return list<string> les codes en clair, à afficher une seule fois */
    public function regenerateRecoveryCodes(string $editorId): array
    {
        $codes = RecoveryCodes::generate(fn (int $n): string => $this->random->bytes($n));
        $this->codes->replace($editorId, array_map(fn (string $c): string => $this->crypto->tokenHash($c), $codes));

        return $codes;
    }

    public function remainingRecoveryCodes(string $editorId): int
    {
        return $this->codes->remaining($editorId);
    }

    /** Réinitialisation d'urgence (FR-043) : le propriétaire du serveur a déposé le fichier jeton ; on vérifie le mot de passe. */
    public function emergencyReset(string $email, string $password): string
    {
        ['editorId' => $id] = $this->checkPassword($email, $password);
        $editor = $this->require($id);
        $this->editors->save(new Editor($editor->id, $editor->email, $editor->passwordHash));
        $this->codes->replace($editor->id, []);

        return $editor->id;
    }

    public function editor(string $editorId): Editor
    {
        return $this->require($editorId);
    }

    private function require(string $editorId): Editor
    {
        $editor = $this->editors->find($editorId);
        if ($editor === null) {
            throw new AccessDenied('invalid_credentials');
        }

        return $editor;
    }

    private function assertNotLocked(Editor $editor, \DateTimeImmutable $now): void
    {
        if ($editor->isLocked($now)) {
            throw new AccessDenied('locked', max(1, $editor->lockedUntil->getTimestamp() - $now->getTimestamp()));
        }
    }

    private function fail(Editor $editor, \DateTimeImmutable $now): never
    {
        $failed = $editor->withFailure($now);
        $this->editors->save($failed);
        if ($failed->isLocked($now) && !$editor->isLocked($now) && $failed->failedAttempts === \Barbier\Domain\Acces\LoginThrottle::FREE_ATTEMPTS) {
            $this->notifier?->notify('account_locked', 'compte ' . $editor->email . ' verrouillé après ' . $failed->failedAttempts . ' échecs');
        }
        throw new AccessDenied($failed->isLocked($now) ? 'locked' : 'invalid_credentials', $failed->isLocked($now) ? $failed->lockedUntil->getTimestamp() - $now->getTimestamp() : 0);
    }
}

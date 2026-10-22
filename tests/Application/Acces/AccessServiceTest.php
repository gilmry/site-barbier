<?php

declare(strict_types=1);

namespace Barbier\Tests\Application\Acces;

use Barbier\Adapter\Security\SecureRandom;
use Barbier\Adapter\Security\SodiumCrypto;
use Barbier\Application\Acces\AccessService;
use Barbier\Domain\Acces\AccessDenied;
use Barbier\Domain\Acces\PasswordPolicy;
use Barbier\Domain\Acces\Totp;
use Barbier\Domain\Shared\DomainException;
use Barbier\Tests\Fakes\Base32;
use Barbier\Tests\Fakes\FakeClock;
use Barbier\Tests\Fakes\InMemoryAccess;
use PHPUnit\Framework\TestCase;

/**
 * Fonctionnalité : accès du gérant (FR-040 à FR-043).
 * Scénarios en langage métier, cas d'usage sur doublures (sans base).
 */
final class AccessServiceTest extends TestCase
{
    private const CODE = 'installation-de-test';
    private const PW = 'Cèdre-lame-serviette-9';
    private InMemoryAccess $store;
    private FakeClock $clock;
    private AccessService $svc;

    protected function setUp(): void
    {
        $this->store = new InMemoryAccess();
        $this->clock = new FakeClock();
        $this->svc = new AccessService($this->store, $this->store, new SodiumCrypto(base64_encode(str_repeat('k', 32))), new PasswordPolicy([]), $this->clock, new SecureRandom());
    }

    /** @return array{0:string, 1:string} id, secret brut */
    private function installedAndEnrolled(): array
    {
        $id = $this->svc->install('Gerant@Salon.be', self::PW, self::PW, self::CODE, self::CODE);
        $secret = Base32::decode($this->svc->startEnrollment($id)['secret']);
        $this->svc->confirmEnrollment($id, Totp::code($secret, intdiv($this->clock->now()->getTimestamp(), 30)));
        $this->clock->advance(31);

        return [$id, $secret];
    }

    private function codeNow(string $secret): string
    {
        return Totp::code($secret, intdiv($this->clock->now()->getTimestamp(), 30));
    }

    /** @happy Étant donné un site installé et un double facteur activé, quand le gérant saisit mot de passe puis code, alors il est connecté */
    public function testInstallationEnrolementConnexion(): void
    {
        [$id, $secret] = $this->installedAndEnrolled();
        self::assertSame(['editorId' => $id, 'enrolled' => true], $this->svc->checkPassword('gerant@salon.be', self::PW));
        $this->svc->verifyTotp($id, $this->codeNow($secret));
        self::assertSame(10, $this->svc->remainingRecoveryCodes($id));
    }

    /** @negative mauvais code d'installation ; installation rejouée ; mots de passe différents */
    public function testInstallationRefusee(): void
    {
        foreach ([[self::PW, 'faux-code-xxxxx'], ['autre-mot-de-passe-12', self::CODE]] as [$again, $code]) {
            try {
                $this->svc->install('g@salon.be', self::PW, $again, $code, self::CODE);
                self::fail('installation acceptée à tort');
            } catch (DomainException) {
                self::assertSame(0, $this->store->count());
            }
        }
        $this->svc->install('g@salon.be', self::PW, self::PW, self::CODE, self::CODE);
        $this->expectException(DomainException::class);
        $this->svc->install('h@salon.be', self::PW, self::PW, self::CODE, self::CODE);
    }

    /** @negative message unique : compte inconnu et mauvais mot de passe sont indiscernables */
    public function testPasDEnumeration(): void
    {
        $this->installedAndEnrolled();
        $messages = [];
        foreach (['inconnu@salon.be', 'gerant@salon.be'] as $email) {
            try {
                $this->svc->checkPassword($email, 'mauvais-mot-de-passe');
            } catch (AccessDenied $e) {
                $messages[] = $e->getMessage();
            }
        }
        self::assertCount(2, $messages);
        self::assertSame($messages[0], $messages[1]);
    }

    /** @edge 5 échecs → verrou d'une minute, même avec le bon mot de passe ; levé ensuite */
    public function testVerrouillageProgressif(): void
    {
        [$id] = $this->installedAndEnrolled();
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->svc->checkPassword('gerant@salon.be', 'mauvais');
            } catch (AccessDenied) {
            }
        }
        try {
            $this->svc->checkPassword('gerant@salon.be', self::PW);
            self::fail('compte non verrouillé');
        } catch (AccessDenied $e) {
            self::assertGreaterThan(0, $e->retryAfter);
        }
        $this->clock->advance(61);
        self::assertSame($id, $this->svc->checkPassword('gerant@salon.be', self::PW)['editorId']);
    }

    /** @security anti-rejeu : un code TOTP accepté ne sert pas deux fois */
    public function testCodeTotpNonRejouable(): void
    {
        [$id, $secret] = $this->installedAndEnrolled();
        $code = $this->codeNow($secret);
        $this->svc->verifyTotp($id, $code);
        $this->expectException(AccessDenied::class);
        $this->svc->verifyTotp($id, $code);
    }

    /** @security téléphone perdu : un code de secours sert une fois ; la réactivation invalide l'ancien secret et les anciens codes */
    public function testCodesDeSecoursEtReactivation(): void
    {
        [$id, $oldSecret] = $this->installedAndEnrolled();
        $this->svc->startEnrollment($id); // remplace le secret (session « secours » côté Http)
        $codes = $this->svc->regenerateRecoveryCodes($id);
        self::assertSame(9, $this->svc->useRecoveryCode($id, strtolower($codes[0])));
        try {
            $this->svc->useRecoveryCode($id, $codes[0]);
            self::fail('code de secours rejoué');
        } catch (AccessDenied) {
        }
        $newSecret = Base32::decode($this->svc->startEnrollment($id)['secret']);
        $this->clock->advance(31);
        $fresh = $this->svc->confirmEnrollment($id, $this->codeNow($newSecret));
        self::assertNotContains($codes[1], $fresh);
        $this->clock->advance(31);
        $this->expectException(AccessDenied::class);
        $this->svc->verifyTotp($id, $this->codeNow($oldSecret));
    }
}

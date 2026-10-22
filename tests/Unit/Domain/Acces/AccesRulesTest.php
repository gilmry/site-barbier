<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Domain\Acces;

use Barbier\Domain\Acces\Editor;
use Barbier\Domain\Acces\LoginThrottle;
use Barbier\Domain\Acces\PasswordPolicy;
use Barbier\Domain\Acces\RecoveryCodes;
use Barbier\Domain\Shared\DomainException;
use PHPUnit\Framework\TestCase;

final class AccesRulesTest extends TestCase
{
    private function policy(): PasswordPolicy
    {
        return new PasswordPolicy(['azertyuiop12', 'motdepasse123']);
    }

    /** @happy */
    public function testMotDePasseAcceptable(): void
    {
        $this->policy()->assertAcceptable('Cèdre-lame-serviette-9', 'antoine@salon.be');
        $this->addToAssertionCount(1);
    }

    /** @negative trop court, trop courant (casse ignorée), contient l'e-mail */
    public function testMotsDePasseRefuses(): void
    {
        foreach (['court-11car', 'MotDePasse123', 'antoine-2026-salon'] as $pw) {
            try {
                $this->policy()->assertAcceptable($pw, 'antoine@salon.be');
                self::fail("accepté à tort : $pw");
            } catch (DomainException $e) {
                self::assertSame('password', $e->field);
            }
        }
    }

    /** @edge verrou : gratuit jusqu'à 4 échecs, 1 min au 5e, doublé, plafonné à 1 h */
    public function testVerrouProgressif(): void
    {
        self::assertSame(0, LoginThrottle::lockSeconds(4));
        self::assertSame(60, LoginThrottle::lockSeconds(5));
        self::assertSame(120, LoginThrottle::lockSeconds(6));
        self::assertSame(3600, LoginThrottle::lockSeconds(50));
    }

    /** @edge le compte verrouillé se déverrouille à l'échéance ; un succès remet à zéro */
    public function testEditorVerrouEtSucces(): void
    {
        $now = new \DateTimeImmutable('2026-10-22 10:00:00');
        $e = new Editor('id', 'g@salon.be', 'h');
        for ($i = 0; $i < 5; $i++) {
            $e = $e->withFailure($now);
        }
        self::assertTrue($e->isLocked($now));
        self::assertFalse($e->isLocked($now->modify('+61 seconds')));
        self::assertSame(0, $e->withSuccess()->failedAttempts);
    }

    /** @security codes de secours : 10, uniques, alphabet sans ambiguïté, saisie tolérante */
    public function testCodesDeSecours(): void
    {
        $codes = RecoveryCodes::generate(static fn (int $n): string => random_bytes($n));
        self::assertCount(10, $codes);
        self::assertCount(10, array_unique($codes));
        foreach ($codes as $c) {
            self::assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{5}-[2-9A-HJ-NP-Z]{5}$/', $c);
        }
        self::assertSame('ABCDE-23456', RecoveryCodes::normalize(' abcde 23456 '));
    }

    /** @negative e-mail invalide refusé dès la construction */
    public function testEditorEmailInvalide(): void
    {
        $this->expectException(DomainException::class);
        new Editor('id', 'pas-un-email', 'h');
    }
}

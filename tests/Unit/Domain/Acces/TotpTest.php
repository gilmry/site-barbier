<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Domain\Acces;

use Barbier\Domain\Acces\Totp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    private const RFC_SECRET = '12345678901234567890'; // RFC 6238, annexe B (SHA1)

    /** @return array<string, array{int, string}> 6 derniers chiffres des vecteurs à 8 chiffres de la RFC */
    public static function rfcVectors(): array
    {
        return ['59' => [59, '287082'], '1111111109' => [1111111109, '081804'], '1111111111' => [1111111111, '050471'],
            '1234567890' => [1234567890, '005924'], '2000000000' => [2000000000, '279037']];
    }

    /** @happy vecteurs de test officiels */
    #[DataProvider('rfcVectors')]
    public function testVecteursRfc6238(int $time, string $expected): void
    {
        self::assertSame($expected, Totp::code(self::RFC_SECRET, intdiv($time, 30)));
        self::assertSame(intdiv($time, 30), Totp::verify(self::RFC_SECRET, $expected, $time, null));
    }

    /** @negative mauvais code, format invalide */
    public function testCodeFauxOuMalForme(): void
    {
        self::assertNull(Totp::verify(self::RFC_SECRET, '000000', 59, null));
        self::assertNull(Totp::verify(self::RFC_SECRET, '28708', 59, null));
        self::assertNull(Totp::verify(self::RFC_SECRET, '28708a', 59, null));
    }

    /** @edge décalage d'horloge : ±30 s accepté, 2 min refusé */
    public function testFenetreDeTolerance(): void
    {
        $code = Totp::code(self::RFC_SECRET, intdiv(1111111111, 30));
        self::assertNotNull(Totp::verify(self::RFC_SECRET, $code, 1111111111 + 30, null));
        self::assertNotNull(Totp::verify(self::RFC_SECRET, $code, 1111111111 - 30, null));
        self::assertNull(Totp::verify(self::RFC_SECRET, $code, 1111111111 + 120, null));
    }

    /** @security anti-rejeu : un pas déjà utilisé (ou antérieur) est refusé */
    public function testAntiRejeu(): void
    {
        $step = intdiv(1234567890, 30);
        $code = Totp::code(self::RFC_SECRET, $step);
        self::assertNull(Totp::verify(self::RFC_SECRET, $code, 1234567890, $step));
        self::assertNull(Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $step - 1), 1234567890, $step));
    }

    public function testBase32EtUri(): void
    {
        self::assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', Totp::base32(self::RFC_SECRET));
        self::assertStringStartsWith('otpauth://totp/Salon:g%40x.be?secret=GEZDG', Totp::provisioningUri(self::RFC_SECRET, 'g@x.be', 'Salon'));
    }
}

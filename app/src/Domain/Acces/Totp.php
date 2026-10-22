<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

/**
 * TOTP — RFC 6238 (HMAC-SHA1, pas de 30 s, 6 chiffres), compatible avec les applis d'authentification.
 * Pur : l'heure et le secret sont fournis. Anti-rejeu : un pas déjà utilisé est refusé.
 */
final class Totp
{
    public const PERIOD = 30;
    public const DIGITS = 6;
    public const WINDOW = 1; // ±1 pas = ±30 s de décalage d'horloge toléré

    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), $secret, true);
        $offset = ord($hash[19]) & 0x0f;
        $bin = ((ord($hash[$offset]) & 0x7f) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($bin % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** @return int|null le pas accepté, ou null si le code est faux ou déjà utilisé */
    public static function verify(string $secret, string $code, int $unixTime, ?int $lastUsedStep): ?int
    {
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }
        $current = intdiv($unixTime, self::PERIOD);
        for ($delta = -self::WINDOW; $delta <= self::WINDOW; $delta++) {
            $step = $current + $delta;
            if (hash_equals(self::code($secret, $step), $code)) {
                return ($lastUsedStep !== null && $step <= $lastUsedStep) ? null : $step;
            }
        }

        return null;
    }

    public static function base32(string $bytes): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    /** URI otpauth:// lue par les applis (QR code). */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($account),
            self::base32($secret),
            rawurlencode($issuer),
        );
    }
}

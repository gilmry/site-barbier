<?php

declare(strict_types=1);

namespace Barbier\Tests\Fakes;

/** Décodage base32 (tests) : retrouver le secret TOTP affiché à l'enrôlement, comme le ferait l'appli. */
final class Base32
{
    public static function decode(string $s): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(rtrim($s, '=')) as $c) {
            $bits .= str_pad(decbin((int) strpos($alphabet, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int) bindec($byte));
            }
        }

        return $out;
    }
}

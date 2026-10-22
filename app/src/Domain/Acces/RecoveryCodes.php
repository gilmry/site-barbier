<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

/** Codes de secours (FR-043) : 10 codes « XXXXX-XXXXX » lisibles (sans 0/O/1/I), affichés une seule fois. */
final class RecoveryCodes
{
    public const COUNT = 10;
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** @return list<string> */
    public static function generate(callable $randomBytes): array
    {
        $codes = [];
        while (count($codes) < self::COUNT) {
            $raw = '';
            foreach (str_split($randomBytes(10)) as $b) {
                $raw .= self::ALPHABET[ord($b) % 32];
            }
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
        }

        return $codes;
    }

    /** Forme canonique pour comparer une saisie (casse, espaces, tiret optionnels). */
    public static function normalize(string $input): string
    {
        $c = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $input));

        return strlen($c) === 10 ? substr($c, 0, 5) . '-' . substr($c, 5) : $c;
    }
}

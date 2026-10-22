<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

/** Langues du site (FR-002). Le français est la langue de référence et de repli. */
enum Lang: string
{
    case FR = 'fr';
    case NL = 'nl';
    case EN = 'en';

    /** Liste blanche : toute autre valeur est refusée (jamais injectée dans un chemin ou du HTML). */
    public static function tryFromString(?string $s): ?self
    {
        return $s === null ? null : self::tryFrom(strtolower($s));
    }

    /** Langue préférée d'un en-tête Accept-Language, FR par défaut. */
    public static function fromAcceptLanguage(?string $header): self
    {
        $best = [self::FR, 0.0];
        foreach (explode(',', (string) $header) as $part) {
            if (preg_match('/^\s*([a-zA-Z]{2})(?:-[a-zA-Z0-9]+)?\s*(?:;\s*q=([0-9.]+))?\s*$/', $part, $m) !== 1) {
                continue;
            }
            $lang = self::tryFrom(strtolower($m[1]));
            $q = isset($m[2]) ? (float) $m[2] : 1.0;
            if ($lang !== null && $q > $best[1]) {
                $best = [$lang, $q];
            }
        }

        return $best[0];
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_map(static fn (self $l): string => $l->value, self::cases());
    }
}

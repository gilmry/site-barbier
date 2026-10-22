<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

use Barbier\Domain\Shared\DomainException;

/** Texte en FR/NL/EN : FR obligatoire, repli sur le FR pour une traduction manquante (jamais de page vide). */
final class TranslatedText
{
    /** @var array<string,string> */
    private array $values;

    /** @param array<string,string|null> $values */
    public function __construct(array $values, string $field = 'text', int $maxLength = 2000, bool $frRequired = true)
    {
        $clean = [];
        foreach ($values as $lang => $value) {
            if (Lang::tryFrom((string) $lang) === null) {
                throw new DomainException('invalid_lang', "Langue inconnue : $lang", $field);
            }
            $value = trim(str_replace("\r\n", "\n", (string) $value));
            if ($value === '') {
                continue;
            }
            if (mb_strlen($value) > $maxLength) {
                throw new DomainException('too_long', "Texte trop long ($maxLength caractères au plus).", "$field.$lang");
            }
            $clean[(string) $lang] = $value;
        }
        if ($frRequired && !isset($clean['fr'])) {
            throw new DomainException('fr_required', 'Le texte en français est obligatoire.', "$field.fr");
        }
        $this->values = $clean;
    }

    public function get(Lang $lang): string
    {
        return $this->values[$lang->value] ?? $this->values['fr'] ?? '';
    }

    public function has(Lang $lang): bool
    {
        return isset($this->values[$lang->value]);
    }

    /** @return list<string> langues sans traduction (« à traduire ») */
    public function missing(): array
    {
        return array_values(array_diff(Lang::codes(), array_keys($this->values)));
    }

    /** @return array<string,string> */
    public function toArray(): array
    {
        return $this->values;
    }
}

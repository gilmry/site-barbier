<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

use Barbier\Domain\Shared\DomainException;

/** Prix TTC en centimes entiers (jamais de flottant). */
final class Money
{
    public function __construct(public readonly int $cents)
    {
        if ($cents < 0 || $cents > 1_000_000) {
            throw new DomainException('invalid_price', 'Le prix doit être positif (10 000 € au plus).', 'price_cents');
        }
    }

    /** « 28 € » (fr), « € 28 » (nl), « €28 » (en) ; décimales seulement si nécessaire ; 0 → « offert ». */
    public function format(Lang $lang): string
    {
        $amount = $this->cents % 100 === 0
            ? (string) intdiv($this->cents, 100)
            : number_format($this->cents / 100, 2, $lang === Lang::EN ? '.' : ',', '');

        return match ($lang) {
            Lang::FR => "$amount €",
            Lang::NL => "€ $amount",
            Lang::EN => "€$amount",
        };
    }
}

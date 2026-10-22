<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

/** HTML déjà assaini, produit uniquement par RichText : la seule chose que les gabarits affichent sans échapper. */
final class SafeHtml implements \Stringable
{
    public function __construct(public readonly string $html)
    {
    }

    public function __toString(): string
    {
        return $this->html;
    }
}

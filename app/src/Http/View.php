<?php

declare(strict_types=1);

namespace Barbier\Http;

use Barbier\Domain\Vitrine\SafeHtml;

/**
 * Rendu des gabarits PHP (app/templates). Toute sortie passe par $e() (échappement HTML), $t() (libellé
 * traduit échappé) ou $html() (SafeHtml seulement) ; l'invariant V1 du gate verify interdit le reste.
 * Un gabarit peut en inclure un autre par include (les variables sont partagées).
 */
final class View
{
    public function __construct(private readonly string $dir)
    {
    }

    /** @param array<string,mixed> $vars */
    public function render(string $template, array $vars = []): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        // $html : n'affiche tel quel que du SafeHtml (texte enrichi assaini) ; tout le reste est échappé.
        $html = static fn (mixed $v): string => $v instanceof SafeHtml ? $v->html : $e($v);
        // $t : libellé traduit, échappé.
        $translator = $vars['translator'] ?? null;
        $t = static fn (string $key, array $params = []): string => $e($translator instanceof Translator ? $translator->get($key, $params) : $key);
        $file = $this->dir . '/' . $template . '.php';
        $view = $this;
        $render = function () use ($file, $vars, $e, $html, $t, $view): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            try {
                include $file;

                return (string) ob_get_contents();
            } finally {
                ob_end_clean();
            }
        };

        return $render();
    }
}

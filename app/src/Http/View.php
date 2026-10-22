<?php

declare(strict_types=1);

namespace Barbier\Http;

/**
 * Rendu des gabarits PHP (app/templates). Toute variable s'affiche via $e() (échappement HTML) ;
 * l'invariant V1 du gate verify interdit les « <?= $… » non échappés dans les gabarits.
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
        $file = $this->dir . '/' . $template . '.php';
        $render = function () use ($file, $vars, $e): string {
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

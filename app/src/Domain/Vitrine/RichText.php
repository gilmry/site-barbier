<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

/**
 * Mise en forme limitée et sûre (FR-006 @security) : **gras**, *italique*, [texte](https://…|tel:…|mailto:…),
 * paragraphes séparés par une ligne vide. Tout est échappé AVANT la conversion : aucun HTML saisi ne passe.
 */
final class RichText
{
    public static function toHtml(string $text): SafeHtml
    {
        $paragraphs = preg_split('/\n\s*\n/', trim(str_replace("\r\n", "\n", $text))) ?: [];
        $html = [];
        foreach ($paragraphs as $p) {
            $p = htmlspecialchars(trim($p), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
            $p = (string) preg_replace_callback('/\[([^\]\n]{1,200})\]\(((?:https:\/\/|tel:|mailto:)[^\s)]{1,300})\)/', static function (array $m): string {
                $external = str_starts_with($m[2], 'https://') ? ' rel="noopener noreferrer"' : '';

                return '<a href="' . $m[2] . '"' . $external . '>' . $m[1] . '</a>';
            }, $p);
            $p = (string) preg_replace('/\*\*([^*\n]{1,500})\*\*/', '<strong>$1</strong>', $p);
            $p = (string) preg_replace('/(?<![*\w])\*([^*\n]{1,500})\*(?![*\w])/', '<em>$1</em>', $p);
            $html[] = '<p>' . nl2br($p, false) . '</p>';
        }

        return new SafeHtml(implode("\n", $html));
    }
}

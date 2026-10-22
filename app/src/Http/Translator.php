<?php

declare(strict_types=1);

namespace Barbier\Http;

use Barbier\Domain\Vitrine\Lang;

/** Libellés d'interface (app/i18n/*.php), repli sur le français ; paramètres « :nom ». */
final class Translator
{
    /** @var array<string, array<string,string>> */
    private static array $cache = [];

    public function __construct(private readonly string $dir, public readonly Lang $lang)
    {
    }

    /** @param array<string,string> $params */
    public function get(string $key, array $params = []): string
    {
        $text = $this->load($this->lang)[$key] ?? $this->load(Lang::FR)[$key] ?? $key;
        foreach ($params as $k => $v) {
            $text = str_replace(':' . $k, $v, $text);
        }

        return $text;
    }

    /** @return array<string,string> */
    public function load(Lang $lang): array
    {
        return self::$cache[$lang->value] ??= (array) require $this->dir . '/' . $lang->value . '.php';
    }

    /** Clé de page légale à partir d'un segment d'URL traduit, ou null. */
    public function legalKey(string $slug): ?string
    {
        foreach (['legal_notice', 'privacy'] as $key) {
            if ($this->get('slug.' . $key) === $slug) {
                return $key;
            }
        }

        return null;
    }
}

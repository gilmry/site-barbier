<?php

declare(strict_types=1);

namespace Barbier\Adapter\I18n;

use Barbier\Application\Notification\Texts;

/** Textes depuis app/i18n/*.php (repli FR) ; dates via intl, fuseau de Bruxelles. */
final class FileTexts implements Texts
{
    /** @var array<string, array<string,string>> */
    private array $cache = [];

    public function __construct(private readonly string $dir, private readonly string $timezone = 'Europe/Brussels')
    {
    }

    public function get(string $lang, string $key, array $params = []): string
    {
        $text = $this->load($lang)[$key] ?? $this->load('fr')[$key] ?? $key;
        foreach ($params as $k => $v) {
            $text = str_replace(':' . $k, $v, $text);
        }

        return $text;
    }

    public function when(string $lang, \DateTimeImmutable $utc): string
    {
        $fmt = new \IntlDateFormatter($this->get($lang, 'date.locale'), \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT, $this->timezone, null, $this->get($lang, 'date.format'));
        $out = $fmt->format($utc);

        return is_string($out) ? $out : $utc->setTimezone(new \DateTimeZone($this->timezone))->format('Y-m-d H:i');
    }

    /** @return array<string,string> */
    private function load(string $lang): array
    {
        if (!in_array($lang, ['fr', 'nl', 'en'], true)) {
            $lang = 'fr';
        }

        return $this->cache[$lang] ??= (array) require $this->dir . '/' . $lang . '.php';
    }
}

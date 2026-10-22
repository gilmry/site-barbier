<?php

declare(strict_types=1);

namespace Barbier\Http\Site;

use Barbier\Application\Vitrine\HomePage;
use Barbier\Domain\Shared\Clock;
use Barbier\Domain\Vitrine\Lang;
use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Http\Translator;
use Barbier\Http\View;

/** Site public rendu côté serveur (FR-001 à FR-005, FR-011), en FR/NL/EN (FR-002). */
final class PublicController
{
    public function __construct(
        private readonly HomePage $pages,
        private readonly View $view,
        private readonly string $i18nDir,
        private readonly Clock $clock,
        private readonly string $version,
    ) {
    }

    /** « / » : langue du navigateur (Accept-Language), FR par défaut. */
    public function root(Request $r): Response
    {
        return (new Response(302, '', ['Location' => '/' . Lang::fromAcceptLanguage($r->header('accept-language'))->value . '/']))
            ->withHeader('Vary', 'Accept-Language');
    }

    /** @param array{path:array<string,string>} $in */
    public function home(Request $r, array $in): Response
    {
        $lang = Lang::tryFromString($in['path']['lang'] ?? null);
        if ($lang === null) {
            return $this->error(Lang::FR, 404);
        }
        $data = $this->pages->home($lang, $this->clock->now());
        $hero = $data['sections']['hero'] ?? [];
        $switch = [];
        foreach (Lang::cases() as $l) {
            $switch[$l->value] = '/' . $l->value . '/';
        }

        return $this->page('home', $lang, $data + [
            'title' => trim(($hero['title'] ?? $data['salon']['name']) . ' — ' . ($hero['eyebrow'] ?? '')),
            'description' => trim(($hero['lead'] ?? '') . ' ' . ($hero['lead_em'] ?? '')),
            'switch' => $switch,
            'closures' => [],
        ]);
    }

    /** @param array{path:array<string,string>} $in */
    public function subpage(Request $r, array $in): Response
    {
        $lang = Lang::tryFromString($in['path']['lang'] ?? null);
        if ($lang === null) {
            return $this->error(Lang::FR, 404);
        }
        $translator = new Translator($this->i18nDir, $lang);
        $key = $translator->legalKey($in['path']['slug'] ?? '');
        if ($key === null) {
            return $this->error($lang, 404);
        }
        $data = $this->pages->legal($lang, $key);
        $switch = [];
        foreach (Lang::cases() as $l) {
            $switch[$l->value] = '/' . $l->value . '/' . (new Translator($this->i18nDir, $l))->get('slug.' . $key);
        }

        return $this->page('legal', $lang, $data + [
            'title' => ($data['page']['title'] ?? '') . ' — ' . $data['salon']['name'],
            'description' => (string) ($data['page']['title'] ?? ''),
            'switch' => $switch,
        ]);
    }

    public function error(Lang $lang, int $status): Response
    {
        $html = $this->view->render('error', ['lang' => $lang->value, 'kind' => $status === 404 ? 'notfound' : 'error', 'translator' => new Translator($this->i18nDir, $lang)]);

        return Response::html($html, $status);
    }

    /** Langue d'un chemin public (/nl/… → NL), FR sinon : pour rendre les erreurs dans la bonne langue. */
    public static function langOfPath(string $path): Lang
    {
        return Lang::tryFromString(explode('/', ltrim($path, '/'))[0] ?? null) ?? Lang::FR;
    }

    /** @param array<string,mixed> $vars */
    private function page(string $template, Lang $lang, array $vars): Response
    {
        $alternates = [];
        foreach ($vars['switch'] as $code => $href) {
            $alternates[$code] = $href;
        }
        $alternates['x-default'] = $vars['switch']['fr'];

        return Response::html($this->view->render($template, $vars + [
            'lang' => $lang->value, 'translator' => new Translator($this->i18nDir, $lang), 'alternates' => $alternates,
            'version' => $this->version, 'onHome' => $template === 'home',
        ]))->withHeader('Content-Language', $lang->value)->withHeader('Cache-Control', 'no-cache');
    }
}

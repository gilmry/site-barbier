<?php

declare(strict_types=1);

namespace Barbier\Http\Site;

use Barbier\Application\Agenda\BookingService;
use Barbier\Application\Vitrine\HomePage;
use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Agenda\TimeRange;
use Barbier\Domain\Shared\DomainException;
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
        /** @var \Closure():BookingService */
        private readonly ?\Closure $booking = null,
        /** @var \Closure():AgendaStore */
        private readonly ?\Closure $agenda = null,
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
            'closures' => $this->closures($lang),
        ]);
    }

    /** Fermetures exceptionnelles des 7 prochains jours (FR-005 @edge), en dates lisibles. @return list<string> */
    private function closures(Lang $lang): array
    {
        if ($this->agenda === null) {
            return [];
        }
        $now = $this->clock->now();
        $out = [];
        $tz = new \DateTimeZone(HomePage::TZ);
        $fmt = new \IntlDateFormatter(['fr' => 'fr_BE', 'nl' => 'nl_BE', 'en' => 'en_GB'][$lang->value], \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, HomePage::TZ, null, 'EEEE d MMMM');
        foreach (($this->agenda)()->closures(new TimeRange($now, $now->modify('+7 days'))) as $c) {
            $day = (new \DateTimeImmutable($c['startsAt'], new \DateTimeZone('UTC')))->setTimezone($tz);
            $out[] = (string) $fmt->format($day);
        }

        return array_values(array_unique($out));
    }

    /** @param array{path:array<string,string>} $in */
    public function subpage(Request $r, array $in): Response
    {
        $lang = Lang::tryFromString($in['path']['lang'] ?? null);
        if ($lang === null) {
            return $this->error(Lang::FR, 404);
        }
        $translator = new Translator($this->i18nDir, $lang);
        if (($in['path']['slug'] ?? '') === $translator->get('slug.book')) {
            return $this->bookingPage($lang, $translator);
        }
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

    /** Page de réservation (FR-022 à FR-024) : coquille rendue côté serveur + îlot « booking ». */
    private function bookingPage(Lang $lang, Translator $translator): Response
    {
        $data = $this->pages->legal($lang, 'privacy');
        $switch = [];
        foreach (Lang::cases() as $l) {
            $switch[$l->value] = '/' . $l->value . '/' . (new Translator($this->i18nDir, $l))->get('slug.book');
        }
        $strings = array_filter($translator->load(Lang::FR), static fn (string $k): bool => str_starts_with($k, 'booking.'), ARRAY_FILTER_USE_KEY);
        foreach ($strings as $k => $_) {
            $strings[$k] = $translator->get($k);
        }
        $strings['booking.privacyUrl'] = '/' . $lang->value . '/' . $translator->get('slug.privacy');

        return $this->page('booking', $lang, [
            'salon' => $data['salon'], 'footer' => $data['footer'], 'switch' => $switch,
            'title' => $translator->get('booking.title') . ' — ' . $data['salon']['name'], 'description' => $translator->get('booking.title'),
            'islandStrings' => json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR),
        ]);
    }

    /** Lien d'annulation (FR-025) : GET affiche le récapitulatif et demande confirmation ; il n'annule PAS. @param array{path:array<string,string>} $in */
    public function cancelPage(Request $r, array $in): Response
    {
        return $this->cancel($in, false);
    }

    /** @param array{path:array<string,string>} $in */
    public function cancelSubmit(Request $r, array $in): Response
    {
        return $this->cancel($in, true);
    }

    /** @param array{path:array<string,string>} $in */
    private function cancel(array $in, bool $confirm): Response
    {
        $lang = Lang::tryFromString($in['path']['lang'] ?? null);
        if ($lang === null || $this->booking === null) {
            return $this->error(Lang::FR, 404);
        }
        $translator = new Translator($this->i18nDir, $lang);
        if (($in['path']['slug'] ?? '') !== $translator->get('slug.cancel')) {
            return $this->error($lang, 404);
        }
        $token = (string) ($in['path']['token'] ?? '');
        $summary = ($this->booking)()->byToken($token);
        $state = 'unknown';
        $message = '';
        if ($summary !== null) {
            $state = $summary['state'] === 'cancelled' ? 'already' : 'confirm';
            if ($confirm && $state === 'confirm') {
                try {
                    $summary = ($this->booking)()->cancelByToken($token);
                    $state = 'done';
                } catch (DomainException $e) {
                    $state = $e->reason === 'too_late' ? 'too_late' : 'error';
                    $message = $e->getMessage();
                }
            }
        }
        $data = $this->pages->legal($lang, 'privacy');
        $switch = [];
        foreach (Lang::cases() as $l) {
            $switch[$l->value] = '/' . $l->value . '/' . (new Translator($this->i18nDir, $l))->get('slug.cancel') . '/' . $token;
        }
        $response = $this->page('cancel', $lang, [
            'salon' => $data['salon'], 'footer' => $data['footer'], 'switch' => $switch, 'summary' => $summary, 'state' => $state, 'message' => $message,
            'title' => $translator->get('cancel.title') . ' — ' . $data['salon']['name'], 'description' => $translator->get('cancel.title'),
            'action' => '/' . $lang->value . '/' . $translator->get('slug.cancel') . '/' . $token,
        ]);

        return ($summary === null ? new Response(404, $response->body, $response->headers) : $response)->withHeader('X-Robots-Tag', 'noindex')->withHeader('Referrer-Policy', 'no-referrer');
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

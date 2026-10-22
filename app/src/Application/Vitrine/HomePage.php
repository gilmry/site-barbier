<?php

declare(strict_types=1);

namespace Barbier\Application\Vitrine;

use Barbier\Domain\Vitrine\Duration;
use Barbier\Domain\Vitrine\Lang;
use Barbier\Domain\Vitrine\Money;
use Barbier\Domain\Vitrine\RichText;
use Barbier\Domain\Vitrine\SectionCatalog;
use Barbier\Domain\Vitrine\TranslatedText;
use Barbier\Domain\Vitrine\VitrineRepository;

/**
 * Prépare les pages publiques dans une langue (FR-001 à FR-005, FR-011) : textes avec repli sur le FR,
 * prix formatés, sections vides masquées, jour courant à l'heure de Bruxelles.
 */
final class HomePage
{
    public const TZ = 'Europe/Brussels';

    public function __construct(private readonly VitrineRepository $repo, private readonly int $retentionMonths = 6)
    {
    }

    /** @return array<string,mixed> */
    public function home(Lang $lang, \DateTimeImmutable $now): array
    {
        $media = $this->repo->media();

        $categories = [];
        foreach ($this->repo->catalog(true) as $c) {
            $services = [];
            foreach ($c['services'] as $s) {
                $services[] = [
                    'id' => $s['id'],
                    'name' => self::text($s['name'], $lang),
                    'description' => self::text($s['description'], $lang),
                    'price' => $s['priceCents'] === 0 ? null : (new Money($s['priceCents']))->format($lang),
                    'duration' => (new Duration($s['durationMin']))->format(),
                ];
            }
            if ($services !== []) {
                $categories[] = ['name' => self::text($c['name'], $lang), 'services' => $services];
            }
        }

        $barbers = [];
        foreach ($this->repo->barbers(true) as $b) {
            $photo = $b['photoMediaId'] !== null ? ($media[$b['photoMediaId']] ?? null) : null;
            $barbers[] = [
                'firstName' => $b['firstName'],
                'initial' => mb_strtoupper(mb_substr($b['firstName'], 0, 1)),
                'specialty' => self::text($b['specialty'], $lang),
                'bio' => self::text($b['bio'], $lang),
                'photo' => $photo === null ? null : ['src' => '/uploads/' . $photo['path'], 'width' => $photo['width'], 'height' => $photo['height']],
            ];
        }

        $today = (int) $now->setTimezone(new \DateTimeZone(self::TZ))->format('N');
        $open = $this->repo->openingHours();
        $hours = [];
        foreach ([1, 2, 3, 4, 5, 6, 7] as $wd) {
            $hours[] = ['weekday' => $wd, 'opens' => $open[$wd]['opens'] ?? null, 'closes' => $open[$wd]['closes'] ?? null, 'today' => $wd === $today];
        }

        $sections = $this->sections('home', $lang, $media);
        $salon = $this->salon($lang);

        return ['lang' => $lang->value, 'sections' => $sections, 'salon' => $salon, 'categories' => $categories, 'barbers' => $barbers, 'hours' => $hours];
    }

    /** @return array<string,mixed> */
    public function legal(Lang $lang, string $key): array
    {
        $sections = $this->sections('legal', $lang, []);

        return ['lang' => $lang->value, 'page' => $sections[$key] ?? ['title' => '', 'body' => ''], 'salon' => $this->salon($lang),
            'footer' => $this->sections('home', $lang, [])['footer'] ?? []];
    }

    /** @return array<string,mixed> */
    private function salon(Lang $lang): array
    {
        $s = $this->repo->salon();

        return [
            'name' => $s['name'],
            'phone' => $s['phone'],
            'phoneHref' => 'tel:' . preg_replace('/[^0-9+]/', '', $s['phone']),
            'email' => $s['email'],
            'address' => array_values(array_filter(explode("\n", self::text($s['address'], $lang)), static fn (string $l): bool => trim($l) !== '')),
            'access' => self::text($s['access'], $lang),
        ];
    }

    /**
     * @param array<string, array{path:string, width:int, height:int, mime:string}> $media
     * @return array<string, array<string, mixed>>
     */
    private function sections(string $page, Lang $lang, array $media): array
    {
        $out = [];
        foreach ($this->repo->sections($page) as $key => $section) {
            $def = SectionCatalog::all()[$key]['fields'] ?? [];
            $fields = [];
            foreach ($def as $name => $meta) {
                $value = self::text($section['fields'][$name] ?? [], $lang);
                $value = str_replace('{{retention_months}}', (string) $this->retentionMonths, $value);
                $fields[$name] = $meta['rich'] ? RichText::toHtml($value) : $value;
            }
            if ($section['mediaId'] !== null && isset($media[$section['mediaId']])) {
                $m = $media[$section['mediaId']];
                $fields['image'] = ['src' => '/uploads/' . $m['path'], 'width' => $m['width'], 'height' => $m['height']];
            }
            $out[$key] = $fields;
        }

        return $out;
    }

    /** @param array<string,string> $values */
    private static function text(array $values, Lang $lang): string
    {
        return $values === [] ? '' : (new TranslatedText($values, 'text', 100000, false))->get($lang);
    }
}

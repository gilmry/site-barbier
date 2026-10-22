<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

/** Édition de la vitrine (FR-006 à FR-010) — réponses validées contre le contrat. */
final class VitrineAdminTest extends IntegrationCase
{
    /** @var array<string,string> */
    private array $csrf;

    protected function setUp(): void
    {
        self::pdo();
        self::truncate('session', 'recovery_code', 'editor', 'media');
        self::resetContent();
        $this->jar = [];
        $this->csrf = $this->asEditor();
    }

    /** @happy modifier l'accroche en FR et NL → visible immédiatement sur le site */
    public function testModifierUneSection(): void
    {
        $list = self::json($this->call('GET', '/api/v1/admin/sections', '/api/v1/admin/sections'));
        $hero = array_values(array_filter($list['sections'], static fn ($s) => $s['key'] === 'hero'))[0];
        self::assertSame([], $hero['fields']['lead']['missing']);
        $fields = array_map(static fn ($f) => $f['values'], $hero['fields']);
        $fields['lead'] = ['fr' => 'Nouvelle accroche du salon.', 'nl' => 'Nieuwe slogan.', 'en' => null];
        $r = $this->call('PUT', '/api/v1/admin/sections/{key}', '/api/v1/admin/sections/hero', ['version' => $hero['version'], 'fields' => $fields], [], $this->csrf);
        self::assertSame(200, $r->status, $r->body);
        self::assertSame(['en'], self::json($r)['fields']['lead']['missing']);
        self::assertStringContainsString('Nouvelle accroche du salon.', $this->page('GET', '/fr/')->body);
        self::assertStringContainsString('Nouvelle accroche du salon.', $this->page('GET', '/en/')->body); // repli FR
    }

    /** @edge deux onglets : la seconde sauvegarde sur une version périmée est refusée (409), rien n'est écrasé */
    public function testVerrouOptimiste(): void
    {
        $hero = array_values(array_filter(self::json($this->call('GET', '/api/v1/admin/sections', '/api/v1/admin/sections'))['sections'], static fn ($s) => $s['key'] === 'footer'))[0];
        $put = fn (string $text) => $this->call('PUT', '/api/v1/admin/sections/{key}', '/api/v1/admin/sections/footer', ['version' => $hero['version'], 'fields' => ['tagline' => ['fr' => $text]]], [], $this->csrf);
        self::assertSame(200, $put('Onglet A')->status);
        self::assertSame(409, $put('Onglet B')->status);
        self::assertStringContainsString('Onglet A', $this->page('GET', '/fr/')->body);
    }

    /** @negative FR vide, champ inconnu, texte trop long → 422 ; sans session → 401 ; sans CSRF → 403 */
    public function testRefus(): void
    {
        $put = fn (array $fields, array $h) => $this->call('PUT', '/api/v1/admin/sections/{key}', '/api/v1/admin/sections/footer', ['version' => 1, 'fields' => $fields], [], $h);
        self::assertSame(422, $put(['tagline' => ['fr' => '', 'nl' => 'x']], $this->csrf)->status);
        self::assertSame(422, $put(['tagline' => ['fr' => 'x'], 'pirate' => ['fr' => 'x']], $this->csrf)->status);
        self::assertSame(422, $put(['tagline' => ['fr' => str_repeat('a', 121)]], $this->csrf)->status);
        self::assertSame(403, $put(['tagline' => ['fr' => 'x']], [])->status);
        $this->jar = [];
        self::assertSame(401, $put(['tagline' => ['fr' => 'x']], $this->csrf)->status);
    }

    /** @happy carte : prix modifié → affiché ; prestation créée puis dépubliée ; durée invalide refusée */
    public function testCarte(): void
    {
        $catalog = self::json($this->call('GET', '/api/v1/admin/catalog', '/api/v1/admin/catalog'));
        $cat = $catalog['categories'][0];
        $svc = $cat['services'][0];
        $input = ['categoryId' => $cat['id'], 'name' => $svc['name'], 'description' => $svc['description'], 'priceCents' => 3000, 'durationMin' => 45, 'published' => true];
        self::assertSame(204, $this->call('PUT', '/api/v1/admin/services/{id}', '/api/v1/admin/services/' . $svc['id'], $input, [], $this->csrf)->status);
        self::assertStringContainsString('30 €', $this->page('GET', '/fr/')->body);
        self::assertSame(422, $this->call('PUT', '/api/v1/admin/services/{id}', '/api/v1/admin/services/' . $svc['id'], ['durationMin' => 42] + $input, [], $this->csrf)->status);
        self::assertSame(422, $this->call('PUT', '/api/v1/admin/services/{id}', '/api/v1/admin/services/' . $svc['id'], ['priceCents' => -1] + $input, [], $this->csrf)->status);
        $new = $this->call('POST', '/api/v1/admin/services', '/api/v1/admin/services', ['name' => ['fr' => 'Coupe éclair', 'nl' => 'Flitssnit'], 'durationMin' => 15, 'priceCents' => 1500] + $input, [], $this->csrf);
        self::assertSame(201, $new->status);
        self::assertStringContainsString('Coupe éclair', $this->page('GET', '/fr/')->body);
        $id = self::json($new)['id'];
        $this->call('PUT', '/api/v1/admin/services/{id}', '/api/v1/admin/services/' . $id, ['name' => ['fr' => 'Coupe éclair'], 'durationMin' => 15, 'priceCents' => 1500, 'published' => false] + $input, [], $this->csrf);
        self::assertStringNotContainsString('Coupe éclair', $this->page('GET', '/fr/')->body);
        self::assertSame(409, $this->call('DELETE', '/api/v1/admin/categories/{id}', '/api/v1/admin/categories/' . $cat['id'], null, [], $this->csrf)->status);
    }

    /** @happy équipe : désactiver un barbier le retire du site ; horaires et coordonnées */
    public function testEquipeHorairesCoordonnees(): void
    {
        $lea = array_values(array_filter(self::json($this->call('GET', '/api/v1/admin/barbers', '/api/v1/admin/barbers'))['barbers'], static fn ($b) => $b['firstName'] === 'Léa'))[0];
        $in = ['firstName' => 'Léa', 'specialty' => $lea['specialty'], 'bio' => $lea['bio'], 'active' => false, 'serviceIds' => $lea['serviceIds']];
        self::assertSame(204, $this->call('PUT', '/api/v1/admin/barbers/{id}', '/api/v1/admin/barbers/' . $lea['id'], $in, [], $this->csrf)->status);
        self::assertStringNotContainsString('Léa', $this->page('GET', '/fr/')->body);
        $days = self::json($this->call('GET', '/api/v1/admin/opening-hours', '/api/v1/admin/opening-hours'))['days'];
        $days[3] = ['weekday' => 4, 'opens' => '10:00', 'closes' => '19:00'];
        self::assertSame(200, $this->call('PUT', '/api/v1/admin/opening-hours', '/api/v1/admin/opening-hours', ['days' => $days], [], $this->csrf)->status);
        self::assertStringNotContainsString('10:00 – 21:00</td>' . "\n" . '              </tr>' . "\n" . '              <tr class=" ">', $this->page('GET', '/fr/')->body);
        $days[3] = ['weekday' => 4, 'opens' => '19:00', 'closes' => '10:00'];
        self::assertSame(422, $this->call('PUT', '/api/v1/admin/opening-hours', '/api/v1/admin/opening-hours', ['days' => $days], [], $this->csrf)->status);
        $salon = self::json($this->call('GET', '/api/v1/admin/salon', '/api/v1/admin/salon'));
        self::assertSame(422, $this->call('PUT', '/api/v1/admin/salon', '/api/v1/admin/salon', ['phone' => 'appelez-moi'] + $salon, [], $this->csrf)->status);
        $salon['phone'] = '+32 2 123 45 67';
        self::assertSame(200, $this->call('PUT', '/api/v1/admin/salon', '/api/v1/admin/salon', $salon, [], $this->csrf)->status);
        self::assertStringContainsString('tel:+3221234567', $this->page('GET', '/fr/')->body);
    }
}

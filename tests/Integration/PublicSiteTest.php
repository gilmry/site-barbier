<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

/** Site public rendu côté serveur (FR-001 à FR-005, FR-011) en FR/NL/EN (FR-002). */
final class PublicSiteTest extends IntegrationCase
{
    protected function setUp(): void
    {
        self::pdo();
        self::truncate('media');
        self::resetContent();
        $this->jar = [];
    }

    /** @happy accueil FR : les 8 sections de la maquette, la carte, l'équipe, les horaires */
    public function testAccueilFrancais(): void
    {
        $r = $this->page('GET', '/fr/');
        self::assertSame(200, $r->status);
        foreach (['Le Corbeau', 'Un atelier, pas un salon', 'Coupe classique', '28 €', 'Le Rituel du Corbeau', '100 min', 'Antoine', 'Léa',
            'Réserver un fauteuil', '10:00 – 21:00', 'Rue de l&apos;Exemple 13', 'Une lame neuve pour chaque client.'] as $text) {
            self::assertStringContainsString($text, $r->body, "absent : $text");
        }
        self::assertStringContainsString('<html lang="fr">', $r->body);
        self::assertStringContainsString('hreflang="nl" href="/nl/"', $r->body);
        self::assertSame('fr', $r->headers['Content-Language']);
    }

    /** @happy accueil NL et EN : textes traduits, prix au format de la langue */
    public function testAccueilNeerlandaisEtAnglais(): void
    {
        $nl = $this->page('GET', '/nl/')->body;
        self::assertStringContainsString('Een atelier, geen salon', $nl);
        self::assertStringContainsString('€ 28', $nl);
        self::assertStringContainsString('Dinsdag', $nl);
        $en = $this->page('GET', '/en')->body;
        self::assertStringContainsString('A workshop, not a salon', $en);
        self::assertStringContainsString('€28', $en);
    }

    /** @negative langue inconnue ou page inconnue → 404 HTML traduite ; « / » redirige selon Accept-Language */
    public function testLangueInconnueEtRedirection(): void
    {
        $r = $this->page('GET', '/de/');
        self::assertSame(404, $r->status);
        self::assertStringStartsWith('text/html', $r->headers['Content-Type']);
        self::assertStringContainsString('Pagina niet gevonden', $this->page('GET', '/nl/nimporte')->body);
        $root = $this->page('GET', '/', '', ['Accept-Language' => 'nl-BE,nl;q=0.9']);
        self::assertSame(302, $root->status);
        self::assertSame('/nl/', $root->headers['Location']);
    }

    /** @edge traduction NL manquante → repli FR ; catégorie sans prestation publiée masquée */
    public function testRepliSurLeFrancais(): void
    {
        self::pdo()->exec("DELETE FROM translation WHERE owner_type = 'section' AND owner_id = 'maison' AND field = 'title' AND lang = 'nl'");
        self::pdo()->exec("UPDATE service SET published = 0 WHERE category_id = 'c0000000-0000-4000-8000-000000000002'");
        $nl = $this->page('GET', '/nl/')->body;
        self::assertStringContainsString('Un atelier, pas un salon', $nl);
        self::assertStringNotContainsString('Baard &amp; scheren', $nl);
    }

    /** @security texte piégé enregistré en base : affiché échappé (XSS stockée impossible) ; en-têtes de sécurité */
    public function testXssStockeeEchappee(): void
    {
        self::pdo()->exec("UPDATE translation SET value = '<script>alert(1)</script><img src=x onerror=alert(2)>' WHERE owner_type = 'service' AND field = 'name' AND lang = 'fr' AND owner_id = '50000000-0000-4000-8000-000000000001'");
        $r = $this->page('GET', '/fr/');
        self::assertStringNotContainsString('<script>alert(1)', $r->body);
        self::assertStringNotContainsString('<img src=x', $r->body);
        self::assertStringContainsString('&lt;script&gt;', $r->body);
        self::assertStringContainsString("default-src 'self'", $r->headers['Content-Security-Policy']);
        self::assertSame('nosniff', $r->headers['X-Content-Type-Options']);
    }

    /** @edge pages légales traduites ; durée de conservation lue de la configuration */
    public function testPagesLegales(): void
    {
        $privacy = $this->page('GET', '/nl/privacy');
        self::assertSame(200, $privacy->status);
        self::assertStringContainsString('6 maanden', $privacy->body);
        self::assertStringNotContainsString('{{retention_months}}', $privacy->body);
        self::assertStringContainsString('<strong>', $privacy->body);
        self::assertSame(200, $this->page('GET', '/fr/mentions-legales')->status);
        self::assertSame(200, $this->page('GET', '/en/legal')->status);
        self::assertSame(404, $this->page('GET', '/fr/legal')->status);
    }
}

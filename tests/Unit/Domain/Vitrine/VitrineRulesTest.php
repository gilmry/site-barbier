<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Domain\Vitrine;

use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Vitrine\DaySchedule;
use Barbier\Domain\Vitrine\Duration;
use Barbier\Domain\Vitrine\Lang;
use Barbier\Domain\Vitrine\Money;
use Barbier\Domain\Vitrine\RichText;
use Barbier\Domain\Vitrine\SectionCatalog;
use Barbier\Domain\Vitrine\TranslatedText;
use PHPUnit\Framework\TestCase;

final class VitrineRulesTest extends TestCase
{
    /** @happy prix formaté par langue, décimales seulement si utiles */
    public function testPrixParLangue(): void
    {
        self::assertSame('28 €', (new Money(2800))->format(Lang::FR));
        self::assertSame('€ 28', (new Money(2800))->format(Lang::NL));
        self::assertSame('€28', (new Money(2800))->format(Lang::EN));
        self::assertSame('28,50 €', (new Money(2850))->format(Lang::FR));
        self::assertSame('€28.50', (new Money(2850))->format(Lang::EN));
    }

    /** @negative prix négatif, durée non multiple de 5 ou hors bornes */
    public function testPrixEtDureesInvalides(): void
    {
        foreach ([static fn () => new Money(-1), static fn () => new Duration(0), static fn () => new Duration(42), static fn () => new Duration(245)] as $f) {
            try {
                $f();
                self::fail('valeur invalide acceptée');
            } catch (DomainException) {
                $this->addToAssertionCount(1);
            }
        }
        self::assertSame(40, (new Duration(40))->minutes);
        self::assertSame(240, (new Duration(240))->minutes);
    }

    /** @edge traduction manquante → repli FR ; FR obligatoire ; langues « à traduire » */
    public function testTexteTraduit(): void
    {
        $t = new TranslatedText(['fr' => 'Coupe', 'nl' => ' ', 'en' => 'Cut']);
        self::assertSame('Coupe', $t->get(Lang::NL));
        self::assertSame('Cut', $t->get(Lang::EN));
        self::assertSame(['nl'], $t->missing());
        $this->expectException(DomainException::class);
        new TranslatedText(['nl' => 'Snit']);
    }

    /** @edge langue : liste blanche et Accept-Language */
    public function testLangue(): void
    {
        self::assertNull(Lang::tryFromString('de'));
        self::assertNull(Lang::tryFromString('../etc'));
        self::assertSame(Lang::NL, Lang::fromAcceptLanguage('nl-BE,nl;q=0.9,fr;q=0.8'));
        self::assertSame(Lang::EN, Lang::fromAcceptLanguage('de-DE,en;q=0.7'));
        self::assertSame(Lang::FR, Lang::fromAcceptLanguage('n\'importe quoi'));
    }

    /** @negative horaires : fin avant début, moitié de plage, jour invalide */
    public function testHorairesInvalides(): void
    {
        self::assertTrue((new DaySchedule(1, null, null))->isClosed());
        foreach ([[2, '19:00', '10:00'], [2, '10:00', null], [8, '10:00', '19:00'], [2, '25:00', '26:00']] as [$d, $o, $c]) {
            try {
                new DaySchedule($d, $o, $c);
                self::fail("horaire invalide accepté : $d $o $c");
            } catch (DomainException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /** @security texte enrichi : HTML saisi échappé, liens limités à https/tel/mailto */
    public function testTexteEnrichiSur(): void
    {
        $html = RichText::toHtml("<script>alert(1)</script> **gras** et *italique*\n\n[site](https://exemple.be) [x](javascript:alert(1)) [tel](tel:+3220000000)")->html;
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringContainsString('<strong>gras</strong>', $html);
        self::assertStringContainsString('<em>italique</em>', $html);
        self::assertStringContainsString('<a href="https://exemple.be" rel="noopener noreferrer">site</a>', $html);
        self::assertStringNotContainsString('javascript:alert(1)"', $html);
        self::assertStringContainsString('<a href="tel:+3220000000">tel</a>', $html);
        self::assertSame(2, substr_count($html, '<p>'));
        self::assertStringNotContainsString('" onmouseover', RichText::toHtml('[a](https://x.be/"onmouseover=alert(1))')->html);
    }

    /** @negative sections : champ inconnu, FR manquant, longueur dépassée */
    public function testCatalogueDesSections(): void
    {
        $ok = ['tagline' => ['fr' => 'Barbier à Bruxelles']];
        self::assertSame('Barbier à Bruxelles', SectionCatalog::validate('footer', $ok)['tagline']->get(Lang::FR));
        foreach ([['tagline' => ['fr' => 'x'], 'pirate' => ['fr' => 'x']], ['tagline' => ['nl' => 'x']], ['tagline' => ['fr' => str_repeat('a', 121)]]] as $bad) {
            try {
                SectionCatalog::validate('footer', $bad);
                self::fail('section invalide acceptée');
            } catch (DomainException $e) {
                self::assertNotNull($e->field);
            }
        }
    }
}

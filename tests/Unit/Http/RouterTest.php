<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Http;

use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function router(): Router
    {
        $r = new Router();
        $r->api('GET', '/api/v1/barbers/{id}', static fn (Request $q, array $in): Response => Response::json(['id' => $in['path']['id']]));
        $r->api('GET', '/api/v1/health', static fn (): Response => Response::json(['status' => 'ok']));

        return $r;
    }

    /** @happy */
    public function testRouteSimpleEtParametreDeChemin(): void
    {
        $res = $this->router()->dispatch(new Request('GET', '/api/v1/barbers/l%C3%A9a'));
        self::assertSame(200, $res->status);
        self::assertSame('{"id":"léa"}', $res->body);
        self::assertSame(200, $this->router()->dispatch(new Request('GET', '/api/v1/health/'))->status);
    }

    /** @negative */
    public function testCheminInconnuDonne404ProblemJson(): void
    {
        $res = $this->router()->dispatch(new Request('GET', '/api/v1/nope'));
        self::assertSame(404, $res->status);
        self::assertStringStartsWith('application/problem+json', $res->headers['Content-Type']);
        self::assertSame(404, json_decode($res->body, true)['status']);
    }

    /** @negative */
    public function testMauvaiseMethodeDonne405(): void
    {
        self::assertSame(405, $this->router()->dispatch(new Request('POST', '/api/v1/health'))->status);
    }

    /** @edge un paramètre ne traverse jamais un « / » */
    public function testParametreNeTraversePasUnSlash(): void
    {
        self::assertSame(404, $this->router()->dispatch(new Request('GET', '/api/v1/barbers/a/b'))->status);
    }

    /** @security les métacaractères de regex d'un motif ne s'interprètent pas */
    public function testMotifEstLitteral(): void
    {
        $r = new Router();
        $r->page('GET', '/a.b', static fn (): Response => Response::json([]));
        self::assertSame(404, $r->dispatch(new Request('GET', '/axb'))->status);
        self::assertSame(200, $r->dispatch(new Request('GET', '/a.b'))->status);
    }

    public function testTableDeRoutesIntrospectable(): void
    {
        self::assertSame(
            [['method' => 'GET', 'pattern' => '/api/v1/barbers/{id}', 'contract' => true], ['method' => 'GET', 'pattern' => '/api/v1/health', 'contract' => true]],
            $this->router()->routes(),
        );
    }

    /** @negative une page générique (/{a}/{b}/{c}) ne capture jamais un chemin d'API inconnu */
    public function testPageGeneriqueNeCapturePasLApi(): void
    {
        $r = new Router();
        $r->api('GET', '/api/v1/health', static fn (): Response => Response::json([]));
        $r->page('GET', '/{lang}/{slug}/{token}', static fn (): Response => Response::html('page'));
        $r->page('POST', '/{lang}/{slug}/{token}', static fn (): Response => Response::html('page'));
        self::assertSame(404, $r->dispatch(new Request('GET', '/api/v1/inconnu'))->status);
        self::assertStringStartsWith('application/problem+json', $r->dispatch(new Request('GET', '/api/v1/inconnu'))->headers['Content-Type']);
        self::assertSame(405, $r->dispatch(new Request('POST', '/api/v1/health'))->status);
        self::assertSame('page', $r->dispatch(new Request('GET', '/fr/annuler/abc'))->body);
    }
}

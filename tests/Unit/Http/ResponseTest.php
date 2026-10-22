<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Http;

use Barbier\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    /** @happy */
    public function testJsonUtf8SansEchappementDesAccents(): void
    {
        $res = Response::json(['nom' => 'Léa'], 201);
        self::assertSame(201, $res->status);
        self::assertSame('{"nom":"Léa"}', $res->body);
        self::assertSame('no-store', $res->headers['Cache-Control']);
    }

    /** @negative le détail est facultatif */
    public function testProblemSansDetail(): void
    {
        self::assertSame(['type' => 'about:blank', 'title' => 'Ressource introuvable', 'status' => 404], json_decode(Response::problem(404, 'Ressource introuvable')->body, true));
    }

    /** @edge une donnée non encodable lève au lieu de renvoyer un corps vide */
    public function testDonneeNonEncodableLeve(): void
    {
        $this->expectException(\JsonException::class);
        Response::json(['x' => "\xB1\x31"]);
    }

    /** @security le JSON reste du JSON : pas d'injection de balise exploitable par un navigateur qui sniffe */
    public function testContentTypeExplicite(): void
    {
        self::assertSame('application/json; charset=utf-8', Response::json([])->headers['Content-Type']);
    }
}

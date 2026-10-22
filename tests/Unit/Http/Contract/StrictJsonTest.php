<?php

declare(strict_types=1);

namespace Barbier\Tests\Unit\Http\Contract;

use Barbier\Http\Contract\ContractViolation;
use Barbier\Http\Contract\Spec;
use Barbier\Http\Contract\StrictJson;
use Barbier\Http\Request;
use PHPUnit\Framework\TestCase;

final class StrictJsonTest extends TestCase
{
    private function strict(): StrictJson
    {
        return new StrictJson(new Spec(dirname(__DIR__, 3) . '/fixtures/test.openapi.json'));
    }

    /** @param array<string,mixed> $query */
    private function req(string $body, array $query = [], string $type = 'application/json'): Request
    {
        return new Request('POST', '/things/abc', $body, $query, ['Content-Type' => $type]);
    }

    /** @return list<string> */
    private function fields(callable $fn): array
    {
        try {
            $fn();
        } catch (ContractViolation $e) {
            return array_column($e->errors, 'field');
        }
        self::fail('ContractViolation attendue');
    }

    /** @happy */
    public function testRequeteConformeDecodee(): void
    {
        $in = $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"Coupe","price":2800}', ['lang' => 'nl']), ['id' => 'abc']);
        self::assertSame(['name' => 'Coupe', 'price' => 2800], $in['body']);
        self::assertSame(['lang' => 'nl'], $in['query']);
    }

    /** @negative champ inconnu, type faux, champ manquant */
    public function testChampInconnuTypeFauxEtManquant(): void
    {
        $f = $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"x","price":"28","stock":3}'), ['id' => 'abc']));
        self::assertNotEmpty($f);
        self::assertContainsOnly('string', $f);
        $f = $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"x"}'), ['id' => 'abc']));
        self::assertNotEmpty($f);
    }

    /** @negative JSON invalide, corps absent, mauvais Content-Type */
    public function testCorpsInvalide(): void
    {
        self::assertSame(['body'], $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":'), ['id' => 'abc'])));
        self::assertSame(['body'], $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req(''), ['id' => 'abc'])));
        self::assertSame(['body'], $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"x","price":1}', [], 'text/plain'), ['id' => 'abc'])));
    }

    /** @edge bornes : longueur max, minimum 0, paramètre de requête inconnu, valeur multiple */
    public function testBornesEtParametres(): void
    {
        $ok = $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"' . str_repeat('a', 20) . '","price":0}'), ['id' => 'abc']);
        self::assertSame(0, $ok['body']['price']);
        self::assertNotEmpty($this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"' . str_repeat('a', 21) . '","price":-1}'), ['id' => 'abc'])));
        self::assertSame(['query.debug'], $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"x","price":1}', ['debug' => '1']), ['id' => 'abc'])));
        self::assertSame(['query.lang'], $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"x","price":1}', ['lang' => ['fr', 'nl']]), ['id' => 'abc'])));
    }

    /** @security paramètre de chemin hors motif (tentative d'injection) */
    public function testParametreDeCheminHorsMotif(): void
    {
        self::assertSame(['path.id'], $this->fields(fn () => $this->strict()->validateRequest('POST', '/things/{id}', $this->req('{"name":"x","price":1}'), ['id' => "1' OR '1'='1"])));
    }

    public function testValidationDeReponse(): void
    {
        $s = $this->strict();
        self::assertSame([], $s->responseErrors('POST', '/things/{id}', 201, '{"name":"a","price":1}', 'application/json'));
        self::assertNotEmpty($s->responseErrors('POST', '/things/{id}', 201, '{"name":"a","price":1,"extra":true}', 'application/json'));
        self::assertNotEmpty($s->responseErrors('POST', '/things/{id}', 500, '{}', 'application/json'));
    }
}

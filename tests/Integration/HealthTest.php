<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

final class HealthTest extends IntegrationCase
{
    /** @happy */
    public function testSanteAvecBaseJoignable(): void
    {
        $r = $this->call('GET', '/api/v1/health', '/api/v1/health');
        self::assertSame(200, $r->status);
        self::assertSame(['status' => 'ok', 'database' => 'ok'], self::json($r));
    }

    /** @negative paramètre inconnu → 400 problem+json conforme, champ nommé */
    public function testParametreInconnuRefuse(): void
    {
        $r = $this->call('GET', '/api/v1/health', '/api/v1/health', null, ['debug' => '1']);
        self::assertSame(400, $r->status);
        self::assertSame('query.debug', self::json($r)['errors'][0]['field']);
    }

    /** @edge corps envoyé à une opération qui n'en attend pas */
    public function testCorpsInattenduRefuse(): void
    {
        $r = $this->call('GET', '/api/v1/health', '/api/v1/health', ['x' => 1]);
        self::assertSame(400, $r->status);
    }

    /** @security base injoignable : 503 sans fuite d'information technique */
    public function testBaseInjoignableSansFuite(): void
    {
        $k = new \Barbier\Kernel(['db' => ['host' => '127.0.0.1', 'port' => 1, 'database' => 'x', 'username' => 'x', 'password' => 'x']]);
        $r = $k->handle(new \Barbier\Http\Request('GET', '/api/v1/health'));
        self::assertSame(503, $r->status);
        self::assertStringNotContainsString('SQLSTATE', $r->body);
    }
}

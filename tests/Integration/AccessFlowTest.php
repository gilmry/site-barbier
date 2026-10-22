<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

use Barbier\Domain\Acces\Totp;
use Barbier\Tests\Fakes\Base32;
use Barbier\Tests\Fakes\FakeClock;

/** Parcours HTTP de l'accès sur vraie base (FR-040 à FR-043) — réponses validées contre le contrat. */
final class AccessFlowTest extends IntegrationCase
{
    private const PW = 'Cèdre-lame-serviette-9';
    private FakeClock $fake;

    protected function setUp(): void
    {
        self::pdo();
        self::truncate('session', 'recovery_code', 'editor', 'app_state');
        $this->clock = $this->fake = new FakeClock(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->jar = [];
    }

    private function csrf(): array
    {
        $s = self::json($this->call('GET', '/api/v1/admin/session', '/api/v1/admin/session'));

        return ['X-CSRF-Token' => (string) ($s['csrf'] ?? '')];
    }

    private function code(string $secret): string
    {
        return Totp::code($secret, intdiv($this->fake->now()->getTimestamp(), 30));
    }

    /** Installe par le formulaire puis active le double facteur. @return string secret brut */
    private function installAndEnrol(): string
    {
        $form = http_build_query(['email' => 'gerant@salon.be', 'password' => self::PW, 'password_again' => self::PW, 'install_code' => getenv('INSTALL_CODE')]);
        $r = $this->page('POST', '/install', $form, ['Content-Type' => 'application/x-www-form-urlencoded']);
        self::assertSame(303, $r->status, $r->body);
        self::assertSame('pre_enrolment', self::json($this->call('GET', '/api/v1/admin/session', '/api/v1/admin/session'))['stage']);
        $enrol = self::json($this->call('POST', '/api/v1/admin/account/totp', '/api/v1/admin/account/totp', null, [], $this->csrf()));
        $secret = Base32::decode($enrol['secret']);
        $codes = $this->call('POST', '/api/v1/admin/account/totp/confirm', '/api/v1/admin/account/totp/confirm', ['code' => $this->code($secret)], [], $this->csrf());
        self::assertSame(200, $codes->status, $codes->body);
        self::assertCount(10, self::json($codes)['codes']);
        $this->fake->advance(31);

        return $secret;
    }

    /** @happy installation → double facteur → déconnexion → connexion en deux temps ; /install disparaît */
    public function testParcoursCompletDuGerant(): void
    {
        self::assertSame(200, $this->page('GET', '/install')->status);
        $secret = $this->installAndEnrol();
        self::assertSame('full', self::json($this->call('GET', '/api/v1/admin/session', '/api/v1/admin/session'))['stage']);
        self::assertSame(404, $this->page('GET', '/install')->status);
        self::assertSame(204, $this->call('DELETE', '/api/v1/admin/session', '/api/v1/admin/session', null, [], $this->csrf())->status);
        self::assertSame('none', self::json($this->call('GET', '/api/v1/admin/session', '/api/v1/admin/session'))['stage']);

        $login = $this->call('POST', '/api/v1/admin/session', '/api/v1/admin/session', ['email' => 'GERANT@salon.be', 'password' => self::PW], [], ['X-Requested-With' => 'barbier']);
        self::assertSame('password_ok', self::json($login)['stage']);
        $full = $this->call('POST', '/api/v1/admin/session/totp', '/api/v1/admin/session/totp', ['code' => $this->code($secret)], [], $this->csrf());
        self::assertSame('full', self::json($full)['stage']);
        self::assertTrue(self::json($full)['recentTotp']);
    }

    /** @negative mauvais code d'installation → 422 et aucun compte ; écriture sans jeton CSRF → 403 */
    public function testInstallationEtCsrfRefuses(): void
    {
        $form = http_build_query(['email' => 'g@salon.be', 'password' => self::PW, 'password_again' => self::PW, 'install_code' => 'mauvais-code-123']);
        self::assertSame(422, $this->page('POST', '/install', $form, ['Content-Type' => 'application/x-www-form-urlencoded'])->status);
        self::assertSame(0, (int) self::pdo()->query('SELECT COUNT(*) FROM editor')->fetchColumn());
        $this->installAndEnrol();
        self::assertSame(403, $this->call('DELETE', '/api/v1/admin/session', '/api/v1/admin/session')->status);
        $this->jar = [];
        self::assertSame(403, $this->call('POST', '/api/v1/admin/session', '/api/v1/admin/session', ['email' => 'gerant@salon.be', 'password' => self::PW])->status);
    }

    /** @edge 5 mots de passe faux → 429 avec Retry-After, même avec le bon mot de passe */
    public function testVerrouillageHttp(): void
    {
        $this->installAndEnrol();
        $this->jar = [];
        for ($i = 0; $i < 5; $i++) {
            $this->call('POST', '/api/v1/admin/session', '/api/v1/admin/session', ['email' => 'gerant@salon.be', 'password' => 'faux-mot-de-passe'], [], ['X-Requested-With' => 'barbier']);
        }
        $r = $this->call('POST', '/api/v1/admin/session', '/api/v1/admin/session', ['email' => 'gerant@salon.be', 'password' => self::PW], [], ['X-Requested-With' => 'barbier']);
        self::assertSame(429, $r->status);
        self::assertGreaterThan(0, (int) $r->headers['Retry-After']);
    }

    /** @security une session « mot de passe seul » n'ouvre pas l'admin ; le code rejoué est refusé ; régénérer les codes exige un TOTP récent */
    public function testEtapesEtTotpRecent(): void
    {
        $secret = $this->installAndEnrol();
        $this->call('DELETE', '/api/v1/admin/session', '/api/v1/admin/session', null, [], $this->csrf());
        $this->call('POST', '/api/v1/admin/session', '/api/v1/admin/session', ['email' => 'gerant@salon.be', 'password' => self::PW], [], ['X-Requested-With' => 'barbier']);
        self::assertSame(403, $this->call('POST', '/api/v1/admin/account/recovery-codes', '/api/v1/admin/account/recovery-codes', null, [], $this->csrf())->status);
        $code = $this->code($secret);
        $this->call('POST', '/api/v1/admin/session/totp', '/api/v1/admin/session/totp', ['code' => $code], [], $this->csrf());
        self::assertSame(401, $this->call('POST', '/api/v1/admin/session/totp', '/api/v1/admin/session/totp', ['code' => $code], [], $this->csrf())->status);
        self::assertSame(200, $this->call('POST', '/api/v1/admin/account/recovery-codes', '/api/v1/admin/account/recovery-codes', null, [], $this->csrf())->status);
        $this->fake->advance(601);
        self::assertSame(403, $this->call('POST', '/api/v1/admin/account/recovery-codes', '/api/v1/admin/account/recovery-codes', null, [], $this->csrf())->status);
    }

    /** @security session inactive 30 min → expirée */
    public function testExpirationDInactivite(): void
    {
        $this->installAndEnrol();
        $this->fake->advance(1801);
        self::assertSame('none', self::json($this->call('GET', '/api/v1/admin/session', '/api/v1/admin/session'))['stage']);
    }

    /** @security réinitialisation d'urgence : 404 sans fichier jeton ; avec, jeton + mot de passe → réactivation, jeton supprimé */
    public function testReinitialisationDUrgence(): void
    {
        $this->installAndEnrol();
        $token = dirname(__DIR__, 2) . '/app/var/reset-2fa.token';
        @unlink($token);
        self::assertSame(404, $this->page('GET', '/install/reset-2fa')->status);
        @mkdir(dirname($token), 0750, true);
        file_put_contents($token, "jeton-depose-par-sftp\n");
        try {
            $this->jar = [];
            self::assertSame(200, $this->page('GET', '/install/reset-2fa')->status);
            $bad = http_build_query(['email' => 'gerant@salon.be', 'password' => self::PW, 'token' => 'mauvais-jeton-xx']);
            self::assertSame(422, $this->page('POST', '/install/reset-2fa', $bad, ['Content-Type' => 'application/x-www-form-urlencoded'])->status);
            $ok = http_build_query(['email' => 'gerant@salon.be', 'password' => self::PW, 'token' => 'jeton-depose-par-sftp']);
            self::assertSame(303, $this->page('POST', '/install/reset-2fa', $ok, ['Content-Type' => 'application/x-www-form-urlencoded'])->status);
            self::assertFileDoesNotExist($token);
            self::assertSame('pre_enrolment', self::json($this->call('GET', '/api/v1/admin/session', '/api/v1/admin/session'))['stage']);
            self::assertSame(0, (int) self::pdo()->query('SELECT COUNT(*) FROM recovery_code')->fetchColumn());
        } finally {
            @unlink($token);
        }
    }
}

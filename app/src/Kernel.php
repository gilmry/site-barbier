<?php

declare(strict_types=1);

namespace Barbier;

use Barbier\Adapter\Clock\SystemClock;
use Barbier\Adapter\Notification\LogSecurityNotifier;
use Barbier\Adapter\Persistence\Database;
use Barbier\Adapter\Persistence\PdoEditorRepository;
use Barbier\Adapter\Persistence\PdoRecoveryCodeStore;
use Barbier\Adapter\Security\SecureRandom;
use Barbier\Adapter\Security\SodiumCrypto;
use Barbier\Application\Acces\AccessService;
use Barbier\Domain\Acces\PasswordPolicy;
use Barbier\Domain\Shared\Clock;
use Barbier\Http\Admin\AccessController;
use Barbier\Http\Admin\InstallController;
use Barbier\Http\Contract\Spec;
use Barbier\Http\Contract\StrictJson;
use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Http\Router;
use Barbier\Http\SecurityHeaders;
use Barbier\Http\SessionManager;
use Barbier\Http\View;
use PDO;
use Throwable;

/**
 * Câblage de l'application : services (créés à la demande), routes → contrôleurs.
 * Seule la couche Http traduit en codes HTTP ; le domaine et les cas d'usage n'en savent rien.
 */
final class Kernel
{
    private ?PDO $pdo = null;
    /** @var array<string,object> */
    private array $services = [];

    /** @param array<string,mixed> $config */
    public function __construct(private readonly array $config, private readonly ?Clock $clock = null)
    {
    }

    public function router(): Router
    {
        $router = new Router(new StrictJson(Spec::default()));
        $access = fn (): AccessController => $this->accessController();
        $install = fn (): InstallController => $this->installController();

        $router->api('GET', '/api/v1/health', fn (): Response => $this->health());

        // Accès (FR-040 à FR-043) — API consommée par l'îlot d'administration.
        $router->api('GET', '/api/v1/admin/session', fn (Request $r): Response => $access()->status($r));
        $router->api('POST', '/api/v1/admin/session', fn (Request $r, array $in): Response => $access()->login($r, $in));
        $router->api('DELETE', '/api/v1/admin/session', fn (Request $r): Response => $access()->logout($r));
        $router->api('POST', '/api/v1/admin/session/totp', fn (Request $r, array $in): Response => $access()->totp($r, $in));
        $router->api('POST', '/api/v1/admin/session/recovery', fn (Request $r, array $in): Response => $access()->recovery($r, $in));
        $router->api('POST', '/api/v1/admin/account/totp', fn (Request $r): Response => $access()->startEnrollment($r));
        $router->api('POST', '/api/v1/admin/account/totp/confirm', fn (Request $r, array $in): Response => $access()->confirmEnrollment($r, $in));
        $router->api('POST', '/api/v1/admin/account/recovery-codes', fn (Request $r): Response => $access()->regenerateCodes($r));

        // Pages rendues côté serveur.
        $router->page('GET', '/install', fn (Request $r): Response => $install()->show($r));
        $router->page('POST', '/install', fn (Request $r): Response => $install()->submit($r));
        $router->page('GET', '/install/reset-2fa', fn (): Response => $install()->resetShow());
        $router->page('POST', '/install/reset-2fa', fn (Request $r): Response => $install()->resetSubmit($r));
        $router->page('GET', '/admin', fn (): Response => Response::html($this->view()->render('admin', ['version' => $this->version()]))->withHeader('Cache-Control', 'no-store'));

        return $router;
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->router()->dispatch($request);
        } catch (Throwable $e) {
            error_log('[barbier] ' . $e::class . ': ' . $e->getMessage());
            // Jamais de trace technique vers le client (FR-001 @negative).
            $response = Response::problem(500, 'Erreur interne');
        }

        return SecurityHeaders::apply($response, $request->isHttps() || $this->env() === 'prod');
    }

    // --- Services ---------------------------------------------------------------------------------

    public function pdo(): PDO
    {
        /** @var array{host?:string, port?:int, database?:string, username?:string, password?:string} $db */
        $db = $this->config['db'] ?? [];

        return $this->pdo ??= Database::connect($db);
    }

    public function clock(): Clock
    {
        return $this->clock ?? $this->services['clock'] ??= new SystemClock();
    }

    public function crypto(): SodiumCrypto
    {
        return $this->services['crypto'] ??= new SodiumCrypto((string) ($this->config['app_key'] ?? ''));
    }

    public function access(): AccessService
    {
        return $this->services['access'] ??= new AccessService(
            new PdoEditorRepository($this->pdo()),
            new PdoRecoveryCodeStore($this->pdo()),
            $this->crypto(),
            new PasswordPolicy(array_values(array_filter(array_map('trim', (array) file(self::appDir() . '/data/common-passwords.txt'))))),
            $this->clock(),
            new SecureRandom(),
        );
    }

    public function sessions(): SessionManager
    {
        return $this->services['sessions'] ??= new SessionManager($this->pdo(), $this->crypto(), $this->clock(), $this->env() === 'prod');
    }

    public function securityNotifier(): LogSecurityNotifier
    {
        return $this->services['securityNotifier'] ??= new LogSecurityNotifier(self::appDir() . '/var/log');
    }

    public function view(): View
    {
        return $this->services['view'] ??= new View(self::appDir() . '/templates');
    }

    public static function appDir(): string
    {
        return dirname(__DIR__);
    }

    private function env(): string
    {
        return (string) ($this->config['env'] ?? 'prod');
    }

    private function version(): string
    {
        $v = @file_get_contents(dirname(self::appDir()) . '/VERSION');

        return is_string($v) ? trim($v) : 'dev';
    }

    private function accessController(): AccessController
    {
        return $this->services['accessController'] ??= new AccessController($this->access(), $this->sessions(), $this->clock());
    }

    private function installController(): InstallController
    {
        return new InstallController($this->config, fn (): PDO => $this->pdo(), fn (): AccessService => $this->access(), fn (): SessionManager => $this->sessions(), $this->view(), self::appDir(), $this->securityNotifier());
    }

    private function health(): Response
    {
        try {
            $this->pdo()->query('SELECT 1')->fetchColumn();
        } catch (Throwable) {
            return Response::json(['status' => 'degraded', 'database' => 'unreachable'], 503);
        }

        return Response::json(['status' => 'ok', 'database' => 'ok']);
    }
}

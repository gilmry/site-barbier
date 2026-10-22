<?php

declare(strict_types=1);

namespace Barbier;

use Barbier\Adapter\Clock\SystemClock;
use Barbier\Adapter\I18n\FileTexts;
use Barbier\Adapter\Mail\SmtpMailer;
use Barbier\Adapter\Persistence\PdoAgendaStore;
use Barbier\Adapter\Persistence\PdoMailLog;
use Barbier\Adapter\Media\FileMediaStorage;
use Barbier\Adapter\Media\GdImageProcessor;
use Barbier\Adapter\Notification\LogSecurityNotifier;
use Barbier\Adapter\Notification\MailSecurityNotifier;
use Barbier\Adapter\Persistence\Migrator;
use Barbier\Adapter\Persistence\PdoBackupExporter;
use Barbier\Adapter\Persistence\Database;
use Barbier\Adapter\Persistence\PdoEditorRepository;
use Barbier\Adapter\Persistence\PdoRecoveryCodeStore;
use Barbier\Adapter\Persistence\PdoVitrineRepository;
use Barbier\Adapter\Security\SecureRandom;
use Barbier\Adapter\Security\SodiumCrypto;
use Barbier\Application\Acces\AccessService;
use Barbier\Application\Agenda\AgendaAdmin;
use Barbier\Application\Agenda\BookingService;
use Barbier\Application\Agenda\MaintenanceService;
use Barbier\Application\Agenda\PrivacyService;
use Barbier\Application\Agenda\StoreAgendaGuard;
use Barbier\Application\Exploitation\HealthService;
use Barbier\Application\Notification\NotificationService;
use Barbier\Domain\Agenda\RetentionPolicy;
use Barbier\Domain\Agenda\SlotCalculator;
use Barbier\Application\Vitrine\HomePage;
use Barbier\Application\Vitrine\VitrineAdmin;
use Barbier\Domain\Acces\PasswordPolicy;
use Barbier\Domain\Shared\Clock;
use Barbier\Http\Admin\AccessController;
use Barbier\Http\Admin\AgendaController;
use Barbier\Http\Admin\ExploitationController;
use Barbier\Http\Admin\InstallController;
use Barbier\Http\Admin\VitrineController;
use Barbier\Http\Contract\Spec;
use Barbier\Http\Contract\StrictJson;
use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Http\Router;
use Barbier\Http\SecurityHeaders;
use Barbier\Http\SessionManager;
use Barbier\Http\Site\BookingApiController;
use Barbier\Http\Site\PublicController;
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
    private ?Request $request = null;
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

        // Édition de la vitrine (FR-006 à FR-011).
        $v = fn (string $action): \Closure => fn (Request $r, array $in): Response => $this->vitrineController()->route($action, $r, $in);
        $router->api('GET', '/api/v1/admin/sections', $v('sections'));
        $router->api('PUT', '/api/v1/admin/sections/{key}', $v('section'));
        $router->api('GET', '/api/v1/admin/salon', $v('salon'));
        $router->api('PUT', '/api/v1/admin/salon', $v('salon.update'));
        $router->api('GET', '/api/v1/admin/opening-hours', $v('hours'));
        $router->api('PUT', '/api/v1/admin/opening-hours', $v('hours.update'));
        $router->api('GET', '/api/v1/admin/catalog', $v('catalog'));
        $router->api('POST', '/api/v1/admin/categories', $v('category.create'));
        $router->api('PUT', '/api/v1/admin/categories/{id}', $v('category.update'));
        $router->api('DELETE', '/api/v1/admin/categories/{id}', $v('category.delete'));
        $router->api('POST', '/api/v1/admin/services', $v('service.create'));
        $router->api('PUT', '/api/v1/admin/services/{id}', $v('service.update'));
        $router->api('DELETE', '/api/v1/admin/services/{id}', $v('service.delete'));
        $router->api('GET', '/api/v1/admin/barbers', $v('barbers'));
        $router->api('POST', '/api/v1/admin/barbers', $v('barber.create'));
        $router->api('PUT', '/api/v1/admin/barbers/{id}', $v('barber.update'));
        $router->api('GET', '/api/v1/admin/media', $v('media'));
        $router->api('POST', '/api/v1/admin/media', $v('media.upload'));
        $router->api('PUT', '/api/v1/admin/media/{id}', $v('media.update'));
        $router->api('DELETE', '/api/v1/admin/media/{id}', $v('media.delete'));

        // Réservation publique (FR-022 à FR-025).
        $bk = fn (string $action): \Closure => fn (Request $r, array $in): Response => $this->bookingApi()->route($action, $r, $in);
        $router->api('GET', '/api/v1/services', $bk('services'));
        $router->api('GET', '/api/v1/barbers', $bk('barbers'));
        $router->api('GET', '/api/v1/availability/days', $bk('days'));
        $router->api('GET', '/api/v1/availability/slots', $bk('slots'));
        $router->api('POST', '/api/v1/bookings', $bk('book'));
        $router->api('POST', '/api/v1/bookings/cancel', $bk('cancel'));

        // Agenda côté salon (FR-020, FR-021, FR-026, FR-032) et droits RGPD (FR-012).
        $ag = fn (string $action): \Closure => fn (Request $r, array $in): Response => $this->agendaController()->route($action, $r, $in);
        $router->api('GET', '/api/v1/admin/barbers/{id}/availability', $ag('availability'));
        $router->api('PUT', '/api/v1/admin/barbers/{id}/availability', $ag('availability.set'));
        $router->api('GET', '/api/v1/admin/absences', $ag('absences'));
        $router->api('POST', '/api/v1/admin/absences', $ag('absence.add'));
        $router->api('DELETE', '/api/v1/admin/absences/{id}', $ag('absence.delete'));
        $router->api('GET', '/api/v1/admin/bookings', $ag('bookings'));
        $router->api('POST', '/api/v1/admin/bookings/{id}/cancel', $ag('booking.cancel'));
        $router->api('GET', '/api/v1/admin/mail-log', $ag('mails'));
        $router->api('POST', '/api/v1/admin/mail-log/retry', $ag('mails.retry'));
        $router->api('POST', '/api/v1/admin/privacy/search', $ag('privacy.search'));
        $router->api('POST', '/api/v1/admin/privacy/export', $ag('privacy.export'));
        $router->api('POST', '/api/v1/admin/privacy/erase', $ag('privacy.erase'));

        // Exploitation (Story 5.2, FR-044, FR-042).
        $ex = fn (string $action): \Closure => fn (Request $r): Response => $this->exploitationController()->route($action, $r);
        $router->api('GET', '/api/v1/admin/health', $ex('health'));
        $router->api('GET', '/api/v1/admin/backup', $ex('backup'));
        $router->api('GET', '/api/v1/admin/migrations', $ex('migrations'));
        $router->api('POST', '/api/v1/admin/migrations', $ex('migrate'));

        // Pages rendues côté serveur.
        $router->page('GET', '/install', fn (Request $r): Response => $install()->show($r));
        $router->page('POST', '/install', fn (Request $r): Response => $install()->submit($r));
        $router->page('GET', '/install/reset-2fa', fn (): Response => $install()->resetShow());
        $router->page('POST', '/install/reset-2fa', fn (Request $r): Response => $install()->resetSubmit($r));
        $router->page('GET', '/admin', fn (): Response => Response::html($this->view()->render('admin', ['version' => $this->version()]))->withHeader('Cache-Control', 'no-store'));

        // Site public (FR-001 à FR-005, FR-011) — en dernier : /{lang} et /{lang}/{slug} sont génériques.
        $site = fn (): PublicController => $this->publicController();
        $router->page('GET', '/', fn (Request $r): Response => $site()->root($r));
        $router->page('GET', '/{lang}', fn (Request $r, array $in): Response => $site()->home($r, $in));
        $router->page('GET', '/{lang}/{slug}', fn (Request $r, array $in): Response => $site()->subpage($r, $in));
        $router->page('GET', '/{lang}/{slug}/{token}', fn (Request $r, array $in): Response => $site()->cancelPage($r, $in));
        $router->page('POST', '/{lang}/{slug}/{token}', fn (Request $r, array $in): Response => $site()->cancelSubmit($r, $in));

        return $router;
    }

    public function handle(Request $request): Response
    {
        $this->request = $request;
        $isApi = str_starts_with($request->path(), '/api/');
        if (!$isApi && $request->method() === 'GET') {
            $this->dailyMaintenance();
        }
        try {
            $response = $this->router()->dispatch($request);
        } catch (Throwable $e) {
            error_log('[barbier] ' . $e::class . ': ' . $e->getMessage());
            // Jamais de trace technique vers le client (FR-001 @negative).
            $response = Response::problem(500, 'Erreur interne');
        }
        // Hors API, une erreur s'affiche en page HTML dans la langue de l'adresse (FR-001, FR-002).
        if (!$isApi && in_array($response->status, [404, 405, 500], true) && str_starts_with($response->headers['Content-Type'] ?? '', 'application/problem+json')) {
            try {
                $response = $this->publicController()->error(PublicController::langOfPath($request->path()), $response->status === 500 ? 500 : 404);
            } catch (Throwable) {
                $response = Response::html('<!doctype html><title>Erreur</title><p>Erreur.</p>', $response->status === 500 ? 500 : 404);
            }
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
            $this->securityNotifier(),
        );
    }

    public function sessions(): SessionManager
    {
        return $this->services['sessions'] ??= new SessionManager($this->pdo(), $this->crypto(), $this->clock(), $this->env() === 'prod');
    }

    public function securityNotifier(): LogSecurityNotifier
    {
        /** @var array{host?:string, port?:int, username?:string, password?:string, secure?:string, from?:string, from_name?:string} $smtp */
        $smtp = (array) ($this->config['smtp'] ?? []);

        return $this->services['securityNotifier'] ??= new LogSecurityNotifier(self::appDir() . '/var/log',
            new MailSecurityNotifier(new SmtpMailer($smtp), fn (): string => $this->vitrine()->salon()['email']));
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

    public function vitrine(): PdoVitrineRepository
    {
        return $this->services['vitrine'] ??= new PdoVitrineRepository($this->pdo());
    }

    public function vitrineAdmin(): VitrineAdmin
    {
        return $this->services['vitrineAdmin'] ??= (new VitrineAdmin($this->vitrine(), new SecureRandom(), new GdImageProcessor(),
            new FileMediaStorage((string) ($this->config['uploads_dir'] ?? dirname(self::appDir()) . '/www/uploads'))))
            ->withGuard(new StoreAgendaGuard($this->agendaStore(), $this->clock()));
    }

    public function agendaStore(): PdoAgendaStore
    {
        return $this->services['agendaStore'] ??= new PdoAgendaStore($this->pdo());
    }

    public function mailLog(): PdoMailLog
    {
        return $this->services['mailLog'] ??= new PdoMailLog($this->pdo());
    }

    public function notifications(): NotificationService
    {
        /** @var array{host?:string, port?:int, username?:string, password?:string, secure?:string, from?:string, from_name?:string} $smtp */
        $smtp = (array) ($this->config['smtp'] ?? []);

        return $this->services['notifications'] ??= new NotificationService(new SmtpMailer($smtp), $this->mailLog(),
            new FileTexts(self::appDir() . '/i18n'), $this->vitrine(), $this->agendaStore(), $this->crypto(), new SecureRandom(), $this->baseUrl());
    }

    public function booking(): BookingService
    {
        return $this->services['booking'] ??= new BookingService($this->agendaStore(), $this->vitrine(), new SlotCalculator(), $this->clock(), new SecureRandom(), $this->crypto(), $this->notifications());
    }

    public function agendaAdmin(): AgendaAdmin
    {
        return $this->services['agendaAdmin'] ??= new AgendaAdmin($this->agendaStore(), $this->vitrine(), $this->clock(), new SecureRandom(), $this->notifications(), $this->mailLog());
    }

    public function maintenance(): MaintenanceService
    {
        return $this->services['maintenance'] ??= new MaintenanceService($this->agendaStore(), $this->clock(), new RetentionPolicy($this->retentionMonths()));
    }

    /** Adresse publique du site pour les liens des e-mails : config `base_url`, sinon l'hôte de la requête. */
    public function baseUrl(): string
    {
        if (($this->config['base_url'] ?? '') !== '') {
            return rtrim((string) $this->config['base_url'], '/');
        }
        $host = $this->request?->header('host') ?? 'localhost';

        return ($this->request?->isHttps() || $this->env() === 'prod' ? 'https' : 'http') . '://' . preg_replace('/[^A-Za-z0-9.:\-]/', '', $host);
    }

    /** Purge du jour au premier passage (si l'hébergeur n'a pas de tâche planifiée) — jamais bloquante. */
    private function dailyMaintenance(): void
    {
        try {
            $this->maintenance()->dailyPurge();
        } catch (Throwable $e) {
            error_log('[barbier] purge : ' . $e->getMessage());
        }
    }

    private function exploitationController(): ExploitationController
    {
        $health = new HealthService($this->agendaStore(), $this->mailLog(), $this->clock(), new PdoBackupExporter($this->pdo()), $this->version(),
            (string) ($this->config['uploads_dir'] ?? dirname(self::appDir()) . '/www/uploads'), self::appDir() . '/var/log', (($this->config['smtp']['host'] ?? '') !== ''));

        return $this->services['exploitation'] ??= new ExploitationController($health, new Migrator($this->pdo(), self::appDir() . '/migrations'), $this->accessController());
    }

    private function bookingApi(): BookingApiController
    {
        return $this->services['bookingApi'] ??= new BookingApiController($this->booking());
    }

    private function agendaController(): AgendaController
    {
        return $this->services['agendaController'] ??= new AgendaController($this->agendaAdmin(), new PrivacyService($this->agendaStore(), $this->notifications(), $this->clock()), $this->mailLog(), $this->accessController());
    }

    private function vitrineController(): VitrineController
    {
        return $this->services['vitrineController'] ??= new VitrineController($this->vitrineAdmin(), $this->accessController());
    }

    public function retentionMonths(): int
    {
        return (int) ($this->config['retention_months'] ?? 6);
    }

    private function publicController(): PublicController
    {
        return $this->services['public'] ??= new PublicController(new HomePage($this->vitrine(), $this->retentionMonths()), $this->view(), self::appDir() . '/i18n', $this->clock(), $this->version(),
            fn (): BookingService => $this->booking(), fn (): PdoAgendaStore => $this->agendaStore());
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

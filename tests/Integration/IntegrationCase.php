<?php

declare(strict_types=1);

namespace Barbier\Tests\Integration;

use Barbier\Adapter\Persistence\Database;
use Barbier\Adapter\Persistence\Migrator;
use Barbier\Http\Contract\Spec;
use Barbier\Http\Contract\StrictJson;
use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Kernel;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base des tests d'intégration : vraie base MySQL (variables DB_*), schéma migré, application appelée
 * en processus (Kernel), et chaque réponse d'API validée contre le contrat (élément 4).
 */
abstract class IntegrationCase extends TestCase
{
    protected static ?PDO $pdo = null;

    /** @return array{host:string, port:int, database:string, username:string, password:string} */
    protected static function dbConfig(): array
    {
        return [
            'host' => getenv('DB_HOST') ?: '127.0.0.1', 'port' => (int) (getenv('DB_PORT') ?: 3306),
            'database' => (string) getenv('DB_NAME'), 'username' => (string) getenv('DB_USER'), 'password' => (string) getenv('DB_PASSWORD'),
        ];
    }

    protected static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = Database::connect(self::dbConfig());
            (new Migrator(self::$pdo, dirname(__DIR__, 2) . '/app/migrations'))->up();
        }

        return self::$pdo;
    }

    protected ?\Barbier\Domain\Shared\Clock $clock = null;
    /** @var array<string,string> cookies du « navigateur » de test */
    protected array $jar = [];

    /** @return array<string,mixed> */
    protected static function config(): array
    {
        return ['env' => 'test', 'db' => self::dbConfig(), 'app_key' => (string) getenv('APP_KEY'), 'install_code' => (string) getenv('INSTALL_CODE'),
            'uploads_dir' => dirname(__DIR__, 2) . '/app/var/test-uploads'];
    }

    protected function kernel(): Kernel
    {
        self::pdo();

        return new Kernel(self::config(), $this->clock);
    }

    /**
     * Ouvre directement une session d'administration complète (sans passer par l'UI) et renvoie l'en-tête CSRF.
     *
     * @return array<string,string>
     */
    protected function asEditor(): array
    {
        $k = $this->kernel();
        $editor = $k->access()->isInstalled() ? null : $k->access()->install('gerant@salon.be', 'Cèdre-lame-serviette-9', 'Cèdre-lame-serviette-9', (string) getenv('INSTALL_CODE'), (string) getenv('INSTALL_CODE'));
        $id = $editor ?? (string) self::pdo()->query('SELECT id FROM editor LIMIT 1')->fetchColumn();
        [$session, $cookie] = $k->sessions()->open($id, \Barbier\Http\AdminSession::FULL, true);
        preg_match('/^([^=]+)=([^;]*)/', $cookie, $m);
        $this->jar[$m[1]] = $m[2];

        return ['X-CSRF-Token' => $session->csrf];
    }

    /** Remet le contenu initial : tables de la vitrine vidées, puis migration 0004 (contenu) rejouée. */
    protected static function resetContent(): void
    {
        self::truncate('barber_service', 'barber', 'service', 'category', 'translation', 'section', 'salon', 'opening_hours', 'media');
        $sql = (string) file_get_contents(dirname(__DIR__, 2) . '/app/migrations/0004_contenu_initial.up.sql');
        foreach (\Barbier\Adapter\Persistence\Migrator::statements($sql) as $st) {
            self::pdo()->exec($st);
        }
    }

    /** Vide les tables métier entre deux tests (le schéma reste migré). */
    protected static function truncate(string ...$tables): void
    {
        self::pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $t) {
            self::pdo()->exec("DELETE FROM `$t`");
        }
        self::pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Appelle l'API et vérifie que la réponse respecte le contrat pour l'opération visée.
     *
     * @param array<string,mixed> $query
     * @param array<string,string> $headers
     */
    protected function call(string $method, string $pattern, string $path, ?array $body = null, array $query = [], array $headers = []): Response
    {
        $raw = $body === null ? '' : (string) json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }
        $response = $this->kernel()->handle(new Request($method, $path, $raw, $query, $headers, $this->jar));
        $this->remember($response);
        $errors = (new StrictJson(Spec::default()))->responseErrors($method, $pattern, $response->status, $response->body, $response->headers['Content-Type'] ?? '');
        self::assertSame([], $errors, "Réponse hors contrat pour $method $pattern ({$response->status}) : {$response->body}");

        return $response;
    }

    /** Requête de page (hors contrat) avec le même « navigateur ». @param array<string,string> $headers */
    protected function page(string $method, string $path, string $body = '', array $headers = []): Response
    {
        $response = $this->kernel()->handle(new Request($method, $path, $body, [], $headers, $this->jar));
        $this->remember($response);

        return $response;
    }

    private function remember(Response $r): void
    {
        $c = $r->headers['Set-Cookie'] ?? null;
        if ($c !== null && preg_match('/^([^=]+)=([^;]*)/', $c, $m) === 1) {
            if ($m[2] === '') {
                unset($this->jar[$m[1]]);
            } else {
                $this->jar[$m[1]] = $m[2];
            }
        }
    }

    /** @return array<string,mixed> */
    protected static function json(Response $r): array
    {
        return (array) json_decode($r->body, true, 64, JSON_THROW_ON_ERROR);
    }
}

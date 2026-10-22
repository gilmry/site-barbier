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

    protected function kernel(): Kernel
    {
        self::pdo();

        return new Kernel(['db' => self::dbConfig()]);
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
        $response = $this->kernel()->handle(new Request($method, $path, $raw, $query, $headers));
        $errors = (new StrictJson(Spec::default()))->responseErrors($method, $pattern, $response->status, $response->body, $response->headers['Content-Type'] ?? '');
        self::assertSame([], $errors, "Réponse hors contrat pour $method $pattern ({$response->status}) : {$response->body}");

        return $response;
    }

    /** @return array<string,mixed> */
    protected static function json(Response $r): array
    {
        return (array) json_decode($r->body, true, 64, JSON_THROW_ON_ERROR);
    }
}

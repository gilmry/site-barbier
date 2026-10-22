<?php

declare(strict_types=1);

namespace Barbier;

use Barbier\Adapter\Persistence\Database;
use Barbier\Http\Contract\Spec;
use Barbier\Http\Contract\StrictJson;
use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Http\Router;
use PDO;
use Throwable;

/**
 * Câblage de l'application : routes → gestionnaires. Seule la couche Http traduit en codes HTTP.
 * Les cas d'usage arrivent avec les stories métier ; le socle n'expose que la santé.
 */
final class Kernel
{
    private ?PDO $pdo = null;

    /** @param array{db?:array<string,mixed>} $config */
    public function __construct(private readonly array $config)
    {
    }

    public function router(): Router
    {
        $router = new Router(new StrictJson(Spec::default()));
        $router->api('GET', '/api/v1/health', fn (): Response => $this->health());

        return $router;
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router()->dispatch($request);
        } catch (Throwable) {
            // Jamais de trace technique vers le client (FR-001 @negative).
            return Response::problem(500, 'Erreur interne');
        }
    }

    private function health(): Response
    {
        try {
            $this->db()->query('SELECT 1')->fetchColumn();
        } catch (Throwable) {
            return Response::json(['status' => 'degraded', 'database' => 'unreachable'], 503);
        }

        return Response::json(['status' => 'ok', 'database' => 'ok']);
    }

    private function db(): PDO
    {
        /** @var array{host?:string, port?:int, database?:string, username?:string, password?:string} $db */
        $db = $this->config['db'] ?? [];

        return $this->pdo ??= Database::connect($db);
    }
}

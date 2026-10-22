<?php

declare(strict_types=1);

namespace Barbier\Http;

use Barbier\Http\Contract\ContractViolation;
use Barbier\Http\Contract\StrictJson;

/**
 * Table de routes explicite : (méthode, motif) → gestionnaire. Les routes d'API (« contract »)
 * sont validées par StrictJson AVANT le gestionnaire ; les pages rendues côté serveur ne le sont pas.
 * La table est introspectable (routes()) pour le gate de parité routes ↔ spec.
 *
 * Gestionnaire : fn(Request, array{path:array, query:array, body:mixed}): Response
 */
final class Router
{
    /** @var list<array{method:string, pattern:string, handler:callable, contract:bool}> */
    private array $routes = [];

    public function __construct(private readonly ?StrictJson $strict = null)
    {
    }

    public function api(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = ['method' => strtoupper($method), 'pattern' => $pattern, 'handler' => $handler, 'contract' => true];
    }

    public function page(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = ['method' => strtoupper($method), 'pattern' => $pattern, 'handler' => $handler, 'contract' => false];
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            $params = self::match($route['pattern'], $request->path());
            if ($params === null) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method()) {
                continue;
            }
            $input = ['path' => $params, 'query' => $request->query(), 'body' => null];
            if ($route['contract'] && $this->strict !== null) {
                try {
                    $input = $this->strict->validateRequest($route['method'], $route['pattern'], $request, $params);
                } catch (ContractViolation $e) {
                    return Response::problem(400, 'Requête invalide', null, $e->errors);
                }
            }

            return ($route['handler'])($request, $input);
        }

        return $pathMatched
            ? Response::problem(405, 'Méthode non autorisée')
            : Response::problem(404, 'Ressource introuvable');
    }

    /** @return list<array{method:string, pattern:string, contract:bool}> */
    public function routes(): array
    {
        return array_map(
            static fn (array $r): array => ['method' => $r['method'], 'pattern' => $r['pattern'], 'contract' => $r['contract']],
            $this->routes,
        );
    }

    /**
     * Motif à la mode OpenAPI : /api/v1/barbers/{id}. Un paramètre ne traverse jamais un « / ».
     *
     * @return array<string,string>|null
     */
    private static function match(string $pattern, string $path): ?array
    {
        $regex = '#^' . preg_replace('#\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}#', '(?P<$1>[^/]+)', preg_quote($pattern, '#')) . '$#';
        if (preg_match($regex, $path, $m) !== 1) {
            return null;
        }

        return array_map('rawurldecode', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}

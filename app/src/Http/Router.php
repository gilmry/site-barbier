<?php

declare(strict_types=1);

namespace Barbier\Http;

/**
 * Table de routes explicite : (méthode, motif) → gestionnaire. La table est introspectable
 * (routes()) pour le gate de parité routes ↔ spec (Story 0.2).
 */
final class Router
{
    /** @var list<array{method:string, pattern:string, handler:callable(Request, array<string,string>):Response}> */
    private array $routes = [];

    /** @param callable(Request, array<string,string>):Response $handler */
    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = ['method' => strtoupper($method), 'pattern' => $pattern, 'handler' => $handler];
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
            if ($route['method'] === $request->method()) {
                return ($route['handler'])($request, $params);
            }
        }

        return $pathMatched
            ? Response::problem(405, 'Méthode non autorisée')
            : Response::problem(404, 'Ressource introuvable');
    }

    /** @return list<array{method:string, pattern:string}> */
    public function routes(): array
    {
        return array_map(static fn (array $r): array => ['method' => $r['method'], 'pattern' => $r['pattern']], $this->routes);
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

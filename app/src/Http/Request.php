<?php

declare(strict_types=1);

namespace Barbier\Http;

/** Requête HTTP minimale : méthode, chemin, corps brut. Le décodage strict viendra avec StrictJson (Story 0.2). */
final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly string $rawBody = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            is_string($path) && $path !== '' ? $path : '/',
            (string) file_get_contents('php://input'),
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    /** Chemin normalisé : sans barre finale (sauf la racine). */
    public function path(): string
    {
        $p = rtrim($this->path, '/');

        return $p === '' ? '/' : $p;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }
}

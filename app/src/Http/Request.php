<?php

declare(strict_types=1);

namespace Barbier\Http;

/** Requête HTTP : méthode, chemin, paramètres de requête, en-têtes (clés en minuscules), corps brut. */
final class Request
{
    /** @var array<string,string> */
    private array $headers;

    /**
     * @param array<string,mixed>  $query
     * @param array<string,string> $headers
     * @param array<string,string> $cookies
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly string $rawBody = '',
        private readonly array $query = [],
        array $headers = [],
        private readonly array $cookies = [],
        private readonly string $clientIp = '127.0.0.1',
    ) {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (is_string($v) && str_starts_with($k, 'HTTP_')) {
                $headers[str_replace('_', '-', strtolower(substr($k, 5)))] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            is_string($path) && $path !== '' ? $path : '/',
            (string) file_get_contents('php://input'),
            $_GET,
            $headers,
            array_filter($_COOKIE, 'is_string'),
            (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
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

    /** @return array<string,mixed> */
    public function query(): array
    {
        return $this->query;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function clientIp(): string
    {
        return $this->clientIp;
    }

    public function isHttps(): bool
    {
        return ($this->header('x-forwarded-proto') ?? '') === 'https' || (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off');
    }
}

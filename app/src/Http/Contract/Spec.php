<?php

declare(strict_types=1);

namespace Barbier\Http\Contract;

use RuntimeException;

/** Le contrat OpenAPI chargé une fois : opérations par (méthode, chemin) et pointeurs JSON. */
final class Spec
{
    public const BASE_URI = 'https://site-barbier.local/openapi.json';

    /** @var array<string,mixed> */
    private array $doc;

    public function __construct(private readonly string $file)
    {
        $json = @file_get_contents($file);
        $doc = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($doc) || !str_starts_with((string) ($doc['openapi'] ?? ''), '3.1')) {
            throw new RuntimeException("Contrat OpenAPI 3.1 illisible : $file");
        }
        $this->doc = $doc;
    }

    public static function default(): self
    {
        static $spec = null;

        return $spec ??= new self(dirname(__DIR__, 4) . '/openapi/site-barbier.openapi.json');
    }

    /** @return array<string,mixed> */
    public function document(): array
    {
        return $this->doc;
    }

    public function raw(): string
    {
        return (string) file_get_contents($this->file);
    }

    /** @return array<string,mixed>|null */
    public function operation(string $method, string $path): ?array
    {
        $op = $this->doc['paths'][$path][strtolower($method)] ?? null;

        return is_array($op) ? $op : null;
    }

    /** @return list<array{method:string, pattern:string}> */
    public function operations(): array
    {
        $out = [];
        foreach ($this->doc['paths'] ?? [] as $path => $ops) {
            foreach ($ops as $method => $_) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $out[] = ['method' => strtoupper($method), 'pattern' => $path];
                }
            }
        }

        return $out;
    }

    /** Pointeur JSON (RFC 6901) vers un nœud du document, encodé pour un fragment d'URI, préfixé de l'URI de base. */
    public static function pointer(string ...$segments): string
    {
        return self::BASE_URI . '#/' . implode('/', array_map(
            static fn (string $s): string => rawurlencode(str_replace(['~', '/'], ['~0', '~1'], $s)),
            $segments,
        ));
    }
}

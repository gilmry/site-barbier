<?php

declare(strict_types=1);

namespace Barbier\Http;

/** Réponse HTTP immuable ; l'émission est séparée pour rester testable sans serveur. */
final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }

    /** @param array<mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self($status, self::encode($data), [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Erreur au format RFC 9457 (problem+json). */
    /** @param list<array{field:string, message:string}> $errors */
    public static function problem(int $status, string $title, ?string $detail = null, array $errors = []): self
    {
        $data = ['type' => 'about:blank', 'title' => $title, 'status' => $status];
        if ($detail !== null) {
            $data['detail'] = $detail;
        }
        if ($errors !== []) {
            $data['errors'] = $errors;
        }

        return new self($status, self::encode($data), [
            'Content-Type' => 'application/problem+json; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, $html, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function noContent(): self
    {
        return new self(204, '', []);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->status, $this->body, [...$this->headers, $name => $value]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }

    /** @param array<mixed> $data */
    private static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}

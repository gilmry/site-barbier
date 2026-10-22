<?php

declare(strict_types=1);

namespace Barbier\Http;

/** En-têtes de sécurité de toutes les réponses (FR-001, FR-041 @security). Aucun tiers autorisé (ADR-007). */
final class SecurityHeaders
{
    public static function apply(Response $response, bool $https): Response
    {
        $r = $response
            ->withHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'")
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        return $https ? $r->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains') : $r;
    }
}

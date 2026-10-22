<?php

declare(strict_types=1);

namespace Barbier\Http;

use Barbier\Domain\Acces\AccessDenied;
use Barbier\Domain\Shared\DomainException;

/** Seule traduction des exceptions du domaine en codes HTTP (règle des couches). */
final class Errors
{
    private const STATUS = [
        'invalid_credentials' => 401,
        'locked' => 429,
        'not_found' => 404,
        'already_installed' => 404,
        'conflict' => 409,
        'slot_taken' => 409,
        'stale_version' => 409,
        'no_pending_enrollment' => 409,
        'has_future_bookings' => 409,
        'too_late' => 422,
        'rate_limited' => 429,
        'already_cancelled' => 422,
        'in_past' => 422,
        'outside_hours' => 422,
        'overlap' => 422,
        'notify_failed' => 409,
        'rejected' => 422,
    ];

    public static function fromDomain(DomainException $e): Response
    {
        $status = self::STATUS[$e->reason] ?? 422;
        $errors = $e->field !== null ? [['field' => $e->field, 'message' => $e->getMessage()]] : [];
        $r = Response::problem($status, $e->getMessage(), $e->reason, $errors);
        if ($e instanceof AccessDenied && $e->retryAfter > 0) {
            $r = $r->withHeader('Retry-After', (string) $e->retryAfter);
        }

        return $r;
    }
}

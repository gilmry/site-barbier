<?php

declare(strict_types=1);

namespace Barbier\Http;

use Barbier\Domain\Acces\Crypto;
use Barbier\Domain\Shared\Clock;
use PDO;

/**
 * Sessions d'administration en base (FR-041) : identifiant aléatoire de 256 bits, seule son empreinte
 * est stockée ; renouvelé à chaque changement d'étape ; expiration d'inactivité 30 min, absolue 12 h.
 * Cookie `__Host-` Secure HttpOnly SameSite=Strict en HTTPS (`barbier_sid` sans Secure en dev/test HTTP).
 */
final class SessionManager
{
    public const IDLE = 1800;
    public const ABSOLUTE = 43200;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Crypto $crypto,
        private readonly Clock $clock,
        private readonly bool $secureCookies,
    ) {
    }

    public function cookieName(): string
    {
        return $this->secureCookies ? '__Host-barbier' : 'barbier_sid';
    }

    public function current(Request $request): ?AdminSession
    {
        $token = $request->cookie($this->cookieName());
        if ($token === null || strlen($token) !== 64 || !ctype_xdigit($token)) {
            return null;
        }
        $hash = $this->crypto->tokenHash($token);
        $st = $this->pdo->prepare('SELECT * FROM session WHERE id_hash = ?');
        $st->execute([$hash]);
        $row = $st->fetch();
        if ($row === false) {
            return null;
        }
        $now = $this->clock->now()->getTimestamp();
        $created = (new \DateTimeImmutable((string) $row['created_at'], new \DateTimeZone('UTC')))->getTimestamp();
        $seen = (new \DateTimeImmutable((string) $row['last_seen'], new \DateTimeZone('UTC')))->getTimestamp();
        if ($now - $seen > self::IDLE || $now - $created > self::ABSOLUTE) {
            $this->pdo->prepare('DELETE FROM session WHERE id_hash = ?')->execute([$hash]);

            return null;
        }
        $this->pdo->prepare('UPDATE session SET last_seen = ? WHERE id_hash = ?')->execute([$this->clock->now()->format('Y-m-d H:i:s'), $hash]);

        return new AdminSession($hash, (string) $row['editor_id'], (string) $row['stage'], (string) $row['csrf_token'],
            $row['totp_verified_at'] === null ? null : new \DateTimeImmutable((string) $row['totp_verified_at'], new \DateTimeZone('UTC')));
    }

    /** Ouvre (ou remplace) la session : nouvel identifiant à chaque étape. @return array{0:AdminSession, 1:string} session + en-tête Set-Cookie */
    public function open(string $editorId, string $stage, bool $totpVerified, ?AdminSession $previous = null): array
    {
        if ($previous !== null) {
            $this->pdo->prepare('DELETE FROM session WHERE id_hash = ?')->execute([$previous->idHash]);
        }
        $token = bin2hex(random_bytes(32));
        $csrf = bin2hex(random_bytes(32));
        $now = $this->clock->now();
        $verified = $totpVerified ? $now : null;
        $this->pdo->prepare('INSERT INTO session (id_hash, editor_id, stage, csrf_token, totp_verified_at, created_at, last_seen) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$this->crypto->tokenHash($token), $editorId, $stage, $csrf, $verified?->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')]);

        return [new AdminSession($this->crypto->tokenHash($token), $editorId, $stage, $csrf, $verified), $this->cookie($token, self::ABSOLUTE)];
    }

    /** Marque un code TOTP vérifié à l'instant (actions sensibles). */
    public function touchTotp(AdminSession $s): AdminSession
    {
        $now = $this->clock->now();
        $this->pdo->prepare('UPDATE session SET totp_verified_at = ? WHERE id_hash = ?')->execute([$now->format('Y-m-d H:i:s'), $s->idHash]);

        return new AdminSession($s->idHash, $s->editorId, $s->stage, $s->csrf, $now);
    }

    /** @return string en-tête Set-Cookie qui efface le cookie */
    public function close(?AdminSession $s): string
    {
        if ($s !== null) {
            $this->pdo->prepare('DELETE FROM session WHERE id_hash = ?')->execute([$s->idHash]);
        }

        return $this->cookie('', 0);
    }

    public function closeAllFor(string $editorId): void
    {
        $this->pdo->prepare('DELETE FROM session WHERE editor_id = ?')->execute([$editorId]);
    }

    private function cookie(string $value, int $maxAge): string
    {
        return sprintf('%s=%s; Path=/; Max-Age=%d; HttpOnly; SameSite=Strict%s', $this->cookieName(), $value, $maxAge, $this->secureCookies ? '; Secure' : '');
    }
}

<?php

declare(strict_types=1);

namespace Barbier\Http\Admin;

use Barbier\Application\Acces\AccessService;
use Barbier\Domain\Shared\Clock;
use Barbier\Domain\Shared\DomainException;
use Barbier\Http\AdminSession;
use Barbier\Http\Errors;
use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Http\SessionManager;

/**
 * API de session et de compte (FR-040, FR-041, FR-043) et garde d'accès de toute l'API d'administration.
 * Toute écriture exige l'en-tête X-CSRF-Token de la session (ou, sans session, X-Requested-With).
 */
final class AccessController
{
    public function __construct(
        private readonly AccessService $access,
        private readonly SessionManager $sessions,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Garde : session présente, à l'une des étapes admises, jeton CSRF valide pour les écritures.
     *
     * @param list<string> $stages
     */
    public function guard(Request $r, array $stages = [AdminSession::FULL], bool $recentTotp = false): AdminSession|Response
    {
        $s = $this->sessions->current($r);
        if ($s === null) {
            return Response::problem(401, 'Connexion requise', 'unauthenticated');
        }
        if ($r->method() !== 'GET' && !hash_equals($s->csrf, (string) $r->header('x-csrf-token'))) {
            return Response::problem(403, 'Jeton de sécurité manquant ou invalide', 'csrf');
        }
        if (!in_array($s->stage, $stages, true)) {
            return Response::problem(403, 'Étape de connexion insuffisante', 'stage');
        }
        if ($recentTotp && !$s->hasRecentTotp($this->clock->now())) {
            return Response::problem(403, 'Saisissez à nouveau un code de votre appli', 'totp_required');
        }

        return $s;
    }

    public function status(Request $r): Response
    {
        return Response::json($this->view($this->sessions->current($r)));
    }

    /** @param array{body:mixed} $in */
    public function login(Request $r, array $in): Response
    {
        if (($bad = $this->anonymousCsrf($r)) !== null) {
            return $bad;
        }
        try {
            $res = $this->access->checkPassword((string) $in['body']['email'], (string) $in['body']['password']);
        } catch (DomainException $e) {
            return Errors::fromDomain($e);
        }
        [$s, $cookie] = $this->sessions->open($res['editorId'], $res['enrolled'] ? AdminSession::PASSWORD_OK : AdminSession::PRE_ENROLMENT, false, $this->sessions->current($r));

        return Response::json($this->view($s))->withHeader('Set-Cookie', $cookie);
    }

    /** Code TOTP : termine la connexion (password_ok → full) ou rafraîchit le « TOTP récent » (full). @param array{body:mixed} $in */
    public function totp(Request $r, array $in): Response
    {
        $s = $this->guard($r, [AdminSession::PASSWORD_OK, AdminSession::FULL]);
        if ($s instanceof Response) {
            return $s;
        }
        try {
            $this->access->verifyTotp($s->editorId, (string) $in['body']['code']);
        } catch (DomainException $e) {
            return Errors::fromDomain($e);
        }
        if ($s->stage === AdminSession::FULL) {
            return Response::json($this->view($this->sessions->touchTotp($s)));
        }
        [$full, $cookie] = $this->sessions->open($s->editorId, AdminSession::FULL, true, $s);

        return Response::json($this->view($full))->withHeader('Set-Cookie', $cookie);
    }

    /** @param array{body:mixed} $in */
    public function recovery(Request $r, array $in): Response
    {
        $s = $this->guard($r, [AdminSession::PASSWORD_OK]);
        if ($s instanceof Response) {
            return $s;
        }
        try {
            $this->access->useRecoveryCode($s->editorId, (string) $in['body']['code']);
        } catch (DomainException $e) {
            return Errors::fromDomain($e);
        }
        [$rec, $cookie] = $this->sessions->open($s->editorId, AdminSession::RECOVERY, false, $s);

        return Response::json($this->view($rec))->withHeader('Set-Cookie', $cookie);
    }

    public function logout(Request $r): Response
    {
        $s = $this->sessions->current($r);
        if ($s !== null && !hash_equals($s->csrf, (string) $r->header('x-csrf-token'))) {
            return Response::problem(403, 'Jeton de sécurité manquant ou invalide', 'csrf');
        }

        return Response::noContent()->withHeader('Set-Cookie', $this->sessions->close($s));
    }

    /** (Ré)enrôlement : après /install, en session « secours », ou avec un TOTP récent. */
    public function startEnrollment(Request $r): Response
    {
        $s = $this->enrollmentGuard($r);
        if ($s instanceof Response) {
            return $s;
        }

        return Response::json($this->access->startEnrollment($s->editorId));
    }

    /** @param array{body:mixed} $in */
    public function confirmEnrollment(Request $r, array $in): Response
    {
        $s = $this->enrollmentGuard($r);
        if ($s instanceof Response) {
            return $s;
        }
        try {
            $codes = $this->access->confirmEnrollment($s->editorId, (string) $in['body']['code']);
        } catch (DomainException $e) {
            return Errors::fromDomain($e);
        }
        $this->sessions->closeAllFor($s->editorId); // un nouveau secret ferme toutes les autres sessions
        [, $cookie] = $this->sessions->open($s->editorId, AdminSession::FULL, true);

        return Response::json(['codes' => $codes])->withHeader('Set-Cookie', $cookie);
    }

    public function regenerateCodes(Request $r): Response
    {
        $s = $this->guard($r, [AdminSession::FULL], true);
        if ($s instanceof Response) {
            return $s;
        }

        return Response::json(['codes' => $this->access->regenerateRecoveryCodes($s->editorId)]);
    }

    private function enrollmentGuard(Request $r): AdminSession|Response
    {
        $s = $this->guard($r, [AdminSession::PRE_ENROLMENT, AdminSession::RECOVERY, AdminSession::FULL]);
        if ($s instanceof Response || $s->stage !== AdminSession::FULL) {
            return $s;
        }

        return $s->hasRecentTotp($this->clock->now()) ? $s : Response::problem(403, 'Saisissez à nouveau un code de votre appli', 'totp_required');
    }

    /** Sans session, une écriture doit porter X-Requested-With (bloque les formulaires inter-sites). */
    private function anonymousCsrf(Request $r): ?Response
    {
        $s = $this->sessions->current($r);
        $ok = $s !== null ? hash_equals($s->csrf, (string) $r->header('x-csrf-token')) : $r->header('x-requested-with') === 'barbier';

        return $ok ? null : Response::problem(403, 'Jeton de sécurité manquant ou invalide', 'csrf');
    }

    /** @return array<string,mixed> */
    private function view(?AdminSession $s): array
    {
        if ($s === null) {
            return ['stage' => 'none'];
        }
        $editor = $this->access->editor($s->editorId);
        $view = ['stage' => $s->stage, 'email' => $editor->email, 'csrf' => $s->csrf, 'recentTotp' => $s->hasRecentTotp($this->clock->now())];
        if ($s->stage === AdminSession::FULL) {
            $view['recoveryCodesRemaining'] = $this->access->remainingRecoveryCodes($s->editorId);
        }

        return $view;
    }
}

<?php

declare(strict_types=1);

namespace Barbier\Http\Admin;

use Barbier\Adapter\Persistence\Migrator;
use Barbier\Application\Acces\AccessService;
use Barbier\Domain\Notification\SecurityNotifier;
use Barbier\Domain\Shared\DomainException;
use Barbier\Http\AdminSession;
use Barbier\Http\Request;
use Barbier\Http\Response;
use Barbier\Http\SessionManager;
use Barbier\Http\View;

/**
 * Installation sans ligne de commande (FR-042) : contrôle d'environnement → migrations → compte du gérant
 * → session « pré-enrôlement » → /admin pour activer le double facteur. Répond 404 une fois installé.
 */
final class InstallController
{
    /** @param array<string,mixed> $config @param \Closure():?\PDO $pdo @param \Closure():AccessService $access @param \Closure():SessionManager $sessions */
    public function __construct(
        private readonly array $config,
        private readonly \Closure $pdo,
        private readonly \Closure $access,
        private readonly \Closure $sessions,
        private readonly View $view,
        private readonly string $appDir,
        private readonly ?SecurityNotifier $notifier = null,
    ) {
    }

    /** Réinitialisation d'urgence (FR-043) : 404 tant que le propriétaire n'a pas déposé app/var/reset-2fa.token. */
    public function resetShow(): Response
    {
        return $this->resetToken() === null ? self::notFound() : Response::html($this->view->render('reset-2fa', ['errors' => [], 'email' => '']));
    }

    public function resetSubmit(Request $r): Response
    {
        $expected = $this->resetToken();
        if ($expected === null) {
            return self::notFound();
        }
        parse_str($r->rawBody(), $f);
        $email = is_string($f['email'] ?? null) ? $f['email'] : '';
        $given = is_string($f['token'] ?? null) ? trim($f['token']) : '';
        $fail = fn (string $msg): Response => Response::html($this->view->render('reset-2fa', ['errors' => ['_' => $msg], 'email' => $email]), 422);
        if (!hash_equals($expected, $given)) {
            $this->notifier?->notify('reset_2fa_refused', 'jeton incorrect');

            return $fail('Jeton incorrect.');
        }
        try {
            $id = ($this->access)()->emergencyReset($email, is_string($f['password'] ?? null) ? $f['password'] : '');
        } catch (DomainException $e) {
            $this->notifier?->notify('reset_2fa_refused', $e->reason);

            return $fail($e->getMessage());
        }
        @unlink($this->appDir . '/var/reset-2fa.token');
        ($this->sessions)()->closeAllFor($id);
        [, $cookie] = ($this->sessions)()->open($id, AdminSession::PRE_ENROLMENT, false);
        $this->notifier?->notify('reset_2fa_done', 'double facteur réinitialisé par jeton déposé sur le serveur');

        return (new Response(303, '', ['Location' => '/admin']))->withHeader('Set-Cookie', $cookie);
    }

    private function resetToken(): ?string
    {
        $t = @file_get_contents($this->appDir . '/var/reset-2fa.token');

        return is_string($t) && strlen(trim($t)) >= 12 ? trim($t) : null;
    }

    private static function notFound(): Response
    {
        return Response::html('<!doctype html><title>Introuvable</title><p>Page introuvable.</p>', 404);
    }

    public function show(Request $r): Response
    {
        if ($this->installed()) {
            return self::notFound();
        }

        return $this->render($r, [], '');
    }

    public function submit(Request $r): Response
    {
        if ($this->installed()) {
            return self::notFound();
        }
        parse_str($r->rawBody(), $f);
        $email = is_string($f['email'] ?? null) ? $f['email'] : '';
        $checks = $this->checks($r);
        if (EnvironmentCheck::blocking($checks)) {
            return $this->render($r, ['_' => "L'hébergement ne remplit pas les conditions."], $email);
        }
        $pdo = ($this->pdo)();
        try {
            (new Migrator($pdo, $this->appDir . '/migrations'))->up();
            $id = ($this->access)()->install(
                $email,
                is_string($f['password'] ?? null) ? $f['password'] : '',
                is_string($f['password_again'] ?? null) ? $f['password_again'] : '',
                is_string($f['install_code'] ?? null) ? $f['install_code'] : '',
                (string) ($this->config['install_code'] ?? ''),
            );
        } catch (DomainException $e) {
            return $this->render($r, [$e->field ?? '_' => $e->getMessage()], $email, 422);
        }
        [, $cookie] = ($this->sessions)()->open($id, AdminSession::PRE_ENROLMENT, false);

        return (new Response(303, '', ['Location' => '/admin']))->withHeader('Set-Cookie', $cookie);
    }

    private function installed(): bool
    {
        try {
            return ($this->access)()->isInstalled();
        } catch (\Throwable) {
            return false; // base vide (tables absentes) ou injoignable : pas encore installé
        }
    }

    /** @return list<array{label:string, ok:bool, detail:string, blocking:bool}> */
    private function checks(Request $r): array
    {
        try {
            $pdo = ($this->pdo)();
        } catch (\Throwable) {
            $pdo = null;
        }

        return EnvironmentCheck::run($this->config, $this->appDir . '/var', $pdo, $r->isHttps());
    }

    /** @param array<string,string> $errors */
    private function render(Request $r, array $errors, string $email, int $status = 200): Response
    {
        $checks = $this->checks($r);

        return Response::html($this->view->render('install', [
            'checks' => $checks, 'blocked' => EnvironmentCheck::blocking($checks), 'errors' => $errors, 'email' => $email,
        ]), $status);
    }
}

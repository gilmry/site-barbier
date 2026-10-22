<?php

declare(strict_types=1);

namespace Barbier\Adapter\Notification;

use Barbier\Domain\Notification\SecurityNotifier;

/**
 * Journal de sécurité (app/var/log/security.log, hors racine web). Toujours écrit, même quand l'envoi
 * d'e-mail (Story 4.1) est indisponible : une alerte ne dépend jamais d'un seul canal.
 */
final class LogSecurityNotifier implements SecurityNotifier
{
    public function __construct(private readonly string $logDir, private readonly ?SecurityNotifier $next = null)
    {
    }

    public function notify(string $event, string $detail): void
    {
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0750, true);
        }
        @file_put_contents($this->logDir . '/security.log', sprintf("%s %s %s\n", gmdate('c'), $event, str_replace(["\r", "\n"], ' ', $detail)), FILE_APPEND | LOCK_EX);
        $this->next?->notify($event, $detail);
    }
}

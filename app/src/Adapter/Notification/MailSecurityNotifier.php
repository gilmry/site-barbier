<?php

declare(strict_types=1);

namespace Barbier\Adapter\Notification;

use Barbier\Domain\Notification\Mail;
use Barbier\Domain\Notification\Mailer;
use Barbier\Domain\Notification\SecurityNotifier;

/** Alerte de sécurité par e-mail au salon (Story 5.2 : verrouillage du compte, réinitialisation d'urgence). */
final class MailSecurityNotifier implements SecurityNotifier
{
    private const LABELS = [
        'account_locked' => 'Compte d’administration verrouillé après plusieurs échecs de connexion',
        'reset_2fa_done' => 'Double facteur réinitialisé par jeton déposé sur le serveur',
    ];

    /** @param \Closure():string $salonEmail */
    public function __construct(private readonly Mailer $mailer, private readonly \Closure $salonEmail)
    {
    }

    public function notify(string $event, string $detail): void
    {
        if (!isset(self::LABELS[$event])) {
            return; // seuls les événements qui demandent une action du salon partent par e-mail
        }
        try {
            $this->mailer->send(new Mail(($this->salonEmail)(), 'Alerte de sécurité du site : ' . self::LABELS[$event],
                self::LABELS[$event] . ".\n\nDétail : $detail\nDate : " . gmdate('c') . " (UTC)\n\nSi ce n'est pas vous, changez votre mot de passe et vérifiez l'administration."));
        } catch (\Throwable) {
            // le journal de sécurité (LogSecurityNotifier) garde la trace même si l'e-mail échoue
        }
    }
}

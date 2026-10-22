<?php

declare(strict_types=1);

namespace Barbier\Domain\Notification;

/** Port d'envoi d'e-mails (SMTP de l'hébergeur, ADR-009). */
interface Mailer
{
    /** @throws \RuntimeException si l'envoi échoue (le message d'erreur est journalisé, jamais montré au client) */
    public function send(Mail $mail): void;
}

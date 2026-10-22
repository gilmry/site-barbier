<?php

declare(strict_types=1);

namespace Barbier\Domain\Notification;

/** Prévient le salon d'un événement de sécurité (réinitialisation d'urgence, verrouillage…). */
interface SecurityNotifier
{
    public function notify(string $event, string $detail): void;
}

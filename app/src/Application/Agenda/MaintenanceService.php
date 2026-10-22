<?php

declare(strict_types=1);

namespace Barbier\Application\Agenda;

use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Agenda\RetentionPolicy;
use Barbier\Domain\Shared\Clock;

/**
 * Purge quotidienne (FR-027) : réservations dont le rendez-vous a plus de 6 mois (cellules et journal des
 * e-mails en cascade), compteurs de débit de la veille. Idempotente ; une seule exécution par jour, déclenchée
 * par la tâche planifiée de l'hébergeur (app/bin/cron.php) ou, à défaut, par le premier passage du jour.
 */
final class MaintenanceService
{
    public function __construct(private readonly AgendaStore $store, private readonly Clock $clock, private readonly RetentionPolicy $retention, private readonly \DateTimeZone $tz = new \DateTimeZone('Europe/Brussels'))
    {
    }

    /** @return int|null réservations purgées, ou null si la purge du jour a déjà eu lieu */
    public function dailyPurge(bool $force = false): ?int
    {
        $today = $this->clock->now()->setTimezone($this->tz)->format('Y-m-d');
        if (!$force && $this->store->state('last_purge') === $today) {
            return null;
        }

        return $this->store->transactional(function () use ($today, $force): ?int {
            if (!$force && $this->store->state('last_purge') === $today) {
                return null;
            }
            $this->store->lockAllBarbers();
            $cutoff = new \DateTimeImmutable($this->retention->cutoffDate($this->clock->now(), $this->tz) . ' 00:00:00', $this->tz);
            $n = $this->store->purgeBefore($cutoff->setTimezone(new \DateTimeZone('UTC')));
            $this->store->purgeRateLimits($this->clock->now()->modify('-1 day'));
            $this->store->setState('last_purge', $today);

            return $n;
        });
    }

    public function lastPurge(): ?string
    {
        return $this->store->state('last_purge');
    }
}

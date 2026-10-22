<?php

declare(strict_types=1);

/**
 * Tâche planifiée de l'hébergement (OVH : « Tâches planifiées – Cron », langage PHP 8.4, chaque jour à 3 h) :
 * purge des réservations échues (FR-027). Sans tâche planifiée, la purge se fait au premier passage du jour.
 * Usage : php app/bin/cron.php
 */
use Barbier\Kernel;

require dirname(__DIR__) . '/src/bootstrap.php';
$app = dirname(__DIR__);
$config = require (is_file($app . '/config.php') ? $app . '/config.php' : $app . '/config.example.php');
$n = (new Kernel($config))->maintenance()->dailyPurge();
fwrite(STDOUT, $n === null ? "purge : déjà faite aujourd'hui\n" : "purge : $n réservation(s) effacée(s)\n");

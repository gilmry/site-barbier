<?php

declare(strict_types=1);

namespace Barbier\Application\Notification;

/** Port des textes traduits (e-mails, dates) : la couche Application ne lit pas les fichiers elle-même. */
interface Texts
{
    /** @param array<string,string> $params */
    public function get(string $lang, string $key, array $params = []): string;

    /** Date et heure locales lisibles (« mardi 10 novembre à 15:00 »). */
    public function when(string $lang, \DateTimeImmutable $utc): string;
}

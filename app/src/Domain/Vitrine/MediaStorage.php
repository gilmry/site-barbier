<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

/** Port : stockage des médias publiés (www/uploads/ sur l'hébergement). */
interface MediaStorage
{
    public function put(string $name, string $bytes): void;

    public function delete(string $name): void;
}

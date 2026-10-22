<?php

declare(strict_types=1);

namespace Barbier\Adapter\Media;

use Barbier\Domain\Vitrine\MediaStorage;
use RuntimeException;

/** Médias dans www/uploads/ : noms générés (uuid.ext), jamais le nom d'origine ; dossier sans exécution (.htaccess). */
final class FileMediaStorage implements MediaStorage
{
    public function __construct(private readonly string $dir)
    {
    }

    public function put(string $name, string $bytes): void
    {
        if (preg_match('/^[0-9a-f-]{36}\.(webp|jpg)$/', $name) !== 1) {
            throw new RuntimeException('nom de média invalide');
        }
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0755, true)) {
            throw new RuntimeException('dossier des médias non inscriptible');
        }
        if (@file_put_contents($this->dir . '/' . $name, $bytes, LOCK_EX) === false) {
            throw new RuntimeException('écriture du média impossible');
        }
    }

    public function delete(string $name): void
    {
        if (preg_match('/^[0-9a-f-]{36}\.(webp|jpg)$/', $name) === 1) {
            @unlink($this->dir . '/' . $name);
        }
    }
}

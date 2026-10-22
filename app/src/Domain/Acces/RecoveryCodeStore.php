<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

/** Codes de secours stockés hachés (jamais en clair). */
interface RecoveryCodeStore
{
    /** @param list<string> $hashes remplace tous les codes de l'éditeur */
    public function replace(string $editorId, array $hashes): void;

    /** Consomme le code s'il existe et n'a pas servi. */
    public function consume(string $editorId, string $hash, \DateTimeImmutable $now): bool;

    public function remaining(string $editorId): int;
}

<?php

declare(strict_types=1);

namespace Barbier\Tests\Fakes;

use Barbier\Domain\Acces\Editor;
use Barbier\Domain\Acces\EditorRepository;
use Barbier\Domain\Acces\RecoveryCodeStore;

/** Doublures en mémoire des ports d'accès (tests des cas d'usage, sans base). */
final class InMemoryAccess implements EditorRepository, RecoveryCodeStore
{
    /** @var array<string,Editor> */
    public array $editors = [];
    /** @var array<string,array<string,bool>> editorId → hash → utilisé */
    public array $codes = [];

    public function count(): int
    {
        return count($this->editors);
    }

    public function find(string $id): ?Editor
    {
        return $this->editors[$id] ?? null;
    }

    public function findByEmail(string $email): ?Editor
    {
        foreach ($this->editors as $e) {
            if ($e->email === Editor::normalizeEmail($email)) {
                return $e;
            }
        }

        return null;
    }

    public function save(Editor $editor): void
    {
        $this->editors[$editor->id] = $editor;
    }

    public function replace(string $editorId, array $hashes): void
    {
        $this->codes[$editorId] = array_fill_keys($hashes, false);
    }

    public function consume(string $editorId, string $hash, \DateTimeImmutable $now): bool
    {
        if (($this->codes[$editorId][$hash] ?? true) === true) {
            return false;
        }
        $this->codes[$editorId][$hash] = true;

        return true;
    }

    public function remaining(string $editorId): int
    {
        return count(array_filter($this->codes[$editorId] ?? [], static fn (bool $used): bool => !$used));
    }
}

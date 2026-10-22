<?php

declare(strict_types=1);

namespace Barbier\Domain\Acces;

interface EditorRepository
{
    public function count(): int;

    public function find(string $id): ?Editor;

    public function findByEmail(string $email): ?Editor;

    public function save(Editor $editor): void;
}

<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

/** Port : vérifie et ré-encode une image envoyée (FR-007). */
interface ImageProcessor
{
    /**
     * @return array{bytes:string, mime:string, extension:string, width:int, height:int}
     * @throws \Barbier\Domain\Shared\DomainException type non autorisé, image illisible ou trop grande
     */
    public function process(string $path): array;
}

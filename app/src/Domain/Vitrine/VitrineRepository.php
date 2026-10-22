<?php

declare(strict_types=1);

namespace Barbier\Domain\Vitrine;

/**
 * Port du contenu de la vitrine (lecture et écriture). Les textes sont rendus sous forme
 * field → [lang → valeur] ; l'adaptateur ne connaît ni la logique de repli ni les règles.
 */
interface VitrineRepository
{
    /** @return array<string, array{page:string, position:int, version:int, mediaId:?string, fields: array<string, array<string,string>>}> */
    public function sections(?string $page = null): array;

    /** Enregistre les champs d'une section si sa version n'a pas changé ; @return bool false si version périmée */
    public function saveSection(string $key, int $expectedVersion, array $fields, ?string $mediaId): bool;

    /** @return array{name:string, phone:string, email:string, address: array<string,string>, access: array<string,string>} */
    public function salon(): array;

    /** @param array<string,string> $address @param array<string,string> $access */
    public function saveSalon(string $name, string $phone, string $email, array $address, array $access): void;

    /** @return list<array{id:string, position:int, name: array<string,string>, services: list<array{id:string, categoryId:string, priceCents:int, durationMin:int, published:bool, position:int, name: array<string,string>, description: array<string,string>}>}> */
    public function catalog(bool $publishedOnly): array;

    /** @return list<array{id:string, firstName:string, photoMediaId:?string, active:bool, position:int, specialty: array<string,string>, bio: array<string,string>, serviceIds: list<string>}> */
    public function barbers(bool $activeOnly): array;

    /** @return array<int, array{opens:string, closes:string}> jours ouverts uniquement (ISO 1–7) */
    public function openingHours(): array;

    /** @param array<int, array{opens:string, closes:string}|null> $days */
    public function saveOpeningHours(array $days): void;

    /** @return array<string, array{path:string, width:int, height:int, mime:string}> */
    public function media(): array;

    /** @param array<string,string|null> $name */
    public function saveCategory(string $id, int $position, array $name): void;

    public function categoryExists(string $id): bool;

    public function categoryServiceCount(string $id): int;

    public function deleteCategory(string $id): void;

    /** @param array<string,string|null> $name @param array<string,string|null> $description */
    public function saveService(string $id, string $categoryId, int $priceCents, int $durationMin, bool $published, int $position, array $name, array $description): void;

    public function serviceExists(string $id): bool;

    public function deleteService(string $id): void;

    /**
     * @param array<string,string|null> $specialty @param array<string,string|null> $bio @param list<string> $serviceIds
     */
    public function saveBarber(string $id, string $firstName, ?string $photoMediaId, bool $active, int $position, array $specialty, array $bio, array $serviceIds): void;

    public function barberExists(string $id): bool;

    /** @param array<string,string|null> $alt */
    public function addMedia(string $id, string $path, string $mime, int $width, int $height, array $alt): void;

    /** @param array<string,string|null> $alt */
    public function setMediaAlt(string $id, array $alt): void;

    /** @return array<string, array<string,string>> mediaId → lang → texte alternatif */
    public function mediaAlts(): array;

    /** Nombre de sections ou barbiers qui utilisent ce média. */
    public function mediaReferences(string $id): int;

    public function deleteMedia(string $id): void;

    /** Prochaine position libre dans une liste ordonnée (category, service, barber). */
    public function nextPosition(string $table): int;
}

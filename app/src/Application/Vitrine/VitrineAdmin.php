<?php

declare(strict_types=1);

namespace Barbier\Application\Vitrine;

use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Shared\Random;
use Barbier\Domain\Vitrine\DaySchedule;
use Barbier\Domain\Vitrine\Duration;
use Barbier\Domain\Vitrine\ImageProcessor;
use Barbier\Domain\Vitrine\MediaStorage;
use Barbier\Domain\Vitrine\Money;
use Barbier\Domain\Vitrine\SectionCatalog;
use Barbier\Domain\Vitrine\TranslatedText;
use Barbier\Domain\Vitrine\VitrineRepository;

/**
 * Cas d'usage d'édition de la vitrine (FR-006 à FR-011) : chaque saisie passe par les objets du domaine
 * (TranslatedText, Money, Duration, DaySchedule, SectionCatalog) avant d'être enregistrée.
 * Les garde-fous liés aux réservations (suppression, désactivation, réduction d'horaires) sont ajoutés par
 * la Story 3.11 via le port AgendaGuard.
 */
final class VitrineAdmin
{
    public function __construct(
        private readonly VitrineRepository $repo,
        private readonly Random $random,
        private readonly ImageProcessor $images,
        private readonly MediaStorage $storage,
        private ?AgendaGuard $guard = null,
    ) {
    }

    public function withGuard(AgendaGuard $guard): self
    {
        $this->guard = $guard;

        return $this;
    }

    // --- Sections -------------------------------------------------------------------------------

    /** @return list<array<string,mixed>> */
    public function sections(): array
    {
        $out = [];
        foreach ($this->repo->sections() as $key => $section) {
            $def = SectionCatalog::all()[$key] ?? null;
            if ($def === null) {
                continue;
            }
            $fields = [];
            foreach ($def['fields'] as $name => $meta) {
                $values = $section['fields'][$name] ?? [];
                $fields[$name] = $meta + ['values' => (object) $values, 'missing' => array_values(array_diff(['fr', 'nl', 'en'], array_keys($values)))];
            }
            $out[] = ['key' => $key, 'page' => $section['page'], 'label' => $def['label'], 'version' => $section['version'], 'mediaId' => $section['mediaId'], 'fields' => $fields];
        }

        return $out;
    }

    /** @param array<string, array<string,string|null>> $fields @return array<string,mixed> */
    public function updateSection(string $key, int $version, array $fields, ?string $mediaId): array
    {
        $validated = SectionCatalog::validate($key, $fields);
        if ($mediaId !== null && !isset($this->repo->media()[$mediaId])) {
            throw new DomainException('not_found', 'Image introuvable.', 'mediaId');
        }
        $raw = array_map(static fn (TranslatedText $t): array => $t->toArray() + ['fr' => null, 'nl' => null, 'en' => null], $validated);
        if (!$this->repo->saveSection($key, $version, $raw, $mediaId)) {
            throw new DomainException('stale_version', 'Ce texte a été modifié entre-temps (autre onglet ?). Rechargez avant d’enregistrer.');
        }
        foreach ($this->sections() as $s) {
            if ($s['key'] === $key) {
                return $s;
            }
        }
        throw new DomainException('not_found', 'Section inconnue.');
    }

    // --- Coordonnées et horaires ----------------------------------------------------------------

    /** @return array<string,mixed> */
    public function salon(): array
    {
        $s = $this->repo->salon();

        return ['name' => $s['name'], 'phone' => $s['phone'], 'email' => $s['email'], 'address' => (object) $s['address'], 'access' => (object) $s['access']];
    }

    /** @param array<string,mixed> $in @return array<string,mixed> */
    public function updateSalon(array $in): array
    {
        $name = trim((string) $in['name']);
        $phone = trim((string) $in['phone']);
        $email = trim((string) $in['email']);
        if ($name === '') {
            throw new DomainException('invalid_salon', 'Le nom du salon est obligatoire.', 'name');
        }
        if (preg_match('/^\+?[0-9 .\/()-]{6,30}$/', $phone) !== 1 || strlen((string) preg_replace('/\D/', '', $phone)) < 8) {
            throw new DomainException('invalid_phone', 'Numéro de téléphone invalide.', 'phone');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('invalid_email', 'Adresse e-mail invalide.', 'email');
        }
        $address = new TranslatedText((array) $in['address'], 'address', 300);
        $access = new TranslatedText((array) $in['access'], 'access', 300, false);
        $this->repo->saveSalon($name, $phone, $email, $address->toArray() + ['fr' => null, 'nl' => null, 'en' => null], $access->toArray() + ['fr' => null, 'nl' => null, 'en' => null]);

        return $this->salon();
    }

    /** @return list<array{weekday:int, opens:?string, closes:?string}> */
    public function openingHours(): array
    {
        $open = $this->repo->openingHours();
        $out = [];
        foreach ([1, 2, 3, 4, 5, 6, 7] as $wd) {
            $out[] = ['weekday' => $wd, 'opens' => $open[$wd]['opens'] ?? null, 'closes' => $open[$wd]['closes'] ?? null];
        }

        return $out;
    }

    /** @param list<array{weekday:int, opens:?string, closes:?string}> $days @return list<array{weekday:int, opens:?string, closes:?string}> */
    public function updateOpeningHours(array $days): array
    {
        $byDay = [];
        foreach ($days as $d) {
            $schedule = new DaySchedule((int) $d['weekday'], $d['opens'], $d['closes']);
            if (isset($byDay[$schedule->weekday])) {
                throw new DomainException('invalid_hours', 'Chaque jour ne peut figurer qu’une fois.', 'days');
            }
            $byDay[$schedule->weekday] = $schedule->isClosed() ? null : ['opens' => (string) $schedule->opens, 'closes' => (string) $schedule->closes];
        }
        if (count($byDay) !== 7) {
            throw new DomainException('invalid_hours', 'Les 7 jours de la semaine sont attendus.', 'days');
        }
        $apply = fn () => $this->repo->saveOpeningHours($byDay);
        $this->guard !== null ? $this->guard->salonHoursChange($byDay, $apply) : $apply();

        return $this->openingHours();
    }

    // --- Carte ----------------------------------------------------------------------------------

    /** @return list<array<string,mixed>> */
    public function catalog(): array
    {
        $out = [];
        foreach ($this->repo->catalog(false) as $c) {
            $c['name'] = (object) $c['name'];
            $c['services'] = array_map(static fn (array $s): array => ['name' => (object) $s['name'], 'description' => (object) $s['description']] + $s, $c['services']);
            $out[] = $c;
        }

        return $out;
    }

    /** @param array<string,mixed> $in */
    public function saveCategory(?string $id, array $in): string
    {
        $name = new TranslatedText((array) $in['name'], 'name', 60);
        if ($id !== null && !$this->repo->categoryExists($id)) {
            throw new DomainException('not_found', 'Catégorie introuvable.');
        }
        $id ??= $this->random->uuid();
        $this->repo->saveCategory($id, (int) ($in['position'] ?? $this->repo->nextPosition('category')), $name->toArray() + ['fr' => null, 'nl' => null, 'en' => null]);

        return $id;
    }

    public function deleteCategory(string $id): void
    {
        if (!$this->repo->categoryExists($id)) {
            throw new DomainException('not_found', 'Catégorie introuvable.');
        }
        if ($this->repo->categoryServiceCount($id) > 0) {
            throw new DomainException('conflict', 'Retirez d’abord les prestations de cette catégorie.');
        }
        $this->repo->deleteCategory($id);
    }

    /** @param array<string,mixed> $in */
    public function saveService(?string $id, array $in): string
    {
        $name = new TranslatedText((array) $in['name'], 'name', 80);
        $description = new TranslatedText((array) $in['description'], 'description', 300, false);
        $price = new Money((int) $in['priceCents']);
        $duration = new Duration((int) $in['durationMin']);
        $categoryId = (string) $in['categoryId'];
        if (!$this->repo->categoryExists($categoryId)) {
            throw new DomainException('not_found', 'Catégorie introuvable.', 'categoryId');
        }
        if ($id !== null && !$this->repo->serviceExists($id)) {
            throw new DomainException('not_found', 'Prestation introuvable.');
        }
        $id ??= $this->random->uuid();
        $published = (bool) $in['published'];
        $apply = fn () => $this->repo->saveService($id, $categoryId, $price->cents, $duration->minutes, $published,
            (int) ($in['position'] ?? $this->repo->nextPosition('service')), $name->toArray() + ['fr' => null, 'nl' => null, 'en' => null],
            $description->toArray() + ['fr' => null, 'nl' => null, 'en' => null]);
        $this->guard !== null && !$published ? $this->guard->serviceUnpublish($id, $apply) : $apply();

        return $id;
    }

    public function deleteService(string $id): void
    {
        if (!$this->repo->serviceExists($id)) {
            throw new DomainException('not_found', 'Prestation introuvable.');
        }
        $apply = fn () => $this->repo->deleteService($id);
        $this->guard !== null ? $this->guard->serviceDelete($id, $apply) : $apply();
    }

    // --- Équipe ---------------------------------------------------------------------------------

    /** @return list<array<string,mixed>> */
    public function barbers(): array
    {
        return array_map(static fn (array $b): array => ['specialty' => (object) $b['specialty'], 'bio' => (object) $b['bio']] + $b, $this->repo->barbers(false));
    }

    /** @param array<string,mixed> $in */
    public function saveBarber(?string $id, array $in): string
    {
        $firstName = trim((string) $in['firstName']);
        if ($firstName === '' || mb_strlen($firstName) > 60) {
            throw new DomainException('invalid_barber', 'Le prénom est obligatoire (60 caractères au plus).', 'firstName');
        }
        $specialty = new TranslatedText((array) $in['specialty'], 'specialty', 60);
        $bio = new TranslatedText((array) $in['bio'], 'bio', 400, false);
        $photo = $in['photoMediaId'] ?? null;
        if ($photo !== null && !isset($this->repo->media()[$photo])) {
            throw new DomainException('not_found', 'Photo introuvable.', 'photoMediaId');
        }
        $services = array_values(array_unique(array_map('strval', (array) $in['serviceIds'])));
        foreach ($services as $sid) {
            if (!$this->repo->serviceExists($sid)) {
                throw new DomainException('not_found', 'Prestation introuvable.', 'serviceIds');
            }
        }
        if ($id !== null && !$this->repo->barberExists($id)) {
            throw new DomainException('not_found', 'Barbier introuvable.');
        }
        $id ??= $this->random->uuid();
        $active = (bool) $in['active'];
        $apply = fn () => $this->repo->saveBarber($id, $firstName, $photo, $active, (int) ($in['position'] ?? $this->repo->nextPosition('barber')),
            $specialty->toArray() + ['fr' => null, 'nl' => null, 'en' => null], $bio->toArray() + ['fr' => null, 'nl' => null, 'en' => null], $services);
        $this->guard !== null ? $this->guard->barberChange($id, $active, $apply) : $apply();

        return $id;
    }

    // --- Images ---------------------------------------------------------------------------------

    /** @return list<array<string,mixed>> */
    public function media(): array
    {
        $alts = $this->repo->mediaAlts();
        $out = [];
        foreach ($this->repo->media() as $id => $m) {
            $out[] = self::mediaView($id, $m, $alts[$id] ?? []);
        }

        return $out;
    }

    /** @return array<string,mixed> */
    public function uploadMedia(string $tmpPath): array
    {
        $img = $this->images->process($tmpPath);
        $id = $this->random->uuid();
        $name = $id . '.' . $img['extension'];
        $this->storage->put($name, $img['bytes']);
        try {
            $this->repo->addMedia($id, $name, $img['mime'], $img['width'], $img['height'], ['fr' => null, 'nl' => null, 'en' => null]);
        } catch (\Throwable $e) {
            $this->storage->delete($name);
            throw $e;
        }

        return self::mediaView($id, ['path' => $name, 'width' => $img['width'], 'height' => $img['height'], 'mime' => $img['mime']], []);
    }

    /** @param array<string,string|null> $alt @return array<string,mixed> */
    public function updateMediaAlt(string $id, array $alt): array
    {
        $media = $this->repo->media();
        if (!isset($media[$id])) {
            throw new DomainException('not_found', 'Image introuvable.');
        }
        $text = new TranslatedText($alt, 'alt', 200, false);
        $this->repo->setMediaAlt($id, $text->toArray() + ['fr' => null, 'nl' => null, 'en' => null]);

        return self::mediaView($id, $media[$id], $text->toArray());
    }

    public function deleteMedia(string $id): void
    {
        $media = $this->repo->media();
        if (!isset($media[$id])) {
            throw new DomainException('not_found', 'Image introuvable.');
        }
        if ($this->repo->mediaReferences($id) > 0) {
            throw new DomainException('conflict', 'Cette image est encore utilisée (section ou barbier).');
        }
        $this->repo->deleteMedia($id);
        $this->storage->delete($media[$id]['path']);
    }

    /** @param array{path:string, width:int, height:int, mime:string} $m @param array<string,string> $alt @return array<string,mixed> */
    private static function mediaView(string $id, array $m, array $alt): array
    {
        return ['id' => $id, 'src' => '/uploads/' . $m['path'], 'width' => $m['width'], 'height' => $m['height'], 'alt' => (object) $alt,
            'warning' => $m['width'] < 200 ? 'Image très petite : elle risque d’être floue.' : null];
    }
}

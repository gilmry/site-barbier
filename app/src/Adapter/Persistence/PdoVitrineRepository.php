<?php

declare(strict_types=1);

namespace Barbier\Adapter\Persistence;

use Barbier\Domain\Vitrine\VitrineRepository;
use PDO;

final class PdoVitrineRepository implements VitrineRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, array<string, array<string,string>>> owner_id → field → lang → value */
    private function translations(string $ownerType): array
    {
        $st = $this->pdo->prepare('SELECT owner_id, field, lang, value FROM translation WHERE owner_type = ?');
        $st->execute([$ownerType]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[(string) $r['owner_id']][(string) $r['field']][(string) $r['lang']] = (string) $r['value'];
        }

        return $out;
    }

    /** @param array<string, array<string,string|null>> $fields */
    private function writeTranslations(string $ownerType, string $ownerId, array $fields): void
    {
        $del = $this->pdo->prepare('DELETE FROM translation WHERE owner_type = ? AND owner_id = ? AND field = ?');
        $ins = $this->pdo->prepare('INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES (?, ?, ?, ?, ?)');
        foreach ($fields as $field => $values) {
            $del->execute([$ownerType, $ownerId, $field]);
            foreach ($values as $lang => $value) {
                if ($value !== null && trim($value) !== '') {
                    $ins->execute([$ownerType, $ownerId, $field, $lang, $value]);
                }
            }
        }
    }

    public function sections(?string $page = null): array
    {
        $tr = $this->translations('section');
        $st = $page === null
            ? $this->pdo->query('SELECT * FROM section ORDER BY page, position')
            : $this->pdo->prepare('SELECT * FROM section WHERE page = ? ORDER BY position');
        if ($page !== null) {
            $st->execute([$page]);
        }
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $key = (string) $r['section_key'];
            $out[$key] = ['page' => (string) $r['page'], 'position' => (int) $r['position'], 'version' => (int) $r['version'],
                'mediaId' => $r['media_id'] === null ? null : (string) $r['media_id'], 'fields' => $tr[$key] ?? []];
        }

        return $out;
    }

    public function saveSection(string $key, int $expectedVersion, array $fields, ?string $mediaId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('UPDATE section SET version = version + 1, media_id = ? WHERE section_key = ? AND version = ?');
            $st->execute([$mediaId, $key, $expectedVersion]);
            if ($st->rowCount() !== 1) {
                $this->pdo->rollBack();

                return false;
            }
            $this->writeTranslations('section', $key, $fields);
            $this->pdo->commit();

            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function salon(): array
    {
        $r = $this->pdo->query('SELECT name, phone, email FROM salon WHERE id = 1')->fetch() ?: ['name' => '', 'phone' => '', 'email' => ''];
        $tr = $this->translations('salon')['1'] ?? [];

        return ['name' => (string) $r['name'], 'phone' => (string) $r['phone'], 'email' => (string) $r['email'],
            'address' => $tr['address'] ?? [], 'access' => $tr['access'] ?? []];
    }

    public function saveSalon(string $name, string $phone, string $email, array $address, array $access): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO salon (id, name, phone, email) VALUES (1, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), email = VALUES(email)')
                ->execute([$name, $phone, $email]);
            $this->writeTranslations('salon', '1', ['address' => $address, 'access' => $access]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function catalog(bool $publishedOnly): array
    {
        $cats = $this->translations('category');
        $svcTr = $this->translations('service');
        $services = [];
        foreach ($this->pdo->query($publishedOnly ? 'SELECT * FROM service WHERE published = 1 ORDER BY position, id' : 'SELECT * FROM service ORDER BY position, id')->fetchAll() as $s) {
            $id = (string) $s['id'];
            $services[(string) $s['category_id']][] = ['id' => $id, 'categoryId' => (string) $s['category_id'], 'priceCents' => (int) $s['price_cents'],
                'durationMin' => (int) $s['duration_min'], 'published' => (bool) $s['published'], 'position' => (int) $s['position'],
                'name' => $svcTr[$id]['name'] ?? [], 'description' => $svcTr[$id]['description'] ?? []];
        }
        $out = [];
        foreach ($this->pdo->query('SELECT * FROM category ORDER BY position, id')->fetchAll() as $c) {
            $id = (string) $c['id'];
            $out[] = ['id' => $id, 'position' => (int) $c['position'], 'name' => $cats[$id]['name'] ?? [], 'services' => $services[$id] ?? []];
        }

        return $out;
    }

    public function barbers(bool $activeOnly): array
    {
        $tr = $this->translations('barber');
        $links = [];
        foreach ($this->pdo->query('SELECT barber_id, service_id FROM barber_service')->fetchAll() as $l) {
            $links[(string) $l['barber_id']][] = (string) $l['service_id'];
        }
        $out = [];
        foreach ($this->pdo->query($activeOnly ? 'SELECT * FROM barber WHERE active = 1 ORDER BY position, id' : 'SELECT * FROM barber ORDER BY position, id')->fetchAll() as $b) {
            $id = (string) $b['id'];
            $out[] = ['id' => $id, 'firstName' => (string) $b['first_name'], 'photoMediaId' => $b['photo_media_id'] === null ? null : (string) $b['photo_media_id'],
                'active' => (bool) $b['active'], 'position' => (int) $b['position'], 'specialty' => $tr[$id]['specialty'] ?? [], 'bio' => $tr[$id]['bio'] ?? [],
                'serviceIds' => $links[$id] ?? []];
        }

        return $out;
    }

    public function openingHours(): array
    {
        $out = [];
        foreach ($this->pdo->query('SELECT weekday, opens, closes FROM opening_hours ORDER BY weekday')->fetchAll() as $r) {
            $out[(int) $r['weekday']] = ['opens' => substr((string) $r['opens'], 0, 5), 'closes' => substr((string) $r['closes'], 0, 5)];
        }

        return $out;
    }

    public function saveOpeningHours(array $days): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('DELETE FROM opening_hours');
            $ins = $this->pdo->prepare('INSERT INTO opening_hours (weekday, opens, closes) VALUES (?, ?, ?)');
            foreach ($days as $weekday => $d) {
                if ($d !== null) {
                    $ins->execute([$weekday, $d['opens'] === '24:00' ? '23:59:59' : $d['opens'] . ':00', $d['closes'] === '24:00' ? '23:59:59' : $d['closes'] . ':00']);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function media(): array
    {
        $out = [];
        foreach ($this->pdo->query('SELECT * FROM media')->fetchAll() as $m) {
            $out[(string) $m['id']] = ['path' => (string) $m['path'], 'width' => (int) $m['width'], 'height' => (int) $m['height'], 'mime' => (string) $m['mime']];
        }

        return $out;
    }

    public function saveCategory(string $id, int $position, array $name): void
    {
        $this->tx(function () use ($id, $position, $name): void {
            $this->pdo->prepare('INSERT INTO category (id, position) VALUES (?, ?) ON DUPLICATE KEY UPDATE position = VALUES(position)')->execute([$id, $position]);
            $this->writeTranslations('category', $id, ['name' => $name]);
        });
    }

    public function categoryExists(string $id): bool
    {
        return $this->exists('category', $id);
    }

    public function categoryServiceCount(string $id): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM service WHERE category_id = ?');
        $st->execute([$id]);

        return (int) $st->fetchColumn();
    }

    public function deleteCategory(string $id): void
    {
        $this->tx(function () use ($id): void {
            $this->pdo->prepare("DELETE FROM translation WHERE owner_type = 'category' AND owner_id = ?")->execute([$id]);
            $this->pdo->prepare('DELETE FROM category WHERE id = ?')->execute([$id]);
        });
    }

    public function saveService(string $id, string $categoryId, int $priceCents, int $durationMin, bool $published, int $position, array $name, array $description): void
    {
        $this->tx(function () use ($id, $categoryId, $priceCents, $durationMin, $published, $position, $name, $description): void {
            $this->pdo->prepare('INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), price_cents = VALUES(price_cents), duration_min = VALUES(duration_min),
                published = VALUES(published), position = VALUES(position)')
                ->execute([$id, $categoryId, $priceCents, $durationMin, $published ? 1 : 0, $position]);
            $this->writeTranslations('service', $id, ['name' => $name, 'description' => $description]);
        });
    }

    public function serviceExists(string $id): bool
    {
        return $this->exists('service', $id);
    }

    public function deleteService(string $id): void
    {
        $this->tx(function () use ($id): void {
            $this->pdo->prepare("DELETE FROM translation WHERE owner_type = 'service' AND owner_id = ?")->execute([$id]);
            $this->pdo->prepare('DELETE FROM service WHERE id = ?')->execute([$id]);
        });
    }

    public function saveBarber(string $id, string $firstName, ?string $photoMediaId, bool $active, int $position, array $specialty, array $bio, array $serviceIds): void
    {
        $this->tx(function () use ($id, $firstName, $photoMediaId, $active, $position, $specialty, $bio, $serviceIds): void {
            $this->pdo->prepare('INSERT INTO barber (id, first_name, photo_media_id, active, position) VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), photo_media_id = VALUES(photo_media_id), active = VALUES(active), position = VALUES(position)')
                ->execute([$id, $firstName, $photoMediaId, $active ? 1 : 0, $position]);
            $this->writeTranslations('barber', $id, ['specialty' => $specialty, 'bio' => $bio]);
            $this->pdo->prepare('DELETE FROM barber_service WHERE barber_id = ?')->execute([$id]);
            $ins = $this->pdo->prepare('INSERT INTO barber_service (barber_id, service_id) VALUES (?, ?)');
            foreach ($serviceIds as $sid) {
                $ins->execute([$id, $sid]);
            }
        });
    }

    public function barberExists(string $id): bool
    {
        return $this->exists('barber', $id);
    }

    public function addMedia(string $id, string $path, string $mime, int $width, int $height, array $alt): void
    {
        $this->tx(function () use ($id, $path, $mime, $width, $height, $alt): void {
            $this->pdo->prepare('INSERT INTO media (id, path, mime, width, height, created_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())')
                ->execute([$id, $path, $mime, $width, $height]);
            $this->writeTranslations('media', $id, ['alt' => $alt]);
        });
    }

    public function setMediaAlt(string $id, array $alt): void
    {
        $this->writeTranslations('media', $id, ['alt' => $alt]);
    }

    public function mediaAlts(): array
    {
        $out = [];
        foreach ($this->translations('media') as $id => $fields) {
            $out[$id] = $fields['alt'] ?? [];
        }

        return $out;
    }

    public function mediaReferences(string $id): int
    {
        $st = $this->pdo->prepare('SELECT (SELECT COUNT(*) FROM section WHERE media_id = ?) + (SELECT COUNT(*) FROM barber WHERE photo_media_id = ?)');
        $st->execute([$id, $id]);

        return (int) $st->fetchColumn();
    }

    public function deleteMedia(string $id): void
    {
        $this->tx(function () use ($id): void {
            $this->pdo->prepare("DELETE FROM translation WHERE owner_type = 'media' AND owner_id = ?")->execute([$id]);
            $this->pdo->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
        });
    }

    public function nextPosition(string $table): int
    {
        return (int) $this->pdo->query(match ($table) {
            'category' => 'SELECT COALESCE(MAX(position), 0) + 1 FROM category',
            'service' => 'SELECT COALESCE(MAX(position), 0) + 1 FROM service',
            'barber' => 'SELECT COALESCE(MAX(position), 0) + 1 FROM barber',
        })->fetchColumn();
    }

    private function exists(string $table, string $id): bool
    {
        $st = $this->pdo->prepare(match ($table) {
            'category' => 'SELECT COUNT(*) FROM category WHERE id = ?',
            'service' => 'SELECT COUNT(*) FROM service WHERE id = ?',
            'barber' => 'SELECT COUNT(*) FROM barber WHERE id = ?',
        });
        $st->execute([$id]);

        return (int) $st->fetchColumn() > 0;
    }

    private function tx(callable $fn): void
    {
        $own = !$this->pdo->inTransaction();
        if ($own) {
            $this->pdo->beginTransaction();
        }
        try {
            $fn();
            if ($own) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($own) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}

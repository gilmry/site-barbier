<?php

declare(strict_types=1);

namespace Barbier\Http\Admin;

use Barbier\Application\Vitrine\VitrineAdmin;
use Barbier\Domain\Shared\DomainException;
use Barbier\Http\AdminSession;
use Barbier\Http\Errors;
use Barbier\Http\Request;
use Barbier\Http\Response;

/** API d'édition de la vitrine (FR-006 à FR-011). Garde : session complète + CSRF (AccessController::guard). */
final class VitrineController
{
    public function __construct(private readonly VitrineAdmin $admin, private readonly AccessController $access)
    {
    }

    /** Exécute $fn derrière la garde d'accès ; traduit les erreurs du domaine. */
    public function run(Request $r, callable $fn): Response
    {
        $s = $this->access->guard($r, [AdminSession::FULL]);
        if ($s instanceof Response) {
            return $s;
        }
        try {
            return $fn();
        } catch (DomainException $e) {
            return Errors::fromDomain($e);
        }
    }

    /** @param array{path:array<string,string>, body:mixed} $in */
    public function route(string $action, Request $r, array $in): Response
    {
        $id = $in['path']['id'] ?? null;
        $b = is_array($in['body']) ? $in['body'] : [];

        return $this->run($r, fn (): Response => match ($action) {
            'sections' => Response::json(['sections' => $this->admin->sections()]),
            'section' => Response::json($this->admin->updateSection((string) $in['path']['key'], (int) $b['version'], (array) $b['fields'], $b['mediaId'] ?? null)),
            'salon' => Response::json($this->admin->salon()),
            'salon.update' => Response::json($this->admin->updateSalon($b)),
            'hours' => Response::json(['days' => $this->admin->openingHours()]),
            'hours.update' => Response::json(['days' => $this->admin->updateOpeningHours((array) $b['days'])]),
            'catalog' => Response::json(['categories' => $this->admin->catalog()]),
            'category.create' => Response::json(['id' => $this->admin->saveCategory(null, $b)], 201),
            'category.update' => $this->done(fn () => $this->admin->saveCategory((string) $id, $b)),
            'category.delete' => $this->done(fn () => $this->admin->deleteCategory((string) $id)),
            'service.create' => Response::json(['id' => $this->admin->saveService(null, $b)], 201),
            'service.update' => $this->done(fn () => $this->admin->saveService((string) $id, $b)),
            'service.delete' => $this->done(fn () => $this->admin->deleteService((string) $id)),
            'barbers' => Response::json(['barbers' => $this->admin->barbers()]),
            'barber.create' => Response::json(['id' => $this->admin->saveBarber(null, $b)], 201),
            'barber.update' => $this->done(fn () => $this->admin->saveBarber((string) $id, $b)),
            'media' => Response::json(['media' => $this->admin->media()]),
            'media.upload' => $this->upload($r),
            'media.update' => Response::json($this->admin->updateMediaAlt((string) $id, (array) $b['alt'])),
            'media.delete' => $this->done(fn () => $this->admin->deleteMedia((string) $id)),
        });
    }

    private function done(callable $fn): Response
    {
        $fn();

        return Response::noContent();
    }

    private function upload(Request $r): Response
    {
        $file = $r->files()['file'] ?? null;
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $tooBig = in_array($file['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);

            return Response::problem(422, $tooBig ? 'Image trop lourde (8 Mo au plus).' : 'Envoi du fichier incomplet.', 'upload', [['field' => 'file', 'message' => 'fichier refusé']]);
        }
        if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
            return Response::problem(422, 'Envoi du fichier invalide.', 'upload');
        }

        return Response::json($this->admin->uploadMedia($file['tmp_name']), 201);
    }
}

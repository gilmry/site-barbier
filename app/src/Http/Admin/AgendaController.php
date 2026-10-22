<?php

declare(strict_types=1);

namespace Barbier\Http\Admin;

use Barbier\Application\Agenda\AgendaAdmin;
use Barbier\Application\Agenda\PrivacyService;
use Barbier\Domain\Notification\MailLog;
use Barbier\Domain\Shared\DomainException;
use Barbier\Http\AdminSession;
use Barbier\Http\Errors;
use Barbier\Http\Request;
use Barbier\Http\Response;

/** API d'administration de l'agenda (FR-020, FR-021, FR-026, FR-032) et des droits RGPD (FR-012). */
final class AgendaController
{
    public function __construct(
        private readonly AgendaAdmin $agenda,
        private readonly PrivacyService $privacy,
        private readonly MailLog $mailLog,
        private readonly AccessController $access,
    ) {
    }

    /** @param array{path:array<string,string>, query:array<string,string>, body:mixed} $in */
    public function route(string $action, Request $r, array $in): Response
    {
        $s = $this->access->guard($r, [AdminSession::FULL]);
        if ($s instanceof Response) {
            return $s;
        }
        $id = $in['path']['id'] ?? '';
        $q = $in['query'];
        $b = is_array($in['body']) ? $in['body'] : [];
        try {
            return match ($action) {
                'availability' => Response::json(['ranges' => $this->agenda->availability($id)]),
                'availability.set' => Response::json(['ranges' => $this->agenda->setAvailability($id, (array) $b['ranges'])]),
                'absences' => Response::json(['absences' => $this->agenda->absences()]),
                'absence.add' => Response::json(['id' => $this->agenda->addAbsence($b['barberId'] ?? null, (string) $b['startsAt'], (string) $b['endsAt'])], 201),
                'absence.delete' => $this->done(fn () => $this->agenda->deleteAbsence($id)),
                'bookings' => Response::json(['bookings' => $this->agenda->bookings($q['date'], $q['barber'] ?? null, $q['state'] ?? null)]),
                'booking.cancel' => $this->done(fn () => $this->agenda->cancel($id, isset($b['message']) ? (string) $b['message'] : null)),
                'mails' => Response::json(['entries' => $this->agenda->mailLog(), 'failed' => $this->mailLog->failedCount()]),
                'mails.retry' => Response::json(['results' => array_map(static fn ($k, $v) => ['id' => $k, 'state' => $v], array_keys($res = $this->agenda->retryMails((array) $b['ids'])), $res)]),
                'privacy.search' => Response::json(['bookings' => array_map(static fn (array $x): array => ['id' => $x['id'], 'barberName' => $x['barberName'], 'startsAt' => $x['startsAt'],
                    'state' => $x['state'], 'clientName' => $x['clientName'], 'clientPhone' => $x['clientPhone'], 'clientEmail' => $x['clientEmail']], $this->privacy->search((string) $b['email']))]),
                'privacy.export' => Response::json($this->privacy->export((string) $b['email']))->withHeader('Content-Disposition', 'attachment; filename="donnees-client.json"'),
                'privacy.erase' => Response::json($this->privacy->erase((string) $b['email'], (bool) $b['confirmWithoutEmail'])),
            };
        } catch (DomainException $e) {
            return Errors::fromDomain($e);
        }
    }

    private function done(callable $fn): Response
    {
        $fn();

        return Response::noContent();
    }
}

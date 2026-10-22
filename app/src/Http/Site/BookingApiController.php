<?php

declare(strict_types=1);

namespace Barbier\Http\Site;

use Barbier\Application\Agenda\BookingService;
use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Vitrine\Lang;
use Barbier\Http\Errors;
use Barbier\Http\Request;
use Barbier\Http\Response;

/** API publique de réservation (FR-022 à FR-025) consommée par l'îlot « booking ». */
final class BookingApiController
{
    public function __construct(private readonly BookingService $booking)
    {
    }

    /** @param array{query:array<string,string>, body:mixed} $in */
    public function route(string $action, Request $r, array $in): Response
    {
        $q = $in['query'];
        $b = is_array($in['body']) ? $in['body'] : [];
        try {
            return match ($action) {
                'services' => Response::json(['services' => $this->booking->services(Lang::from($q['lang']))]),
                'barbers' => Response::json(['barbers' => $this->booking->barbers($q['service'], Lang::from($q['lang']))]),
                'days' => Response::json(['days' => $this->booking->days($q['service'], $q['barber'], $q['from'], $q['to'])]),
                'slots' => Response::json(['slots' => $this->booking->slots($q['service'], $q['barber'], $q['date'])]),
                'book' => Response::json($this->booking->book($b, Lang::from((string) $b['lang']), $r->clientIp()), 201),
                'cancel' => Response::json($this->booking->cancelByToken((string) $b['token'])),
            };
        } catch (DomainException $e) {
            return Errors::fromDomain($e);
        }
    }
}

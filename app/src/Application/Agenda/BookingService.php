<?php

declare(strict_types=1);

namespace Barbier\Application\Agenda;

use Barbier\Application\Notification\NotificationService;
use Barbier\Domain\Acces\Crypto;
use Barbier\Domain\Agenda\AgendaStore;
use Barbier\Domain\Agenda\Booking;
use Barbier\Domain\Agenda\ClientContact;
use Barbier\Domain\Agenda\NoPreferenceAssigner;
use Barbier\Domain\Agenda\SlotCalculator;
use Barbier\Domain\Agenda\SlotTaken;
use Barbier\Domain\Agenda\TimeRange;
use Barbier\Domain\Shared\Clock;
use Barbier\Domain\Shared\DomainException;
use Barbier\Domain\Shared\Random;
use Barbier\Domain\Vitrine\Lang;
use Barbier\Domain\Vitrine\Money;
use Barbier\Domain\Vitrine\TranslatedText;
use Barbier\Domain\Vitrine\VitrineRepository;

/**
 * Réservation publique (FR-022 à FR-025) : ce qui est réservable, les créneaux libres, réserver (sous verrou,
 * filet des cellules en base), annuler par le lien reçu. Aucune donnée d'un autre client n'est exposée.
 */
final class BookingService
{
    public const RATE_LIMIT = 5;          // réservations réussies par heure et par adresse IP (hachée)
    public const ATTEMPT_LIMIT = 30;      // tentatives par heure (robots qui essaient les créneaux)
    public const MAX_RANGE_DAYS = 62;

    public function __construct(
        private readonly AgendaStore $store,
        private readonly VitrineRepository $vitrine,
        private readonly SlotCalculator $calculator,
        private readonly Clock $clock,
        private readonly Random $random,
        private readonly Crypto $crypto,
        private readonly NotificationService $notifications,
    ) {
    }

    /** Prestations publiées réalisées par au moins un barbier actif. @return list<array<string,mixed>> */
    public function services(Lang $lang): array
    {
        $done = [];
        foreach ($this->vitrine->barbers(true) as $b) {
            foreach ($b['serviceIds'] as $sid) {
                $done[$sid] = true;
            }
        }
        $out = [];
        foreach ($this->vitrine->catalog(true) as $c) {
            foreach ($c['services'] as $s) {
                if (isset($done[$s['id']])) {
                    $out[] = ['id' => $s['id'], 'category' => self::t($c['name'], $lang), 'name' => self::t($s['name'], $lang), 'description' => self::t($s['description'], $lang),
                        'priceCents' => $s['priceCents'], 'price' => (new Money($s['priceCents']))->format($lang), 'durationMin' => $s['durationMin']];
                }
            }
        }

        return $out;
    }

    /** Barbiers actifs qui réalisent la prestation. @return list<array<string,mixed>> */
    public function barbers(string $serviceId, Lang $lang): array
    {
        $this->service($serviceId);
        $media = $this->vitrine->media();
        $out = [];
        foreach ($this->vitrine->barbers(true) as $b) {
            if (in_array($serviceId, $b['serviceIds'], true)) {
                $photo = $b['photoMediaId'] !== null ? ($media[$b['photoMediaId']] ?? null) : null;
                $out[] = ['id' => $b['id'], 'firstName' => $b['firstName'], 'specialty' => self::t($b['specialty'], $lang),
                    'photo' => $photo === null ? null : '/uploads/' . $photo['path']];
            }
        }

        return $out;
    }

    /** Jours (locaux) qui ont au moins un créneau. @return list<string> */
    public function days(string $serviceId, string $barber, string $from, string $to): array
    {
        $service = $this->service($serviceId);
        $start = self::date($from, 'from');
        $end = self::date($to, 'to');
        if ($end < $start || $start->diff($end)->days > self::MAX_RANGE_DAYS) {
            throw new DomainException('invalid_range', 'Période invalide (62 jours au plus).', 'query.to');
        }
        $barbers = $this->candidates($serviceId, $barber);
        $out = [];
        for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
            foreach ($barbers as $b) {
                if ($this->freeFor($b['id'], $d->format('Y-m-d'), $service['durationMin']) !== []) {
                    $out[] = $d->format('Y-m-d');
                    break;
                }
            }
        }

        return $out;
    }

    /** Créneaux libres d'un jour ; « any » = union des barbiers qui réalisent la prestation. @return list<array{start:string, time:string}> */
    public function slots(string $serviceId, string $barber, string $date): array
    {
        $service = $this->service($serviceId);
        self::date($date, 'date');
        $all = [];
        foreach ($this->candidates($serviceId, $barber) as $b) {
            foreach ($this->freeFor($b['id'], $date, $service['durationMin']) as $s) {
                $all[$s->getTimestamp()] = $s;
            }
        }
        ksort($all);
        $tz = $this->calculator->policy()->tz();

        return array_values(array_map(static fn (\DateTimeImmutable $s): array => ['start' => $s->format('Y-m-d\TH:i:s\Z'), 'time' => $s->setTimezone($tz)->format('H:i')], $all));
    }

    /**
     * Réserve (FR-023, FR-024). Sous verrou des barbiers concernés, le créneau est recalculé avec l'état réel ;
     * les cellules de 5 min en base garantissent qu'aucune course ne passe. @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public function book(array $in, Lang $lang, string $clientKey): array
    {
        $now = $this->clock->now();
        if (trim((string) ($in['website'] ?? '')) !== '') {
            throw new DomainException('rejected', 'Réservation refusée.'); // champ piège rempli : robot
        }
        $window = $now->setTime((int) $now->format('H'), 0);
        $ipHash = $this->crypto->tokenHash('ip:' . $clientKey);
        if ($this->store->hit('booking_try', $ipHash, $window) > self::ATTEMPT_LIMIT || $this->store->hits('booking', $ipHash, $window) >= self::RATE_LIMIT) {
            throw new DomainException('rate_limited', 'Trop de réservations depuis cette connexion. Réessayez plus tard ou appelez le salon.');
        }
        $client = new ClientContact((string) $in['name'], (string) $in['phone'], (string) $in['email']);
        $serviceId = (string) $in['serviceId'];
        $barberChoice = (string) $in['barberId'];
        $start = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', (string) $in['start'], new \DateTimeZone('UTC'));
        if ($start === false) {
            throw new DomainException('invalid_start', 'Créneau invalide.', 'start');
        }
        $date = $start->setTimezone($this->calculator->policy()->tz())->format('Y-m-d');
        $token = bin2hex($this->random->bytes(32));

        $booking = $this->store->transactional(function () use ($serviceId, $barberChoice, $start, $date, $client, $lang, $token): Booking {
            $candidates = $this->candidates($serviceId, $barberChoice);
            $this->store->lockBarbers(array_column($candidates, 'id'));
            // Relecture SOUS le verrou : prestation toujours publiée, barbiers toujours actifs (ADR-006).
            $service = $this->service($serviceId);
            $candidates = $this->candidates($serviceId, $barberChoice);
            $free = [];
            foreach ($candidates as $b) {
                foreach ($this->freeFor($b['id'], $date, $service['durationMin']) as $s) {
                    if ($s == $start) {
                        $free[] = $b;
                    }
                }
            }
            $tz = $this->calculator->policy()->tz();
            $dayStart = (new \DateTimeImmutable($date . ' 00:00:00', $tz))->setTimezone(new \DateTimeZone('UTC'));
            $barberId = NoPreferenceAssigner::choose($free, $this->store->countConfirmedByBarber(new TimeRange($dayStart, $dayStart->modify('+1 day'))));
            if ($barberId === null) {
                throw new SlotTaken();
            }
            $range = new TimeRange($start, $start->modify('+' . $service['durationMin'] . ' minutes'));
            $booking = new Booking($this->random->uuid(), $barberId, $serviceId, $range, $service['priceCents'], $service['durationMin'], $lang->value, $client);
            $this->store->insertBooking($booking, $this->crypto->tokenHash($token), $this->calculator->policy()->cells($range));
            $this->store->recordStat($start->setTimezone($tz)->format('Y-m'), 1, 0);

            return $booking;
        });
        $this->store->hit('booking', $ipHash, $window);
        $this->notifications->bookingConfirmed($booking, $token);

        return $this->summary($booking, $lang);
    }

    /** Récapitulatif d'une réservation à partir de son lien d'annulation (page de confirmation). @return array<string,mixed>|null */
    public function byToken(string $token): ?array
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return null;
        }
        $b = $this->store->findByTokenHash($this->crypto->tokenHash($token));

        return $b === null ? null : $this->summary($b, Lang::tryFrom($b->lang) ?? Lang::FR) + ['state' => $b->state, 'lang' => $b->lang];
    }

    /** Annulation par le client (FR-025) : POST uniquement, jusqu'à 2 h avant. */
    public function cancelByToken(string $token): array
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            throw new DomainException('not_found', 'Lien d’annulation inconnu ou expiré.');
        }
        $hash = $this->crypto->tokenHash($token);
        $found = $this->store->findByTokenHash($hash) ?? throw new DomainException('not_found', 'Lien d’annulation inconnu ou expiré.');
        $cancelled = $this->store->transactional(function () use ($found, $hash): Booking {
            $this->store->lockBarbers([$found->barberId]);
            $b = $this->store->findByTokenHash($hash) ?? throw new DomainException('not_found', 'Lien d’annulation inconnu ou expiré.');
            $c = $b->cancelByClient($this->clock->now(), $this->calculator->policy());
            $this->store->markCancelled($c);
            $this->store->recordStat($b->range->start->setTimezone($this->calculator->policy()->tz())->format('Y-m'), 0, 1);

            return $c;
        });
        $this->notifications->bookingCancelled($cancelled);

        return $this->summary($cancelled, Lang::tryFrom($cancelled->lang) ?? Lang::FR) + ['state' => $cancelled->state];
    }

    /** @return list<\DateTimeImmutable> */
    private function freeFor(string $barberId, string $date, int $duration): array
    {
        $tz = $this->calculator->policy()->tz();
        $weekday = (int) (new \DateTimeImmutable($date . ' 12:00:00', $tz))->format('N');
        $salon = $this->vitrine->openingHours()[$weekday] ?? null;
        $availability = $this->store->availability($barberId);
        $dayStart = (new \DateTimeImmutable($date . ' 00:00:00', $tz))->setTimezone(new \DateTimeZone('UTC'));
        $busy = $this->store->busy($barberId, new TimeRange($dayStart->modify('-1 day'), $dayStart->modify('+2 days')));

        return $this->calculator->freeSlots($date, $duration, $salon, $availability === [] ? null : ($availability[$weekday] ?? []), $busy, $this->clock->now());
    }

    /** @return list<array{id:string, position:int}> */
    private function candidates(string $serviceId, string $barber): array
    {
        $out = [];
        foreach ($this->vitrine->barbers(true) as $b) {
            if (in_array($serviceId, $b['serviceIds'], true) && ($barber === 'any' || $barber === $b['id'])) {
                $out[] = ['id' => $b['id'], 'position' => $b['position']];
            }
        }
        if ($out === []) {
            throw new DomainException('not_found', 'Barbier indisponible pour cette prestation.', 'barberId');
        }

        return $out;
    }

    /** @return array{id:string, priceCents:int, durationMin:int, name:array<string,string>} */
    private function service(string $serviceId): array
    {
        foreach ($this->vitrine->catalog(true) as $c) {
            foreach ($c['services'] as $s) {
                if ($s['id'] === $serviceId) {
                    return $s;
                }
            }
        }
        throw new DomainException('not_found', 'Prestation introuvable.', 'serviceId');
    }

    /** @return array<string,mixed> */
    private function summary(Booking $b, Lang $lang): array
    {
        $name = '';
        foreach ($this->vitrine->catalog(false) as $c) {
            foreach ($c['services'] as $s) {
                if ($s['id'] === $b->serviceId) {
                    $name = self::t($s['name'], $lang);
                }
            }
        }
        $barber = '';
        foreach ($this->vitrine->barbers(false) as $br) {
            if ($br['id'] === $b->barberId) {
                $barber = $br['firstName'];
            }
        }
        $tz = $this->calculator->policy()->tz();

        return ['id' => $b->id, 'service' => $name, 'barber' => $barber, 'start' => $b->range->start->format('Y-m-d\TH:i:s\Z'),
            'date' => $b->range->start->setTimezone($tz)->format('Y-m-d'), 'time' => $b->range->start->setTimezone($tz)->format('H:i'),
            'durationMin' => $b->durationMin, 'price' => (new Money($b->priceCents))->format($lang)];
    }

    private static function date(string $value, string $field): \DateTimeImmutable
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($d === false || $d->format('Y-m-d') !== $value) {
            throw new DomainException('invalid_date', 'Date invalide.', "query.$field");
        }

        return $d;
    }

    /** @param array<string,string> $values */
    private static function t(array $values, Lang $lang): string
    {
        return $values === [] ? '' : (new TranslatedText($values, 'text', 100000, false))->get($lang);
    }
}

<?php

declare(strict_types=1);

/**
 * Processus de test de concurrence : attend l'instant de départ commun, puis exécute une action et imprime
 * son résultat. Usage : php worker.php <action> <départ-microtime> <arg…>
 *   book <serviceId> <barberId> <startUtc> <n>       → code HTTP de la réservation
 *   absence <barberId> <startsLocal> <endsLocal>     → « ok » ou la raison du refus
 */
use Barbier\Domain\Shared\DomainException;
use Barbier\Http\Request;
use Barbier\Kernel;
use Barbier\Tests\Fakes\FakeClock;

require dirname(__DIR__, 3) . '/harness/bootstrap.php';
[$_, $action, $go] = $argv;
$config = ['env' => 'test', 'app_key' => (string) getenv('APP_KEY'), 'install_code' => 'x', 'base_url' => 'https://www.lecorbeau.test',
    'db' => ['host' => getenv('DB_HOST') ?: '127.0.0.1', 'port' => (int) getenv('DB_PORT'), 'database' => (string) getenv('DB_NAME'), 'username' => (string) getenv('DB_USER'), 'password' => (string) getenv('DB_PASSWORD')],
    'smtp' => ['host' => '127.0.0.1', 'port' => 1, 'secure' => 'none', 'from' => 'r@lecorbeau.be']]; // SMTP volontairement injoignable : sans effet sur la réservation
$kernel = new Kernel($config, new FakeClock(new DateTimeImmutable('2026-11-09 08:00:00', new DateTimeZone('UTC'))));
$kernel->pdo(); // connexion ouverte AVANT le départ : on mesure la course, pas la connexion
while (microtime(true) < (float) $go) {
    usleep(200);
}
if ($action === 'book') {
    [, , , $service, $barber, $start, $n] = $argv;
    $body = json_encode(['serviceId' => $service, 'barberId' => $barber, 'start' => $start, 'name' => "Client $n", 'phone' => '+32 470 00 00 ' . str_pad($n, 2, '0', STR_PAD_LEFT), 'email' => "c$n@example.be", 'lang' => 'fr']);
    $r = $kernel->handle(new Request('POST', '/api/v1/bookings', (string) $body, [], ['Content-Type' => 'application/json'], [], "10.0.0.$n"));
    echo $r->status;
} else {
    [, , , $barber, $from, $to] = $argv;
    try {
        $kernel->agendaAdmin()->addAbsence($barber, $from, $to);
        echo 'ok';
    } catch (DomainException $e) {
        echo $e->reason;
    }
}

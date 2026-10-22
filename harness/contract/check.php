<?php

declare(strict_types=1);

/**
 * Gate `contrat` (partie serveur) — exit 0 = 🟢.
 *  K1 parité : chaque route d'API du routeur existe dans la spec, et chaque opération de la spec
 *     est servie par le routeur (dans les deux sens).
 *  K2 chaque opération a un operationId unique.
 *  K3 tout schéma objet déclare additionalProperties: false (désérialisation stricte).
 *  K4 tout paramètre de requête est une chaîne (pas de coercition implicite côté serveur).
 *  K5 toute opération déclare la réponse 400 que produit la validation stricte.
 */
use Barbier\Http\Contract\Spec;
use Barbier\Kernel;

require dirname(__DIR__, 2) . '/app/src/bootstrap.php';
$spec = Spec::default();
$doc = $spec->document();
$failures = [];

$key = static fn (array $r): string => $r['method'] . ' ' . $r['pattern'];
$routes = array_map($key, array_filter((new Kernel([]))->router()->routes(), static fn (array $r): bool => $r['contract']));
$ops = array_map($key, $spec->operations());
foreach (array_diff($routes, $ops) as $missing) {
    $failures[] = "K1: route servie mais absente de la spec : $missing";
}
foreach (array_diff($ops, $routes) as $missing) {
    $failures[] = "K1: opération de la spec non servie par le routeur : $missing";
}

$ids = [];
foreach ($doc['paths'] ?? [] as $path => $methods) {
    foreach ($methods as $method => $op) {
        if (!is_array($op) || !in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
            continue;
        }
        $id = $op['operationId'] ?? null;
        if (!is_string($id) || $id === '') {
            $failures[] = "K2: operationId manquant : " . strtoupper($method) . " $path";
        } elseif (isset($ids[$id])) {
            $failures[] = "K2: operationId dupliqué : $id";
        }
        $ids[(string) $id] = true;
        if (!isset($op['responses']['400'])) {
            $failures[] = "K5: réponse 400 (validation stricte) non déclarée : " . strtoupper($method) . " $path";
        }
        foreach ($op['parameters'] ?? [] as $p) {
            if (($p['in'] ?? '') === 'query' && ($p['schema']['type'] ?? null) !== 'string') {
                $failures[] = "K4: paramètre de requête non chaîne : {$p['name']} (" . strtoupper($method) . " $path)";
            }
        }
    }
}

$walk = static function (mixed $node, string $where) use (&$walk, &$failures): void {
    if (!is_array($node)) {
        return;
    }
    $isObject = ($node['type'] ?? null) === 'object' || (is_array($node['type'] ?? null) && in_array('object', $node['type'], true));
    if ($isObject && isset($node['properties']) && ($node['additionalProperties'] ?? null) !== false) {
        $failures[] = "K3: schéma objet ouvert (additionalProperties: false manquant) : $where";
    }
    foreach ($node as $k => $child) {
        $walk($child, $where . '/' . $k);
    }
};
$walk($doc['paths'] ?? [], '#/paths');
$walk($doc['components'] ?? [], '#/components');

if ($failures === []) {
    fwrite(STDOUT, 'contrat (serveur): 🟢 ' . count($ops) . " opérations, parité routes ↔ spec, objets fermés.\n");
    exit(0);
}
fwrite(STDERR, "contrat (serveur): 🔴\n - " . implode("\n - ", $failures) . "\n");
exit(1);

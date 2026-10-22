<?php

declare(strict_types=1);

/**
 * Gate `verify` / `plancher` (structurel) — exit 0 = 🟢, ≠0 = 🔴. Dérivé du kit Foyer, chemins du projet.
 *  G1  secrets : app/config.php jamais versionné ; aucun mot de passe en clair dans le gabarit.
 *  G2  migrations : chaque *.up.sql a son *.down.sql ; nommage NNNN_nom ; SQL portable.
 *  H1  pureté : app/src/Domain et app/src/Application sans PDO, SQL, framework, superglobales.
 *  L1  disposition : la racine web (www/) ne contient que le contrôleur frontal comme PHP ;
 *      le code applicatif reste dans app/, hors racine.
 * Les invariants des stories suivantes (contrat, échappement, i18n, SQL concaténé…) s'ajoutent ici.
 */
$root = dirname(__DIR__);
$failures = [];

// G1
$gitignore = (string) @file_get_contents($root . '/.gitignore');
if (!preg_match('#^/app/config\.php$#m', $gitignore)) {
    $failures[] = 'G1: /app/config.php doit figurer dans .gitignore.';
}
$example = (string) @file_get_contents($root . '/app/config.example.php');
if (preg_match('/[\'"]password[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/', $example)) {
    $failures[] = 'G1: mot de passe en clair dans app/config.example.php.';
}

// G2
foreach (glob($root . '/app/migrations/*.sql') ?: [] as $file) {
    $base = basename($file);
    if (preg_match('/^\d{4}_[a-z0-9_]+\.(up|down)\.sql$/', $base) !== 1) {
        $failures[] = "G2: nom de migration invalide : $base (attendu NNNN_nom.up.sql / .down.sql)";
        continue;
    }
    $twin = str_ends_with($base, '.up.sql')
        ? preg_replace('/\.up\.sql$/', '.down.sql', $file)
        : preg_replace('/\.down\.sql$/', '.up.sql', $file);
    if (!is_file((string) $twin)) {
        $failures[] = 'G2: migration sans pendant : ' . $base;
    }
    $sql = (string) preg_replace('/--[^\r\n]*/', '', (string) file_get_contents($file));
    if (preg_match('/\bNOW\(\)|\bAUTO_INCREMENT\b|\bCURRENT_TIMESTAMP\b/i', $sql)) {
        $failures[] = "G2: SQL non portable (NOW()/AUTO_INCREMENT/CURRENT_TIMESTAMP) dans $base";
    }
}

// H1
foreach (['/app/src/Domain', '/app/src/Application'] as $dir) {
    foreach (rglob($root . $dir) as $file) {
        $code = strip_php_comments((string) file_get_contents($file));
        if (preg_match('/\bPDO\b|->query\(|->prepare\(|\bSELECT\s|\bINSERT\s+INTO\b|\$_(GET|POST|SERVER|SESSION|COOKIE|FILES)\b|Barbier\\\\(Adapter|Http)\\\\/', $code)) {
            $failures[] = 'H1: dépendance d\'infrastructure dans une couche pure : ' . substr($file, strlen($root) + 1);
        }
    }
}

// L1
foreach (rglob($root . '/www') as $file) {
    if (substr($file, strlen($root) + 1) !== 'www/index.php') {
        $failures[] = 'L1: PHP interdit dans la racine web hors contrôleur frontal : ' . substr($file, strlen($root) + 1);
    }
}
if (!is_file($root . '/www/index.php') || !is_dir($root . '/app/src')) {
    $failures[] = 'L1: disposition attendue absente (www/index.php, app/src/).';
}

// C1 — contrat matérialisé présent : la spec (source de vérité) et le client généré.
foreach (['openapi/site-barbier.openapi.json', 'frontend/src/generated/api.ts'] as $f) {
    if (!is_file($root . '/' . $f)) {
        $failures[] = "C1: contrat matérialisé incomplet, absent : $f";
    }
}

// F1 — les îlots ne parlent au serveur QUE par le client généré : aucun appel réseau à la main hors
// du transport commun, aucune URL d'API en dur hors du code généré.
foreach (rglob($root . '/frontend/src', ['js', 'ts', 'svelte', 'mjs']) as $file) {
    $rel = substr($file, strlen($root) + 1);
    if (str_starts_with($rel, 'frontend/src/generated/')) {
        continue;
    }
    $code = (string) file_get_contents($file);
    if ($rel !== 'frontend/src/shared/transport.ts' && preg_match('/\bfetch\s*\(|XMLHttpRequest|axios/', $code)) {
        $failures[] = "F1: appel réseau à la main hors du transport commun : $rel";
    }
    if (preg_match('#[\'"`]/api/#', $code)) {
        $failures[] = "F1: URL d'API en dur hors du client généré : $rel";
    }
}

// J1 — anti-dette de documentation : les trois harnais rejouent LES parcours de journeys/, jamais une
// copie. Le moteur charge journeys/ ; aucun autre script du harnais ne pilote un navigateur ; chaque
// parcours a un id, un titre et des étapes décrites en langage métier.
$engine = (string) @file_get_contents($root . '/harness/browser/run.mjs');
if (!str_contains($engine, "resolve(ROOT, 'journeys')")) {
    $failures[] = 'J1: harness/browser/run.mjs ne charge plus les parcours depuis journeys/.';
}
foreach (rglob($root . '/harness', ['js', 'mjs', 'ts']) as $file) {
    $rel = substr($file, strlen($root) + 1);
    if ($rel !== 'harness/browser/run.mjs' && !str_contains($rel, 'node_modules') && preg_match('/\.goto\(|chromium\.launch/', (string) file_get_contents($file))) {
        $failures[] = "J1: parcours recopié hors de journeys/ : $rel";
    }
}
$journeys = glob($root . '/journeys/*.journey.mjs') ?: [];
if ($journeys === []) {
    $failures[] = 'J1: aucun parcours de référence dans journeys/.';
}
foreach ($journeys as $file) {
    $code = (string) file_get_contents($file);
    $steps = preg_match_all('/\bid:\s*[\'"]/', $code) - 1;
    $described = preg_match_all('/\bdescription:\s*[\'"]/', $code);
    if (!preg_match('/\btitle:\s*[\'"]/', $code) || $steps < 1 || $described < $steps) {
        $failures[] = 'J1: parcours sans titre ou étape sans description métier : ' . basename($file);
    }
}

// V1 — gabarits : toute sortie passe par $e (échappement), $t (traduction échappée) ou $html (SafeHtml seul) ;
// jamais d'echo/print.
foreach (rglob($root . '/app/templates') as $file) {
    foreach (file($file) ?: [] as $n => $line) {
        if (preg_match_all('/<\?=(?!\s*\$e\(|\s*\$t\(|\s*\$html\()/', $line) > 0 || preg_match('/<\?php\s+(echo|print)\b/', $line) === 1) {
            $failures[] = 'V1: sortie non échappée dans ' . substr($file, strlen($root) + 1) . ':' . ($n + 1);
        }
    }
}

// S1 — SQL : jamais de valeur concaténée dans une requête (requêtes préparées seulement, ADR-010).
// Exceptions documentées : le Migrator exécute les fichiers de migration versionnés ; l'export de sauvegarde
// concatène des noms de tables lus dans information_schema et filtrés par /^[a-z_]+$/ (jamais une saisie).
foreach (rglob($root . '/app/src') as $file) {
    if (str_ends_with($file, '/Adapter/Persistence/Migrator.php') || str_ends_with($file, '/Adapter/Persistence/PdoBackupExporter.php')) {
        continue;
    }
    $code = strip_php_comments((string) file_get_contents($file));
    // Interdit : variable concaténée (« . $x ») ou interpolée (« "…$x" ») dans l'appel ; une requête passée
    // en variable doit être un littéral (ternaire de littéraux admis).
    if (preg_match('/->(query|exec|prepare)\((?:[^;]*?)\.\s*\$/', $code) === 1
        || preg_match('/->(query|exec|prepare)\(\s*"[^"]*\{?\$[a-z]/i', $code) === 1
        || preg_match('/->(query|exec|prepare)\(\s*\$[a-zA-Z_]+\s*\)/', $code) === 1) {
        $failures[] = 'S1: requête SQL construite par concaténation/interpolation : ' . substr($file, strlen($root) + 1);
    }
}

if ($failures === []) {
    fwrite(STDOUT, "verify: 🟢 G1 G2 H1 L1 C1 F1 J1 V1 S1 verts.\n");
    exit(0);
}
fwrite(STDERR, "verify: 🔴\n - " . implode("\n - ", $failures) . "\n");
exit(1);

function strip_php_comments(string $code): string
{
    $out = '';
    foreach (token_get_all($code) as $tok) {
        if (is_array($tok)) {
            if ($tok[0] === T_COMMENT || $tok[0] === T_DOC_COMMENT) {
                continue;
            }
            $out .= $tok[1];
        } else {
            $out .= $tok;
        }
    }

    return $out;
}

/**
 * @param list<string> $extensions
 * @return list<string>
 */
function rglob(string $dir, array $extensions = ['php']): array
{
    if (!is_dir($dir)) {
        return [];
    }
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && in_array(strtolower($f->getExtension()), $extensions, true)) {
            $out[] = $f->getPathname();
        }
    }
    sort($out);

    return $out;
}

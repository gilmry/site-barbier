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

if ($failures === []) {
    fwrite(STDOUT, "verify: 🟢 G1 G2 H1 L1 verts.\n");
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

/** @return list<string> */
function rglob(string $dir): array
{
    if (!is_dir($dir)) {
        return [];
    }
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
            $out[] = $f->getPathname();
        }
    }
    sort($out);

    return $out;
}

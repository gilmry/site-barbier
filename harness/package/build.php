<?php

declare(strict_types=1);

/**
 * Construit le paquet de déploiement (Story 0.4) : un zip REPRODUCTIBLE (entrées triées, dates et droits
 * fixés) à envoyer tel quel en SFTP à la racine de l'hébergement (OVH : la racine contient www/).
 *   app/   code serveur, gabarits, traductions, migrations, dépendances (sans outils de dev)
 *   www/   contrôleur frontal, .htaccess, assets, îlots compilés, uploads/.htaccess
 *   .ovhconfig  version de PHP chez OVH
 * Jamais dans le paquet : config.php, tests, harnais, sources des îlots, .git, outils de dev.
 * Usage : php harness/package/build.php <dossier-staging> <zip-de-sortie> <version>
 */
[$_, $staging, $out, $version] = $argv + [null, null, null, null];
if ($staging === null || $out === null || $version === null) {
    fwrite(STDERR, "usage: build.php <staging> <out.zip> <version>\n");
    exit(2);
}
$root = dirname(__DIR__, 2);

$files = [];
$add = static function (string $src, string $dest) use (&$files): void {
    $files[$dest] = $src;
};
$addTree = static function (string $dir, string $prefix, callable $keep) use ($add): void {
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = substr($f->getPathname(), strlen($dir) + 1);
        if ($f->isFile() && $keep($rel)) {
            $add($f->getPathname(), $prefix . '/' . str_replace('\\', '/', $rel));
        }
    }
};

$always = static fn (string $rel): bool => true;
foreach (['src', 'templates', 'i18n', 'migrations', 'data'] as $d) {
    $addTree("$root/app/$d", "app/$d", $always);
}
$add("$root/app/config.example.php", 'app/config.example.php');
$add("$root/deploy/app.htaccess", 'app/.htaccess');
$addTree("$staging/app/vendor", 'app/vendor', static fn (string $rel): bool => !preg_match('#(^|/)(tests?|docs?|\.github)/#i', $rel));
$addTree("$root/www", 'www', static fn (string $rel): bool => !str_starts_with($rel, 'uploads/') || $rel === 'uploads/.htaccess');
$add("$root/deploy/ovhconfig", '.ovhconfig');
$add("$root/openapi/site-barbier.openapi.json", 'openapi/site-barbier.openapi.json');
file_put_contents("$staging/VERSION", $version . "\n");
$add("$staging/VERSION", 'VERSION');

ksort($files, SORT_STRING);
@unlink($out);
$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    fwrite(STDERR, "impossible de créer $out\n");
    exit(1);
}
$epoch = mktime(0, 0, 0, 1, 1, 2020);
foreach ($files as $dest => $src) {
    $zip->addFromString($dest, (string) file_get_contents($src));
    $zip->setMtimeName($dest, $epoch);
    $zip->setExternalAttributesName($dest, ZipArchive::OPSYS_UNIX, 0100644 << 16);
    $zip->setCompressionName($dest, ZipArchive::CM_DEFLATE, 9);
}
$zip->close();
fwrite(STDOUT, sprintf("paquet : %s (%d fichiers, %.1f Ko)\n", basename($out), count($files), filesize($out) / 1024));

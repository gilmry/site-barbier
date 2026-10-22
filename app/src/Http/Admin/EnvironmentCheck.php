<?php

declare(strict_types=1);

namespace Barbier\Http\Admin;

/** Contrôle d'environnement de l'installation et du tableau de bord (FR-042, Story 5.2). */
final class EnvironmentCheck
{
    public const PHP_MIN = '8.4.0';
    public const EXTENSIONS = ['pdo_mysql', 'gd', 'mbstring', 'intl', 'openssl', 'sodium', 'json'];

    /**
     * @param array<string,mixed> $config
     * @return list<array{label:string, ok:bool, detail:string, blocking:bool}>
     */
    public static function run(array $config, string $varDir, ?\PDO $pdo, bool $https): array
    {
        $checks = [];
        $checks[] = ['label' => 'PHP ' . self::PHP_MIN . ' ou plus', 'ok' => version_compare(PHP_VERSION, self::PHP_MIN, '>='), 'detail' => 'version ' . PHP_VERSION, 'blocking' => true];
        foreach (self::EXTENSIONS as $ext) {
            $checks[] = ['label' => "Extension $ext", 'ok' => extension_loaded($ext), 'detail' => extension_loaded($ext) ? 'présente' : 'absente', 'blocking' => true];
        }
        $webp = function_exists('gd_info') && (gd_info()['WebP Support'] ?? false) && (gd_info()['JPEG Support'] ?? false);
        $checks[] = ['label' => 'Images JPEG et WebP (GD)', 'ok' => $webp, 'detail' => $webp ? 'prises en charge' : 'non prises en charge', 'blocking' => true];
        if (!is_dir($varDir)) {
            @mkdir($varDir, 0750, true);
        }
        $checks[] = ['label' => 'Dossier app/var inscriptible', 'ok' => is_dir($varDir) && is_writable($varDir), 'detail' => 'journaux, jeton de réinitialisation', 'blocking' => true];
        $key = base64_decode((string) ($config['app_key'] ?? ''), true);
        $checks[] = ['label' => 'Clé de l\'application (app_key)', 'ok' => is_string($key) && strlen($key) === 32, 'detail' => '32 octets en base64 dans config.php', 'blocking' => true];
        $checks[] = ['label' => 'Code d\'installation (install_code)', 'ok' => strlen((string) ($config['install_code'] ?? '')) >= 12, 'detail' => '12 caractères au moins dans config.php', 'blocking' => true];
        $checks[] = ['label' => 'Base de données joignable', 'ok' => $pdo !== null, 'detail' => $pdo !== null ? 'connexion établie' : 'vérifier les accès dans config.php', 'blocking' => true];
        $checks[] = ['label' => 'Connexion chiffrée (HTTPS)', 'ok' => $https, 'detail' => $https ? 'active' : 'obligatoire en production', 'blocking' => ($config['env'] ?? 'prod') === 'prod'];

        return $checks;
    }

    /** @param list<array{ok:bool, blocking:bool}> $checks */
    public static function blocking(array $checks): bool
    {
        foreach ($checks as $c) {
            if (!$c['ok'] && $c['blocking']) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace Barbier\Adapter\Security;

use Barbier\Domain\Acces\Crypto;
use RuntimeException;

/**
 * Cryptographie de l'application : Argon2id (bcrypt si l'hébergeur ne l'a pas), libsodium secretbox
 * pour les secrets TOTP, HMAC-SHA256 pour les empreintes de jetons. Clé : config `app_key` (32 octets base64).
 */
final class SodiumCrypto implements Crypto
{
    private string $key;

    public function __construct(string $appKeyBase64)
    {
        $key = base64_decode($appKeyBase64, true);
        if (!is_string($key) || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('config app_key invalide : 32 octets encodés en base64 attendus.');
        }
        $this->key = $key;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function seal(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return $nonce . sodium_crypto_secretbox($plain, $nonce, $this->key);
    }

    public function open(string $sealed): ?string
    {
        if (strlen($sealed) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $plain = sodium_crypto_secretbox_open(
            substr($sealed, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($sealed, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->key,
        );

        return $plain === false ? null : $plain;
    }

    public function tokenHash(string $token): string
    {
        return hash_hmac('sha256', $token, $this->key);
    }
}

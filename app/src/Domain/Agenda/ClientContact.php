<?php

declare(strict_types=1);

namespace Barbier\Domain\Agenda;

use Barbier\Domain\Shared\DomainException;

/** Coordonnées d'un client (FR-023) : nom, téléphone et e-mail obligatoires et valides. */
final class ClientContact
{
    public readonly string $name;
    public readonly string $phone;
    public readonly string $email;

    public function __construct(string $name, string $phone, string $email)
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $phone = trim($phone);
        $email = mb_strtolower(trim($email));
        if ($name === '' || mb_strlen($name) > 80 || preg_match('/[\r\n<>]/', $name) === 1) {
            throw new DomainException('invalid_name', 'Indiquez votre nom (80 caractères au plus).', 'name');
        }
        if (preg_match('/^\+?[0-9 .\/()-]{6,30}$/', $phone) !== 1 || strlen((string) preg_replace('/\D/', '', $phone)) < 8) {
            throw new DomainException('invalid_phone', 'Numéro de téléphone invalide.', 'phone');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190 || preg_match('/[\r\n]/', $email) === 1) {
            throw new DomainException('invalid_email', 'Adresse e-mail invalide.', 'email');
        }
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
    }
}

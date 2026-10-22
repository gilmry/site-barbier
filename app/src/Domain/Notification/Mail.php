<?php

declare(strict_types=1);

namespace Barbier\Domain\Notification;

use Barbier\Domain\Shared\DomainException;

/** Un e-mail en texte brut (+ pièce jointe facultative). Aucun retour à la ligne dans les en-têtes (FR-030 @security). */
final class Mail
{
    /** @param array{name:string, mime:string, content:string}|null $attachment */
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $text,
        public readonly ?string $replyTo = null,
        public readonly ?array $attachment = null,
    ) {
        foreach (['to' => $to, 'subject' => $subject, 'replyTo' => (string) $replyTo] as $field => $value) {
            if (preg_match('/[\r\n]/', $value) === 1) {
                throw new DomainException('header_injection', 'Caractère interdit dans un en-tête d’e-mail.', $field);
            }
        }
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('invalid_email', 'Destinataire invalide.', 'to');
        }
    }

    /** Destinataire masqué pour l'affichage du journal : g•••@salon.be */
    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1) . '•••@' . $domain;
    }
}

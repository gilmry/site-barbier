<?php

declare(strict_types=1);

namespace Barbier\Adapter\Mail;

use Barbier\Domain\Notification\Mail;
use Barbier\Domain\Notification\Mailer;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/**
 * Envoi par le SMTP authentifié de l'hébergeur (ADR-009), via PHPMailer. Expéditeur = adresse du domaine du
 * salon (SPF/DKIM) ; réponse vers le salon. Sans configuration SMTP, la fonction mail() de l'hébergeur.
 *
 * @phpstan-type SmtpConfig array{host?:string, port?:int, username?:string, password?:string, secure?:string, from?:string, from_name?:string}
 */
final class SmtpMailer implements Mailer
{
    /** @param SmtpConfig $config */
    public function __construct(private readonly array $config)
    {
    }

    public function send(Mail $mail): void
    {
        $m = new PHPMailer(true);
        try {
            $m->CharSet = PHPMailer::CHARSET_UTF8;
            $m->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
            if (($this->config['host'] ?? '') !== '') {
                $m->isSMTP();
                $m->Host = (string) $this->config['host'];
                $m->Port = (int) ($this->config['port'] ?? 587);
                $m->SMTPAuth = ($this->config['username'] ?? '') !== '';
                $m->Username = (string) ($this->config['username'] ?? '');
                $m->Password = (string) ($this->config['password'] ?? '');
                $secure = (string) ($this->config['secure'] ?? 'tls');
                $m->SMTPSecure = $secure === 'none' ? '' : $secure;
                $m->SMTPAutoTLS = $secure !== 'none';
                $m->Timeout = 10;
            } else {
                $m->isMail();
            }
            $m->setFrom((string) ($this->config['from'] ?? 'no-reply@localhost'), (string) ($this->config['from_name'] ?? ''));
            $m->addAddress($mail->to);
            if ($mail->replyTo !== null) {
                $m->addReplyTo($mail->replyTo);
            }
            $m->Subject = $mail->subject;
            $m->Body = $mail->text;
            $m->isHTML(false);
            if ($mail->attachment !== null) {
                $m->addStringAttachment($mail->attachment['content'], $mail->attachment['name'], PHPMailer::ENCODING_BASE64, $mail->attachment['mime']);
            }
            $m->send();
        } catch (MailerException $e) {
            throw new RuntimeException('envoi impossible : ' . $m->ErrorInfo, 0, $e);
        }
    }
}

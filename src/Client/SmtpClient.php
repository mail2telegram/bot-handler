<?php

namespace M2T\Client;

use M2T\Model\Email;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Psr\Log\LoggerInterface;
use Throwable;

class SmtpClient
{
    protected LoggerInterface $logger;
    protected PHPMailer $mailer;

    public function __construct(LoggerInterface $logger, PHPMailer $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth = true;
        $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
    }

    public function send(Email $mailAccount, string $to, string $subject, string $text, array $attachment = []): bool
    {
        $this->mailer->Host = $mailAccount->smtpHost;
        $this->mailer->Port = $mailAccount->smtpPort;
        $this->mailer->SMTPSecure = $mailAccount->smtpSocketType;
        $this->mailer->Username = explode('@', $mailAccount->email)[0];
        $this->mailer->Password = $mailAccount->getPwd();

        $this->mailer->Subject = $subject;
        $this->mailer->Body = $text ?: ' ';

        try {
            if ($attachment && !$this->mailer->addStringAttachment($attachment['file'], $attachment['fileName'])) {
                return false;
            }

            $this->mailer->setFrom($mailAccount->email);
            $this->mailer->addAddress($to);

            $result = $this->mailer->send();
            if (!$result) {
                $this->mailer->Username = $mailAccount->email;
                $result = $this->mailer->send();
            }
        } catch (Throwable $e) {
            $this->logger->debug('SMTP: ' . $e);
            return false;
        }

        return $result;
    }

    public function check(Email $mailAccount): bool
    {
        $this->mailer->Host = $mailAccount->smtpHost;
        $this->mailer->Port = $mailAccount->smtpPort;
        $this->mailer->SMTPSecure = $mailAccount->smtpSocketType;
        $this->mailer->Username = explode('@', $mailAccount->email)[0];
        $this->mailer->Password = $mailAccount->getPwd();

        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            return false;
        }
    }
}

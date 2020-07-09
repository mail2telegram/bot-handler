<?php

namespace M2T\Client;

use M2T\Model\Email;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class SmtpClient
{
    protected PHPMailer $mailer;

    public function __construct(PHPMailer $mailer)
    {
        $this->mailer = $mailer;
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth = true;
        $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
    }

    /**
     * @param Email  $mailAccount
     * @param string $to
     * @param string $subject
     * @param string $text
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send(Email $mailAccount, string $to, string $subject, string $text): bool
    {
        $this->mailer->Host = $mailAccount->smtpHost;
        $this->mailer->Port = $mailAccount->smtpPort;
        $this->mailer->SMTPSecure = $mailAccount->smtpSocketType;
        $this->mailer->Username = explode('@', $mailAccount->email)[0];
        $this->mailer->Password = $mailAccount->getPwd();

        $this->mailer->Subject = $subject;
        $this->mailer->Body = $text;

        $this->mailer->setFrom($mailAccount->email);
        $this->mailer->addAddress($to);
        $result = $this->mailer->send();

        if (!$result) {
            $this->mailer->Username = $mailAccount->email;
            $result = $this->mailer->send();
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

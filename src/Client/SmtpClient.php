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
     * @param Email  $mailbox
     * @param string $to
     * @param string $subject
     * @param string $text
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send(Email $mailbox, string $to, string $subject, string $text): bool
    {
        $this->mailer->Host = $mailbox->smtpHost;
        $this->mailer->Port = $mailbox->smtpPort;
        $this->mailer->SMTPSecure = $mailbox->smtpSocketType;
        $this->mailer->Username = explode('@', $mailbox->email)[0];
        $this->mailer->Password = $mailbox->pwd;

        $this->mailer->Subject = $subject;
        $this->mailer->Body = $text;

        $this->mailer->setFrom($mailbox->email);
        $this->mailer->addAddress($to);
        $result = $this->mailer->send();

        if (!$result) {
            $this->mailer->Username = $mailbox->email;
            $result = $this->mailer->send();
        }

        return $result;
    }

    public function check(Email $mailbox): bool
    {
        $this->mailer->Host = $mailbox->smtpHost;
        $this->mailer->Port = $mailbox->smtpPort;
        $this->mailer->SMTPSecure = $mailbox->smtpSocketType;
        $this->mailer->Username = explode('@', $mailbox->email)[0];
        $this->mailer->Password = $mailbox->pwd;

        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            return false;
        }
    }
}

<?php

namespace M2T\Client;

use M2T\Model\Email;
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
     * @param Email $account
     * @param string           $to
     * @param string           $subject
     * @param string           $text
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send(Email $account, string $to, string $subject, string $text): bool
    {
        $this->mailer->Host = $account->smtpHost;
        $this->mailer->Port = $account->smtpPort;
        $this->mailer->SMTPSecure = $account->smtpSocketType;

        $this->mailer->Username = explode('@', $account->email)[0];
        $this->mailer->Password = $account->pwd;

        $this->mailer->Subject = $subject;
        $this->mailer->Body = $text;

        $this->mailer->setFrom($account->email);
        $this->mailer->addAddress($to);
        $result = $this->mailer->send();

        if (!$result) {
            $this->mailer->Username = $account->email;
            $result = $this->mailer->send();
        }

        return $result;
    }
}

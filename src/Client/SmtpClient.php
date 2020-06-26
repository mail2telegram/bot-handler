<?php

namespace App\Client;

use App\App;
use App\Model\Account;
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
     * @param \App\Model\Account $account
     * @param string             $text
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function draftSend(Account $account, string $text): bool
    {
        $this->mailer->Host = $account->smtpHost;
        $this->mailer->Port = $account->smtpPort;
        $this->mailer->SMTPSecure = $account->smtpSocketType;
        $this->mailer->Username = $account->email;
        $this->mailer->Password = $account->pwd;

        $this->mailer->Subject = 'Test mail from M2T';
        $this->mailer->Body = $text;

        $this->mailer->setFrom($account->email);
        $this->mailer->addAddress(App::get('test')['mailTo']);
        return $this->mailer->send();
    }
}

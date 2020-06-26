<?php

namespace App\Model;

class Email
{
    public string $email;
    public string $pwd;
    public string $imapHost;
    public int $imapPort;
    public string $imapSocketType;
    public string $smtpHost;
    public int $smtpPort;
    public string $smtpSocketType;

    public function __construct(
        string $email,
        string $pwd,
        string $imapHost,
        string $imapPort,
        string $imapSocketType,
        string $smtpHost,
        string $smtpPort,
        string $smtpSocketType
    ) {
        $this->email = $email;
        $this->pwd = $pwd;
        $this->imapHost = $imapHost;
        $this->imapPort = $imapPort;
        $this->imapSocketType = $imapSocketType;
        $this->smtpHost = $smtpHost;
        $this->smtpPort = $smtpPort;
        $this->smtpSocketType = $smtpSocketType;
    }
}

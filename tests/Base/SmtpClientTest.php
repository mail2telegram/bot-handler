<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\Client\SmtpClient;
use M2T\Model\Email;
use PHPMailer\PHPMailer\PHPMailer;

class SmtpClientTest extends Unit
{
    protected BaseTester $tester;

    public function testSend(): void
    {
        $mailboxes = $this->tester->emailProvider();
        $to = $mailboxes[0]->email;
        foreach ($mailboxes as $mailbox) {
            $client = new SmtpClient(new PHPMailer());
            $result = $client->send($mailbox, $to, 'test', 'test');
            static::assertTrue($result, $mailbox->email);
        }
    }

    public function testCheck(): void
    {
        $mailboxes = $this->tester->emailProvider();
        foreach ($mailboxes as $mailbox) {
            $client = new SmtpClient(new PHPMailer());
            $result = $client->check($mailbox);
            static::assertTrue($result, $mailbox->email);
        }
    }

    public function testCheckFailed(): void
    {
        $mailbox = new Email(
            'mail2telegram.app@gmail.com',
            'XXX',
            'imap.gmail.com',
            993,
            'ssl',
            'smtp.gmail.com',
            465,
            'ssl'
        );
        $client = new SmtpClient(new PHPMailer());
        $result = $client->check($mailbox);
        static::assertFalse($result, $mailbox->email);
    }
}

<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\Client\SmtpClient;
use PHPMailer\PHPMailer\PHPMailer;

class SmtpClientTest extends Unit
{
    protected BaseTester $tester;

    public function testSend(): void
    {
        $emails = $this->tester->emailProvider();
        $to = $emails[0]->email;
        foreach ($emails as $email) {
            $client = new SmtpClient(new PHPMailer());
            $result = $client->send($email, $to, 'test', 'test');
            static::assertTrue($result, $email->email);
        }
    }
}

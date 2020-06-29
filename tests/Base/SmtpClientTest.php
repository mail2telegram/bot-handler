<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use M2T\App;
use M2T\Client\SmtpClient;
use Codeception\Test\Unit;

class SmtpClientTest extends Unit
{
    protected BaseTester $tester;

    public function testSend(): void
    {
        $account = $this->tester->accountProvider();
        $to = $account->emails[0]->email;
        foreach ($account->emails as $email) {
            /** @var SmtpClient $client */
            $client = App::get(SmtpClient::class);
            $result = $client->send($email, $to, 'test', 'test');
            static::assertTrue($result, $email->email);
        }
    }
}

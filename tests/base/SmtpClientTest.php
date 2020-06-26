<?php

/** @noinspection PhpIllegalPsrClassPathInspection PhpUnhandledExceptionInspection */

use App\App;
use App\Client\SmtpClient;
use Codeception\Test\Unit;

class SmtpClientTest extends Unit
{
    protected BaseTester $tester;

    public function testSend(): void
    {
        new App();

        /** @var SmtpClient $client */
        $client = App::get(SmtpClient::class);

        $account = App::get('test')['emails'][0];
        $to = App::get('test')['mailTo'];
        $result = $client->send($account, $to, 'test', 'test');
        static::assertTrue($result);
    }
}

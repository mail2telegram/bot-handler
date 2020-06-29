<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Unit;

use UnitTester;
use M2T\App;
use Codeception\Test\Unit;
use M2T\Client\MailConfigClient;

class MailConfigClientTest extends Unit
{
    protected UnitTester $tester;

    public function testGet(): void
    {
        /** @var MailConfigClient $client */
        $client = App::get(MailConfigClient::class);

        $expected = [];
        $result = $client->get('domain-not-found-x1y2.com');
        static::assertSame($expected, $result);

        $expected = [
            'imapHost' => 'imap.gmail.com',
            'imapPort' => '993',
            'imapSocketType' => 'ssl',
            'smtpHost' => 'smtp.gmail.com',
            'smtpPort' => '465',
            'smtpSocketType' => 'ssl',
        ];
        $result = $client->get('gmail.com');
        static::assertSame($expected, $result);

        $expected = [
            'imapHost' => 'imap.yandex.com',
            'imapPort' => '993',
            'imapSocketType' => 'ssl',
            'smtpHost' => 'smtp.yandex.com',
            'smtpPort' => '465',
            'smtpSocketType' => 'ssl',
        ];
        $result = $client->get('yandex.ru');
        static::assertSame($expected, $result);
    }
}

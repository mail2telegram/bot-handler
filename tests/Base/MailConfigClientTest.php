<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\MailConfigClient;
use Psr\Log\LoggerInterface;

class MailConfigClientTest extends Unit
{
    protected BaseTester $tester;

    public function testGet(): void
    {
        $client = new MailConfigClient(App::get(LoggerInterface::class));

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

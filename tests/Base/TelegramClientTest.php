<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\TelegramClient;
use Psr\Log\LoggerInterface;

class TelegramClientTest extends Unit
{
    protected BaseTester $tester;

    public function testDeleteMarkupBtn(): void
    {
        $client = new TelegramClient(App::get(LoggerInterface::class));
        $data = [[['callback_data' => 'delete:123']]];
        $result = $client->deleteMarkupBtn($data, 'delete');
        static::assertTrue($result);
    }

    public function testDeleteMarkupBtnFail(): void
    {
        $client = new TelegramClient(App::get(LoggerInterface::class));
        $data = [[['callback_data' => 'spam:123']]];
        $result = $client->deleteMarkupBtn($data, 'delete');
        static::assertFalse($result);
    }
}

<?php

namespace Unit;

use M2T\App;
use M2T\Client\ImapClient;
use UnitTester;
use Codeception\Test\Unit;

class ImapClientTest extends Unit
{
    protected UnitTester $tester;

    public function testAppendToSent(): void
    {
        /** @var ImapClient $client */
        $client = App::get(ImapClient::class);
        $account = $this->tester->accountProvider();
        $to = $account->emails[0]->email;
        foreach ($account->emails as $email) {
            $result = $client->appendToSent($email, $to, 'test', 'test');
            static::assertTrue($result, $email->email);
        }
    }
}

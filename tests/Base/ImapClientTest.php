<?php

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\Client\ImapClient;

class ImapClientTest extends Unit
{
    protected BaseTester $tester;

    public function testAppendToSent(): void
    {
        $client = new ImapClient();
        $emails = $this->tester->emailProvider();
        $to = $emails[0]->email;
        foreach ($emails as $email) {
            $result = $client->appendToSent($email, $to, 'test', 'test');
            static::assertTrue($result, $email->email);
        }
    }
}

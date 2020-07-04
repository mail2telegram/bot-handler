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
        $mailboxes = $this->tester->emailProvider();
        $to = $mailboxes[0]->email;
        foreach ($mailboxes as $mailbox) {
            $result = $client->appendToSent($mailbox, $to, 'test', 'test');
            static::assertTrue($result, $mailbox->email);
        }
    }

    // @todo draft
    protected function testDelete(): void
    {
        $client = new ImapClient();
        $mailbox = $this->tester->emailProvider()[0];
        $mailId = 94;

        $result = $client->delete($mailbox, $mailId);
        static::assertTrue($result, $mailbox->email);
    }
}

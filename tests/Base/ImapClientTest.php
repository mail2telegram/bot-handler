<?php

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\Client\ImapClient;
use M2T\Model\Email;

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

    public function testCheck(): void
    {
        $client = new ImapClient();
        $mailboxes = $this->tester->emailProvider();
        foreach ($mailboxes as $mailbox) {
            $result = $client->check($mailbox);
            static::assertTrue($result, $mailbox->email);
        }
    }

    public function testCheckFailed(): void
    {
        $client = new ImapClient();
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
        $result = $client->check($mailbox);
        static::assertFalse($result, $mailbox->email);
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

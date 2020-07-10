<?php

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\ImapClient;
use M2T\Model\Email;
use Psr\Log\LoggerInterface;

class ImapClientTest extends Unit
{
    protected BaseTester $tester;

    public function providerMail(): array
    {
        /** @noinspection PhpIncludeInspection */
        return require codecept_data_dir('/mailAccountList.php');
    }

    /**
     * @dataProvider providerMail
     * @param $mailAccount
     * @param $expected
     */
    public function testCheck(Email $mailAccount, bool $expected): void
    {
        $client = new ImapClient(App::get(LoggerInterface::class));
        $result = $client->check($mailAccount);
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider providerMail
     * @param $mailAccount
     * @param $expected
     */
    public function testAppendToSent(Email $mailAccount, bool $expected): void
    {
        if (!$expected) {
            return;
        }
        $client = new ImapClient(App::get(LoggerInterface::class));
        $to = $mailAccount->email;
        $result = $client->appendToSent($mailAccount, $to, 'test', 'test');
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider providerMail
     * @param $mailAccount
     * @param $expected
     */
    protected function testFolderList(Email $mailAccount, bool $expected): void
    {
        if (!$expected) {
            return;
        }
        $client = new ImapClient(App::get(LoggerInterface::class));
        $result = $client->folderList($mailAccount);
        array_map(fn($el) => codecept_debug($el . ' => ' . imap_mutf7_to_utf8($el)), $result);
        static::assertSame($expected, (bool) $result);
    }

    protected function testDelete(): void
    {
        $mailId = 196;
        $mailAccount = $this->providerMail()['Gmail'][0];
        $client = new ImapClient(App::get(LoggerInterface::class));
        $result = $client->delete($mailAccount, $mailId);
        static::assertTrue($result, $mailAccount->email);
    }

    protected function testMoveToTrash(): void
    {
        $mailId = 196;
        $mailAccount = $this->providerMail()['Gmail'][0];
        $client = new ImapClient(App::get(LoggerInterface::class));
        $result = $client->moveToTrash($mailAccount, $mailId);
        static::assertTrue($result, $mailAccount->email);
    }
}

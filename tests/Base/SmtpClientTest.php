<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\App;
use M2T\Client\SmtpClient;
use M2T\Model\DraftEmail;
use M2T\Model\Email;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;

class SmtpClientTest extends Unit
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
        $client = new SmtpClient(App::get(LoggerInterface::class), new PHPMailer());
        $result = $client->check($mailAccount);
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider providerMail
     * @param $mailAccount
     * @param $expected
     */
    public function testSend(Email $mailAccount, bool $expected): void
    {
        $client = new SmtpClient(App::get(LoggerInterface::class), new PHPMailer());
        $email = new DraftEmail($mailAccount->email, [['address' => $mailAccount->email]], 'test', 'test');
        $result = $client->send($mailAccount, $email);
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider providerMail
     * @param $mailAccount
     * @param $expected
     */
    public function testSendEmptyMsg(Email $mailAccount, bool $expected): void
    {
        if (!$expected) {
            return;
        }
        $client = new SmtpClient(App::get(LoggerInterface::class), new PHPMailer());
        $email = new DraftEmail($mailAccount->email, [['address' => $mailAccount->email]], '', '');
        $result = $client->send($mailAccount, $email);
        static::assertSame($expected, $result);
    }
}

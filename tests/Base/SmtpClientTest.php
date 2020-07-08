<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Base;

use BaseTester;
use Codeception\Test\Unit;
use M2T\Client\SmtpClient;
use M2T\Model\Email;
use PHPMailer\PHPMailer\PHPMailer;

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
        $client = new SmtpClient(new PHPMailer());
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
        $client = new SmtpClient(new PHPMailer());
        $to = $mailAccount->email;
        $result = $client->send($mailAccount, $to, 'test', 'test');
        static::assertSame($expected, $result);
    }
}

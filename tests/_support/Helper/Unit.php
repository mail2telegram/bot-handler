<?php

namespace Helper;

use Codeception\Module;
use Codeception\Stub;
use M2T\App;
use M2T\Model\Account;
use M2T\Model\Email;
use Redis;

class Unit extends Module
{
    public function _initialize(): void
    {
        /** @noinspection PhpIncludeInspection */
        require_once codecept_root_dir() . '/vendor/autoload.php';
        $account = $this->accountProvider();
        $redis = Stub::make(
            Redis::class,
            [
                'set' => true,
                'get' => serialize($account),
                'del' => 1,
                'keys' => ['account:' . $account->chatId],
            ]
        );
        new App([Redis::class => fn() => $redis]);
    }

    public function accountProvider(): Account
    {
        $pwd = getenv('TEST_EMAIL_PWD') ?: (require './config.php')['testEmailPwd'];
        return new Account(
            123456,
            [
                new Email(
                    'mail2telegram.app@gmail.com',
                    $pwd,
                    'imap.gmail.com',
                    993,
                    'ssl',
                    'smtp.gmail.com',
                    465,
                    'ssl'
                ),
                new Email(
                    'mail2telegram.app@yandex.ru',
                    $pwd,
                    'imap.yandex.com',
                    993,
                    'ssl',
                    'smtp.yandex.com',
                    465,
                    'ssl'
                ),
                new Email(
                    'mail2telegram.app@mail.ru',
                    $pwd,
                    'imap.mail.ru',
                    993,
                    'ssl',
                    'smtp.mail.ru',
                    465,
                    'ssl'
                ),
            ]
        );
    }
}

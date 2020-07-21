<?php

use M2T\Model\Mailbox;

$pwd = (require './config.php')['testEmailPwd'];
return [
    //'Gmail' => [
    //    new Mailbox(
    //        'mail2telegram.app@gmail.com',
    //        $pwd,
    //        'imap.gmail.com',
    //        993,
    //        'ssl',
    //        'smtp.gmail.com',
    //        465,
    //        'ssl'
    //    ),
    //    true,
    //],
    'Yandex' => [
        new Mailbox(
            'mail2telegram.app@yandex.ru',
            $pwd,
            'imap.yandex.com',
            993,
            'ssl',
            'smtp.yandex.com',
            465,
            'ssl'
        ),
        true,
    ],
    'MailRu' => [
        new Mailbox(
            'mail2telegram.app@mail.ru',
            $pwd,
            'imap.mail.ru',
            993,
            'ssl',
            'smtp.mail.ru',
            465,
            'ssl'
        ),
        true,
    ],
    'Gmail | wrong pwd' => [
        new Mailbox(
            'mail2telegram.app@gmail.com',
            'XXX',
            'imap.gmail.com',
            993,
            'ssl',
            'smtp.gmail.com',
            465,
            'ssl'
        ),
        false,
    ],
    'Gmail | wrong host' => [
        new Mailbox(
            'mail2telegram.app@gmail.com',
            'XXX',
            'imap.gmail-xxxxxxxxxxxxxxxx.com',
            993,
            'ssl',
            'smtp.gmail.com',
            465,
            'ssl'
        ),
        false,
    ],
];

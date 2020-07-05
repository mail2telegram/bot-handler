<?php

use M2T\Model\Email;

return [
    'logLevel' => 'debug',
    'telegramToken' => 'XXX',
    'telegramTimeout' => 5.0,
    'telegramLongPollingTimeout' => 2,
    'telegramMaxShowAtList' => 7,
    'redis' => [
        'host' => 'm2t_redis',
    ],
    'amqp' => [
        'host' => 'm2t_rabbitmq',
        'port' => '5672',
        'user' => 'guest',
        'pwd' => 'guest',
    ],
    // for tests only
    'testEmailPwd' => 'XXX',
    'test' => [
        'emails' => [
            new Email(
                'mail2telegram.app@gmail.com',
                'XXX',
                'imap.gmail.com',
                993,
                'ssl',
                'smtp.gmail.com',
                465,
                'ssl'
            ),
        ],
        'mailTo' => 'mail2telegram.app@gmail.com',
    ],
];

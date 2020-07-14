<?php

return [
    'botName' => 'MailProxyTestBot',
    'logLevel' => 'debug',
    'telegramToken' => 'XXX',
    'cryptoKey' => 'XXX',
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
    'testEmailPwd' => getenv('TEST_EMAIL_PWD') ?: 'XXX',
];

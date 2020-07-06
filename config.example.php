<?php

return [
    'logLevel' => 'debug',
    'telegramToken' => 'XXX',
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
];

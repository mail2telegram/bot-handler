<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pahanini\Monolog\Formatter\CliFormatter;
use Psr\Log\LoggerInterface;

return [
    'telegramToken' => 'XXX',
    'telegramLongPollingTimeout' => 2,
    'telegramUpdatesLimit' => 100, // 1-100
    'queue' => 'telegram_update',
    'redis' => [
        'host' => 'm2t_redis',
    ],
    'amqp' => [
        'host' => 'm2t_rabbitmq',
        'port' => '5672',
        'user' => 'guest',
        'pwd' => 'guest',
    ],
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR);
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
];

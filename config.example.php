<?php

use App\Model\Email;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pahanini\Monolog\Formatter\CliFormatter;
use Psr\Log\LoggerInterface;

return [
    'workerMemoryLimit' => 134_217_728, // 128MB
    'telegramToken' => 'XXX',
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
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR);
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
];

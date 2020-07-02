<?php

use M2T\App;
use M2T\Model\Email;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pahanini\Monolog\Formatter\CliFormatter;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;

return [
    'workerMemoryLimit' => 134_217_728, // 128MB
    'telegramToken' => 'XXX',
    'queueAmount' => 2,
    'queueExchange' => 'telegram_update',
    'queueRoutingKey' => '1',
    'testEmailPwd' => 'XXX',
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
    'shared' => [
        LoggerInterface::class,
    ],
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR);
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
    Redis::class => static function () {
        static $connect;
        if (null === $connect) {
            $connect = new Redis();
        }
        if (!$connect->isConnected() && !$connect->pconnect(App::get('redis')['host'])) {
            throw new RuntimeException('No Redis connection');
        }
        return $connect;
    },
    AMQPStreamConnection::class => static function () {
        static $connect;
        if (null === $connect) {
            $config = App::get('amqp');
            $connect = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['pwd']
            );
        }
        return $connect;
    },
    AMQPChannel::class => static function () {
        return App::get(AMQPStreamConnection::class)->channel();
    },
];

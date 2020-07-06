<?php

use M2T\App;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pahanini\Monolog\Formatter\CliFormatter;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;

return [
    'logLevel' => 'info', /** @see \Psr\Log\LogLevel */
    'workerMemoryLimit' => 134_217_728, // 128MB
    'telegramTimeout' => 5.0,
    'telegramMaxShowAtList' => 7,
    'queueAmount' => 1,
    'queueExchange' => 'telegram_update',
    'queueRoutingKey' => '1',
    'shared' => [
        LoggerInterface::class,
    ],
    LoggerInterface::class => static function () {
        $stream = new StreamHandler(STDERR, App::get('logLevel'));
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
    Redis::class => static function () {
        static $connect;
        if (null === $connect) {
            $connect = new Redis();
        }
        if (!$connect->isConnected()) {
            $config = App::get('redis');
            if (!$connect->pconnect(
                $config['host'],
                $config['port'] ?? 6379,
                $config['timeout'] ?? 0.0,
                $config['persistentId'] ?? null,
                $config['retryInterval'] ?? 0,
                $config['readTimeout'] ?? 0.0
            )) {
                throw new RedisException('No Redis connection');
            }
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

<?php

use M2T\Client\MailConfigClient;
use M2T\Client\MailConfigClientInterface;
use M2T\Interfaces\ICrypto;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Mqwerty\Crypto;
use pahanini\Monolog\Formatter\CliFormatter;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;

return [
    'logLevel' => 'info',
    'workerMemoryLimit' => 134_217_728, // 128MB
    'telegramTimeout' => 5.0,
    'telegramMaxShowAtList' => 7,
    'queueAmount' => 1,
    'queueExchange' => 'telegram_update',
    'queueRoutingKey' => '1',
    'shared' => [
        LoggerInterface::class,
        ICrypto::class,
    ],
    ICrypto::class => fn($c) => new Crypto($c->get('cryptoKey')),
    LoggerInterface::class => static function ($c) {
        $stream = new StreamHandler(STDERR, $c->get('logLevel'));
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },
    Redis::class => static function ($c) {
        static $connect;
        if (null === $connect) {
            $connect = new Redis();
        }
        if (!$connect->isConnected()) {
            $config = $c->get('redis');
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
    AMQPStreamConnection::class => static function ($c) {
        static $connect;
        if (null === $connect) {
            $config = $c->get('amqp');
            $connect = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['pwd']
            );
        }
        return $connect;
    },
    AMQPChannel::class => fn($c) => $c->get(AMQPStreamConnection::class)->channel(),
    MailConfigClientInterface::class => fn($c) => $c->get(MailConfigClient::class),
];

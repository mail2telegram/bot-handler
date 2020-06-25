<?php

namespace App;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

final class Worker
{
    private const MEMORY_LIMIT = 134_217_728; // 128MB

    private LoggerInterface $logger;
    private AMQPChannel $channel;

    public function __construct(
        LoggerInterface $logger,
        AMQPChannel $channel
    ) {
        $this->logger = $logger;
        $this->channel = $channel;

        $this->logger->info('Worker started');
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
    }

    public function signalHandler($signo): void
    {
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                if (!defined('TERMINATED')) {
                    define('TERMINATED', true);
                    $this->logger->info('Worker terminated signal');
                }
        }
    }

    public function loop(): void
    {
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(App::get('queue'), getmypid(), false, true, false, false, [$this, 'task']);
        while ($this->channel->is_consuming()) {
            if (defined('TERMINATED')) {
                break;
            }
            if (memory_get_usage(true) >= self::MEMORY_LIMIT) {
                $this->logger->warning('Worker out of memory');
                break;
            }
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->channel->wait();
        }
        $this->logger->info('Worker finished');
    }

    public function task(AMQPMessage $msg): void
    {
        try {
            $update = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);
            $this->logger->debug('Task:', $update);
            // @todo handle message
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
        }
    }
}

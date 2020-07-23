<?php

namespace M2T;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

final class Worker
{
    private LoggerInterface $logger;
    private AMQPChannel $channel;
    private QueueLocator $locator;
    private Handler $handler;
    private int $memoryLimit;

    public function __construct(
        LoggerInterface $logger,
        AMQPChannel $channel,
        QueueLocator $locator,
        Handler $handler
    ) {
        $this->logger = $logger;
        $this->channel = $channel;
        $this->locator = $locator;
        $this->handler = $handler;
        $this->memoryLimit = App::get('workerMemoryLimit');

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
        $this->channel->exchange_declare(App::get('queueExchange'), 'x-consistent-hash', false, true, false);

        $queue = $this->locator->lock();
        if (!$queue) {
            $this->logger->error('No available queue');
            return;
        }

        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->queue_bind($queue, App::get('queueExchange'), App::get('queueRoutingKey'));
        $this->channel->basic_consume($queue, '', false, true, false, false, [$this, 'task']);
        while ($this->channel->is_consuming()) {
            if (defined('TERMINATED')) {
                break;
            }
            if (memory_get_usage(true) >= $this->memoryLimit) {
                $this->logger->warning('Worker out of memory');
                break;
            }
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->channel->wait();
        }

        $this->locator->release($queue);
        $this->channel->queue_unbind($queue, App::get('queueExchange'), App::get('queueRoutingKey'));
        $this->logger->info('Worker finished');
    }

    public function task(AMQPMessage $msg): void
    {
        try {
            $update = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);
            $this->logger->debug('Update:', $update);
            $this->handler->handle($update);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
        }
    }
}

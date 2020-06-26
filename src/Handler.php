<?php

namespace App;

use App\Client\SmtpClient;
use App\Client\TelegramClient;
use Psr\Log\LoggerInterface;
use Throwable;

class Handler
{
    protected LoggerInterface $logger;
    protected TelegramClient $telegram;
    protected SmtpClient $mailer;

    protected const MSG_REGISTER = 'Напишите email и пароль через пробел:';

    public function __construct(LoggerInterface $logger, TelegramClient $telegram, SmtpClient $mailer)
    {
        $this->logger = $logger;
        $this->telegram = $telegram;
        $this->mailer = $mailer;
    }

    public function handle(array $update): void
    {
        // @todo handle updates here

        if (isset($update['message'])) {
            $msg = &$update['message'];
            $chatId = &$update['message']['chat']['id'];

            // @todo draft register.step.0
            if (isset($msg['text']) && $msg['text'] === '/register') {
                /** @noinspection JsonEncodingApiUsageInspection */
                $this->telegram->sendMessage(
                    $chatId,
                    static::MSG_REGISTER,
                    json_encode(['force_reply' => true])
                );
                return;
            }

            // @todo draft register.step.1
            if (
                isset($msg['reply_to_message'], $msg['text'])
                && $msg['reply_to_message']['from']['is_bot'] === true
                && $msg['reply_to_message']['text'] === static::MSG_REGISTER
            ) {
                // @todo validate, get imap and smtp host/port, save to redis
                $accountData = $msg['text'];
                $this->logger->debug('$accountData: ' . $accountData);
                $this->telegram->deleteMessage($chatId, $msg['message_id']);
                $this->telegram->sendMessage($chatId, 'Принято!');
            }

            // @todo draft reply to mail
            if (
                isset($msg['reply_to_message'], $msg['text'])
                && $msg['reply_to_message']['from']['is_bot'] === true
                // && is reply to mail
            ) {
                try {
                    $account = App::get('test')['emails'][0];
                    $to = App::get('test')['mailTo'];
                    $result = $this->mailer->send($account, $to, 'Test mail from M2T', $msg['text']);
                } catch (Throwable $e) {
                    $this->logger->error((string) $e);
                    $result = false;
                }
                $this->telegram->sendMessage($chatId, $result ? 'Отправлено' : 'Ошибка');
                return;
            }
        }

        // @todo draft callback
        if (isset($update['callback_query']['data']) && ['callback_query']['data'] === 'Cancel') {
            // do something
            return;
        }
    }
}

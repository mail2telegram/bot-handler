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

    protected const MSG_REGISTER = "Шутка :-)\nТолько email, логин и пароль.\nВведи их через запятую:";

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
                $this->telegram->sendMessage($chatId, 'Мне нужна твоя одежда и мотоцикл!');
                sleep(1);
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
                $accountData = $msg['text'];
                // @todo validate, get imap and smtp host/port, save to redis
                $this->logger->debug('$accountData: ' . $accountData);
                $this->telegram->sendMessage($chatId, 'Принято!');
                $this->telegram->deleteMessage($chatId, $msg['message_id']);
            }

            // @todo draft reply to mail
            if (
                isset($msg['reply_to_message'], $msg['text'])
                && $msg['reply_to_message']['from']['is_bot'] === true
                // && is reply to mail
            ) {
                try {
                    $result = $this->mailer->draftSend($msg['text']);
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

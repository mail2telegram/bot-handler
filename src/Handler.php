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

    public function __construct(LoggerInterface $logger, TelegramClient $telegram, SmtpClient $mailer)
    {
        $this->logger = $logger;
        $this->telegram = $telegram;
        $this->mailer = $mailer;
    }

    public function handle(array $update): void
    {
        // @todo handle updates here

        if (isset($update['message']['text']) && $update['message']['text'] === '/register') {
            $this->draftRegister($update);
            return;
        }

        if (isset($update['message']['reply_to_message'], $update['message']['text'])) {
            try {
                $result = $this->mailer->draftSend($update['message']['text']);
            } catch (Throwable $e) {
                $this->logger->error((string) $e);
                $result = false;
            }
            $chatId = $update['message']['chat']['id'];
            $this->telegram->sendMessage($chatId, $result ? 'Отправлено' : 'Ошибка');
            return;
        }

        if (isset($update['callback_query']['data']) && ['callback_query']['data'] === 'Cancel') {
            // do something
            return;
        }
    }

    public function draftRegister(array $update): void
    {
        $chatId = $update['message']['chat']['id'];
        $this->telegram->sendMessage($chatId, 'Мне нужна твоя одежда и мотоцикл!');
        sleep(1);
        $this->telegram->sendMessage($chatId, "Шутка :-)\nТолько email, логин и пароль");
    }
}

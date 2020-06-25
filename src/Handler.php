<?php

namespace App;

use App\Client\TelegramClient;
use Psr\Log\LoggerInterface;

class Handler
{
    protected LoggerInterface $logger;
    private TelegramClient $telegram;

    public function __construct(LoggerInterface $logger, TelegramClient $telegram)
    {
        $this->logger = $logger;
        $this->telegram = $telegram;
    }

    public function handle(array $update): void
    {
        // @todo handle updates here

        if (isset($update['message']['text']) && $update['message']['text'] === '/register') {
            $this->draftRegister($update);
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

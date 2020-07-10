<?php

namespace M2T\Controller;

use M2T\Client\TelegramClient;
use M2T\State;

class Help
{
    protected State $state;
    protected TelegramClient $messenger;

    public function __construct(State $state, TelegramClient $messenger)
    {
        $this->state = $state;
        $this->messenger = $messenger;
    }

    public function actionIndex(): void
    {
        $msg = '<b>Добрый день!</b>' . PHP_EOL .
            'Вы можете:' . PHP_EOL .
            '/register - Зарегистрировать email' . PHP_EOL .
            '/send - Отправить письмо' . PHP_EOL .
            '/edit - Редактировать настройки ящика' . PHP_EOL .
            '/delete - Удалить ящик' . PHP_EOL .
            '/list - Отобразить список зарегистрированных ящиков' . PHP_EOL .
            '/help - Показать это сообщение' . PHP_EOL;
        $this->messenger->sendMessage($this->state->chatId, $msg);
    }
}

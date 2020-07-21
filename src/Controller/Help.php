<?php

namespace M2T\Controller;

use M2T\Client\TelegramClient;
use M2T\State;

class Help
{
    protected const MSG = <<<'HELP'
        <b>Добрый день!</b>
        Вы можете:
        /register - Зарегистрировать email
        /send - Отправить письмо
        /edit - Редактировать настройки ящика
        /delete - Удалить ящик
        /list - Отобразить список зарегистрированных ящиков
        /help - Показать это сообщение
        HELP;

    protected State $state;
    protected TelegramClient $messenger;

    public function __construct(State $state, TelegramClient $messenger)
    {
        $this->state = $state;
        $this->messenger = $messenger;
    }

    public function actionIndex(): void
    {
        $this->messenger->sendMessage($this->state->chatId, static::MSG);
    }
}

<?php

namespace M2T\Controller;

use M2T\Client\MessengerInterface;

class Help
{
    protected int $chatId;
    protected MessengerInterface $messenger;

    public function __construct(int $chatId, MessengerInterface $messenger)
    {
        $this->chatId = $chatId;
        $this->messenger = $messenger;
    }

    public function actionIndex(): void
    {
        $msg = '<b>Добрый день!</b>' . PHP_EOL .
            'Вы можете:' . PHP_EOL .
            '/register Зарегистрировать email' . PHP_EOL .
            '/send Отправить письмо' . PHP_EOL .
            '/edit Редактировать настройки ящика' . PHP_EOL .
            '/delete Удалить ящик' . PHP_EOL .
            '/list Отобразить список зарегистрированных ящиков' . PHP_EOL .
            '/help Показать это сообщение' . PHP_EOL;
        $this->messenger->sendMessage($this->chatId, $msg);
    }
}

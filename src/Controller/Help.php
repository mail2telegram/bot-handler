<?php

namespace M2T\Controller;

class Help extends Base
{
    public function actionIndex(): string
    {
        $this->messenger->sendMessage(
            $this->account->chatId,
            '<b>Добрый день!</b>' . PHP_EOL .
            'Вы можете:' . PHP_EOL .
            '/register Зарегистрировать email' . PHP_EOL .
            '/send Отправить письмо' . PHP_EOL .
            '/edit Редактировать настройки ящика' . PHP_EOL .
            '/delete Удалить ящик' . PHP_EOL .
            '/list Отобразить список зарегистрированных ящиков' . PHP_EOL .
            '/help  Показать это сообщение' . PHP_EOL
        );
        return 'help:success';
    }
}

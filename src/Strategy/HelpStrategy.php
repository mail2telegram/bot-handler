<?php


namespace M2T\Strategy;

use M2T\Client\SmtpClient;
use M2T\Client\TelegramClient;
use Psr\Log\LoggerInterface;
use Throwable;

class HelpStrategy extends BaseStrategy implements StrategyInterface
{

    protected function actionIndex(): string
    {
        $this->messenger->sendMessage(
            $this->chatId,
            '<b>Добрый день!</b>'.PHP_EOL.
            'Вы можете:'.PHP_EOL.
            '/register Зарегистрировать email'.PHP_EOL.
            '/send Отправить письмо'.PHP_EOL.
            '/edit Редактировать настройки ящика'.PHP_EOL.
            '/delete Удалить ящик'.PHP_EOL.
            '/list Отобразить список зарегистрированных ящиков'.PHP_EOL.
            '/help  Показать это сообщение'.PHP_EOL
        );
        return 'help:success';
    }


}

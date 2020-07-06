<?php

namespace M2T\Controller;

class MailboxList extends Base
{
    public const MSG_YOUR_LIST = '<b>Список ваших email:</b>';
    public const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';

    public function actionIndex(): string
    {
        $list = array_map(fn($email) => $email->email, $this->account->emails);
        $msg = count($list)
            ? static::MSG_YOUR_LIST . PHP_EOL . implode(PHP_EOL, $list)
            : static::MSG_YOUR_LIST . PHP_EOL . static::MSG_EMPTY_LIST;
        $this->messenger->sendMessage($this->account->chatId, $msg);
        return 'list:complete';
    }
}

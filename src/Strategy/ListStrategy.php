<?php


namespace M2T\Strategy;


class ListStrategy extends BaseStrategy implements StrategyInterface
{
    public const MSG_YOUR_LIST = '<b>Лист ваших email:</b>';
    public const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';

    protected function actionIndex(): string
    {
        $list = [];
        foreach ($this->account->emails as $key => $email) {
            $list[] = $email->email;
        }

        if (count($list) > 0) {
            $msg = static::MSG_YOUR_LIST . PHP_EOL . implode(PHP_EOL, $list);
        } else {
            $msg = static::MSG_YOUR_LIST . PHP_EOL . static::MSG_EMPTY_LIST;
        }

        $this->messenger->sendMessage($this->chatId, $msg);

        return 'list:complete';
    }

}

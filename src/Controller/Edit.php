<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\App;

class Edit extends Base
{
    public const MSG_CHOOSE_EMAIL = 'Выберите email для редактирования, или введите если нет в списке';
    public const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';
    public const MSG_EMAIL_NOT_FOUND = 'Email %email% не найден в вашем списке';
    public const MSG_CONFIRM_RUN = 'Вы видите текущие настройки. Отредактировать?';
    public const MSG_YES = 'Да';
    public const MSG_YES_RUN_EDIT = 'Да, редактировать';
    public const MSG_NO = 'Нет';

    public function actionIndex(): string
    {
        $list = [];
        foreach ($this->account->emails as $key => $email) {
            if ($key >= App::get('telegramMaxShowAtList')) {
                break;
            }
            $list[] = [$email->email];
        }

        if (count($this->account->emails) > 0) {
            $this->messenger->sendMessage(
                $this->account->chatId,
                static::MSG_CHOOSE_EMAIL,
                json_encode(
                    [
                        'keyboard' => $list,
                        'one_time_keyboard' => true,
                    ]
                )
            );
            return 'edit:emailChoosed';
        }

        $msg = static::MSG_CHOOSE_EMAIL . PHP_EOL . static::MSG_EMPTY_LIST;
        $this->messenger->sendMessage($this->account->chatId, $msg);
        return 'edit:cancel';
    }

    public function actionShowCurrentSettings(array $update): string
    {
        $msg = &$update['message'];
        $emailString = trim($msg['text']);

        $email = $this->accountManager->getEmail($this->account, $emailString);
        if ($email === null) {
            $this->messenger->sendMessage(
                $this->account->chatId,
                str_replace('%email%', $emailString, static::MSG_EMAIL_NOT_FOUND),
            );
            // @todo правильно ли это для Edit?
            return 'delete:error';
        }

        $email->selected = true;

        $this->messenger->sendMessage(
            $this->account->chatId,
            static::MSG_CONFIRM_RUN . PHP_EOL . $email->getSettings(),
            json_encode(
                [
                    'keyboard' => [[static::MSG_YES_RUN_EDIT], [static::MSG_NO],],
                    'one_time_keyboard' => true,
                ]
            )
        );
        return 'edit:runEdit';
    }
}

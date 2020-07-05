<?php


namespace M2T\Strategy;

use M2T\App;

class DeleteStrategy extends BaseStrategy implements StrategyInterface
{
    public const MSG_CHOOSE_EMAIL = 'Выберите какой email удалить, или введите если нет в списке:';
    public const MSG_EMAIL_NOT_FOUND = 'Email %email% не найден в вашем списке';
    public const MSG_BTN_CONFIRM_DELETE = 'Действительно удалить %email%?';
    public const MSG_DELETE_CANCELED = 'Отменено!';
    public const MSG_BTN_CONFIRMED = 'Да, удалить %email%';
    public const MSG_BTN_NOT_CONFIRMED = 'Нет';
    public const MSG_DELETE_COMPLETE = 'Удалено';
    public const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';

    protected function actionIndex(): string
    {
        $list = [];
        foreach ($this->account->emails as $key => $email) {
            if ($key >= App::get('telegramMaxShowAtList')) break;
            $list[] = [$email->email];
        }

        if (count($this->account->emails) > 0) {
            $this->messenger->sendMessage($this->chatId,
                static::MSG_CHOOSE_EMAIL,
                json_encode(['keyboard' => $list,
                    'one_time_keyboard' => true
                ])
            );
            return 'delete:emailChoosed';
        } else {
            $msg = static::MSG_CHOOSE_EMAIL . PHP_EOL . static::MSG_EMPTY_LIST;
            $this->messenger->sendMessage($this->chatId, $msg);
            return 'delete:cancel';
        }
    }


    protected function actionCheckAndConfirm(): string
    {
        $msg = &$this->incomingData['message'];
        $emailString = trim($msg['text']);


        if (!$this->accountManager->checkExistEmail($this->account, $emailString)) {
            return $this->sendErrorEmailNotFound($emailString);
        }

        $this->messenger->sendMessage($this->chatId,
            str_replace('%email%', $emailString, static::MSG_BTN_CONFIRM_DELETE),
            json_encode(['keyboard' => [
                [str_replace('%email%', $emailString, static::MSG_BTN_CONFIRMED)],
                [static::MSG_BTN_NOT_CONFIRMED],],
                'one_time_keyboard' => true
            ])
        );
        return 'delete:confirmationRequested';
    }

    protected function actionDelete(): string
    {
        $msg = &$this->incomingData['message'];
        $emailString = $msg['text'];

        if (!isset($msg['entities'][0]) || $msg['entities'][0]['type'] != 'email') {
            return $this->sendErrorEmailNotFound();
        }

        $emailString = mb_substr($emailString, $msg['entities'][0]['offset'], $msg['entities'][0]['length']);


        $email = $this->accountManager->getEmail($this->account, $emailString);
        if ($email == null || !$this->accountManager->deleteEmail($this->account, $email->email)) {
            return $this->sendErrorEmailNotFound($emailString);
        }

        $this->messenger->sendMessage($this->chatId,
            static::MSG_DELETE_COMPLETE
        );

        return 'delete:complete';
    }

    protected function actionCanceled(): string
    {
        $this->messenger->sendMessage($this->chatId,
            static::MSG_DELETE_CANCELED
        );
        return 'delete:canceled';
    }

    public function sendErrorEmailNotFound($emailString = ''): string
    {
        $this->messenger->sendMessage($this->chatId,
            str_replace('%email%', $emailString, static::MSG_EMAIL_NOT_FOUND),
        );
        return 'delete:error';
    }

}

<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\App;

class MailboxDelete extends Base
{
    protected const MSG_CHOOSE_EMAIL = 'Выберите какой email удалить, или введите если нет в списке:';
    protected const MSG_EMAIL_NOT_FOUND = 'Email %email% не найден в вашем списке';
    protected const MSG_BTN_CONFIRM_DELETE = 'Действительно удалить %email%?';
    protected const MSG_DELETE_CANCELED = 'Отменено!';
    protected const MSG_BTN_CONFIRMED = 'Да, удалить %email%';
    protected const MSG_BTN_NOT_CONFIRMED = 'Нет';
    protected const MSG_DELETE_COMPLETE = 'Удалено';

    protected const ACTION_CHECK = 'actionCheck';
    protected const ACTION_DELETE = 'actionDelete';

    public function actionIndex(): void
    {
        $account = $this->accountManager->load($this->state->chatId);
        if (!$account || !$account->emails) {
            $this->messenger->sendMessage($this->state->chatId, static::MSG_EMPTY_LIST);
            return;
        }

        $list = [];
        foreach ($account->emails as $key => $email) {
            if ($key >= App::get('telegramMaxShowAtList')) {
                break;
            }
            $list[] = [$email->email];
        }

        $this->messenger->sendMessage(
            $this->state->chatId,
            static::MSG_CHOOSE_EMAIL,
            json_encode(
                [
                    'keyboard' => $list,
                    'one_time_keyboard' => true,
                ]
            )
        );
        $this->setState(static::ACTION_CHECK);
    }

    public function actionCheck(array $update): void
    {
        $msg = &$update['message'];
        $emailString = $msg['text'];
        $account = $this->accountManager->load($this->state->chatId);

        if (!$account || !$account->emails || !$this->accountManager->mailboxExist($account, $emailString)) {
            $this->sendErrorEmailNotFound($emailString);
            return;
        }

        $this->messenger->sendMessage(
            $this->state->chatId,
            str_replace('%email%', $emailString, static::MSG_BTN_CONFIRM_DELETE),
            json_encode(
                [
                    'keyboard' => [
                        [str_replace('%email%', $emailString, static::MSG_BTN_CONFIRMED)],
                        [static::MSG_BTN_NOT_CONFIRMED],
                    ],
                    'one_time_keyboard' => true,
                ]
            )
        );
        $this->setState(static::ACTION_DELETE);
    }

    public function actionDelete(array $update): void
    {
        $msg = &$update['message'];
        $emailString = $msg['text'];

        if ($emailString === static::MSG_BTN_NOT_CONFIRMED) {
            $this->messenger->sendMessage($this->state->chatId, static::MSG_DELETE_CANCELED);
            return;
        }

        $account = $this->accountManager->load($this->state->chatId);
        if (!$account || !$account->emails || !isset($msg['entities'][0]) || $msg['entities'][0]['type'] !== 'email') {
            $this->sendErrorEmailNotFound();
            return;
        }

        $emailString = mb_substr($emailString, $msg['entities'][0]['offset'], $msg['entities'][0]['length']);
        $email = $this->accountManager->mailboxGet($account, $emailString);
        if ($email === null || !$this->accountManager->mailboxDelete($account, $email->email)) {
            $this->sendErrorEmailNotFound($emailString);
            return;
        }

        $this->messenger->sendMessage(
            $this->state->chatId,
            static::MSG_DELETE_COMPLETE
        );
    }

    protected function sendErrorEmailNotFound($emailString = ''): void
    {
        $this->messenger->sendMessage(
            $this->state->chatId,
            str_replace('%email%', $emailString, static::MSG_EMAIL_NOT_FOUND),
        );
    }
}

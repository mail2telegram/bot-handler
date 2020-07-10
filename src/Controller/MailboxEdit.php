<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

class MailboxEdit extends BaseMailbox
{
    protected const MSG_CHOOSE_EMAIL = 'Выберите email для редактирования, или введите если нет в списке';
    protected const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';
    protected const MSG_EMAIL_NOT_FOUND = 'Email %email% не найден в вашем списке';
    protected const MSG_CONFIRM_RUN = 'Вы видите текущие настройки. Отредактировать?';
    protected const MSG_YES = 'Да';
    protected const MSG_YES_RUN_EDIT = 'Да, редактировать';
    public const MSG_NO = 'Нет';

    protected const ACTION_SHOW_CURRENT_SETTINGS = 'actionShowCurrentSettings';

    public function actionIndex(): void
    {
        if (!$account = $this->getAccountOrReply()) {
            return;
        }
        $this->replyChooseEmail($account);
        $this->setState(static::ACTION_SHOW_CURRENT_SETTINGS);
    }

    public function actionShowCurrentSettings(array $update): void
    {
        $msg = &$update['message'];
        $emailString = $msg['text'];

        $account = $this->accountManager->load($this->state->chatId);
        if (!$account || !$account->emails || !$mailbox = $this->accountManager->mailboxGet($account, $emailString)) {
            $this->messenger->sendMessage(
                $this->state->chatId,
                str_replace('%email%', $emailString, static::MSG_EMAIL_NOT_FOUND),
            );
            return;
        }

        $this->messenger->sendMessage(
            $this->state->chatId,
            static::MSG_CONFIRM_RUN . PHP_EOL . $mailbox->getSettings(),
            json_encode(
                [
                    'keyboard' => [[static::MSG_YES_RUN_EDIT], [static::MSG_NO],],
                    'one_time_keyboard' => true,
                ]
            )
        );

        $this->state->mailbox = $mailbox;
        $this->setState(MailboxAdd::ACTION_REQUEST_IMAP_HOST, MailboxAdd::class);
    }
}

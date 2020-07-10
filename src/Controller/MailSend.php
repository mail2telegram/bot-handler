<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\App;
use M2T\Model\DraftEmail;

class MailSend extends BaseMail
{
    protected const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';
    protected const MSG_CHOOSE_EMAIL = 'Выберите email с которого будем отправлять или введите если не отображен';
    protected const MSG_INSERT_TITLE = 'Введите заголовок:';
    protected const MSG_INSERT_TO = 'Укажите кому:';
    protected const MSG_INSERT_MESSAGE = 'Введите текст сообщения:';
    protected const MSG_ERROR = 'Произошла ошибка во время отправки';

    protected const ACTION_INSERT_TITLE = 'actionInsertTitle';
    protected const ACTION_INSERT_TO = 'actionInsertTo';
    protected const ACTION_INSERT_MESSAGE = 'actionInsertMessage';
    protected const ACTION_SEND = 'actionSend';

    public function actionIndex(): void
    {
        if (!$account = $this->getAccountOrReply()) {
            return;
        }

        $list = [];
        foreach ($account->emails as $key => $email) {
            if ($key >= App::get('telegramMaxShowAtList')) {
                break;
            }
            $list[] = [$email->email];
        }

        if (count($account->emails) === 1) {
            $this->state->draftEmail = new DraftEmail();
            $this->state->draftEmail->from = $account->emails[0]->email;
            $this->sendInsertTitleDialog();
            return;
        }

        if (count($account->emails)) {
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
            $this->setState(static::ACTION_INSERT_TITLE);
            return;
        }

        $msg = static::MSG_CHOOSE_EMAIL . PHP_EOL . static::MSG_EMPTY_LIST;
        $this->messenger->sendMessage($this->state->chatId, $msg);
    }

    public function actionInsertTitle(array $update): void
    {
        $account = $this->accountManager->load($this->state->chatId);
        if (!$account) {
            $this->replyError();
            return;
        }

        $mailboxString = &$update['message']['text'];
        $mailbox = $this->accountManager->mailboxGet($account, $mailboxString);
        if (!$mailbox) {
            $this->replyError();
            return;
        }

        $this->state->draftEmail = new DraftEmail();
        $this->state->draftEmail->from = $mailbox->email;

        $this->sendInsertTitleDialog();
    }

    public function actionInsertTo($update): void
    {
        $this->state->draftEmail->subject = &$update['message']['text'];
        $this->messenger->sendMessage($this->state->chatId, static::MSG_INSERT_TO);
        $this->setState(static::ACTION_INSERT_MESSAGE);
    }

    public function actionInsertMessage($update): void
    {
        $this->state->draftEmail->to = &$update['message']['text'];
        $this->messenger->sendMessage($this->state->chatId, static::MSG_INSERT_MESSAGE);
        $this->setState(static::ACTION_SEND);
    }

    public function actionSend($update): void
    {
        $account = $this->accountManager->load($this->state->chatId);
        if (!$account) {
            $this->replyError();
            return;
        }

        $mailbox = $this->accountManager->mailboxGet($account, $this->state->draftEmail->from);
        if ($mailbox === null) {
            $this->replyError();
            return;
        }

        $message = '';
        $attachment = [];
        $this->parseMessageAndAttachment($update, $message, $attachment);

        $subject = $this->state->draftEmail->subject;
        $to = $this->state->draftEmail->to;
        $this->state->draftEmail = null;

        $this->send($mailbox, $to, $subject, $message, $attachment);
    }

    protected function sendInsertTitleDialog(): void
    {
        $this->messenger->sendMessage($this->state->chatId, static::MSG_INSERT_TITLE);
        $this->setState(static::ACTION_INSERT_TO);
    }
}

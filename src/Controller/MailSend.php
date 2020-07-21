<?php

namespace M2T\Controller;

use M2T\App;
use M2T\Model\Email;

class MailSend extends BaseMail
{
    protected const MSG_CHOOSE_EMAIL = 'Выберите email с которого будем отправлять или введите если не отображен';
    protected const MSG_INSERT_SUBJECT = 'Введите заголовок:';
    protected const MSG_INSERT_TO = 'Укажите кому:';
    protected const MSG_INSERT_MESSAGE = 'Введите текст сообщения:';
    protected const MSG_ERROR = 'Произошла ошибка во время отправки';
    protected const MSG_INCORRECT_EMAIL = 'Вы ввели некорректный email. Укажите кому:';

    protected const ACTION_INSERT_EMAIL_ACCOUNT = 'actionInsertEmailAccount';
    protected const ACTION_INSERT_TO = 'actionInsertTo';
    protected const ACTION_INSERT_SUBJECT = 'actionInsertSubject';
    public const ACTION_INSERT_MSG_AND_SEND = 'actionSend';

    public function actionIndex(): void
    {
        if (!$account = $this->getAccountOrReply()) {
            return;
        }

        if (count($account->emails) === 1) {
            $this->state->draftEmail = new Email();
            $this->state->draftEmail->from = $account->emails[0]->email;
            $this->messenger->sendMessage($this->state->chatId, static::MSG_INSERT_TO);
            $this->setState(static::ACTION_INSERT_TO);
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
            [
                'keyboard' => $list,
                'one_time_keyboard' => true,
            ]
        );
        $this->setState(static::ACTION_INSERT_EMAIL_ACCOUNT);
    }

    public function actionInsertEmailAccount(array $update): void
    {
        $account = $this->accountManager->load($this->state->chatId);
        if (!$account) {
            $this->replyError();
            return;
        }

        $mailbox = $this->accountManager->mailboxGet($account, $update['message']['text']);
        if (!$mailbox) {
            $this->replyError();
            return;
        }

        $this->state->draftEmail = new Email();
        $this->state->draftEmail->from = $mailbox->email;

        $this->messenger->sendMessage($this->state->chatId, static::MSG_INSERT_TO);
        $this->setState(static::ACTION_INSERT_TO);
    }

    public function actionInsertTo(array $update): void
    {
        if (!filter_var($update['message']['text'], FILTER_VALIDATE_EMAIL)) {
            $this->messenger->sendMessage($this->state->chatId, static::MSG_INCORRECT_EMAIL);
            $this->setState(static::ACTION_INSERT_TO);
            return;
        }
        $this->state->draftEmail->to = [['address' => $update['message']['text']]];
        $this->messenger->sendMessage($this->state->chatId, static::MSG_INSERT_SUBJECT);
        $this->setState(static::ACTION_INSERT_SUBJECT);
    }

    public function actionInsertSubject(array $update): void
    {
        $this->state->draftEmail->subject = $update['message']['text'];
        $this->messenger->sendMessage($this->state->chatId, static::MSG_INSERT_MESSAGE);
        $this->setState(static::ACTION_INSERT_MSG_AND_SEND);
    }

    public function actionSend(array $update): void
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

        $this->parseMessageAndAttachment($update, $this->state->draftEmail);
        $this->send($mailbox, $this->state->draftEmail);
        $this->state->draftEmail = null;
    }
}

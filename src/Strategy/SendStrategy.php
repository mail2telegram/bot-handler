<?php

namespace M2T\Strategy;

use M2T\App;
use M2T\Client\ImapClient;
use M2T\Client\SmtpClient;
use M2T\Model\DraftEmail;

class SendStrategy extends BaseStrategy
{
    public const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';
    public const MSG_CHOOSE_EMAIL = 'Выберите email с которого будем отправлять или введите если не отображен';
    public const MSG_INSERT_TITLE = 'Введите заголовок:';
    public const MSG_INSERT_TO = 'Укажите кому:';
    public const MSG_INSERT_MESSAGE = 'Введите текст сообщения:';
    public const MSG_ERROR = 'Произошла ошибка во время отправки';

    protected function actionIndex(): string
    {
        $list = [];
        foreach ($this->account->emails as $key => $email) {
            if ($key >= App::get('telegramMaxShowAtList')) {
                break;
            }
            $list[] = [$email->email];
        }

        if (count($this->account->emails) == 1) {
            $this->account->draftEmail = new DraftEmail();
            $this->account->draftEmail->from = $this->account->emails[0]->email;
            return $this->sendInsertTitleDialog();
        } elseif (count($this->account->emails) > 0) {
            $this->messenger->sendMessage(
                $this->chatId,
                static::MSG_CHOOSE_EMAIL,
                json_encode(
                    [
                        'keyboard' => $list,
                        'one_time_keyboard' => true,
                    ]
                )
            );
            return 'send:emailChoosed';
        } else {
            $msg = static::MSG_CHOOSE_EMAIL . PHP_EOL . static::MSG_EMPTY_LIST;
            $this->messenger->sendMessage($this->chatId, $msg);
            return 'send:cancel';
        }
    }

    protected function actionInsertTitle(): string
    {
        $mailboxString = &$this->incomingData['message']['text'];
        $mailbox = $this->accountManager->getEmail($this->account, $mailboxString);
        if ($mailbox == null) {
            return $this->sendErrorHasOccurred();
        }

        $this->account->draftEmail = new DraftEmail();
        $this->account->draftEmail->from = $mailbox->email;

        return $this->sendInsertTitleDialog();
    }

    protected function actionInsertTo(): string
    {
        $this->account->draftEmail->subject = &$this->incomingData['message']['text'];
        $this->messenger->sendMessage($this->chatId, static::MSG_INSERT_TO);
        return 'send:toInserted';
    }

    protected function actionInsertMessage(): string
    {
        $this->account->draftEmail->to = &$this->incomingData['message']['text'];

        $this->messenger->sendMessage($this->chatId, static::MSG_INSERT_MESSAGE);
        return 'send:messageInserted';
    }

    protected function actionSend(): string
    {
        try {
            $mailbox = $this->accountManager->getEmail($this->account, $this->account->draftEmail->from);
            if ($mailbox == null) {
                return $this->sendErrorHasOccurred();
            }

            $msg = &$this->incomingData['message']['text'];
            $subject = $this->account->draftEmail->subject;
            $to = $this->account->draftEmail->to;

            /** @var SmtpClient $mailer */
            $mailer = App::get(SmtpClient::class);
            $result = $mailer->send($mailbox, $to, $subject, $msg);
            App::get(ImapClient::class)->appendToSent($mailbox, $to, $subject, $msg);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            return $this->sendErrorHasOccurred();
        }
        $this->messenger->sendMessage($this->chatId, $result ? 'Отправлено' : 'Ошибка');

        return 'send:complete';
    }

    protected function sendInsertTitleDialog(): string
    {
        $this->messenger->sendMessage($this->chatId, static::MSG_INSERT_TITLE);
        return 'send:titleInserted';
    }

    public function sendErrorHasOccurred($emailString = ''): string
    {
        $this->messenger->sendMessage(
            $this->chatId,
            static::MSG_ERROR,
        );
        return 'send:error';
    }

}

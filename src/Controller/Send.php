<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\App;
use M2T\Client\ImapClient;
use M2T\Client\SmtpClient;
use M2T\Model\DraftEmail;
use Throwable;

class Send extends Base
{
    public const MSG_EMPTY_LIST = 'Не добавлено пока ни одного';
    public const MSG_CHOOSE_EMAIL = 'Выберите email с которого будем отправлять или введите если не отображен';
    public const MSG_INSERT_TITLE = 'Введите заголовок:';
    public const MSG_INSERT_TO = 'Укажите кому:';
    public const MSG_INSERT_MESSAGE = 'Введите текст сообщения:';
    public const MSG_ERROR = 'Произошла ошибка во время отправки';

    public function actionIndex(): string
    {
        $list = [];
        foreach ($this->account->emails as $key => $email) {
            if ($key >= App::get('telegramMaxShowAtList')) {
                break;
            }
            $list[] = [$email->email];
        }

        if (count($this->account->emails) === 1) {
            $this->account->draftEmail = new DraftEmail();
            $this->account->draftEmail->from = $this->account->emails[0]->email;
            return $this->sendInsertTitleDialog();
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
            return 'send:emailChoosed';
        }

        $msg = static::MSG_CHOOSE_EMAIL . PHP_EOL . static::MSG_EMPTY_LIST;
        $this->messenger->sendMessage($this->account->chatId, $msg);
        return 'send:cancel';
    }

    public function actionInsertTitle(array $update): string
    {
        $mailboxString = &$update['message']['text'];
        $mailbox = $this->accountManager->getEmail($this->account, $mailboxString);
        if ($mailbox === null) {
            return $this->sendErrorHasOccurred();
        }

        $this->account->draftEmail = new DraftEmail();
        $this->account->draftEmail->from = $mailbox->email;

        return $this->sendInsertTitleDialog();
    }

    public function actionInsertTo($update): string
    {
        $this->account->draftEmail->subject = &$update['message']['text'];
        $this->messenger->sendMessage($this->account->chatId, static::MSG_INSERT_TO);
        return 'send:toInserted';
    }

    public function actionInsertMessage($update): string
    {
        $this->account->draftEmail->to = &$update['message']['text'];

        $this->messenger->sendMessage($this->account->chatId, static::MSG_INSERT_MESSAGE);
        return 'send:messageInserted';
    }

    public function actionSend($update): string
    {
        try {
            $mailbox = $this->accountManager->getEmail($this->account, $this->account->draftEmail->from);
            if ($mailbox === null) {
                return $this->sendErrorHasOccurred();
            }

            $msg = &$update['message']['text'];
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
        $this->messenger->sendMessage($this->account->chatId, $result ? 'Отправлено' : 'Ошибка');

        return 'send:complete';
    }

    protected function sendInsertTitleDialog(): string
    {
        $this->messenger->sendMessage($this->account->chatId, static::MSG_INSERT_TITLE);
        return 'send:titleInserted';
    }

    protected function sendErrorHasOccurred(): string
    {
        $this->messenger->sendMessage(
            $this->account->chatId,
            static::MSG_ERROR,
        );
        return 'send:error';
    }
}

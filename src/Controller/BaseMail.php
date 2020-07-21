<?php

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\Client\ImapClient;
use M2T\Client\SmtpClient;
use M2T\Client\TelegramClient;
use M2T\Model\Attachment;
use M2T\Model\Email;
use M2T\Model\Mailbox;
use M2T\State;

abstract class BaseMail extends BaseMailbox
{
    protected const MSG_SENT = 'Отправлено';

    protected State $state;
    protected TelegramClient $messenger;
    protected AccountManager $accountManager;
    protected SmtpClient $smtpClient;
    protected ImapClient $imapClient;

    /** @noinspection MagicMethodsValidityInspection PhpMissingParentConstructorInspection */
    public function __construct(
        State $state,
        TelegramClient $messenger,
        AccountManager $accountManager,
        SmtpClient $smtpClient,
        ImapClient $imapClient
    ) {
        $this->state = $state;
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
        $this->smtpClient = $smtpClient;
        $this->imapClient = $imapClient;
    }

    protected function send(Mailbox $mailAccount, Email $email): bool
    {
        $result = $this->smtpClient->send($mailAccount, $email);
        if (!$result) {
            $this->replyError();
            return false;
        }

        // Gmail при отправке по SMTP сам добавляет письмо в отправленные.
        // А Яндекс нет. В крайнем случае будет добавлено дважды.
        if ($mailAccount->smtpHost !== 'smtp.gmail.com') {
            $this->imapClient->appendToSent($mailAccount, $email);
        }

        $this->messenger->sendMessage($this->state->chatId, static::MSG_SENT);
        return true;
    }

    protected function parseMessageAndAttachment(array $update, Email $email): void
    {
        $email->message = $update['message']['text'] ?? '';
        $email->message = $update['message']['caption'] ?? $email->message;

        $fileName = '';
        $fileContent = '';
        $fileId = '';
        $action = '';

        if (isset($update['message']['document'])) {
            $fileId = $update['message']['document']['file_id'];
            $action = 'upload_document';
        } elseif (isset($update['message']['photo'])) {
            $fileId = $update['message']['photo'][1]['file_id'];
            $action = 'upload_photo';
        } elseif (isset($update['message']['video'])) {
            $fileId = $update['message']['video']['file_id'];
            $action = 'upload_video';
        } elseif (isset($update['message']['audio'])) {
            $fileId = $update['message']['audio']['file_id'];
            $action = 'upload_audio';
        }

        if ($action) {
            $this->messenger->sendChatAction($this->state->chatId, $action);
            if (!$this->messenger->getFile($fileId, $fileName, $fileContent)) {
                $this->replyError();
                return;
            }
            if ($fileContent) {
                $email->attachment = new Attachment($fileName, $fileContent);
            }
        }
    }
}

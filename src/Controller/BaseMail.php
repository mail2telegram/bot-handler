<?php

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\Client\ImapClient;
use M2T\Client\SmtpClient;
use M2T\Client\TelegramClient;
use M2T\Model\Email;
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

    protected function send(Email $mailboxFrom, string $to, string $subject, string $msg, array $attachment = []): bool
    {
        $result = $this->smtpClient->send($mailboxFrom, $to, $subject, $msg, $attachment);
        if (!$result) {
            $this->replyError();
            return false;
        }

        // Gmail при отправке по SMTP сам добавляет письмо в отправленные.
        // А Яндекс нет. В крайнем случае будет добавлено дважды.
        if ($mailboxFrom->smtpHost !== 'smtp.gmail.com') {
            // @todo add $attachment
            $this->imapClient->appendToSent($mailboxFrom, $to, $subject, $msg);
        }

        $this->messenger->sendMessage($this->state->chatId, static::MSG_SENT);
        return true;
    }

    /**
     * Парсит входящее сообщение, извлекая из него содержание сообщения, и при наличии, фото либо документ
     * @param array  $update
     * @param string $message
     * @param array  $attachment
     */
    protected function parseMessageAndAttachment(array $update, string &$message, array &$attachment): void
    {
        $message = $update['message']['text'] ?? '';
        $message = $update['message']['caption'] ?? $message;
        $attachment = [];

        $fileName = '';
        $fileContent = '';
        $fileId = '';
        $action = '';

        // @todo check other upload types (video, audio, etc)
        if (isset($update['message']['document'])) {
            $fileId = $update['message']['document']['file_id'];
            $action = 'upload_document';
        } elseif (isset($update['message']['photo'])) {
            $fileId = $update['message']['photo'][1]['file_id'];
            $action = 'upload_photo';
        }

        if ($action) {
            $this->messenger->sendChatAction($this->state->chatId, $action);
            if (!$this->messenger->getFile($fileId, $fileName, $fileContent)) {
                $this->replyError();
                return;
            }
            if ($fileContent) {
                $attachment = ['file' => $fileContent, 'fileName' => $fileName];
            }
        }
    }
}

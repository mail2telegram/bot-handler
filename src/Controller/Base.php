<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\App;
use M2T\Client\ImapClient;
use M2T\Client\MessengerInterface;
use M2T\Client\SmtpClient;
use M2T\Model\Account;
use M2T\State;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class Base
{
    protected const MSG_EMPTY_LIST = 'No email addresses';
    protected const MSG_CHOOSE_EMAIL = 'Выберите email или введите если его нет в списке';
    protected const MSG_ERROR = 'Error';
    protected const MSG_SENT = 'Отправлено';

    protected State $state;
    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;
    protected LoggerInterface $logger;

    public function __construct(
        State $state,
        MessengerInterface $messenger,
        AccountManager $accountManager,
        LoggerInterface $logger
    ) {
        $this->state = $state;
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
        $this->logger = $logger;
    }

    public function setState(string $action, string $handler = ''): void
    {
        if (!$handler) {
            $handler = static::class;
        }
        $this->state->set($handler, $action);
        $this->state->changed = true;
    }

    protected function getAccountOrReply(): ?Account
    {
        $account = $this->accountManager->load($this->state->chatId);
        if (!$account || !$account->emails) {
            $this->messenger->sendMessage($this->state->chatId, static::MSG_EMPTY_LIST);
            return null;
        }
        return $account;
    }

    protected function replyChooseEmail(Account $account): void
    {
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
    }

    /**
     * @param $mailboxFrom
     * @param $to
     * @param $subject
     * @param $msg
     * @param array $attachment
     * @return bool
     * @suppress PhanUndeclaredMethod
     */
    protected function send($mailboxFrom, $to, $subject, $msg, $attachment = []): bool
    {
        try {
            $result = App::get(SmtpClient::class)->send($mailboxFrom, $to, $subject, $msg, $attachment);

            // Gmail при отправке по SMTP сам добавляет письмо в отправленные.
            // А Яндекс нет. В крайнем случае будет добавлено дважды.
            if ($mailboxFrom->smtpHost !== 'smtp.gmail.com') {
                App::get(ImapClient::class)->appendToSent($mailboxFrom, $to, $subject, $msg);
            }
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->replyError();
            return false;
        }
        $this->messenger->sendMessage($this->state->chatId, $result ? static::MSG_SENT : static::MSG_ERROR);
        return $result;
    }

    /**
     * Парсит входящее сообщение, извлекая из него содержание сообщения, и при наличии, фото либо документ
     * @param array $update
     * @param string $message
     * @param array $attachment
     */
    protected function parseMessageAndAttachment(array $update, &$message, &$attachment): void
    {
        $message = $update['message']['text'] ?? '';
        $message = $update['message']['caption'] ?? $message;
        $attachment = [];

        $file = $fileName = null;

        if (isset($update['message']['document'])) {
            $this->messenger->sendChatAction($this->state->chatId, 'upload_document');
            if (!$this->messenger->getFile($update['message']['document']['file_id'], $file, $fileName)) {
                $this->replyError();
                return;
            }
            $attachment = ['file' => $file,'fileName' => $fileName];
        }
        if (isset($update['message']['photo'])) {
            $this->messenger->sendChatAction($this->state->chatId, 'upload_photo');
            if (!$this->messenger->getFile($update['message']['photo'][1]['file_id'], $file, $fileName)) {
                $this->replyError();
                return;
            }
            $attachment = ['file' => $file,'fileName' => $fileName];
        }
    }

    protected function replyError(): void
    {
        $this->messenger->sendMessage($this->state->chatId, static::MSG_ERROR);
    }
}

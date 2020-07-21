<?php

namespace M2T\Action;

use M2T\AccountManager;
use M2T\Client\ImapClient;
use M2T\Client\TelegramClient;
use M2T\Model\Mailbox;

abstract class MailBase
{
    public const NAME = 'abstract';

    protected const MSG_NO_MAILBOXES = 'No email addresses';
    protected const MSG_MAILBOX_NOT_FOUND = 'Email address not found in account';
    protected const MSG_ERROR = 'Error';

    protected TelegramClient $messenger;
    protected AccountManager $accountManager;
    protected ImapClient $imapClient;

    public function __construct(
        TelegramClient $messenger,
        AccountManager $accountManager,
        ImapClient $imapClient
    ) {
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
        $this->imapClient = $imapClient;
    }

    protected function getChatId(array $callback): int
    {
        return $callback['message']['chat']['id'];
    }

    protected function getEmailAccountOrReply(array $callback, string $email): ?Mailbox
    {
        $chatId = $this->getChatId($callback);
        $account = $this->accountManager->load($chatId);
        if (!$account || !$account->emails) {
            $this->messenger->answerCallbackQuery($callback['id'], static::MSG_NO_MAILBOXES);
            return null;
        }
        if (!$mailbox = $this->accountManager->mailboxGetByHash($account, $email)) {
            $this->messenger->answerCallbackQuery($callback['id'], static::MSG_MAILBOX_NOT_FOUND);
            return null;
        }
        return $mailbox;
    }

    protected function replyError($callbackId): void
    {
        $this->messenger->answerCallbackQuery($callbackId, static::MSG_ERROR);
    }
}

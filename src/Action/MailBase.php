<?php

namespace M2T\Action;

use M2T\AccountManager;
use M2T\Client\ImapClient;
use M2T\Client\MessengerInterface;
use M2T\Model\Email;

abstract class MailBase
{
    protected const MSG_NO_MAILBOXES = 'No email addresses';
    protected const MSG_MAILBOX_NOT_FOUND = 'Email address not found in account';
    protected const MSG_ERROR = 'Error';

    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;
    protected ImapClient $imapClient;

    public function __construct(
        MessengerInterface $messenger,
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

    protected function getEmailAccountOrReply(array $callback, string $email): ?Email
    {
        $chatId = $callback['message']['chat']['id'];
        $account = $this->accountManager->load($chatId);
        if (!$account || !$account->emails) {
            $this->messenger->sendMessage($chatId, static::MSG_NO_MAILBOXES);
            return null;
        }
        if (!$mailbox = $this->accountManager->mailboxGet($account, $email)) {
            $this->messenger->sendMessage($chatId, static::MSG_MAILBOX_NOT_FOUND);
            return null;
        }
        return $mailbox;
    }

    protected function replyError($chatId): void
    {
        $this->messenger->sendMessage($chatId, static::MSG_ERROR);
    }
}

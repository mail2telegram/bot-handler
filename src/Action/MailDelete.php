<?php

namespace M2T\Action;

use M2T\AccountManager;
use M2T\Client\ImapClient;
use M2T\Client\MessengerInterface;

class MailDelete
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

    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $callback['from']['id'];
        $account = $this->accountManager->load($chatId);
        if (!$account || !$account->emails) {
            $this->messenger->sendMessage($chatId, static::MSG_NO_MAILBOXES);
            return;
        }
        if (!$mailbox = $this->accountManager->mailboxGet($account, $email)) {
            $this->messenger->sendMessage($chatId, static::MSG_MAILBOX_NOT_FOUND);
            return;
        }
        if ($this->imapClient->delete($mailbox, $mailId)) {
            $this->messenger->deleteMessage($chatId, $callback['message']['message_id']);
            return;
        }
        $this->messenger->sendMessage($chatId, static::MSG_ERROR);
    }
}

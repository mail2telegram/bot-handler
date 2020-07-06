<?php

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\Client\MessengerInterface;

class MailboxList
{
    protected const REPLY_MSG_EMPTY_LIST = 'No email addresses';

    protected int $chatId;
    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;

    public function __construct(int $chatId, MessengerInterface $messenger, AccountManager $accountManager)
    {
        $this->chatId = $chatId;
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
    }

    public function actionIndex(): void
    {
        $account = $this->accountManager->load($this->chatId);
        $msg = $account && $account->emails
            ? implode(PHP_EOL, array_map(fn($email) => $email->email, $account->emails))
            : static::REPLY_MSG_EMPTY_LIST;
        $this->messenger->sendMessage($this->chatId, $msg);
    }
}

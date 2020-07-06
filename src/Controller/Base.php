<?php

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\Client\MessengerInterface;
use M2T\Model\Account;
use Psr\Log\LoggerInterface;

abstract class Base
{
    protected LoggerInterface $logger;
    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;
    protected Account $account;

    public function __construct(
        LoggerInterface $logger,
        MessengerInterface $messenger,
        AccountManager $accountManager,
        Account $account
    ) {
        $this->logger = $logger;
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
        $this->account = $account;
    }
}

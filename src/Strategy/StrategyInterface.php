<?php

namespace M2T\Strategy;

use M2T\AccountManager;
use M2T\Client\MessengerInterface;
use M2T\Handler;
use M2T\Model\Account;
use Psr\Log\LoggerInterface;

interface StrategyInterface
{

    public function __construct(
        array $incomingData,
        LoggerInterface $logger,
        MessengerInterface $messenger,
        Account $account,
        AccountManager $accountManager,
        Handler $handler
    );

    public function run();

}

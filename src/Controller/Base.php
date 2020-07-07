<?php

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\Client\MessengerInterface;
use M2T\State;

abstract class Base
{
    protected const MSG_EMPTY_LIST = 'No email addresses';

    protected State $state;
    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;

    public function __construct(
        State $state,
        MessengerInterface $messenger,
        AccountManager $accountManager
    ) {
        $this->state = $state;
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
    }

    public function setState(string $action, string $handler = ''): void
    {
        if (!$handler) {
            $handler = static::class;
        }
        $this->state->set($handler, $action);
        $this->state->changed = true;
    }
}

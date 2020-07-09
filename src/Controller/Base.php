<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\App;
use M2T\Client\MessengerInterface;
use M2T\Model\Account;
use M2T\State;

abstract class Base
{
    protected const MSG_EMPTY_LIST = 'No email addresses';
    protected const MSG_CHOOSE_EMAIL = 'Выберите email или введите если его нет в списке';
    protected const MSG_ERROR = 'Error';

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

    protected function replyError(): void
    {
        $this->messenger->sendMessage($this->state->chatId, static::MSG_ERROR);
    }
}

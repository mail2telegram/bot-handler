<?php

namespace M2T\Controller;

class MailboxList extends Base
{
    public function actionIndex(): void
    {
        $account = $this->accountManager->load($this->state->chatId);
        if (!$account || !$account->emails) {
            $this->messenger->sendMessage($this->state->chatId, static::MSG_EMPTY_LIST);
            return;
        }
        $msg = implode(PHP_EOL, array_map(fn($email) => $email->email, $account->emails));
        $this->messenger->sendMessage($this->state->chatId, $msg);
    }
}

<?php

namespace M2T\Controller;

class MailboxList extends Base
{
    public function actionIndex(): void
    {
        if (!$account = $this->getAccountOrReply()) {
            return;
        }
        $msg = implode(PHP_EOL, array_map(fn($email) => $email->email, $account->emails));
        $this->messenger->sendMessage($this->state->chatId, $msg);
    }
}

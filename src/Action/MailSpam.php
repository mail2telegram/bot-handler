<?php

namespace M2T\Action;

class MailSpam extends MailBase
{
    protected const MSG_SUCCESS = 'Moved to Spam folder';

    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $callback['from']['id'];
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->moveToSpam($mailbox, $mailId)) {
            $this->messenger->deleteMessage($chatId, $callback['message']['message_id']);
            $this->messenger->sendMessage($chatId, static::MSG_SUCCESS);
            return;
        }
        $this->messenger->sendMessage($chatId, static::MSG_ERROR);
    }
}
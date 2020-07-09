<?php

namespace M2T\Action;

class MailDelete extends MailBase
{
    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $callback['from']['id'];
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->moveToTrash($mailbox, $mailId)) {
            $this->messenger->deleteMessage($chatId, $callback['message']['message_id']);
            return;
        }
        $this->messenger->sendMessage($chatId, static::MSG_ERROR);
    }
}
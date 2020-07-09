<?php

namespace M2T\Action;

class MailDelete extends MailBase
{
    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $this->getChatId($callback);
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->moveToTrash($mailbox, $mailId)) {
            $this->messenger->deleteMessage($chatId, $callback['message']['message_id']);
            return;
        }
        $this->replyError($chatId);
    }
}

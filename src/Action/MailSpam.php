<?php

namespace M2T\Action;

class MailSpam extends MailBase
{
    public const NAME = 'spam';
    protected const MSG_SUCCESS = 'Moved to Spam folder';

    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $this->getChatId($callback);
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->moveToSpam($mailbox, $mailId)) {
            $this->messenger->deleteMessage($chatId, $callback['message']['message_id']);
            $this->messenger->sendMessage($chatId, static::MSG_SUCCESS);
            return;
        }
        $this->replyError($chatId);
    }
}

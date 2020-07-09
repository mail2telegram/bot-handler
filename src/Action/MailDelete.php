<?php

namespace M2T\Action;

class MailDelete extends MailBase
{
    public const NAME = 'delete';
    protected const MSG_SUCCESS = 'Deleted';

    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $this->getChatId($callback);
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->moveToTrash($mailbox, $mailId)) {
            $this->messenger->deleteMessage($chatId, $callback['message']['message_id']);
            $this->messenger->answerCallbackQuery($callback['id'], static::MSG_SUCCESS);
            return;
        }
        $this->replyError($callback['id']);
    }
}
